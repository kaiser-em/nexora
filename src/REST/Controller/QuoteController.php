<?php

declare(strict_types=1);

namespace Silao\REST\Controller;

use Silao\Application\Service\CalculateQuoteService;
use Silao\REST\RestErrorMapper;
use Silao\REST\Schema\QuoteSchema;
use Throwable;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;

final class QuoteController extends AbstractRestController
{
    public function calculate(WP_REST_Request $request): WP_REST_Response|WP_Error
    {
        if ($this->isHoneypotTriggered($request)) {
            return new WP_REST_Response(['success' => false], 200);
        }

        try {
            $command = QuoteSchema::toCommand($request);
            $service = $this->container->get(CalculateQuoteService::class);
            $dto = $service->execute($command);

            return new WP_REST_Response([
                'success' => true,
                'data' => [
                    'currency' => $dto->currency,
                    'subtotal' => $dto->subtotalMinorUnits,
                    'fees' => $dto->feesMinorUnits,
                    'discounts' => $dto->discountsMinorUnits,
                    'total' => $dto->totalMinorUnits,
                    'formatted_total' => $dto->formattedTotal,
                    'lines' => array_map(static fn($l) => [
                        'id' => $l->id,
                        'type' => $l->type,
                        'description' => $l->description,
                        'quantity' => $l->quantity,
                        'unit_price' => $l->unitPriceMinorUnits,
                        'total' => $l->totalMinorUnits,
                        'formatted_total' => $l->formattedTotal,
                        'metadata' => $l->metadata,
                    ], $dto->lines),
                ],
            ], 200);
        } catch (Throwable $e) {
            return RestErrorMapper::toWpError($e);
        }
    }
}