<?php

declare(strict_types=1);

namespace Nexora\Domain\Model\ValueObject;

use Nexora\Domain\Model\Exception\InvalidOptionException;

final readonly class SelectedOptionInput
{
    public string $optionId;
    public int $quantity;

    /**
     * @throws InvalidOptionException
     */
    public function __construct(string $optionId, int $quantity)
    {
        $trimmedId = trim($optionId);
        if ($trimmedId === '') {
            throw new InvalidOptionException('Selected option ID cannot be empty.');
        }

        if ($quantity <= 0) {
            throw new InvalidOptionException(
                sprintf('Selected option quantity must be strictly greater than 0, %d given.', $quantity)
            );
        }

        $this->optionId = $trimmedId;
        $this->quantity = $quantity;
    }

    /**
     * @throws InvalidOptionException
     */
    public static function of(string $optionId, int $quantity): self
    {
        return new self($optionId, $quantity);
    }

    public function equals(self $other): bool
    {
        return $this->optionId === $other->optionId && $this->quantity === $other->quantity;
    }
}