<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Booking;

use Silao\Domain\Booking\Booking;
use Silao\Domain\Booking\Enum\BookingStatus;
use Silao\Domain\Booking\Enum\QuoteLineType;
use Silao\Domain\Booking\Exception\InvalidBookingException;
use Silao\Domain\Booking\ValueObject\BookingId;
use Silao\Domain\Booking\ValueObject\BookingReference;
use Silao\Domain\Booking\ValueObject\FormDataSnapshot;
use Silao\Domain\Booking\ValueObject\Quote;
use Silao\Domain\Booking\ValueObject\QuoteLine;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Customer\Customer;
use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\CustomerSnapshot;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Resource\ValueObject\ResourceId;
use PHPUnit\Framework\TestCase;

final class BookingTest extends TestCase
{
    private Currency $eur;
    private CustomerSnapshot $customerSnapshot;
    private BookingModelId $modelId;
    private ResourceId $resourceId;
    private ZonedDateTimeRange $range;
    private FormDataSnapshot $formData;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
        $this->customerSnapshot = new CustomerSnapshot(
            CustomerId::fromString('c1'),
            Email::fromString('john@example.com'),
            'John',
            'Doe'
        );
        $this->modelId = BookingModelId::fromString('model_transfer');
        $this->resourceId = ResourceId::fromString('van_1');
        $this->range = ZonedDateTimeRange::fromIsoStrings('2026-06-15 10:00:00', '2026-06-15 12:00:00', 'Europe/Paris');
        $this->formData = new FormDataSnapshot(['passengers' => 4]);
    }

    private function createDraftBooking(): Booking
    {
        return new Booking(
            BookingId::fromString('book_1'),
            BookingReference::fromString('NEX-2026-ABC123'),
            $this->customerSnapshot,
            $this->modelId,
            $this->resourceId,
            $this->range,
            $this->formData
        );
    }

    private function createDummyQuote(): Quote
    {
        $line = new QuoteLine('l1', QuoteLineType::BasePrice, 'Transfer', 1, Money::of(10000, $this->eur), Money::of(10000, $this->eur));
        return new Quote([$line], Money::of(10000, $this->eur), Money::of(0, $this->eur), Money::of(0, $this->eur), Money::of(10000, $this->eur), $this->eur);
    }

    public function testInitialStateAndEvent(): void
    {
        $booking = $this->createDraftBooking();

        $this->assertSame('book_1', $booking->id()->toString());
        $this->assertSame('NEX-2026-ABC123', $booking->reference()->toString());
        $this->assertTrue($booking->status()->isDraft());
        $this->assertNull($booking->quote());
        $this->assertNull($booking->priceSnapshot());
        $this->assertCount(1, $booking->events());
        $this->assertSame('booking.created', $booking->events()[0]->type());
    }

    public function testCreateQuoteTransitionsToQuotedAndSnapshotsPrice(): void
    {
        $booking = $this->createDraftBooking();
        $quote = $this->createDummyQuote();

        $booking->createQuote($quote);

        $this->assertTrue($booking->status()->isQuoted());
        $this->assertSame($quote, $booking->quote());
        $this->assertNotNull($booking->priceSnapshot());
        $this->assertSame(10000, $booking->priceSnapshot()->total->amount);
        $this->assertCount(2, $booking->events());
        $this->assertSame('booking.quoted', $booking->events()[1]->type());
    }

    public function testConfirmFromQuotedOrPending(): void
    {
        $booking = $this->createDraftBooking();
        $booking->createQuote($this->createDummyQuote());

        // Quoted -> Confirmed
        $booking->confirm();
        $this->assertTrue($booking->status()->isConfirmed());

        // Or Quoted -> Pending -> Confirmed
        $b2 = $this->createDraftBooking();
        $b2->createQuote($this->createDummyQuote());
        $b2->markPending();
        $this->assertTrue($b2->status()->isPending());
        $b2->confirm();
        $this->assertTrue($b2->status()->isConfirmed());
    }

    public function testCancelFromQuotedPendingAndConfirmed(): void
    {
        // Cancel from Quoted
        $b1 = $this->createDraftBooking();
        $b1->createQuote($this->createDummyQuote());
        $b1->cancel();
        $this->assertTrue($b1->status()->isCancelled());

        // Cancel from Pending
        $b2 = $this->createDraftBooking();
        $b2->createQuote($this->createDummyQuote());
        $b2->markPending();
        $b2->cancel();
        $this->assertTrue($b2->status()->isCancelled());

        // Cancel from Confirmed
        $b3 = $this->createDraftBooking();
        $b3->createQuote($this->createDummyQuote());
        $b3->confirm();
        $b3->cancel();
        $this->assertTrue($b3->status()->isCancelled());
    }

    public function testCompleteFromConfirmed(): void
    {
        $booking = $this->createDraftBooking();
        $booking->createQuote($this->createDummyQuote());
        $booking->confirm();

        $booking->complete();
        $this->assertTrue($booking->status()->isCompleted());
    }

    public function testCannotConfirmFromDraft(): void
    {
        $booking = $this->createDraftBooking();
        $this->expectException(InvalidBookingException::class);
        $booking->confirm();
    }

    public function testCannotCompleteFromDraft(): void
    {
        $booking = $this->createDraftBooking();
        $this->expectException(InvalidBookingException::class);
        $booking->complete();
    }

    public function testCannotConfirmFromCancelledOrCompleted(): void
    {
        $booking = $this->createDraftBooking();
        $booking->createQuote($this->createDummyQuote());
        $booking->confirm();
        $booking->complete();

        $this->expectException(InvalidBookingException::class);
        $booking->confirm();
    }

    public function testCannotCancelWhenAlreadyCancelledOrCompleted(): void
    {
        $booking = $this->createDraftBooking();
        $booking->createQuote($this->createDummyQuote());
        $booking->cancel();

        $this->expectException(InvalidBookingException::class);
        $booking->cancel();
    }

    public function testCustomerMutationDoesNotAffectCustomerSnapshot(): void
    {
        $customer = new Customer(CustomerId::fromString('c1'), Email::fromString('initial@example.com'), 'Initial', 'Name');
        $snapshot = $customer->toSnapshot();

        $booking = new Booking(
            BookingId::fromString('b1'),
            BookingReference::fromString('NEX-123'),
            $snapshot,
            $this->modelId,
            $this->resourceId,
            $this->range,
            $this->formData
        );

        // Mutate Customer Aggregate
        $customer->updateContact(Email::fromString('new@example.com'), null);
        $customer->updateName('Updated', 'Person');

        // Historical Snapshot in Booking MUST remain unchanged
        $this->assertSame('initial@example.com', $booking->customerSnapshot()->email->toString());
        $this->assertSame('Initial Name', $booking->customerSnapshot()->fullName());
    }

    public function testReferencesPreservation(): void
    {
        $booking = $this->createDraftBooking();

        $this->assertSame('model_transfer', $booking->modelId()->toString());
        $this->assertNotNull($booking->resourceId());
        $this->assertSame('van_1', $booking->resourceId()->toString());
    }
}