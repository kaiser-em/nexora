<?php

declare(strict_types=1);

namespace Nexora\Domain\Model;

use Nexora\Domain\Common\ValueObject\Money;
use Nexora\Domain\Model\Enum\BookingModelStatus;
use Nexora\Domain\Model\Enum\ResourceStrategyType;
use Nexora\Domain\Model\Exception\InvalidBookingModelException;
use Nexora\Domain\Model\Exception\InvalidFieldException;
use Nexora\Domain\Model\Exception\InvalidOptionException;
use Nexora\Domain\Model\Exception\InvalidPricingRuleException;
use Nexora\Domain\Model\ValueObject\BookingModelId;
use Nexora\Domain\Model\ValueObject\Field;
use Nexora\Domain\Model\ValueObject\Option;
use Nexora\Domain\Model\ValueObject\PricingRule;
use Nexora\Domain\Resource\ValueObject\ResourceId;

final class BookingModel
{
    private string $slug;
    private string $name;
    private string $description;
    private Money $basePrice;
    private BookingModelStatus $status;
    private ResourceStrategyType $resourceStrategy;
    /** @var array<string, ResourceId> */
    private array $eligibleResourceIds = [];
    /** @var array<string, Field> */
    private array $fields = [];
    /** @var array<string, Option> */
    private array $options = [];
    /** @var array<string, PricingRule> */
    private array $pricingRules = [];

    /**
     * @param array<ResourceId> $eligibleResourceIds
     * @param array<Field> $fields
     * @param array<Option> $options
     * @param array<PricingRule> $pricingRules
     * @throws InvalidBookingModelException
     * @throws InvalidFieldException
     * @throws InvalidOptionException
     * @throws InvalidPricingRuleException
     */
    public function __construct(
        private readonly BookingModelId $id,
        string $slug,
        string $name,
        string $description,
        Money $basePrice,
        BookingModelStatus $status = BookingModelStatus::Draft,
        ResourceStrategyType $resourceStrategy = ResourceStrategyType::None,
        array $eligibleResourceIds = [],
        array $fields = [],
        array $options = [],
        array $pricingRules = []
    ) {
        $this->setSlug($slug);
        $this->setNameAndDescription($name, $description);
        $this->basePrice = $basePrice;
        $this->status = $status;
        $this->resourceStrategy = $resourceStrategy;

        if ($this->resourceStrategy === ResourceStrategyType::None && !empty($eligibleResourceIds)) {
            throw new InvalidBookingModelException('Eligible resources cannot be assigned when resource strategy is None.');
        }

        foreach ($eligibleResourceIds as $resourceId) {
            $this->eligibleResourceIds[$resourceId->toString()] = $resourceId;
        }

        foreach ($fields as $field) {
            $this->addField($field);
        }

        foreach ($options as $option) {
            $this->addOption($option);
        }

        foreach ($pricingRules as $rule) {
            $this->addPricingRule($rule);
        }
    }

    public function id(): BookingModelId
    {
        return $this->id;
    }

    public function slug(): string
    {
        return $this->slug;
    }

