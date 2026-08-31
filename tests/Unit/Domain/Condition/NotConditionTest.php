<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Condition;

use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\ValueObject\ArrayConditionContext;
use Silao\Domain\Condition\ValueObject\CompositeCondition;
use Silao\Domain\Condition\ValueObject\NotCondition;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use PHPUnit\Framework\TestCase;

final class NotConditionTest extends TestCase
{
    public function testNotSingleCondition(): void
    {
        $baseCond = new SingleCondition('is_vip', ComparisonOperator::Equals, true);
        $notCond = new NotCondition($baseCond);

        $this->assertFalse($notCond->evaluate(new ArrayConditionContext(['is_vip' => true])));
        $this->assertTrue($notCond->evaluate(new ArrayConditionContext(['is_vip' => false])));
    }

    public function testNotCompositeCondition(): void
    {
        $and = CompositeCondition::and([
            new SingleCondition('a', ComparisonOperator::Equals, 1),
            new SingleCondition('b', ComparisonOperator::Equals, 2),
        ]);

        $notAnd = new NotCondition($and);

        $this->assertFalse($notAnd->evaluate(new ArrayConditionContext(['a' => 1, 'b' => 2])));
        $this->assertTrue($notAnd->evaluate(new ArrayConditionContext(['a' => 1, 'b' => 3])));
    }
}