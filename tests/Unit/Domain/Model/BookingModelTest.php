<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Model;

use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\Money;
use Silao\Domain\Condition\Enum\ComparisonOperator;
use Silao\Domain\Condition\ValueObject\SingleCondition;
use Silao\Domain\Model\BookingModel;
use Silao\Domain\Model\Enum\BookingModelStatus;
use Silao\Domain\Model\Enum\FieldType;
use Silao\Domain\Model\Enum\OptionPricingType;
use Silao\Domain\Model\Enum\PricingCalculationBasis;
use Silao\Domain\Model\Enum\PricingTarget;
use Silao\Domain\Model\Enum\ResourceStrategyType;
use Silao\Domain\Model\Exception\InvalidBookingModelException;
use Silao\Domain\Model\Exception\InvalidFieldException;
use Silao\Domain\Model\Exception\InvalidOptionException;
use Silao\Domain\Model\Exception\InvalidPricingRuleException;
use Silao\Domain\Model\ValueObject\BookingModelId;
use Silao\Domain\Model\ValueObject\Field;
use Silao\Domain\Model\ValueObject\Option;
use Silao\Domain\Model\ValueObject\PricingRule;
use Silao\Domain\Resource\ValueObject\ResourceId;
use PHPUnit\Framework\TestCase;

final class BookingModelTest extends TestCase
{
    private Currency $eur;

    protected function setUp(): void
    {
        $this->eur = Currency::EUR();
    }

    public function testCreationWithValidAttributes(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('transfer_vip'),
            'transfer-vip',
            'Transfer VIP',
            'Luxury transfer service',
            Money::of(10000, $this->eur),
            BookingModelStatus::Draft,
            ResourceStrategyType::SingleSelect
        );

        $this->assertSame('transfer_vip', $model->id()->toString());
        $this->assertSame('transfer-vip', $model->slug());
        $this->assertSame('Transfer VIP', $model->name());
        $this->assertSame(10000, $model->basePrice()->amount);
        $this->assertSame(BookingModelStatus::Draft, $model->status());
        $this->assertSame(ResourceStrategyType::SingleSelect, $model->resourceStrategy());
    }

    public function testInvalidSlugThrowsException(): void
    {
        $this->expectException(InvalidBookingModelException::class);
        new BookingModel(
            BookingModelId::fromString('m1'),
            'Invalid Slug With Spaces!',
            'Name',
            '',
            Money::of(1000, $this->eur)
        );
    }

    public function testAddFieldAndDuplicateRejection(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'model-test',
            'Name',
            '',
            Money::of(1000, $this->eur)
        );

        $f1 = new Field('passengers', FieldType::Number, 'Passengers');
        $model->addField($f1);
        $this->assertCount(1, $model->fields());

        $this->expectException(InvalidFieldException::class);
        $model->addField(new Field('passengers', FieldType::Number, 'Passengers Duplicate'));
    }

    public function testAddOptionAndDuplicateRejection(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'model-test',
            'Name',
            '',
            Money::of(1000, $this->eur)
        );

        $opt1 = new Option('opt_1', 'seat', 'Seat', '', OptionPricingType::Flat, Money::of(500, $this->eur));
        $model->addOption($opt1);

        // Duplicate ID
        $this->expectException(InvalidOptionException::class);
        $model->addOption(new Option('opt_1', 'other_code', 'Seat 2', '', OptionPricingType::Flat, Money::of(500, $this->eur)));
    }

    public function testOptionCurrencyMismatchThrowsException(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'model-test',
            'Name',
            '',
            Money::of(1000, $this->eur)
        );

        $usdOption = new Option('opt_usd', 'code', 'Name', '', OptionPricingType::Flat, Money::of(500, Currency::USD()));

        $this->expectException(InvalidOptionException::class);
        $model->addOption($usdOption);
    }

    public function testAddPricingRuleAndSorting(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'model-test',
            'Name',
            '',
            Money::of(1000, $this->eur)
        );

        $cond = new SingleCondition('passengers', ComparisonOperator::GreaterThan, 4);

        $ruleHighPriority = new PricingRule('r_high', 30, $cond, PricingTarget::Fee, PricingCalculationBasis::FixedAmount, Money::of(1000, $this->eur));
        $ruleLowPriority = new PricingRule('r_low', 10, $cond, PricingTarget::Fee, PricingCalculationBasis::FixedAmount, Money::of(500, $this->eur));

        $model->addPricingRule($ruleHighPriority);
        $model->addPricingRule($ruleLowPriority);

        $rules = array_values($model->pricingRules());
        $this->assertSame('r_low', $rules[0]->id);
        $this->assertSame(10, $rules[0]->priority);
        $this->assertSame('r_high', $rules[1]->id);
        $this->assertSame(30, $rules[1]->priority);
    }

    public function testDuplicatePricingRulePriorityThrowsException(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'model-test',
            'Name',
            '',
            Money::of(1000, $this->eur)
        );

        $cond = new SingleCondition('passengers', ComparisonOperator::GreaterThan, 4);

        $r1 = new PricingRule('r1', 10, $cond, PricingTarget::Fee, PricingCalculationBasis::FixedAmount, Money::of(500, $this->eur));
        $r2 = new PricingRule('r2', 10, $cond, PricingTarget::Fee, PricingCalculationBasis::FixedAmount, Money::of(500, $this->eur));

        $model->addPricingRule($r1);

        $this->expectException(InvalidPricingRuleException::class);
        $model->addPricingRule($r2);
    }

    public function testPublishValidationWithResourceStrategy(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'model-transfer',
            'Name',
            '',
            Money::of(1000, $this->eur),
            BookingModelStatus::Draft,
            ResourceStrategyType::SingleSelect
        );

        // Cannot publish without eligible resources when strategy requires it
        $this->expectException(InvalidBookingModelException::class);
        $model->publish();
    }

    public function testPublishSuccessWhenResourcesAreAssigned(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'model-transfer',
            'Name',
            '',
            Money::of(1000, $this->eur),
            BookingModelStatus::Draft,
            ResourceStrategyType::SingleSelect
        );

        $model->addEligibleResource(ResourceId::fromString('res_van'));
        $model->publish();

        $this->assertTrue($model->status()->isPublished());
    }

    public function testLifecycleTransitions(): void
    {
        $model = new BookingModel(
            BookingModelId::fromString('m1'),
            'model-none',
            'Name',
            '',
            Money::of(1000, $this->eur),
            BookingModelStatus::Draft,
            ResourceStrategyType::None
        );

        $model->publish();
        $this->assertSame(BookingModelStatus::Published, $model->status());

        $model->archive();
        $this->assertSame(BookingModelStatus::Archived, $model->status());

        $model->setAsDraft();
        $this->assertSame(BookingModelStatus::Draft, $model->status());
    }
}