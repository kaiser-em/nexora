<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Condition;

use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\Exception\InvalidConditionException;
use Silao\Domain\Condition\ValueObject\ArrayConditionContext;
use Silao\Domain\Condition\ValueObject\CompositeCondition;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use PHPUnit\Framework\TestCase;

final class CompositeConditionTest extends TestCase
{
    public function testEmptyConditionsArrayThrowsException(): void
    {
        $this->expectException(InvalidConditionException::class);
        CompositeCondition::and([]);
    }

    public function testInvalidElementInArrayThrowsException(): void
    {
        $this->expectException(InvalidConditionException::class);
        
        CompositeCondition::and([new SingleCondition('a', ComparisonOperator::Equals, 1), 'invalid_element']);
    }

    public function testAndCompositionWithShortCircuit(): void
    {
        $cond1 = new SingleCondition('a', ComparisonOperator::Equals, 1);
        $cond2 = new SingleCondition('b', ComparisonOperator::Equals, 2);

        $and = CompositeCondition::and([$cond1, $cond2]);

        $this->assertTrue($and->evaluate(new ArrayConditionContext(['a' => 1, 'b' => 2])));
        $this->assertFalse($and->evaluate(new ArrayConditionContext(['a' => 1, 'b' => 99])));
        $this->assertFalse($and->evaluate(new ArrayConditionContext(['a' => 99, 'b' => 2])));
    }

    public function testOrCompositionWithShortCircuit(): void
    {
        $cond1 = new SingleCondition('is_night', ComparisonOperator::Equals, true);
        $cond2 = new SingleCondition('is_weekend', ComparisonOperator::Equals, true);

        $or = CompositeCondition::or([$cond1, $cond2]);

        $this->assertTrue($or->evaluate(new ArrayConditionContext(['is_night' => true, 'is_weekend' => false])));
        $this->assertTrue($or->evaluate(new ArrayConditionContext(['is_night' => false, 'is_weekend' => true])));
        $this->assertFalse($or->evaluate(new ArrayConditionContext(['is_night' => false, 'is_weekend' => false])));
    }

    public function testNestedCompositionPricingScenario(): void
    {
        // Formula: passengers > 4 AND (is_night == true OR is_weekend == true)
        $passengersCond = new SingleCondition('passengers', ComparisonOperator::GreaterThan, 4);
        $nightCond = new SingleCondition('is_night', ComparisonOperator::Equals, true);
        $weekendCond = new SingleCondition('is_weekend', ComparisonOperator::Equals, true);

        $nestedRule = CompositeCondition::and([
            $passengersCond,
            CompositeCondition::or([$nightCond, $weekendCond]),
        ]);

        // Case A: 5 passengers, night=true, weekend=false -> TRUE
        $this->assertTrue($nestedRule->evaluate(new ArrayConditionContext([
            'passengers' => 5,
            'is_night' => true,
            'is_weekend' => false,
        ])));

        // Case B: 5 passengers, night=false, weekend=true -> TRUE
        $this->assertTrue($nestedRule->evaluate(new ArrayConditionContext([
            'passengers' => 5,
            'is_night' => false,
            'is_weekend' => true,
        ])));

        // Case C: 5 passengers, night=false, weekend=false -> FALSE
        $this->assertFalse($nestedRule->evaluate(new ArrayConditionContext([
            'passengers' => 5,
            'is_night' => false,
            'is_weekend' => false,
        ])));

        // Case D: 3 passengers, night=true, weekend=true -> FALSE
        $this->assertFalse($nestedRule->evaluate(new ArrayConditionContext([
            'passengers' => 3,
            'is_night' => true,
            'is_weekend' => true,
        ])));
    }
}