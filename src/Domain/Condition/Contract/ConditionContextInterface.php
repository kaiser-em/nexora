<?php

declare(strict_types=1);

namespace Nexora\Domain\Condition\Contract;

interface ConditionContextInterface
{
    public function get(string $key, mixed $default = null): mixed;

    public function has(string $key): bool;
}

