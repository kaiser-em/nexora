<?php

declare(strict_types=1);

namespace Silao\Domain\Model\Enum;

enum PricingCalculationBasis: string
{
    case FixedAmount = 'fixed_amount';
    case PerUnitQuantity = 'per_unit_quantity';
    case PerDistance = 'per_distance';
    case PerDuration = 'per_duration';
    case PercentageOfBase = 'percentage_of_base';
    case PercentageOfGrossSubtotal = 'percentage_of_gross_subtotal';
}