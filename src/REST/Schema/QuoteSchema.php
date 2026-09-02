<?php

declare(strict_types=1);

namespace Silao\REST\Schema;

use Silao\Application\Command\CalculateQuoteCommand;
use WP_REST_Request;

final class QuoteSchema
{
    public static function toCommand(WP_REST_Request $request): CalculateQuoteCommand
    {
        $rawOptions = $request->get_param('selected_options');
        $selectedOptions = [];
        if (is_array($rawOptions)) {
            foreach ($rawOptions as $opt) {
                if (is_array($opt) && isset($opt['id'], $opt['quantity'])) {
                    $selectedOptions[] = [
                        'id' => (string) $opt['id'],
                        'quantity' => (int) $opt['quantity'],
                    ];
                }
            }
        }

        $rawFormData = $request->get_param('form_data');
        $formData = is_array($rawFormData) ? $rawFormData : [];

        $rawCustomer = $request->get_param('customer_context');
        $customerContext = is_array($rawCustomer) ? $rawCustomer : null;

        $taxRate = $request->get_param('tax_rate_bips');

        return new CalculateQuoteCommand(
            (string) $request->get_param('model_id'),
            $request->get_param('resource_id') !== null ? (string) $request->get_param('resource_id') : null,
            (string) $request->get_param('starts_at'),
            (string) $request->get_param('ends_at'),
            (string) ($request->get_param('timezone') ?? 'UTC'),
            $selectedOptions,
            $formData,
            $customerContext,
            $taxRate !== null ? (int) $taxRate : null
        );
    }
}