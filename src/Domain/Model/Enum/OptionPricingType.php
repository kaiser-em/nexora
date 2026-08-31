<?php

declare(strict_types=1);

namespace Silao\Domain\Model\Enum;

enum OptionPricingType: string
{
    case Flat = 'flat';
    case PerUnit = 'per_unit';
    case PerPassenger = 'per_passenger';
    case PerDay = 'per_day';
    case Percentage = 'percentage';
}