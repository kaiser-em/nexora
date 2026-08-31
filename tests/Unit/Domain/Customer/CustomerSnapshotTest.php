<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Customer;

use Silao\Domain\Customer\ValueObject\CustomerId;
use Silao\Domain\Customer\ValueObject\CustomerSnapshot;
use Silao\Domain\Customer\ValueObject\Email;
use Silao\Domain\Customer\ValueObject\PhoneNumber;
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