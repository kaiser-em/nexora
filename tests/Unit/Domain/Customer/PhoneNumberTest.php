<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Customer;

use Nexora\Domain\Customer\Exception\InvalidPhoneNumberException;
use Nexora\Domain\Customer\ValueObject\PhoneNumber;
use PHPUnit\Framework\TestCase;

final class PhoneNumberTest extends TestCase
{
    public function testValidPhoneNumberFormats(): void
    {
        $p1 = new PhoneNumber('+33 6 12 34 56 78');
        $this->assertSame('+33 6 12 34 56 78', $p1->toString());
        $this->assertSame('33612345678', $p1->digits());

        $p2 = PhoneNumber::fromString('+1 (555) 123-4567');
        $this->assertSame('15551234567', $p2->digits());

        $this->assertTrue($p1->equals(PhoneNumber::fromString('+33612345678')));
    }

    public function testTooFewDigitsThrowsException(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        new PhoneNumber('+33 1 2'); // Only 4 digits
    }

    public function testTooManyDigitsThrowsException(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        new PhoneNumber('1234567890123456'); // 16 digits > 15
    }

    public function testInvalidCharactersThrowsException(): void
    {
        $this->expectException(InvalidPhoneNumberException::class);
        PhoneNumber::fromString('phone#123456');
    }
}