<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\REST;

use PHPUnit\Framework\TestCase;
use Silao\Infrastructure\Container\Container;
use Silao\REST\Controller\BookingController;
use Silao\REST\Controller\QuoteController;

final class RestSecurityTest extends TestCase
{
    public function testPublicEndpointsReturnTrueWithoutAuthOrNonce(): void
    {
        $container = new Container();
        $quoteCtrl = new QuoteController($container);
        $bookCtrl = new BookingController($container);

        $this->assertTrue($quoteCtrl->checkPublicPermission());
        $this->assertTrue($bookCtrl->checkPublicPermission());
    }

    public function testAdminEndpointsRequireAppropriateCapabilities(): void
    {
        global $silao_current_user_capabilities;

        $container = new Container();
        $bookCtrl = new BookingController($container);

        // 1. Anonymous visitor -> False
        $silao_current_user_capabilities = [];
        $this->assertFalse($bookCtrl->checkManageBookingsPermission());

        // 2. Subscriber without capability -> False
        $silao_current_user_capabilities = ['read' => true];
        $this->assertFalse($bookCtrl->checkManageBookingsPermission());

        // 3. User with manage_silao_bookings -> True
        $silao_current_user_capabilities = ['manage_silao_bookings' => true];
        $this->assertTrue($bookCtrl->checkManageBookingsPermission());
    }
}