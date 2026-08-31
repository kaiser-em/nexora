<?php

declare(strict_types=1);

namespace Silao\Tests\Unit\Domain\Model;

use Silao\Domain\Model\Exception\InvalidOptionException;
use Silao\Domain\Model\ValueObject\SelectedOptionInput;
use PHPUnit\Framework\TestCase;

final class SelectedOptionInputTest extends TestCase
{
    public function testValidSelectedOptionInput(): void
    {
        $input = new SelectedOptionInput('opt_baby_seat', 2);
        $this->assertSame('opt_baby_seat', $input->optionId);
        $this->assertSame(2, $input->quantity);
        $this->assertTrue($input->equals(SelectedOptionInput::of('opt_baby_seat', 2)));
    }

    public function testEmptyOptionIdThrowsException(): void
    {
        $this->expectException(InvalidOptionException::class);
        new SelectedOptionInput('   ', 1);
    }

    public function testZeroOrNegativeQuantityThrowsException(): void
    {
        $this->expectException(InvalidOptionException::class);
        new SelectedOptionInput('opt_1', 0);
    }
}