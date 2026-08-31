<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Resource;

use Silao\Domain\Resource\Exception\InvalidResourceException;
use Silao\Domain\Resource\ValueObject\ResourceId;
use PHPUnit\Framework\TestCase;

final class ResourceIdTest extends TestCase
{
    public function testValidResourceId(): void
    {
        $id = new ResourceId('res_123');
        $this->assertSame('res_123', $id->toString());
        $this->assertTrue($id->equals(ResourceId::fromString('res_123')));
        $this->assertFalse($id->equals(ResourceId::fromString('res_456')));
    }

    public function testEmptyResourceIdThrowsException(): void
    {
        $this->expectException(InvalidResourceException::class);
        new ResourceId('   ');
    }
}