<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Admin;

use PHPUnit\Framework\TestCase;

final class AdminAuthorizationTest extends TestCase
{
    public function testScreenCapabilitiesDistinction(): void
    {
        global $silao_current_user_capabilities;

        // 1. Anonymous visitor -> No access
        $silao_current_user_capabilities = [];
        $this->assertFalse(current_user_can('manage_silao'));
        $this->assertFalse(current_user_can('manage_silao_bookings'));

        // 2. Booking Manager only -> Has manage_silao_bookings, not manage_silao
        $silao_current_user_capabilities = ['manage_silao_bookings' => true];
        $this->assertTrue(current_user_can('manage_silao_bookings'));
        $this->assertFalse(current_user_can('manage_silao'));

        // 3. Full Admin -> Has manage_silao
        $silao_current_user_capabilities = ['manage_silao' => true];
        $this->assertTrue(current_user_can('manage_silao'));
    }
}