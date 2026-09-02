<?php

declare(strict_types=1);

namespace Silao\Domain\Engine;

use Silao\Domain\Booking\Enum\QuoteLineType;
use Silao\Domain\Booking\ValueObject\Quote;
use Silao\Domain\Booking\ValueObject\QuoteLine;
use Silao\Domain\Common\Enum\RoundingMode;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Common\ValueObject\Percentage;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\OptionPricingType;
use Silao\Domain\Model\Enum\PricingCalculationBasis;
use Silao\Domain\Model\Enum\PricingTarget;
use Silao\Domain\Model\Exception\InvalidPricingContextException;
use Silao\Domain\Model\ValueObject\PricingContext;
use Silao\Domain\Model\ValueObject\PricingRule;

final class PricingEngine
{
    /**
     * @throws InvalidPricingContextException
     */
    public static function calculate(
        BookingModel $model,
        PricingContext $context,
        ?Percentage $taxRate = null
    ): Quote {
        $currency = $model->basePrice()->currency;
        $lines = [];

        // 1. PRIX DE BASE
        $basePrice = $model->basePrice();
        $lines[] = new QuoteLine(
            'line_base_price',
            QuoteLineType::BasePrice,
            sprintf('Tarif de base (%s)', $model->name()),
            1,
            $basePrice,
            $basePrice,
            ['model_id' => $model->id()->toString()]
        );

        // 2. OPTIONS / EXTRAS
        $optionsTotal = Money::zero($currency);
        $optionsMap = $model->options();

        foreach ($context->selectedOptions as $selected) {
            if (!isset($optionsMap[$selected->optionId])) {
                continue;
            }

            $option = $optionsMap[$selected->optionId];
            if (!$option->isAvailable($context)) {
                continue;
            }

            $qty = $selected->quantity;
            $unitPrice = $option->unitPrice;

            $lineTotal = match ($option->pricingType) {
                OptionPricingType::Flat => $unitPrice,
                OptionPricingType::PerUnit => $unitPrice->multiply($qty),
                OptionPricingType::PerPassenger => $unitPrice->multiply(max(1, (int) ($context->get('passengers', 1)))),
                OptionPricingType::PerDay => $unitPrice->multiply(max(1, intdiv($context->getDurationInMinutes(), 1440))),
                OptionPricingType::Percentage => $basePrice->applyPercentage(
                    Percentage::fromBasisPoints($unitPrice->amount),
                    RoundingMode::HalfUp
                ),
            };

            $optionsTotal = $optionsTotal->add($lineTotal);
            $lines[] = new QuoteLine(
                'line_opt_' . $option->id,
                QuoteLineType::Option,
                $option->name,
                $qty,
                $unitPrice,
                $lineTotal,
                ['option_id' => $option->id, 'option_code' => $option->code]
            );
        }

        // 3. SOUS-TOTAL BRUT
        $grossSubtotal = $basePrice->add($optionsTotal);

        // 4. SURTAXES / MAJORATIONS (Target: BasePrice | Subtotal)
        $surchargesTotal = Money::zero($currency);
        $feesTotal = Money::zero($currency);
        $discountsTotal = Money::zero($currency);

        $rules = $model->pricingRules();
        foreach ($rules as $rule) {
            if (!$rule->condition->evaluate($context)) {
                continue;
            }

            $lineAmount = self::evaluateRuleAdjustment($rule, $context, $basePrice, $grossSubtotal);

            match ($rule->target) {
                PricingTarget::BasePrice, PricingTarget::Subtotal => [
                    $surchargesTotal = $surchargesTotal->add($lineAmount),
                    $lines[] = new QuoteLine(
                        'line_rule_' . $rule->id,
                        QuoteLineType::Adjustment,
                        'Majoration contextuelle',
                        1,
                        $lineAmount,
                        $lineAmount,
                        ['rule_id' => $rule->id]
                    ),
                ],
                PricingTarget::Fee => [
                    $feesTotal = $feesTotal->add($lineAmount),
                    $lines[] = new QuoteLine(
                        'line_rule_' . $rule->id,
                        QuoteLineType::Fee,
                        'Frais annexes',
                        1,
                        $lineAmount,
                        $lineAmount,
                        ['rule_id' => $rule->id]
                    ),
                ],
                PricingTarget::Discount => [
                    $discountsTotal = $discountsTotal->add($lineAmount),
                    $lines[] = new QuoteLine(
                        'line_rule_' . $rule->id,
                        QuoteLineType::Discount,
                        'Remise promotionnelle',
                        1,
                        $lineAmount,
                        $lineAmount,
                        ['rule_id' => $rule->id]
                    ),
                ],
            };

            if ($rule->stopProcessing) {
                break;
            }
        }

        // 5. PLAFONNEMENT DES REMISES
        $maxDiscount = $grossSubtotal->add($surchargesTotal);
        if ($discountsTotal->isGreaterThan($maxDiscount)) {
            $discountsTotal = $maxDiscount;
        }

        // 6 & 7. BASE TAXABLE
        $taxableBase = $grossSubtotal->add($surchargesTotal)->subtract($discountsTotal)->add($feesTotal);

        // 8. TAXES / TVA (Taux en bips)
        $taxTotal = Money::zero($currency);
        if ($taxRate !== null && !$taxRate->isZero()) {
            $taxAmount = $taxableBase->applyPercentage($taxRate, RoundingMode::HalfUp);
            $taxTotal = $taxTotal->add($taxAmount);
            $lines[] = new QuoteLine(
                'line_tax',
                QuoteLineType::Adjustment,
                sprintf('TVA (%d%%)', intdiv($taxRate->toBasisPoints(), 100)),
                1,
                $taxAmount,
                $taxAmount,
                ['tax_rate_bips' => $taxRate->toBasisPoints()]
            );
        }

        // 9. TOTAL GÉNÉRAL
        $grandTotal = $taxableBase->add($taxTotal);
        $totalSubtotal = $grossSubtotal->add($surchargesTotal)->add($taxTotal);
        $totalFees = $feesTotal;

        return new Quote(
            $lines,
            $totalSubtotal,
            $totalFees,
            $discountsTotal,
            $grandTotal,
            $currency
        );
    }

