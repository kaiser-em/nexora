<?php

declare(strict_types=1);

namespace Silao\Domain\Model\Enum;

enum PricingTarget: string
{
    case BasePrice = 'base_price';
    case Subtotal = 'subtotal';
    case Fee = 'fee';
    case Discount = 'discount';
}