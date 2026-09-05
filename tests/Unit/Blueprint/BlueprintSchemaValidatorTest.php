<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Blueprint;

use PHPUnit\Framework\TestCase;
use Silao\Blueprint\Exception\InvalidBlueprintException;
use Silao\Blueprint\Validator\BlueprintSchemaValidator;

final class BlueprintSchemaValidatorTest extends TestCase
{
    public function testRejectsAdditionalProperties(): void
    {
        $data = [
            'id' => 'test',
            'version' => '1',
            'model' => [],
            'fields' => [],
            'options' => [],
            'pricing_rules' => [],
            'sample_resources' => [],
            'execute_php' => 'eval()',
        ];

        $this->expectException(InvalidBlueprintException::class);
        BlueprintSchemaValidator::validate($data);
    }

    public function testAcceptsSystemWhitelistedFieldsInAST(): void
    {
        $this->expectNotToPerformAssertions();

        $data = [
            'schema_version' => '1',
            'id' => 'test',
            'version' => '1',
            'name' => 'Name',
            'description' => 'Desc',
            'category' => 'test',
            'model' => [],
            'fields' => [],
            'options' => [],
            'pricing_rules' => [
                [
                    'condition' => [
                        'type' => 'single',
                        'field' => 'time.hour',
                        'operator' => 'greater_than',
                        'expected_value' => 20
                    ]
                ]
            ],
            'sample_resources' => [],
        ];

        BlueprintSchemaValidator::validate($data);
    }

    public function testRejectsUndeclaredDynamicFieldsInAST(): void
    {
        $data = [
            'schema_version' => '1',
            'id' => 'test',
            'version' => '1',
            'name' => 'Name',
            'description' => 'Desc',
            'category' => 'test',
            'model' => [],
            'fields' => [
                ['name' => 'passengers']
            ],
            'options' => [],
            'pricing_rules' => [
                [
                    'condition' => [
                        'type' => 'single',
                        'field' => 'undeclared_field',
                        'operator' => 'equals',
                        'expected_value' => 1
                    ]
                ]
            ],
            'sample_resources' => [],
        ];

        $this->expectException(InvalidBlueprintException::class);
        BlueprintSchemaValidator::validate($data);
    }
}