<?php

declare(strict_types=1);

namespace Silao\Domain\Condition\ValueObject;

use Silao\Domain\Condition\Contract\ConditionContextInterface;
use Silao\Domain\Condition\Contract\ConditionInterface;
use Silao\Domain\Condition\Enum\LogicalOperator;
use Silao\Domain\Condition\Exception\InvalidConditionException;

final readonly class CompositeCondition implements ConditionInterface
{
    public LogicalOperator $operator;
    /** @var array<ConditionInterface> */
    public array $conditions;

    /**
     * @param array<mixed> $conditions
     * @throws InvalidConditionException
     */
    public function __construct(LogicalOperator $operator, array $conditions)
    {
        if (empty($conditions)) {
            throw new InvalidConditionException('Composite condition requires at least one sub-condition.');
        }

        $validated = [];
        foreach ($conditions as $condition) {
            if (!$condition instanceof ConditionInterface) {
                throw new InvalidConditionException('Each element of conditions must implement ConditionInterface.');
            }
            $validated[] = $condition;
        }

        $this->operator = $operator;
        $this->conditions = $validated;
    }

    /**
     * @param array<mixed> $conditions
     * @throws InvalidConditionException
     */
    public static function and(array $conditions): self
    {
        return new self(LogicalOperator::And, $conditions);
    }

    /**
     * @param array<mixed> $conditions
     * @throws InvalidConditionException
     */
    public static function or(array $conditions): self
    {
        return new self(LogicalOperator::Or, $conditions);
    }

    public function evaluate(ConditionContextInterface $context): bool
    {
        if ($this->operator === LogicalOperator::And) {
            foreach ($this->conditions as $condition) {
                // Short-circuit AND: breaks on first false
                if (!$condition->evaluate($context)) {
                    return false;
                }
            }

            return true;
        }

        // Short-circuit OR: breaks on first true
        foreach ($this->conditions as $condition) {
            if ($condition->evaluate($context)) {
                return true;
            }
        }

        return false;
    }
}