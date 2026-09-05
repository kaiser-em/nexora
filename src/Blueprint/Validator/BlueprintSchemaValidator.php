<?php

declare(strict_types=1);

namespace Silao\Blueprint\Validator;

use Silao\Blueprint\Exception\InvalidBlueprintException;

final class BlueprintSchemaValidator
{
    private const array SYSTEM_FIELDS = [
        'time.hour',
        'time.minute',
        'date.day_of_week',
        'date.is_weekend',
        'duration_minutes',
    ];

    /**
     * @param array<string, mixed> $data
     * @throws InvalidBlueprintException
     */
    public static function validate(array $data): void
    {
        // 1. Structure Checks (No Additional Properties mapped directly, strict keys)
        $allowedKeys = ['schema_version', 'id', 'version', 'name', 'description', 'category', 'model', 'fields', 'options', 'pricing_rules', 'sample_resources'];
        $diff = array_diff(array_keys($data), $allowedKeys);
        if (!empty($diff)) {
            throw new InvalidBlueprintException(sprintf('Blueprint contains unknown properties: %s', implode(', ', $diff)));
        }

        $requiredKeys = ['id', 'version', 'model', 'fields', 'options', 'pricing_rules', 'sample_resources'];
        foreach ($requiredKeys as $k) {
            if (!isset($data[$k])) {
                throw new InvalidBlueprintException(sprintf('Blueprint is missing required property: "%s".', $k));
            }
        }

        // 2. Build Whitelist for Semantic AST Check
        $whitelistedPaths = self::SYSTEM_FIELDS;

        if (is_array($data['fields'])) {
            foreach ($data['fields'] as $f) {
                if (isset($f['name']) && is_string($f['name'])) {
                    $whitelistedPaths[] = $f['name'];
                }
            }
        }

        if (is_array($data['options'])) {
            foreach ($data['options'] as $o) {
                if (isset($o['id']) && is_string($o['id'])) {
                    $whitelistedPaths[] = 'options.' . $o['id'] . '.quantity';
                }
            }
        }

        // 3. Semantic Validation of Conditions (AST Closure)
        if (is_array($data['pricing_rules'])) {
            foreach ($data['pricing_rules'] as $rule) {
                if (isset($rule['condition']) && is_array($rule['condition'])) {
                    self::validateConditionAST($rule['condition'], $whitelistedPaths);
                }
            }
        }
    }

    /**
     * @param array<string, mixed> $condition
     * @param array<string> $whitelist
     * @throws InvalidBlueprintException
     */
    private static function validateConditionAST(array $condition, array $whitelist): void
    {
        $type = $condition['type'] ?? null;

        if ($type === 'single') {
            $field = $condition['field'] ?? null;
            if (!is_string($field) || !in_array($field, $whitelist, true)) {
                throw new InvalidBlueprintException(sprintf('Condition AST uses an unauthorized or undeclared field: "%s".', (string) $field));
            }
            return;
        }

        if ($type === 'composite') {
            $subs = $condition['conditions'] ?? [];
            if (!is_array($subs)) {
                throw new InvalidBlueprintException('Composite condition requires an array of sub-conditions.');
            }
            foreach ($subs as $sub) {
                if (is_array($sub)) {
                    self::validateConditionAST($sub, $whitelist);
                }
            }
            return;
        }

        if ($type === 'not') {
            $sub = $condition['condition'] ?? null;
            if (is_array($sub)) {
                self::validateConditionAST($sub, $whitelist);
            }
            return;
        }

        throw new InvalidBlueprintException(sprintf('Unknown condition AST type: "%s".', (string) $type));
    }
}