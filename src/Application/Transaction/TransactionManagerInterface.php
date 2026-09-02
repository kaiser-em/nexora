<?php

declare(strict_types=1);

namespace Silao\Application\Transaction;

use Throwable;

interface TransactionManagerInterface
{
    /**
     * @template T
     * @param callable(): T $operation
     * @return T
     * @throws Throwable
     */
    public function transactional(callable $operation): mixed;
}