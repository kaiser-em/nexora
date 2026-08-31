<?php

declare(strict_types=1);

namespace Silao\Domain\Condition\Contract;

interface ConditionInterface
{
    public function evaluate(ConditionContextInterface $context): bool;
}