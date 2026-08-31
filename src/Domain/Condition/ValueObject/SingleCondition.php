<?php

declare(strict_types=1);

namespace Silao\Domain\Condition\ValueObject;

use Silao\Domain\Condition\Contract\ConditionContextInterface;
use Silao\Domain\Condition\Contract\ConditionInterface;
use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\Exception\InvalidConditionException;
use Silao\Domain\Condition\Helper\TypeComparator;

final readonly class SingleCondition implements ConditionInterface
{
    public string $field;
    public ComparisonOperator $operator;
    public mixed $expectedValue;

    /**
     * @throws InvalidConditionException
     */
    public function __construct(string $field, ComparisonOperator $operator, mixed $expectedValue)
    {
        $trimmedField = trim($field);
        if ($trimmedField === '') {
            throw new InvalidConditionException('Condition field name cannot be empty.');
        }

        if (
            in_array($operator, [
                ComparisonOperator::GreaterThan,
                ComparisonOperator::GreaterThanOrEqual,
                ComparisonOperator::LessThan,
                ComparisonOperator::LessThanOrEqual,
            ], true) && !is_int($expectedValue)
        ) {
            throw new InvalidConditionException(
                sprintf('Relational operator %s requires integer expected value, %s given.', $operator->value, get_debug_type($expectedValue))
            );
        }

        if (
            in_array($operator, [ComparisonOperator::In, ComparisonOperator::NotIn], true)
            && !is_array($expectedValue)
        ) {
            throw new InvalidConditionException(
                sprintf('Operator %s requires array expected value, %s given.', $operator->value, get_debug_type($expectedValue))
            );
        }

        $this->field = $trimmedField;
        $this->operator = $operator;
        $this->expectedValue = $expectedValue;
    }

    public function evaluate(ConditionContextInterface $context): bool
    {
        if (!$context->has($this->field)) {
            if ($this->operator === ComparisonOperator::NotEquals) {
                return $this->expectedValue !== null;
            }

            if ($this->operator === ComparisonOperator::NotIn) {
                return true;
            }

            return false;
        }

        $actual = $context->get($this->field);

        try {
            return TypeComparator::compare($actual, $this->operator, $this->expectedValue);
        } catch (InvalidConditionException) {
            return false;
        }
    }
}