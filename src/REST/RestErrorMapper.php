<?php

declare(strict_types=1);

namespace Silao\REST;

use Silao\Application\Exception\ApplicationException;
use Silao\Application\Exception\BookingUnavailableException;
use Silao\Application\Exception\BookingValidationException;
use Silao\Domain\Booking\Exception\InvalidBookingException;
use Silao\Domain\Common\Exception\CurrencyMismatchException;
use Silao\Domain\Common\Exception\MoneyOverflowException;
use Silao\Domain\Model\Exception\InvalidBookingModelException;
use Silao\Domain\Model\Exception\InvalidFieldException;
use Silao\Domain\Model\Exception\InvalidOptionException;
use Silao\Domain\Model\Exception\InvalidPricingContextException;
use Silao\Domain\Model\Exception\InvalidPricingRuleException;
use Silao\Infrastructure\Container\Exception\ServiceNotFoundException;
use Silao\Infrastructure\Exception\PersistenceException;
use Throwable;
use WP_Error;

final class RestErrorMapper
{
    public static function toWpError(Throwable $e): WP_Error
    {
        if ($e instanceof BookingValidationException || $e instanceof InvalidFieldException) {
            return new WP_Error(
                'silao_rest_validation_failed',
                $e->getMessage(),
                ['status' => 422]
            );
        }

        if ($e instanceof BookingUnavailableException) {
            return new WP_Error(
                'silao_rest_unavailable',
                $e->getMessage(),
                ['status' => 409]
            );
        }

        if ($e instanceof InvalidBookingException) {
            return new WP_Error(
                'silao_rest_bad_transition',
                $e->getMessage(),
                ['status' => 400]
            );
        }

        if ($e instanceof InvalidPricingContextException) {
            return new WP_Error(
                'silao_rest_bad_pricing_context',
                $e->getMessage(),
                ['status' => 400]
            );
        }

        if ($e instanceof InvalidPricingRuleException) {
            return new WP_Error(
                'silao_rest_bad_pricing_rule',
                $e->getMessage(),
                ['status' => 400]
            );
        }

        if ($e instanceof InvalidOptionException) {
            return new WP_Error(
                'silao_rest_bad_option',
                $e->getMessage(),
                ['status' => 400]
            );
        }

        if ($e instanceof InvalidBookingModelException) {
            return new WP_Error(
                'silao_rest_bad_model',
                $e->getMessage(),
                ['status' => 400]
            );
        }

        if ($e instanceof CurrencyMismatchException) {
            return new WP_Error(
                'silao_rest_currency_mismatch',
                $e->getMessage(),
                ['status' => 400]
            );
        }

        if ($e instanceof MoneyOverflowException) {
            return new WP_Error(
                'silao_rest_money_overflow',
                $e->getMessage(),
                ['status' => 400]
            );
        }

        if ($e instanceof ServiceNotFoundException) {
            return new WP_Error(
                'silao_rest_not_found',
                $e->getMessage(),
                ['status' => 404]
            );
        }

        if ($e instanceof ApplicationException) {
            return new WP_Error(
                'silao_rest_application_error',
                $e->getMessage(),
                ['status' => 400]
            );
        }

        if ($e instanceof PersistenceException) {
            return new WP_Error(
                'silao_rest_persistence_error',
                'A database persistence error occurred. Please try again later.',
                ['status' => 500]
            );
        }

        // Filet de sécurité global pour toute exception inattendue
        return new WP_Error(
            'silao_rest_internal_error',
            'An unexpected internal server error occurred.',
            ['status' => 500]
        );
    }
}