<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Silao\Application\Command\ReplaceResourceCommand;
use Silao\Application\Service\ReplaceResourceService;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;

final class ReplaceResourceServiceTest extends TestCase
{
    public function testExecuteReplacesResourceUnderTransaction(): void
    {
        $repo = $this->createMock(ResourceRepositoryInterface::class);
        $repo->expects($this->once())->method('save');

        $txManager = new class implements TransactionManagerInterface {
            public function transactional(callable $op): mixed
            {
                return $op();
            }
        };

        $service = new ReplaceResourceService($repo, $txManager);

        $command = new ReplaceResourceCommand(
            'res_van',
            'Mercedes V-Class VIP',
            7,
            'active',
            [['day_of_week' => 1, 'start_time' => '08:00:00', 'end_time' => '18:00:00']]
        );

        $dto = $service->execute($command);

        $this->assertSame('res_van', $dto->resourceId);
        $this->assertSame('Mercedes V-Class VIP', $dto->name);
        $this->assertSame(7, $dto->capacity);
        $this->assertCount(1, $dto->schedules);
    }
}