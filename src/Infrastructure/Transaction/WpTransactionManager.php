<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Transaction;

use Silao\Application\Transaction\TransactionManagerInterface;
use Throwable;
use wpdb;

final readonly class WpTransactionManager implements TransactionManagerInterface
{
    /**
     * @param wpdb|object $wpdb
     */
    public function __construct(
        private object $wpdb
    ) {
    }

    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws Throwable
     */
    public function transactional(callable $operation): mixed
    {
        $this->wpdb->query("START TRANSACTION");

        try {
            $result = $operation();
            $this->wpdb->query("COMMIT");

            return $result;
        } catch (Throwable $e) {
            $this->wpdb->query("ROLLBACK");
            throw $e;
        }
    }
}
