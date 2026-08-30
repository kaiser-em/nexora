<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Booking;

use Nexora\Domain\Booking\Exception\InvalidFormDataSnapshotException;
use Nexora\Domain\Booking\ValueObject\FormDataSnapshot;
use PHPUnit\Framework\TestCase;

final class FormDataSnapshotTest extends TestCase
{
    public function testValidFormDataSnapshot(): void
    {
        $data = [
            'passengers' => 4,
            'pickup_location' => 'CDG',
            'notes' => 'VIP client',
        ];

        $snapshot = new FormDataSnapshot($data);

        $this->assertSame(4, $snapshot->get('passengers'));
        $this->assertSame('CDG', $snapshot->get('pickup_location'));
        $this->assertSame('default', $snapshot->get('unknown', 'default'));
        $this->assertTrue($snapshot->has('passengers'));
        $this->assertFalse($snapshot->has('unknown'));
        $this->assertSame($data, $snapshot->all());
    }

    public function testInvalidKeyThrowsException(): void
    {
        $this->expectException(InvalidFormDataSnapshotException::class);
        new FormDataSnapshot(['   ' => 'value']);
    }
}