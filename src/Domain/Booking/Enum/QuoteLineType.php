<?php

declare(strict_types=1);

namespace Silao\Domain\Booking\Enum;

enum QuoteLineType: string
{
    case BasePrice = 'base_price';
    case Option = 'option';
    case Fee = 'fee';
    case Discount = 'discount';
    case Adjustment = 'adjustment';
}