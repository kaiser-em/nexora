<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Silao\Application\Service\BookingReferenceGenerator;

final class BookingReferenceGeneratorTest extends TestCase
{
    public function testGenerateValidReference(): void
    {
        $ref = BookingReferenceGenerator::generate('SIL');
        $this->assertMatchesRegularExpression('/^SIL-[0-9]{4}-[A-Z0-9]{6}$/', $ref->toString());
    }

    public function testGenerateProducesUniqueReferences(): void
    {
        $ref1 = BookingReferenceGenerator::generate();
        $ref2 = BookingReferenceGenerator::generate();
        $this->assertFalse($ref1->equals($ref2));
    }
}