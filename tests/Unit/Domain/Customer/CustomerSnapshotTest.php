<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Customer;

use Nexora\Domain\Customer\ValueObject\CustomerId;
use Nexora\Domain\Customer\ValueObject\CustomerSnapshot;
use Nexora\Domain\Customer\ValueObject\Email;
use Nexora\Domain\Customer\ValueObject\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class CustomerSnapshotTest extends TestCase
{
    public function testSnapshotImmutabilityAndFullName(): void
    {
        $snapshot = new CustomerSnapshot(
            CustomerId::fromString('cust_1'),
            Email::fromString('john.doe@example.com'),
            'John',
            'Doe',
            PhoneNumber::fromString('+33 6 12 34 56 78'),
            true
        );

        $this->assertSame('cust_1', $snapshot->customerId->toString());
        $this->assertSame('john.doe@example.com', $snapshot->email->toString());
        $this->assertSame('John Doe', $snapshot->fullName());
        $this->assertTrue($snapshot->isGuest);
    }
}