    public function name(): string
    {
        return $this->name;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function basePrice(): Money
    {
        return $this->basePrice;
    }

    public function status(): BookingModelStatus
    {
        return $this->status;
    }

    public function resourceStrategy(): ResourceStrategyType
    {
        return $this->resourceStrategy;
    }

    /**
     * @return array<string, ResourceId>
     */
    public function eligibleResourceIds(): array
    {
        return $this->eligibleResourceIds;
    }

    /**
     * @return array<string, Field>
     */
    public function fields(): array
    {
        return $this->fields;
    }

    /**
     * @return array<string, Option>
     */
    public function options(): array
    {
        return $this->options;
    }

    /**
     * @return array<string, PricingRule>
     */
    public function pricingRules(): array
    {
        return $this->pricingRules;
    }

    /**
     * @throws InvalidFieldException
     */
    public function addField(Field $field): void
    {
        if (isset($this->fields[$field->name])) {
            throw new InvalidFieldException(
                sprintf('Field with name "%s" already exists in model "%s".', $field->name, $this->slug)
            );
        }

        $this->fields[$field->name] = $field;
    }

    /**
     * @throws InvalidOptionException
     */
    public function addOption(Option $option): void
    {
        if (isset($this->options[$option->id])) {
            throw new InvalidOptionException(
                sprintf('Option with ID "%s" already exists in model "%s".', $option->id, $this->slug)
            );
        }

        foreach ($this->options as $existing) {
            if ($existing->code === $option->code) {
                throw new InvalidOptionException(
                    sprintf('Option with code "%s" already exists in model "%s".', $option->code, $this->slug)
                );
            }
        }

        if (!$option->unitPrice->currency->equals($this->basePrice->currency)) {
            throw new InvalidOptionException(
                sprintf(
                    'Option "%s" currency (%s) does not match model currency (%s).',
                    $option->name,
                    $option->unitPrice->currency->code,
                    $this->basePrice->currency->code
                )
            );
        }

        $this->options[$option->id] = $option;
    }

    /**
     * @throws InvalidPricingRuleException
     */
    public function addPricingRule(PricingRule $rule): void
    {
        if (isset($this->pricingRules[$rule->id])) {
            throw new InvalidPricingRuleException(
                sprintf('Pricing rule with ID "%s" already exists in model "%s".', $rule->id, $this->slug)
            );
        }

        foreach ($this->pricingRules as $existing) {
            if ($existing->priority === $rule->priority) {
                throw new InvalidPricingRuleException(
                    sprintf('Pricing rule priority %d is already taken by rule "%s".', $rule->priority, $existing->id)
                );
            }
        }

        if ($rule->adjustmentAmount !== null && !$rule->adjustmentAmount->currency->equals($this->basePrice->currency)) {
            throw new InvalidPricingRuleException(
                sprintf(
                    'Pricing rule "%s" amount currency (%s) does not match model currency (%s).',
                    $rule->id,
                    $rule->adjustmentAmount->currency->code,
                    $this->basePrice->currency->code
                )
            );
        }

        $this->pricingRules[$rule->id] = $rule;

        // Sort rules by priority ascending
        uasort(
            $this->pricingRules,
            static fn(PricingRule $a, PricingRule $b): int => $a->priority <=> $b->priority
        );
    }

    /**
     * @throws InvalidBookingModelException
     */
    public function addEligibleResource(ResourceId $resourceId): void
    {
        if ($this->resourceStrategy === ResourceStrategyType::None) {
            throw new InvalidBookingModelException('Cannot add eligible resources when resource strategy is None.');
        }

        $this->eligibleResourceIds[$resourceId->toString()] = $resourceId;
    }

    /**
     * @throws InvalidBookingModelException
     */
    public function publish(): void
    {
        if ($this->resourceStrategy->requiresResource() && empty($this->eligibleResourceIds)) {
            throw new InvalidBookingModelException(
                sprintf('Cannot publish model "%s": resource strategy requires at least one eligible resource.', $this->slug)
            );
        }

        $this->status = BookingModelStatus::Published;
    }

    public function archive(): void
    {
        $this->status = BookingModelStatus::Archived;
    }

    public function setAsDraft(): void
    {
        $this->status = BookingModelStatus::Draft;
    }

    /**
     * @throws InvalidBookingModelException
     */
    public function updateBasePrice(Money $basePrice): void
    {
        foreach ($this->options as $option) {
            if (!$option->unitPrice->currency->equals($basePrice->currency)) {
                throw new InvalidBookingModelException(
                    'Cannot change model base currency when existing options have a different currency.'
                );
            }
        }

        foreach ($this->pricingRules as $rule) {
            if ($rule->adjustmentAmount !== null && !$rule->adjustmentAmount->currency->equals($basePrice->currency)) {
                throw new InvalidBookingModelException(
                    'Cannot change model base currency when existing pricing rules have a different currency.'
                );
            }
        }

        $this->basePrice = $basePrice;
    }

    /**
     * @throws InvalidBookingModelException
     */
    public function updateDetails(string $name, string $description): void
    {
        $this->setNameAndDescription($name, $description);
    }

    /**
     * @throws InvalidBookingModelException
     */
    private function setSlug(string $slug): void
    {
        $trimmed = trim($slug);
        if (preg_match('/^[a-z0-9]+(?:-[a-z0-9]+)*$/', $trimmed) !== 1) {
            throw new InvalidBookingModelException(
                sprintf('Invalid slug format: "%s". Must be lowercase alphanumeric with hyphens.', $slug)
            );
        }

        $this->slug = $trimmed;
    }

    /**
     * @throws InvalidBookingModelException
     */
    private function setNameAndDescription(string $name, string $description): void
    {
        $trimmedName = trim($name);
        if ($trimmedName === '') {
            throw new InvalidBookingModelException('Booking model name cannot be empty.');
        }

        $this->name = $trimmedName;
        $this->description = trim($description);
    }
}
