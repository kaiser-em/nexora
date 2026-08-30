<?php

declare(strict_types=1);

namespace Nexora\Domain\Condition\Enum;

enum LogicalOperator: string
{
    case And = 'and';
    case Or = 'or';
}