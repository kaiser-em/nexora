<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Silao\Application\Command\CalculateQuoteCommand;
use Silao\Application\Service\CalculateQuoteService;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Repository\BookingModelRepositoryInterface;
use Silao\Domain\Model\ValueObject\BookingModelId;

final class CalculateQuoteServiceTest extends TestCase
{
    public function testExecuteReturnsReadOnlyQuoteDTO(): void
    {
        $eur = Currency::EUR();
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'service-model',
            'Consultation',
            '',
            Money::of(8000, $eur),
            BookingModelStatus::Published
        );

        $repo = $this->createMock(BookingModelRepositoryInterface::class);
        $repo->method('findById')->willReturn($model);

        $service = new CalculateQuoteService($repo);

        $command = new CalculateQuoteCommand(
            'm1',
            null,
            '2026-06-15 10:00:00',
            '2026-06-15 11:00:00',
            'UTC'
        );

        $dto = $service->execute($command);

        $this->assertSame('EUR', $dto->currency);
        $this->assertSame(8000, $dto->totalMinorUnits);
        $this->assertSame('80.00 €', $dto->formattedTotal);
    }
}