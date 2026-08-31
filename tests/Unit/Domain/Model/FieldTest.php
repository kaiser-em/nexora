<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Model;

use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\ValueObject\ArrayConditionContext;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use Silao\Domain\Model\Enum\FieldType;
use Silao\Domain\Model\Exception\InvalidFieldException;
use Silao\Domain\Model\ValueObject\Field;
use PHPUnit\Framework\TestCase;

final class FieldTest extends TestCase
{
    public function testValidFieldCreation(): void
    {
        $field = new Field(
            'passengers_count',
            FieldType::Number,
            'Number of Passengers',
            true,
            1,
            ['min' => 1, 'max' => 8]
        );

        $this->assertSame('passengers_count', $field->name);
        $this->assertSame(FieldType::Number, $field->type);
        $this->assertSame('Number of Passengers', $field->label);
        $this->assertTrue($field->isRequired);
        $this->assertSame(1, $field->defaultValue);
        $this->assertSame(['min' => 1, 'max' => 8], $field->validationRules);
    }

    public function testInvalidNameThrowsException(): void
    {
        $this->expectException(InvalidFieldException::class);
        new Field('bad-name!', FieldType::Text, 'Label');
    }

    public function testEmptyLabelThrowsException(): void
    {
        $this->expectException(InvalidFieldException::class);
        new Field('valid_name', FieldType::Text, '   ');
    }

    public function testVisibilityWithoutConditionAlwaysReturnsTrue(): void
    {
        $field = new Field('notes', FieldType::Textarea, 'Notes');
        $this->assertTrue($field->isVisible(new ArrayConditionContext()));
    }

    public function testVisibilityWithCondition(): void
    {
        $condition = new SingleCondition('needs_child_seat', ComparisonOperator::Equals, true);
        $field = new Field('child_seat_count', FieldType::Number, 'Child Seats', false, null, [], $condition);

        $this->assertTrue($field->isVisible(new ArrayConditionContext(['needs_child_seat' => true])));
        $this->assertFalse($field->isVisible(new ArrayConditionContext(['needs_child_seat' => false])));
        $this->assertFalse($field->isVisible(new ArrayConditionContext()));
    }
}