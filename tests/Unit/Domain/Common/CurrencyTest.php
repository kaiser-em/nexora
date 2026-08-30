<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Common;

use Nexora\Domain\Common\Enum\CurrencyPosition;
use Nexora\Domain\Common\Exception\InvalidArgumentException;
use Nexora\Domain\Common\Exception\InvalidCurrencyException;
use Nexora\Domain\Common\ValueObject\Currency;
use PHPUnit\Framework\TestCase;

final class CurrencyTest extends TestCase
{
    public function testValidCurrencyCreationAndNormalization(): void
    {
        $currency = new Currency('eur', '€', 2, CurrencyPosition::After);
        $this->assertSame('EUR', $currency->code);
        $this->assertSame('€', $currency->symbol);
        $this->assertSame(2, $currency->subunitPrecision);
        $this->assertSame(CurrencyPosition::After, $currency->symbolPosition);
    }

    public function testFactoryHelpers(): void
    {
        $eur = Currency::EUR();
        $this->assertSame('EUR', $eur->code);
        $this->assertSame('€', $eur->symbol);

        $usd = Currency::USD();
        $this->assertSame('USD', $usd->code);
        $this->assertSame('$', $usd->symbol);
        $this->assertSame(CurrencyPosition::Before, $usd->symbolPosition);

        $jpy = Currency::JPY();
        $this->assertSame('JPY', $jpy->code);
        $this->assertSame(0, $jpy->subunitPrecision);
    }

    public function testInvalidIsoCodeFormatThrowsException(): void
    {
        $this->expectException(InvalidCurrencyException::class);
        new Currency('EURO');
    }

    public function testInvalidShortCodeThrowsException(): void
    {
        $this->expectException(InvalidCurrencyException::class);
        new Currency('EU');
    }

    public function testInvalidNumericCodeThrowsException(): void
    {
        $this->expectException(InvalidCurrencyException::class);
        new Currency('123');
    }

    public function testNegativePrecisionThrowsException(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Currency('EUR', '€', -1);
    }

    public function testEquals(): void
    {
        $eur1 = Currency::EUR();
        $eur2 = Currency::of('EUR', '€', 2);
        $usd = Currency::USD();

        $this->assertTrue($eur1->equals($eur2));
        $this->assertFalse($eur1->equals($usd));
    }

    public function testFormatStandardTwoDecimals(): void
    {
        $eur = Currency::EUR();
        $this->assertSame('100.50 €', $eur->format(10050));
        $this->assertSame('0.05 €', $eur->format(5));
        $this->assertSame('0.00 €', $eur->format(0));
        $this->assertSame('-25.50 €', $eur->format(-2550));
    }

    public function testFormatZeroDecimals(): void
    {
        $jpy = Currency::JPY();
        $this->assertSame('¥500', $jpy->format(500));
        $this->assertSame('-¥500', $jpy->format(-500));
        $this->assertSame('¥0', $jpy->format(0));
    }

    public function testFormatThreeDecimals(): void
    {
        $kwd = Currency::of('KWD', 'د.ك', 3);
        $this->assertSame('1.250 د.ك', $kwd->format(1250));
        $this->assertSame('-1.250 د.ك', $kwd->format(-1250));
        $this->assertSame('0.005 د.ك', $kwd->format(5));
    }

    public function testFormatLargeAmountBeyondSafetyCeilingIsDecoupled(): void
    {
        $eur = Currency::EUR();
        // Currency does not enforce Money ceiling and formats large minor units accurately
        $this->assertSame('2000000000000.00 €', $eur->format(2_000_000_000_000_00));
    }
}