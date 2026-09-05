<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Frontend;

use PHPUnit\Framework\TestCase;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Frontend\Shortcode;

final class ShortcodeTest extends TestCase
{
    public function testRenderPublishedModelProducesValidRootContainer(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'transfer-vip',
            'VIP Transfer',
            'Description',
            Money::of(5000, Currency::EUR()),
            BookingModelStatus::Published
        );

        $repo = $this->createMock(BookingModelRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn($model);

        $shortcode = new Shortcode($repo);
        $html = $shortcode->render(['model' => 'transfer-vip']);

        $this->assertStringContainsString('class="silao-booking-widget"', $html);
        $this->assertStringContainsString('data-model-id="m1"', $html);
        $this->assertStringContainsString('data-model-slug="transfer-vip"', $html);
        $this->assertStringContainsString('<noscript>', $html);
        $this->assertTrue(Shortcode::hasRendered());
    }

    public function testRenderDraftModelReturnsEmptyForNonAdmin(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m2'),
            'draft-model',
            'Draft Model',
            '',
            Money::of(5000, Currency::EUR()),
            BookingModelStatus::Draft
        );

        $repo = $this->createMock(BookingModelRepositoryInterface::class);
        $repo->method('findBySlug')->willReturn($model);

        $shortcode = new Shortcode($repo);
        $html = $shortcode->render(['model' => 'draft-model']);

        $this->assertSame('', $html);
    }
}