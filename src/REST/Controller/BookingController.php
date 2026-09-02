<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Application\Service\CreateBookingService;
use Silao\Application\Service\TransitionBookingStatusService;
use Silao\Domain\Booking\Repository\BookingRepositoryInterface;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Resource\ValueObject\ResourceId;
use Silao\REST\RestErrorMapper;
use Silao\REST\Schema\BookingSchema;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class BookingController extends AbstractRestController
{
    public function create(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if ($this->isHoneypotTriggered($request)) {
            return new WP_REST_Response(['success' => false], 200);
        }

        try {
            $command = BookingSchema::toCreateCommand($request);
            $service = $this->container->get(CreateBookingService::class);
            $dto = $service->execute($command);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'booking_id' => $dto->bookingId,
                    'reference' => $dto->reference,
                    'status' => $dto->status,
                    'model_id' => $dto->modelId,
                    'resource_id' => $dto->resourceId,
                    'starts_at_utc' => $dto->startsAtUtc,
                    'ends_at_utc' => $dto->endsAtUtc,
                    'timezone' => $dto->timezone,
                    'customer' => [
                        'customer_id' => $dto->customer->customerId,
                        'email' => $dto->customer->email,
                        'first_name' => $dto->customer->firstName,
                        'last_name' => $dto->customer->lastName,
                        'full_name' => $dto->customer->fullName,
                        'phone' => $dto->customer->phone,
                        'is_guest' => $dto->customer->isGuest,
                    ],
                    'quote' => $dto->quote !== null ? [
                        'currency' => $dto->quote->currency,
                        'total' => $dto->quote->totalMinorUnits,
                        'formatted_total' => $dto->quote->formattedTotal,
                    ] : null,
                    'events' => $dto->eventTypes,
                ],
            ], 201);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function getAll(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $repo = $this->container->get(BookingRepositoryInterface::class);

            $resourceIdParam = $request->get_param('resource_id');
            $startsAtParam = $request->get_param('starts_at');
            $endsAtParam = $request->get_param('ends_at');

            $bookings = [];
            if ($resourceIdParam !== null && $startsAtParam !== null && $endsAtParam !== null) {
                $range = ZonedDateTimeRange::fromIsoStrings((string) $startsAtParam, (string) $endsAtParam, 'UTC');
                $bookings = $repo->findActiveByResourceAndDateRange(ResourceId::fromString((string) $resourceIdParam), $range);
            }

            $data = [];
            foreach ($bookings as $b) {
                $data[] = [
                    'booking_id' => $b->id()->toString(),
                    'reference' => $b->reference()->toString(),
                    'status' => $b->status()->value,
                    'starts_at_utc' => $b->dateTimeRange()->startsAtUtc->format('Y-m-d H:i:s'),
                    'ends_at_utc' => $b->dateTimeRange()->endsAtUtc->format('Y-m-d H:i:s'),
                ];
            }

            return new WP_REST_Response(['success' => true, 'data' => $data], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function getOne(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $id = BookingId::fromString((string) $request->get_param('id'));
            $repo = $this->container->get(BookingRepositoryInterface::class);
            $booking = $repo->findById($id);

            if ($booking === null) {
                return new WP_Error('silao_rest_not_found', 'Booking not found.', ['status' => 404]);
            }

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'booking_id' => $booking->id()->toString(),
                    'reference' => $booking->reference()->toString(),
                    'status' => $booking->status()->value,
                    'model_id' => $booking->modelId()->toString(),
                    'resource_id' => $booking->resourceId()?->toString(),
                    'starts_at_utc' => $booking->dateTimeRange()->startsAtUtc->format('Y-m-d H:i:s'),
                    'ends_at_utc' => $booking->dateTimeRange()->endsAtUtc->format('Y-m-d H:i:s'),
                    'timezone' => $booking->dateTimeRange()->timezone->getName(),
                    'customer' => [
                        'customer_id' => $booking->customerSnapshot()->customerId->toString(),
                        'email' => $booking->customerSnapshot()->email->toString(),
                        'full_name' => $booking->customerSnapshot()->fullName(),
                        'phone' => $booking->customerSnapshot()->phone?->toString(),
                    ],
                    'price' => $booking->priceSnapshot() !== null ? [
                        'currency' => $booking->priceSnapshot()->currency->code,
                        'total' => $booking->priceSnapshot()->total->amount,
                        'formatted_total' => $booking->priceSnapshot()->total->format(),
                    ] : null,
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }

    public function transition(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        try {
            $command = BookingSchema::toTransitionCommand($request);
            $service = $this->container->get(TransitionBookingStatusService::class);
            $dto = $service->execute($command);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'booking_id' => $dto->bookingId,
                    'reference' => $dto->reference,
                    'status' => $dto->status,
                    'events' => $dto->eventTypes,
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }
}