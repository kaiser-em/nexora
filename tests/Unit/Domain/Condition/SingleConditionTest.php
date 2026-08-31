<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Condition;

use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\Exception\InvalidConditionException;
use Silao\Domain\Condition\ValueObject\ArrayConditionContext;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use PHPUnit\Framework\TestCase;

final class SingleConditionTest extends TestCase
{
    public function testEmptyFieldThrowsException(): void
    {
        $this->expectException(InvalidConditionException::class);
        new SingleCondition('   ', ComparisonOperator::Equals, 'value');
    }

    public function testRelationalOperatorWithNonIntegerExpectedThrowsException(): void
    {
        $this->expectException(InvalidConditionException::class);
        new SingleCondition('passengers', ComparisonOperator::GreaterThan, '4');
    }

    public function testInOperatorWithNonArrayExpectedThrowsException(): void
    {
        $this->expectException(InvalidConditionException::class);
        new SingleCondition('vehicle_type', ComparisonOperator::In, 'SEDAN');
    }

    public function testEvaluateMatchingCondition(): void
    {
        $condition = new SingleCondition('passengers', ComparisonOperator::GreaterThan, 4);
        $context = new ArrayConditionContext(['passengers' => 5]);

        $this->assertTrue($condition->evaluate($context));
    }

    public function testEvaluateMissingFieldReturnsFalseOrTrueAppropriately(): void
    {
        $eqCondition = new SingleCondition('unknown_field', ComparisonOperator::Equals, 'val');
        $notEqCondition = new SingleCondition('unknown_field', ComparisonOperator::NotEquals, 'val');
        $notInCondition = new SingleCondition('unknown_field', ComparisonOperator::NotIn, ['a', 'b']);

        $context = new ArrayConditionContext(['other_field' => 123]);

        $this->assertFalse($eqCondition->evaluate($context));
        $this->assertTrue($notEqCondition->evaluate($context));
        $this->assertTrue($notInCondition->evaluate($context));
    }

    public function testContextDifferentiatesNullAndMissingKey(): void
    {
        $context = new ArrayConditionContext(['nullable_field' => null]);

        $this->assertTrue($context->has('nullable_field'));
        $this->assertNull($context->get('nullable_field'));

        $this->assertFalse($context->has('missing_field'));
    }
}