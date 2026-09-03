<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Application\Service;

use PHPUnit\Framework\TestCase;
use Silao\Application\Command\UpdateResourceStatusCommand;
use Silao\Application\Service\UpdateResourceStatusService;
use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Domain\Resource\Enum\ResourceStatus;
use Silao\Domain\Resource\Repository\ResourceRepositoryInterface;
use Silao\Domain\Resource\Resource;
use Silao\Domain\Resource\ValueObject\Capacity;
use Silao\Domain\Resource\ValueObject\ResourceId;

final class UpdateResourceStatusServiceTest extends TestCase
{
    public function testExecuteUpdatesStatusUnderTransaction(): void
    {
        $resource = new Resource(ResourceId::fromString('res_1'), 'Van', Capacity::of(4), ResourceStatus::Active);

        $repo = $this->createMock(ResourceRepositoryInterface::class);
        $repo->method('findById')->willReturn($resource);
        $repo->expects($this->once())->method('save');

        $txManager = new class implements TransactionManagerInterface {
            public function transactional(callable $op): mixed
            {
                return $op();
            }
        };

        $service = new UpdateResourceStatusService($repo, $txManager);

        $dto = $service->execute(new UpdateResourceStatusCommand('res_1', 'maintenance'));

        $this->assertSame('maintenance', $dto->status);
        $this->assertSame(ResourceStatus::Maintenance, $resource->status());
    }
}