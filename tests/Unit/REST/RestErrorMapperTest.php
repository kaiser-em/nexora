<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\REST;

use Exception;
use PHPUnit\Framework\TestCase;
use Silao\Application\Exception\BookingUnavailableException;
use Silao\Application\Exception\BookingValidationException;
use Silao\Domain\Booking\Exception\InvalidBookingException;
use Silao\Domain\Common\Exception\CurrencyMismatchException;
use Silao\Domain\Common\Exception\MoneyOverflowException;
use Silao\Domain\Model\Exception\InvalidBookingModelException;
use Silao\Domain\Model\Exception\InvalidOptionException;
use Silao\Domain\Model\Exception\InvalidPricingContextException;
use Silao\Domain\Model\Exception\InvalidPricingRuleException;
use Silao\Infrastructure\Container\Exception\ServiceNotFoundException;
use Silao\Infrastructure\Exception\PersistenceException;
use Silao\REST\RestErrorMapper;

final class RestErrorMapperTest extends TestCase
{
    public function testMapsValidationExceptionTo422(): void
    {
        $err = RestErrorMapper::toWpError(new BookingValidationException('Field required.'));
        $this->assertSame('silao_rest_validation_failed', $err->get_error_code());
        $this->assertSame(422, $err->get_error_data()['status']);
    }

    public function testMapsUnavailableExceptionTo409(): void
    {
        $err = RestErrorMapper::toWpError(new BookingUnavailableException('Resource busy.'));
        $this->assertSame('silao_rest_unavailable', $err->get_error_code());
        $this->assertSame(409, $err->get_error_data()['status']);
    }

    public function testMapsInvalidBookingToBadTransition400(): void
    {
        $err = RestErrorMapper::toWpError(new InvalidBookingException('Illegal transition.'));
        $this->assertSame('silao_rest_bad_transition', $err->get_error_code());
        $this->assertSame(400, $err->get_error_data()['status']);
    }

    public function testMapsPricingExceptionsTo400(): void
    {
        $errCtx = RestErrorMapper::toWpError(new InvalidPricingContextException('Bad context.'));
        $this->assertSame('silao_rest_bad_pricing_context', $errCtx->get_error_code());

        $errRule = RestErrorMapper::toWpError(new InvalidPricingRuleException('Bad rule.'));
        $this->assertSame('silao_rest_bad_pricing_rule', $errRule->get_error_code());

        $errOpt = RestErrorMapper::toWpError(new InvalidOptionException('Bad opt.'));
        $this->assertSame('silao_rest_bad_option', $errOpt->get_error_code());

        $errModel = RestErrorMapper::toWpError(new InvalidBookingModelException('Bad model.'));
        $this->assertSame('silao_rest_bad_model', $errModel->get_error_code());

        $errCurr = RestErrorMapper::toWpError(new CurrencyMismatchException('Mismatch.'));
        $this->assertSame('silao_rest_currency_mismatch', $errCurr->get_error_code());

        $errMoney = RestErrorMapper::toWpError(new MoneyOverflowException('Overflow.'));
        $this->assertSame('silao_rest_money_overflow', $errMoney->get_error_code());
    }

    public function testMapsNotFoundTo404(): void
    {
        $err = RestErrorMapper::toWpError(new ServiceNotFoundException('Missing entity.'));
        $this->assertSame('silao_rest_not_found', $err->get_error_code());
        $this->assertSame(404, $err->get_error_data()['status']);
    }

    public function testMapsPersistenceExceptionTo500Masked(): void
    {
        $err = RestErrorMapper::toWpError(new PersistenceException('SQL error table xyz'));
        $this->assertSame('silao_rest_persistence_error', $err->get_error_code());
        $this->assertSame(500, $err->get_error_data()['status']);
        $this->assertStringNotContainsString('SQL error', $err->get_error_message());
    }

    public function testMapsUnexpectedThrowableTo500GenericSafetyNet(): void
    {
        $err = RestErrorMapper::toWpError(new Exception('Unexpected internal crash'));
        $this->assertSame('silao_rest_internal_error', $err->get_error_code());
        $this->assertSame(500, $err->get_error_data()['status']);
        $this->assertSame('An unexpected internal server error occurred.', $err->get_error_message());
    }
}