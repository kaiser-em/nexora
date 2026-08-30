<?php

declare(strict_types=1);

namespace Nexora\Domain\Common\Enum;

enum RoundingMode: string
{
    case HalfUp = 'half_up';
    case HalfEven = 'half_even';
}