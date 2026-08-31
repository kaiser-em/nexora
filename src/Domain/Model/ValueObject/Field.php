<?php

declare(strict_types=1);

namespace Silao\Domain\Model\ValueObject;

use Silao\Domain\Condition\Contract\ConditionContextInterface;
use Silao\Domain\Condition\Contract\ConditionInterface;
use Silao\Domain\Model\Enum\FieldType;
use Silao\Domain\Model\Exception\InvalidFieldException;

final readonly class Field
{
    public string $name;
    public FieldType $type;
    public string $label;
    public bool $isRequired;
    public mixed $defaultValue;
    /** @var array<string, mixed> */
    public array $validationRules;
    public ?ConditionInterface $visibilityCondition;

    /**
     * @param array<string, mixed> $validationRules
     * @throws InvalidFieldException
     */
    public function __construct(
        string $name,
        FieldType $type,
        string $label,
        bool $isRequired = false,
        mixed $defaultValue = null,
        array $validationRules = [],
        ?ConditionInterface $visibilityCondition = null
    ) {
        $trimmedName = trim($name);
        if (preg_match('/^[a-zA-Z0-9_]{1,64}$/', $trimmedName) !== 1) {
            throw new InvalidFieldException(
                sprintf('Invalid field name "%s". Must be alphanumeric with underscores (1-64 characters).', $name)
            );
        }

        $trimmedLabel = trim($label);
        if ($trimmedLabel === '') {
            throw new InvalidFieldException('Field label cannot be empty.');
        }

        $this->name = $trimmedName;
        $this->type = $type;
        $this->label = $trimmedLabel;
        $this->isRequired = $isRequired;
        $this->defaultValue = $defaultValue;
        $this->validationRules = $validationRules;
        $this->visibilityCondition = $visibilityCondition;
    }

    public function isVisible(ConditionContextInterface $context): bool
    {
        if ($this->visibilityCondition === null) {
            return true;
        }

        return $this->visibilityCondition->evaluate($context);
    }
}