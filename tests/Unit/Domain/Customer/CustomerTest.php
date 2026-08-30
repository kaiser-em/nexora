<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Customer;

use Nexora\Domain\Customer\Customer;
use Nexora\Domain\Customer\Exception\InvalidCustomerException;
use Nexora\Domain\Customer\ValueObject\CustomerId;
use Nexora\Domain\Customer\ValueObject\Email;
use Nexora\Domain\Customer\ValueObject\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class CustomerTest extends TestCase
{
    public function testGuestCustomerCreation(): void
    {
        $customer = new Customer(
            CustomerId::fromString('cust_1'),
            Email::fromString('guest@example.com'),
            'Jane',
            'Doe'
        );

        $this->assertTrue($customer->isGuest());
        $this->assertFalse($customer->isRegistered());
        $this->assertNull($customer->wpUserId());
        $this->assertSame('Jane Doe', $customer->fullName());
    }

    public function testRegisteredCustomerCreation(): void
    {
        $customer = new Customer(
            CustomerId::fromString('cust_2'),
            Email::fromString('member@example.com'),
            'Alice',
            'Smith',
            PhoneNumber::fromString('+33 6 00 00 00 00'),
            42
        );

        $this->assertFalse($customer->isGuest());
        $this->assertTrue($customer->isRegistered());
        $this->assertSame(42, $customer->wpUserId());
    }

    public function testEmptyNamesThrowException(): void
    {
        $this->expectException(InvalidCustomerException::class);
        new Customer(
            CustomerId::fromString('cust_1'),
            Email::fromString('test@example.com'),
            '   ',
            'Doe'
        );
    }

    public function testInvalidWpUserIdThrowsException(): void
    {
        $this->expectException(InvalidCustomerException::class);
        new Customer(
            CustomerId::fromString('cust_1'),
            Email::fromString('test@example.com'),
            'John',
            'Doe',
            null,
            0 // Invalid WP user ID
        );
    }

    public function testMutationsAndSnapshotIndependence(): void
    {
        $customer = new Customer(
            CustomerId::fromString('cust_1'),
            Email::fromString('initial@example.com'),
            'John',
            'Doe'
        );

        $snapshot = $customer->toSnapshot();
        $this->assertSame('initial@example.com', $snapshot->email->toString());

        // Update profile
        $customer->updateContact(Email::fromString('updated@example.com'), PhoneNumber::fromString('+33611223344'));
        $customer->updateName('Johnny', 'Doelan');
        $customer->linkToWordPressUser(99);

        $this->assertSame('updated@example.com', $customer->email()->toString());
        $this->assertSame('Johnny Doelan', $customer->fullName());
        $this->assertSame(99, $customer->wpUserId());

        // The previous snapshot MUST remain unchanged (historical integrity)
        $this->assertSame('initial@example.com', $snapshot->email->toString());
        $this->assertSame('John Doe', $snapshot->fullName());
    }
}
