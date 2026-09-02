<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Serializer;

use Silao\Domain\Condition\Contract\ConditionInterface;
use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\Enum\LogicalOperator;
use Silao\Domain\Condition\ValueObject\CompositeCondition;
use Silao\Domain\Condition\ValueObject\NotCondition;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use Silao\Infrastructure\Exception\PersistenceException;

final class ConditionSerializer
{
    /**
     * @return array<string, mixed>|null
     */
    public static function serialize(?ConditionInterface $condition): ?array
    {
        if ($condition === null) {
            return null;
        }

        if ($condition instanceof SingleCondition) {
            return [
                'type' => 'single',
                'field' => $condition->field,
                'operator' => $condition->operator->value,
                'expected_value' => $condition->expectedValue,
            ];
        }

        if ($condition instanceof CompositeCondition) {
            $serializedChildren = [];
            foreach ($condition->conditions as $child) {
                $serializedChild = self::serialize($child);
                if ($serializedChild !== null) {
                    $serializedChildren[] = $serializedChild;
                }
            }

            return [
                'type' => 'composite',
                'operator' => $condition->operator->value,
                'conditions' => $serializedChildren,
            ];
        }

        if ($condition instanceof NotCondition) {
            return [
                'type' => 'not',
                'condition' => self::serialize($condition->condition),
            ];
        }

        throw new PersistenceException(
            sprintf('Cannot serialize condition of unknown class "%s".', get_class($condition))
        );
    }

    /**
     * @param array<string, mixed>|null $data
     * @throws PersistenceException
     */
    public static function deserialize(?array $data): ?ConditionInterface
    {
        if ($data === null || empty($data)) {
            return null;
        }

        if (!isset($data['type']) || !is_string($data['type'])) {
            throw new PersistenceException('Malformed condition data: missing or invalid "type" key.');
        }

        return match ($data['type']) {
            'single' => self::deserializeSingle($data),
            'composite' => self::deserializeComposite($data),
            'not' => self::deserializeNot($data),
            default => throw new PersistenceException(sprintf('Unknown condition type "%s".', $data['type'])),
        };
    }

    /**
     * @param array<string, mixed> $data
     * @throws PersistenceException
     */
    private static function deserializeSingle(array $data): SingleCondition
    {
        if (!isset($data['field']) || !is_string($data['field'])) {
            throw new PersistenceException('Malformed SingleCondition: missing or invalid "field".');
        }

        if (!isset($data['operator']) || !is_string($data['operator'])) {
            throw new PersistenceException('Malformed SingleCondition: missing or invalid "operator".');
        }

        $operator = ComparisonOperator::tryFrom($data['operator']);
        if ($operator === null) {
            throw new PersistenceException(sprintf('Unknown comparison operator "%s".', $data['operator']));
        }

        if (!array_key_exists('expected_value', $data)) {
            throw new PersistenceException('Malformed SingleCondition: missing "expected_value".');
        }

        try {
            return new SingleCondition($data['field'], $operator, $data['expected_value']);
        } catch (\Throwable $e) {
            throw new PersistenceException('Failed to instantiate SingleCondition: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws PersistenceException
     */
    private static function deserializeComposite(array $data): CompositeCondition
    {
        if (!isset($data['operator']) || !is_string($data['operator'])) {
            throw new PersistenceException('Malformed CompositeCondition: missing or invalid "operator".');
        }

        $operator = LogicalOperator::tryFrom($data['operator']);
        if ($operator === null) {
            throw new PersistenceException(sprintf('Unknown logical operator "%s".', $data['operator']));
        }

        if (!isset($data['conditions']) || !is_array($data['conditions']) || empty($data['conditions'])) {
            throw new PersistenceException('Malformed CompositeCondition: "conditions" must be a non-empty array.');
        }

        $subConditions = [];
        foreach ($data['conditions'] as $childData) {
            if (!is_array($childData)) {
                throw new PersistenceException('Malformed CompositeCondition: child condition must be an array.');
            }
            $subConditions[] = self::deserialize($childData);
        }

        try {
            return new CompositeCondition($operator, $subConditions);
        } catch (\Throwable $e) {
            throw new PersistenceException('Failed to instantiate CompositeCondition: ' . $e->getMessage(), 0, $e);
        }
    }

    /**
     * @param array<string, mixed> $data
     * @throws PersistenceException
     */
    private static function deserializeNot(array $data): NotCondition
    {
        if (!isset($data['condition']) || !is_array($data['condition'])) {
            throw new PersistenceException('Malformed NotCondition: missing or invalid "condition" object.');
        }

        $child = self::deserialize($data['condition']);
        if ($child === null) {
            throw new PersistenceException('Malformed NotCondition: child condition cannot be null.');
        }

        return new NotCondition($child);
    }
}