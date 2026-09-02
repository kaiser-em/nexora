<?php

declare(strict_types=1);

namespace Silao\REST\Schema;

use Silao\Application\Command\CreateBookingCommand;
use Silao\Application\Command\TransitionBookingStatusCommand;
use WP_REST_Request;

final class BookingSchema
{
    public static function toCreateCommand(WP_REST_Request $request): CreateBookingCommand
    {
        $rawCust = $request->get_param('customer');
        $customer = is_array($rawCust) ? $rawCust : [];

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

        $taxRate = $request->get_param('tax_rate_bips');

        // Note: Any client-provided 'total' or 'price' is intentionally excluded to enforce server authority
        return new CreateBookingCommand(
            (string) $request->get_param('model_id'),
            $request->get_param('resource_id') !== null ? (string) $request->get_param('resource_id') : null,
            (string) $request->get_param('starts_at'),
            (string) $request->get_param('ends_at'),
            (string) ($request->get_param('timezone') ?? 'UTC'),
            (string) ($customer['first_name'] ?? ''),
            (string) ($customer['last_name'] ?? ''),
            (string) ($customer['email'] ?? ''),
            isset($customer['phone']) ? (string) $customer['phone'] : null,
            isset($customer['wp_user_id']) ? (int) $customer['wp_user_id'] : null,
            $selectedOptions,
            $formData,
            is_array($request->get_param('customer_context')) ? $request->get_param('customer_context') : null,
            $taxRate !== null ? (int) $taxRate : null,
            (bool) ($request->get_param('auto_confirm') ?? false)
        );
    }

    public static function toTransitionCommand(WP_REST_Request $request): TransitionBookingStatusCommand
    {
        return new TransitionBookingStatusCommand(
            (string) $request->get_param('id'),
            (string) $request->get_param('action')
        );
    }
}