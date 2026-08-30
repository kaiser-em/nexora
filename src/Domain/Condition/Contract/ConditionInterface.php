<?php

declare(strict_types=1);

namespace Nexora\Domain\Condition\Contract;

interface ConditionInterface
{
    public function evaluate(ConditionContextInterface $context): bool;
}