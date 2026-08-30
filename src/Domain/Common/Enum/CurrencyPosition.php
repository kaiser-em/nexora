<?php

declare(strict_types=1);

namespace Nexora\Domain\Common\Enum;

enum CurrencyPosition: string
{
    case Before = 'before';
    case After = 'after';
}