<?php

declare(strict_types=1);

namespace Silao\Domain\Common\Enum;

enum RoundingMode: string
{
    case HalfUp = 'half_up';
    case HalfEven = 'half_even';
}