<?php

declare(strict_types=1);

namespace Nexora\Domain\Model\Enum;

enum ResourceStrategyType: string
{
    case None = 'none';
    case SingleSelect = 'single_select';
    case AutoAssign = 'auto_assign';
    case SharedCapacityPool = 'shared_capacity_pool';

    public function requiresResource(): bool
    {
        return $this !== self::None;
    }
}