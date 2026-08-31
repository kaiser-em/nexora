<?php

declare(strict_types=1);

namespace Silao\Domain\Condition\ValueObject;

use Silao\Domain\Condition\Contract\ConditionContextInterface;
use Silao\Domain\Condition\Contract\ConditionInterface;

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