    /**
     * @throws InvalidPricingContextException
     */
    private static function evaluateRuleAdjustment(
        PricingRule $rule,
        PricingContext $context,
        Money $basePrice,
        Money $grossSubtotal
    ): Money {
        $amount = $rule->adjustmentAmount;
        $percentage = $rule->adjustmentPercentage;

        return match ($rule->calculationBasis) {
            PricingCalculationBasis::FixedAmount => $amount ?? Money::zero($basePrice->currency),
            PricingCalculationBasis::PerDistance => self::calculateDistanceAdjustment($rule, $context),
            PricingCalculationBasis::PerUnitQuantity => ($amount ?? Money::zero($basePrice->currency))->multiply(
                max(1, (int) ($context->get('passengers', 1)))
            ),
            PricingCalculationBasis::PerDuration => ($amount ?? Money::zero($basePrice->currency))->multiply(
                max(1, $context->dateTimeRange->durationInFullHours())
            ),
            PricingCalculationBasis::PercentageOfBase => $percentage !== null
                ? $basePrice->applyPercentage($percentage, RoundingMode::HalfUp)
                : Money::zero($basePrice->currency),
            PricingCalculationBasis::PercentageOfGrossSubtotal => $percentage !== null
                ? $grossSubtotal->applyPercentage($percentage, RoundingMode::HalfUp)
                : Money::zero($basePrice->currency),
        };
    }

    /**
     * @throws InvalidPricingContextException
     */
    private static function calculateDistanceAdjustment(PricingRule $rule, PricingContext $context): Money
    {
        $amount = $rule->adjustmentAmount;
        if ($amount === null) {
            throw new InvalidPricingContextException('PerDistance pricing rule is missing adjustmentAmount.');
        }

        $rawDistance = $context->get('distance_km', $context->get('distance'));
        if ($rawDistance === null || !is_int($rawDistance) || $rawDistance < 0) {
            throw new InvalidPricingContextException(
                'PerDistance pricing rule requires a positive integer "distance_km" in PricingContext.'
            );
        }

        return $amount->multiply($rawDistance);
    }
}