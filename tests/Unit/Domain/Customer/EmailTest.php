<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Customer;

use Silao\Domain\Customer\Exception\InvalidEmailException;
use Silao\Domain\Customer\ValueObject\Email;
use PHPUnit\Framework\TestCase;

final class EmailTest extends TestCase
{
    public function testValidEmailAndNormalization(): void
    {
        $email = new Email('  USER.Name+tag@Sub.Domain.co.uk  ');
        $this->assertSame('user.name+tag@sub.domain.co.uk', $email->toString());
        $this->assertTrue($email->equals(Email::fromString('user.name+tag@sub.domain.co.uk')));
    }

    public function testInvalidEmailThrowsException(): void
    {
        $this->expectException(InvalidEmailException::class);
        new Email('invalid-email-string');
    }

    public function testMissingDomainThrowsException(): void
    {
        $this->expectException(InvalidEmailException::class);
        Email::fromString('user@');
    }
}