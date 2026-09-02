<?php

declare(strict_types=1);

namespace Silao\Application\Service;

use Silao\Application\Command\CalculateQuoteCommand;
use Silao\Application\DTO\QuoteDTO;
use Silao\Application\DTO\QuoteLineDTO;
use Silao\Application\Exception\ApplicationException;
use Silao\Domain\Common\ValueObject\Percentage;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Engine\PricingEngine;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\PricingContext;
use Silao\Domain\Model\ValueObject\SelectedOptionInput;
use Silao\Domain\Resource\ValueObject\ResourceId;

final readonly class CalculateQuoteService
{
    public function __construct(
        private BookingModelRepositoryInterface $modelRepository
    ) {
    }

    /**
     * @throws ApplicationException
     */
    public function execute(CalculateQuoteCommand $command): QuoteDTO
    {
        $modelId = BookingModelId::fromString($command->modelId);
        $model = $this->modelRepository->findById($modelId);

        if ($model === null) {
            throw new ApplicationException(sprintf('Booking model "%s" not found.', $command->modelId));
        }

        $range = ZonedDateTimeRange::fromIsoStrings(
            $command->startsAtIso,
            $command->endsAtIso,
            $command->timezone
        );

        $selectedOptions = [];
        foreach ($command->selectedOptions as $opt) {
            $selectedOptions[] = SelectedOptionInput::of((string) $opt['id'], (int) $opt['quantity']);
        }

        $resourceId = $command->resourceId !== null && $command->resourceId !== ''
            ? ResourceId::fromString($command->resourceId)
            : null;

        $context = new PricingContext(
            $modelId,
            $resourceId,
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

        $linesDTO = [];
        foreach ($quote->lines() as $line) {
            $linesDTO[] = new QuoteLineDTO(
                $line->id(),
                $line->type()->value,
                $line->description(),
                $line->quantity(),
                $line->unitPrice()->amount,
                $line->total()->amount,
                $line->total()->format(),
                $line->metadata()
            );
        }

        return new QuoteDTO(
            $quote->currency()->code,
            $quote->subtotal()->amount,
            $quote->fees()->amount,
            $quote->discounts()->amount,
            $quote->total()->amount,
            $quote->total()->format(),
            $linesDTO
        );
    }
}