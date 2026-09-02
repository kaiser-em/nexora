<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Infrastructure\Transaction;

use Exception;
use PHPUnit\Framework\TestCase;
use Silao\Infrastructure\Transaction\WpTransactionManager;

final class WpTransactionManagerTest extends TestCase
{
    public function testSuccessfulTransactionCommits(): void
    {
        $mockWpdb = new class {
            /** @var array<string> */
            public array $queries = [];

            public function query(string $sql): int
            {
                $this->queries[] = $sql;
                return 1;
            }
        };

        $txManager = new WpTransactionManager($mockWpdb);
        $result = $txManager->transactional(static fn(): string => "SUCCESS");

        $this->assertSame("SUCCESS", $result);
        $this->assertSame(["START TRANSACTION", "COMMIT"], $mockWpdb->queries);
    }

    public function testFailedTransactionRollbacksAndPropagatesExactException(): void
    {
        $mockWpdb = new class {
            /** @var array<string> */
            public array $queries = [];

            public function query(string $sql): int
            {
                $this->queries[] = $sql;
                return 1;
            }
        };

        $txManager = new WpTransactionManager($mockWpdb);

        $this->expectException(Exception::class);
        $this->expectExceptionMessage("Operation failed");

        try {
            $txManager->transactional(static function (): never {
                throw new Exception("Operation failed");
            });
        } finally {
            $this->assertSame(["START TRANSACTION", "ROLLBACK"], $mockWpdb->queries);
            $this->assertNotContains("COMMIT", $mockWpdb->queries);
        }
    }
}
