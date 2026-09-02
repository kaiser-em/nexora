<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Infrastructure\Event;

use PHPUnit\Framework\TestCase;
use Silao\Application\Event\BookingCancelledEvent;
use Silao\Application\Event\BookingConfirmedEvent;
use Silao\Application\Event\BookingCreatedEvent;
use Silao\Infrastructure\Event\WpHookEventDispatcher;

final class WpHookEventDispatcherTest extends TestCase
{
    public function testDispatchEmitsExpectedWordPressActionHooks(): void
    {
        $emittedActions = [];

        $emitter = static function (string $action, mixed ...$args) use (&$emittedActions): void {
            $emittedActions[] = ['action' => $action, 'args' => $args];
        };

        $dispatcher = new WpHookEventDispatcher($emitter);

        $dispatcher->dispatch(new BookingCreatedEvent('b1', 'SIL-001', 'm1', 'user@example.com'));
        $dispatcher->dispatch(new BookingConfirmedEvent('b1', 'SIL-001'));
        $dispatcher->dispatch(new BookingCancelledEvent('b1', 'SIL-001'));

        $this->assertCount(3, $emittedActions);
        $this->assertSame('silao_booking_created', $emittedActions[0]['action']);
        $this->assertSame(['b1', 'SIL-001', 'm1', 'user@example.com'], $emittedActions[0]['args']);
        $this->assertSame('silao_booking_confirmed', $emittedActions[1]['action']);
        $this->assertSame('silao_booking_cancelled', $emittedActions[2]['action']);
    }
}