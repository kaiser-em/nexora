<?php

declare(strict_types=1);

namespace Nexora\Domain\Condition\ValueObject;

use Nexora\Domain\Condition\Contract\ConditionContextInterface;
use Nexora\Domain\Condition\Contract\ConditionInterface;

final readonly class NotCondition implements ConditionInterface
{
    public function __construct(public ConditionInterface $condition)
    {
    }

    public function evaluate(ConditionContextInterface $context): bool
    {
        return !$this->condition->evaluate($context);
    }
}