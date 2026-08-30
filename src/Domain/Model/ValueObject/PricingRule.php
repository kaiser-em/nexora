<?php

declare(strict_types=1);

namespace Nexora\Domain\Model\ValueObject;

use Nexora\Domain\Common\ValueObject\Money;
use Nexora\Domain\Common\ValueObject\Percentage;
use Nexora\Domain\Condition\Contract\ConditionInterface;
use Nexora\Domain\Model\Enum\PricingCalculationBasis;
use Nexora\Domain\Model\Enum\PricingTarget;
use Nexora\Domain\Model\Exception\InvalidPricingRuleException;

final readonly class PricingRule
{
    public string $id;
    public int $priority;
    public ConditionInterface $condition;
    public PricingTarget $target;
    public PricingCalculationBasis $calculationBasis;
    public ?Money $adjustmentAmount;
    public ?Percentage $adjustmentPercentage;
    public bool $stopProcessing;

    /**
     * @throws InvalidPricingRuleException
     */
    public function __construct(
        string $id,
        int $priority,
        ConditionInterface $condition,
        PricingTarget $target,
        PricingCalculationBasis $calculationBasis,
        ?Money $adjustmentAmount = null,
        ?Percentage $adjustmentPercentage = null,
        bool $stopProcessing = false
    ) {
        $trimmedId = trim($id);
        if ($trimmedId === '') {
            throw new InvalidPricingRuleException('Pricing rule ID cannot be empty.');
        }

        if ($priority <= 0) {
            throw new InvalidPricingRuleException(
                sprintf('Pricing rule priority must be strictly greater than 0, %d given.', $priority)
            );
        }

        self::assertMatrixCompatibility($target, $calculationBasis);
        self::assertAdjustmentTypeCompatibility($calculationBasis, $adjustmentAmount, $adjustmentPercentage);

        $this->id = $trimmedId;
        $this->priority = $priority;
        $this->condition = $condition;
        $this->target = $target;
        $this->calculationBasis = $calculationBasis;
        $this->adjustmentAmount = $adjustmentAmount;
        $this->adjustmentPercentage = $adjustmentPercentage;
        $this->stopProcessing = $stopProcessing;
    }

    /**
     * @throws InvalidPricingRuleException
     */
    private static function assertMatrixCompatibility(PricingTarget $target, PricingCalculationBasis $basis): void
    {
        $isAllowed = match ($target) {
            PricingTarget::BasePrice => in_array($basis, [
                PricingCalculationBasis::FixedAmount,
                PricingCalculationBasis::PerUnitQuantity,
                PricingCalculationBasis::PerDistance,
                PricingCalculationBasis::PerDuration,
                PricingCalculationBasis::PercentageOfBase,
            ], true),
            PricingTarget::Subtotal => in_array($basis, [
                PricingCalculationBasis::FixedAmount,
                PricingCalculationBasis::PercentageOfGrossSubtotal,
            ], true),
            PricingTarget::Fee => in_array($basis, [
                PricingCalculationBasis::FixedAmount,
                PricingCalculationBasis::PercentageOfGrossSubtotal,
            ], true),
            PricingTarget::Discount => in_array($basis, [
                PricingCalculationBasis::FixedAmount,
                PricingCalculationBasis::PercentageOfGrossSubtotal,
                PricingCalculationBasis::PercentageOfBase,
            ], true),
        };

        if (!$isAllowed) {
            throw new InvalidPricingRuleException(
                sprintf('Incompatible pricing matrix combination: Target "%s" with Basis "%s".', $target->value, $basis->value)
            );
        }
    }

    /**
     * @throws InvalidPricingRuleException
     */
    private static function assertAdjustmentTypeCompatibility(
        PricingCalculationBasis $basis,
        ?Money $amount,
        ?Percentage $percentage
    ): void {
        $isPercentageBasis = in_array($basis, [
            PricingCalculationBasis::PercentageOfBase,
            PricingCalculationBasis::PercentageOfGrossSubtotal,
        ], true);

        if ($isPercentageBasis) {
            if ($percentage === null || $percentage->isZero() || $amount !== null) {
                throw new InvalidPricingRuleException(
                    'Percentage calculation basis requires a non-zero Percentage and no Money adjustmentAmount.'
                );
            }
            return;
        }

        if ($amount === null || $percentage !== null) {
            throw new InvalidPricingRuleException(
                'Fixed/Unit/Distance/Duration calculation basis requires a Money adjustmentAmount and no Percentage.'
            );
        }
    }
}