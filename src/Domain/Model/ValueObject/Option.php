<?php

declare(strict_types=1);

namespace Silao\Domain\Model\ValueObject;

use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Condition\Contract\ConditionContextInterface;
use Silao\Domain\Condition\Contract\ConditionInterface;
use Silao\Domain\Model\Enum\OptionPricingType;
use Silao\Domain\Model\Exception\InvalidOptionException;

final readonly class Option
{
    public string $id;
    public string $code;
    public string $name;
    public string $description;
    public OptionPricingType $pricingType;
    public Money $unitPrice;
    public int $minQuantity;
    public int $maxQuantity;
    public bool $isMandatory;
    public int $capacityImpact;
    public ?ConditionInterface $condition;

    /**
     * @throws InvalidOptionException
     */
    public function __construct(
        string $id,
        string $code,
        string $name,
        string $description,
        OptionPricingType $pricingType,
        Money $unitPrice,
        int $minQuantity = 0,
        int $maxQuantity = 1,
        bool $isMandatory = false,
        int $capacityImpact = 0,
        ?ConditionInterface $condition = null
    ) {
        $trimmedId = trim($id);
        if ($trimmedId === '') {
            throw new InvalidOptionException('Option ID cannot be empty.');
        }

        $trimmedCode = trim($code);
        if ($trimmedCode === '') {
            throw new InvalidOptionException('Option code cannot be empty.');
        }

        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw new InvalidOptionException('Option name cannot be empty.');
        }

        if ($minQuantity < 0) {
            throw new InvalidOptionException(
                sprintf('Option minQuantity must be >= 0, %d given.', $minQuantity)
            );
        }

        if ($maxQuantity < 1 || $maxQuantity < $minQuantity) {
            throw new InvalidOptionException(
                sprintf('Option maxQuantity must be >= 1 and >= minQuantity (%d), %d given.', $minQuantity, $maxQuantity)
            );
        }

        if ($capacityImpact < 0) {
            throw new InvalidOptionException(
                sprintf('Option capacityImpact must be >= 0, %d given.', $capacityImpact)
            );
        }

        $this->id = $trimmedId;
        $this->code = $trimmedCode;
        $this->name = $trimmedName;
        $this->description = trim($description);
        $this->pricingType = $pricingType;
        $this->unitPrice = $unitPrice;
        $this->minQuantity = $minQuantity;
        $this->maxQuantity = $maxQuantity;
        $this->isMandatory = $isMandatory;
        $this->capacityImpact = $capacityImpact;
        $this->condition = $condition;
    }

    public function isAvailable(ConditionContextInterface $context): bool
    {
        if ($this->condition === null) {
            return true;
        }

        return $this->condition->evaluate($context);
    }
}