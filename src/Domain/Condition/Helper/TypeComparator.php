<?php

declare(strict_types=1);

namespace Nexora\Domain\Condition\Helper;

use Nexora\Domain\Condition\Enum\ComparisonOperator;
use Nexora\Domain\Condition\Exception\InvalidConditionException;

final class TypeComparator
{
    /**
     * @throws InvalidConditionException
     */
    public static function compare(mixed $actual, ComparisonOperator $operator, mixed $expected): bool
    {
        return match ($operator) {
            ComparisonOperator::Equals => $actual === $expected,
            ComparisonOperator::NotEquals => $actual !== $expected,
            ComparisonOperator::GreaterThan => self::compareInt($actual, $expected, static fn(int $a, int $b): bool => $a > $b),
            ComparisonOperator::GreaterThanOrEqual => self::compareInt($actual, $expected, static fn(int $a, int $b): bool => $a >= $b),
            ComparisonOperator::LessThan => self::compareInt($actual, $expected, static fn(int $a, int $b): bool => $a < $b),
            ComparisonOperator::LessThanOrEqual => self::compareInt($actual, $expected, static fn(int $a, int $b): bool => $a <= $b),
            ComparisonOperator::In => self::compareIn($actual, $expected),
            ComparisonOperator::NotIn => !self::compareIn($actual, $expected),
            ComparisonOperator::Contains => self::compareContains($actual, $expected),
        };
    }

    /**
     * @param callable(int, int): bool $comparison
     * @throws InvalidConditionException
     */
    private static function compareInt(mixed $actual, mixed $expected, callable $comparison): bool
    {
        if (!is_int($actual) || !is_int($expected)) {
            throw new InvalidConditionException(
                sprintf(
                    'Relational operator requires integer operands, %s and %s given.',
                    get_debug_type($actual),
                    get_debug_type($expected)
                )
            );
        }

        return $comparison($actual, $expected);
    }

    /**
     * @throws InvalidConditionException
     */
    private static function compareIn(mixed $actual, mixed $expected): bool
    {
        if (!is_array($expected)) {
            throw new InvalidConditionException(
                sprintf('Operator "In" / "NotIn" requires an array expected value, %s given.', get_debug_type($expected))
            );
        }

        return in_array($actual, $expected, true);
    }

    /**
     * @throws InvalidConditionException
     */
    private static function compareContains(mixed $actual, mixed $expected): bool
    {
        if (is_string($actual)) {
            if (!is_string($expected)) {
                throw new InvalidConditionException(
                    sprintf('Contains operator on string actual value requires a string expected value, %s given.', get_debug_type($expected))
                );
            }

            if ($expected === '') {
                return true;
            }

            return str_contains($actual, $expected);
        }

        if (is_array($actual)) {
            return in_array($expected, $actual, true);
        }

        throw new InvalidConditionException(
            sprintf('Contains operator requires actual value to be a string or array, %s given.', get_debug_type($actual))
        );
    }
}