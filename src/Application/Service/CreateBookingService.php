<?php

declare(strict_types=1);

namespace Silao\Application\Service;

use Silao\Application\Command\CreateBookingCommand;
use Silao\Application\DTO\BookingDTO;
use Silao\Application\DTO\CustomerDTO;
use Silao\Application\DTO\QuoteDTO;
use Silao\Application\DTO\QuoteLineDTO;
use Silao\Application\Event\BookingCreatedEvent;
use Silao\Application\Event\EventDispatcherInterface;
use Silao\Application\Exception\BookingUnavailableException;
use Silao\Application\Exception\BookingValidationException;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Booking\ValueObject\FormDataSnapshot;
use Silao\Domain\Booking\ValueObject\Quote;
use Silao\Domain\Common\ValueObject\Percentage;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Customer\Customer;
use Silao\Domain\Customer\Repository\CustomerRepositoryInterface;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Domain\Customer\ValueObject\PhoneNumber;
use Silao\Domain\Engine\AvailabilityEngine;
use Silao\Domain\Engine\PricingEngine;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\PricingContext;
use Silao\Domain\Model\ValueObject\SelectedOptionInput;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\ValueObject\ResourceId;

final readonly class CreateBookingService
{
    private const int MAX_REFERENCE_RETRY = 3;

    public function __construct(
        private BookingModelRepositoryInterface $modelRepository,
        private ResourceRepositoryInterface $resourceRepository,
        private CustomerRepositoryInterface $customerRepository,
        private BookingRepositoryInterface $bookingRepository,
        private TransactionManagerInterface $transactionManager,
        private EventDispatcherInterface $eventDispatcher
    ) {
    }

    /**
     * @throws BookingValidationException
     * @throws BookingUnavailableException
     */
    public function execute(CreateBookingCommand $command): BookingDTO
    {
        $modelId = BookingModelId::fromString($command->modelId);
        $model = $this->modelRepository->findById($modelId);
        if ($model === null) {
            throw new BookingValidationException(sprintf('Booking model "%s" not found.', $command->modelId));
        }

        foreach ($model->fields() as $field) {
            if ($field->isRequired && (!array_key_exists($field->name, $command->formData) || $command->formData[$field->name] === '')) {
                throw new BookingValidationException(sprintf('Field "%s" (%s) is required.', $field->name, $field->label));
            }
        }

        $range = ZonedDateTimeRange::fromIsoStrings(
            $command->startsAtIso,
            $command->endsAtIso,
            $command->timezone
        );

        /** @var array{booking: Booking, customer: Customer, quote: Quote} $txResult */
        $txResult = $this->transactionManager->transactional(function () use ($model, $modelId, $range, $command): array {
            $resource = null;
            if ($model->resourceStrategy() === ResourceStrategyType::AutoAssign) {
                $candidateIds = array_map(static fn($r) => $r->toString(), array_values($model->eligibleResourceIds()));
                sort($candidateIds);

                foreach ($candidateIds as $cId) {
                    $candResource = $this->resourceRepository->findById(ResourceId::fromString($cId));
                    if ($candResource !== null) {
                        $activeConf = $this->bookingRepository->findActiveByResourceAndDateRange($candResource->id(), $range);
                        $check = AvailabilityEngine::check($model, $candResource, $range, 1, $activeConf);
                        if ($check['is_available']) {
                            $resource = $candResource;
                            break;
                        }
                    }
                }

                if ($resource === null) {
                    throw new BookingUnavailableException('No available resource found for auto-assignment.');
                }
            } elseif ($model->resourceStrategy()->requiresResource()) {
                if ($command->resourceId === null || $command->resourceId === '') {
                    throw new BookingValidationException('A resource must be selected for this booking model.');
                }
                $resourceId = ResourceId::fromString($command->resourceId);
                $resource = $this->resourceRepository->findById($resourceId);
                if ($resource === null) {
                    throw new BookingValidationException(sprintf('Resource "%s" not found.', $command->resourceId));
                }

                $activeConf = $this->bookingRepository->findActiveByResourceAndDateRange($resourceId, $range);
                $check = AvailabilityEngine::check($model, $resource, $range, 1, $activeConf);
                if (!$check['is_available']) {
                    throw new BookingUnavailableException(sprintf('Selected resource is unavailable: %s', $check['reason_code'] ?? 'UNKNOWN'));
                }
            }

            $selectedOptions = [];
            foreach ($command->selectedOptions as $opt) {
                $selectedOptions[] = SelectedOptionInput::of((string) $opt['id'], (int) $opt['quantity']);
            }

            $context = new PricingContext(
                $modelId,
                $resource?->id(),
                $range,
                $selectedOptions,
                $command->formData,
                $command->customerContext,
                $model->basePrice()->currency
            );

            $taxRate = $command->taxRateBips !== null
                ? Percentage::fromBasisPoints($command->taxRateBips)
                : null;

            $quote = PricingEngine::calculate($model, $context, $taxRate);

            $customerEmail = Email::fromString($command->customerEmail);
            $customer = $this->customerRepository->findByEmail($customerEmail);

            if ($customer === null) {
                $customerId = CustomerId::fromString('cust_' . bin2hex(random_bytes(6)));
                $customerPhone = $command->customerPhone !== null && $command->customerPhone !== ''
                    ? PhoneNumber::fromString($command->customerPhone)
                    : null;

                $customer = new Customer(
                    $customerId,
                    $customerEmail,
                    $command->customerFirstName,
                    $command->customerLastName,
                    $customerPhone,
                    $command->customerWpUserId
                );
                $this->customerRepository->save($customer);
            }

            $booking = null;
            $attempts = 0;

            while ($booking === null && $attempts < self::MAX_REFERENCE_RETRY) {
                $attempts++;
                $bookingId = BookingId::fromString('book_' . bin2hex(random_bytes(8)));
                $reference = BookingReferenceGenerator::generate();

                $candidateBooking = new Booking(
                    $bookingId,
                    $reference,
                    $customer->toSnapshot(),
                    $modelId,
                    $resource?->id(),
                    $range,
                    new FormDataSnapshot($command->formData)
                );

                $candidateBooking->createQuote($quote);

                if ($command->autoConfirm) {
                    $candidateBooking->confirm();
                }

                try {
                    $this->bookingRepository->save($candidateBooking);
                    $booking = $candidateBooking;
                } catch (\Throwable $e) {
                    if ($attempts >= self::MAX_REFERENCE_RETRY) {
                        throw new BookingUnavailableException('Failed to persist booking after reference retry: ' . $e->getMessage(), 0, $e);
                    }
                }
            }

            if ($booking === null) {
                throw new BookingUnavailableException('Failed to create booking.');
            }

            return ['booking' => $booking, 'customer' => $customer, 'quote' => $quote];
        });

        $this->eventDispatcher->dispatch(new BookingCreatedEvent(
            $txResult['booking']->id()->toString(),
            $txResult['booking']->reference()->toString(),
            $txResult['booking']->modelId()->toString(),
            $txResult['customer']->email()->toString()
        ));

        return self::assembleDTO($txResult['booking'], $txResult['customer'], $txResult['quote']);
    }

    private static function assembleDTO(Booking $booking, Customer $customer, Quote $quote): BookingDTO
    {
        $cDto = new CustomerDTO(
            $customer->id()->toString(),
            $customer->email()->toString(),
            $customer->firstName(),
            $customer->lastName(),
            $customer->fullName(),
            $customer->phone()?->toString(),
            $customer->isGuest()
        );

        $linesDTO = [];
        foreach ($quote->lines() as $l) {
            $linesDTO[] = new QuoteLineDTO(
                $l->id(),
                $l->type()->value,
                $l->description(),
                $l->quantity(),
                $l->unitPrice()->amount,
                $l->total()->amount,
                $l->total()->format(),
                $l->metadata()
            );
        }

        $quoteDTO = new QuoteDTO(
            $quote->currency()->code,
            $quote->subtotal()->amount,
            $quote->fees()->amount,
            $quote->discounts()->amount,
            $quote->total()->amount,
            $quote->total()->format(),
            $linesDTO
        );

        $eventTypes = array_map(static fn($e) => $e->type(), $booking->events());

        return new BookingDTO(
            $booking->id()->toString(),
            $booking->reference()->toString(),
            $booking->status()->value,
            $booking->modelId()->toString(),
            $booking->resourceId()?->toString(),
            $booking->dateTimeRange()->startsAtUtc->format('Y-m-d H:i:s'),
            $booking->dateTimeRange()->endsAtUtc->format('Y-m-d H:i:s'),
            $booking->dateTimeRange()->timezone->getName(),
            $cDto,
            $booking->formDataSnapshot()->all(),
            $quoteDTO,
            $eventTypes
        );
    }
}