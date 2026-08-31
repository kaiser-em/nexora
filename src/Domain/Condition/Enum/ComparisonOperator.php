<?php

declare(strict_types=1);

namespace Silao\Domain\Condition\Enum;

enum ComparisonOperator: string
{
    case Equals = 'equals';
    case NotEquals = 'not_equals';
    case GreaterThan = 'greater_than';
    case GreaterThanOrEqual = 'greater_than_or_equal';
    case LessThan = 'less_than';
    case LessThanOrEqual = 'less_than_or_equal';
    case In = 'in';
    case NotIn = 'not_in';
    case Contains = 'contains';
}