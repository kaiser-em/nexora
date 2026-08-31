<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Model;

use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\PricingContext;
use Silao\Domain\Model\ValueObject\SelectedOptionInput;
use Silao\Domain\Resource\ValueObject\ResourceId;
use PHPUnit\Framework\TestCase;

final class PricingContextTest extends TestCase
{
    public function testContextResolutions(): void
    {
        // Monday 2026-06-15 10:30 in Paris (08:30 UTC)
        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:30:00', '2026-06-15 12:00:00', 'Europe/Paris');

        $context = new PricingContext(
            BookingModelId::fromString('transfer_model'),
            ResourceId::fromString('van_1'),
            $range,
            [
                SelectedOptionInput::of('baby_seat', 2),
                SelectedOptionInput::of('wifi', 1),
            ],
            [
                'passengers' => 4,
                'distance_km' => 35,
                'pickup_location' => 'CDG',
            ],
            [
                'is_registered' => true,
                'tier' => 'VIP',
            ],
            Currency::EUR()
        );

        // 1. Direct form data
        $this->assertSame(4, $context->get('passengers'));
        $this->assertSame(35, $context->get('distance_km'));
        $this->assertSame('CDG', $context->get('pickup_location'));

        // 2. Temporal resolution
        $this->assertSame(10, $context->get('time.hour'));
        $this->assertSame(30, $context->get('time.minute'));
        $this->assertSame(1, $context->get('date.day_of_week')); // Monday
        $this->assertFalse($context->get('date.is_weekend'));
        $this->assertSame(90, $context->get('duration_minutes'));

        // 3. Option quantities
        $this->assertSame(2, $context->get('options.baby_seat.quantity'));
        $this->assertSame(1, $context->get('options.wifi.quantity'));
        $this->assertSame(0, $context->get('options.unknown.quantity'));

        // 4. Customer context
        $this->assertTrue($context->get('customer.is_registered'));
        $this->assertSame('VIP', $context->get('customer.tier'));

        // 5. Default value for missing
        $this->assertSame('default_val', $context->get('non_existent_key', 'default_val'));

        // 6. Has checks
        $this->assertTrue($context->has('passengers'));
        $this->assertTrue($context->has('time.hour'));
        $this->assertTrue($context->has('options.baby_seat.quantity'));
        $this->assertTrue($context->has('customer.is_registered'));
        $this->assertFalse($context->has('missing_field'));
    }
}