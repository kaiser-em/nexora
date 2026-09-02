<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Infrastructure\Container;

use PHPUnit\Framework\TestCase;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\Exception\ServiceNotFoundException;
use stdClass;

final class ContainerTest extends TestCase
{
    public function testLazyFactoryNotCalledBeforeGet(): void
    {
        $container = new Container();
        $tracker = new class {
            public bool $called = false;
        };

        $container->bind('service_a', static function () use ($tracker): stdClass {
            $tracker->called = true;
            return new stdClass();
        });

        $this->assertFalse($tracker->called);
        $this->assertTrue($container->has('service_a'));

        $instance = $container->get('service_a');
        $this->assertTrue($tracker->called);
        $this->assertInstanceOf(stdClass::class, $instance);
    }

    public function testSingletonFactoryCalledOnlyOnce(): void
    {
        $container = new Container();
        $tracker = new class {
            public int $callCount = 0;
        };

        $container->singleton('service_single', static function () use ($tracker): stdClass {
            $tracker->callCount++;
            return new stdClass();
        });

        $obj1 = $container->get('service_single');
        $obj2 = $container->get('service_single');

        $this->assertSame(1, $tracker->callCount);
        $this->assertSame($obj1, $obj2);
    }

    public function testUnregisteredServiceThrowsException(): void
    {
        $container = new Container();
        $this->expectException(ServiceNotFoundException::class);
        $container->get('unknown_service');
    }
}