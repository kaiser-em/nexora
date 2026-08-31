<?php

declare(strict_types=1);

namespace Silao\Domain\Resource\ValueObject;

use Silao\Domain\Resource\Exception\InvalidResourceException;

final readonly class ResourceId
{
    public string $value;

    /**
     * @throws InvalidResourceException
     */
    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if ($trimmed === '') {
            throw new InvalidResourceException('Resource ID cannot be empty.');
        }
        $this->value = $trimmed;
    }

    /**
     * @throws InvalidResourceException
     */
    public static function fromString(string $value): self
    {
        return new self($value);
    }

    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function toString(): string
    {
        return $this->value;
    }
}