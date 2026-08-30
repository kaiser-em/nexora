<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Integration;

use Nexora\Domain\Booking\Booking;
use Nexora\Domain\Booking\Enum\QuoteLineType;
use Nexora\Domain\Booking\ValueObject\BookingId;
use Nexora\Domain\Booking\ValueObject\BookingReference;
use Nexora\Domain\Booking\ValueObject\FormDataSnapshot;
use Nexora\Domain\Booking\ValueObject\Quote;
use Nexora\Domain\Booking\ValueObject\QuoteLine;
use Nexora\Domain\Common\ValueObject\Currency;
use Nexora\Domain\Common\ValueObject\Money;
use Nexora\Domain\Common\ValueObject\ZonedDateTimeRange;
use Nexora\Domain\Customer\Customer;
use Nexora\Domain\Customer\ValueObject\CustomerId;
use Nexora\Domain\Customer\ValueObject\Email;
use Nexora\Domain\Customer\ValueObject\PhoneNumber;
use Nexora\Domain\Model\ValueObject\BookingModelId;
use PHPUnit\Framework\TestCase;

final class SnapshotIntegrityTest extends TestCase
{
    public function testCustomerMutationDoesNotAlterHistoricalBookingSnapshot(): void
    {
        $customer = new Customer(
            CustomerId::fromString('cust_99'),
            Email::fromString('original@domain.com'),
            'OriginalFirst',
            'OriginalLast',
            PhoneNumber::fromString('+33 6 12 34 56 78')
        );

        $snapshot = $customer->toSnapshot();
        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'UTC');

        $booking = new Booking(
            BookingId::fromString('b_snap'),
            BookingReference::fromString('NEX-SNAP-01'),
            $snapshot,
            BookingModelId::fromString('m1'),
            null,
            $range,
            new FormDataSnapshot(['seats' => 1])
        );

        // Mutate Customer profile multiple times
        $customer->updateName('NewFirst', 'NewLast');
        $customer->updateContact(Email::fromString('new.email@domain.com'), PhoneNumber::fromString('+33 6 99 99 99 99'));
        $customer->linkToWordPressUser(1234);

        // Assert booking snapshot remains strictly sealed
        $this->assertSame('original@domain.com', $booking->customerSnapshot()->email->toString());
        $this->assertSame('OriginalFirst OriginalLast', $booking->customerSnapshot()->fullName());
        $this->assertSame('+33 6 12 34 56 78', $booking->customerSnapshot()->phone?->toString());
        $this->assertTrue($booking->customerSnapshot()->isGuest);
    }

    public function testPriceSnapshotCannotBeCorruptedByExternalQuoteModification(): void
    {
        $eur = Currency::EUR();
        $range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'UTC');

        $customer = new Customer(CustomerId::fromString('c1'), Email::fromString('test@example.com'), 'A', 'B');

        $booking = new Booking(
            BookingId::fromString('b_price'),
            BookingReference::fromString('NEX-PRICE-01'),
            $customer->toSnapshot(),
            BookingModelId::fromString('m1'),
            null,
            $range,
            new FormDataSnapshot(['item' => 'test'])
        );

        $line = new QuoteLine('l1', QuoteLineType::BasePrice, 'Base', 1, Money::of(10000, $eur), Money::of(10000, $eur));
        $quote = new Quote([$line], Money::of(10000, $eur), Money::of(0, $eur), Money::of(0, $eur), Money::of(10000, $eur), $eur);

        $booking->createQuote($quote);

        $snapshot = $booking->priceSnapshot();
        $this->assertNotNull($snapshot);
        $this->assertSame(10000, $snapshot->total->amount);
        $this->assertSame('EUR', $snapshot->currency->code);
    }
}