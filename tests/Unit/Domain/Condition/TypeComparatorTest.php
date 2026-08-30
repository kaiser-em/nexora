<?php

declare(strict_types=1);

namespace Nexora\Tests\Unit\Domain\Condition;

use Nexora\Domain\Condition\Enum\ComparisonOperator;
use Nexora\Domain\Condition\Exception\InvalidConditionException;
use Nexora\Domain\Condition\Helper\TypeComparator;
use PHPUnit\Framework\TestCase;

final class TypeComparatorTest extends TestCase
{
    public function testEqualsAndNotEqualsStrict(): void
    {
        $this->assertTrue(TypeComparator::compare(4, ComparisonOperator::Equals, 4));
        $this->assertFalse(TypeComparator::compare(4, ComparisonOperator::Equals, '4'));
        $this->assertTrue(TypeComparator::compare(false, ComparisonOperator::NotEquals, 0));
        $this->assertTrue(TypeComparator::compare('CDG', ComparisonOperator::Equals, 'CDG'));
    }

    public function testRelationalOperatorsOnIntegers(): void
    {
        $this->assertTrue(TypeComparator::compare(5, ComparisonOperator::GreaterThan, 4));
        $this->assertFalse(TypeComparator::compare(4, ComparisonOperator::GreaterThan, 4));
        $this->assertTrue(TypeComparator::compare(4, ComparisonOperator::GreaterThanOrEqual, 4));
        $this->assertTrue(TypeComparator::compare(3, ComparisonOperator::LessThan, 4));
        $this->assertTrue(TypeComparator::compare(4, ComparisonOperator::LessThanOrEqual, 4));
    }

    public function testRelationalOperatorsRejectNonIntegers(): void
    {
        $this->expectException(InvalidConditionException::class);
        TypeComparator::compare('10', ComparisonOperator::GreaterThan, 5);
    }

    public function testInAndNotInOperators(): void
    {
        $allowed = ['SEDAN', 'VAN', 'VIP'];

        $this->assertTrue(TypeComparator::compare('VAN', ComparisonOperator::In, $allowed));
        $this->assertFalse(TypeComparator::compare('BUS', ComparisonOperator::In, $allowed));
        $this->assertTrue(TypeComparator::compare('BUS', ComparisonOperator::NotIn, $allowed));
    }

    public function testInOperatorRejectsNonArrayExpected(): void
    {
        $this->expectException(InvalidConditionException::class);
        TypeComparator::compare('VAN', ComparisonOperator::In, 'SEDAN,VAN');
    }

    public function testContainsOnStringAndArray(): void
    {
        $this->assertTrue(TypeComparator::compare('VIP Transfer', ComparisonOperator::Contains, 'VIP'));
        $this->assertFalse(TypeComparator::compare('Standard Transfer', ComparisonOperator::Contains, 'VIP'));
        $this->assertTrue(TypeComparator::compare(['baby_seat', 'wifi'], ComparisonOperator::Contains, 'wifi'));
        $this->assertFalse(TypeComparator::compare(['baby_seat'], ComparisonOperator::Contains, 'gps'));
    }

    public function testContainsOnStringRejectsNonStringExpected(): void
    {
        $this->expectException(InvalidConditionException::class);
        TypeComparator::compare('Order 123', ComparisonOperator::Contains, 123);
    }
}