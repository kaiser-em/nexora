<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Customer;

use Silao\Domain\Customer\Exception\InvalidCustomerException;
use Silao\Domain\Customer\ValueObject\CustomerId;
use PHPUnit\Framework\TestCase;

final class CustomerIdTest extends TestCase
{
    public function testValidCustomerId(): void
    {
        $id = new CustomerId('cust_123');
        $this->assertSame('cust_123', $id->toString());
        $this->assertTrue($id->equals(CustomerId::fromString('cust_123')));
    }

    public function testEmptyCustomerIdThrowsException(): void
    {
        $this->expectException(InvalidCustomerException::class);
        new CustomerId('   ');
    }
}