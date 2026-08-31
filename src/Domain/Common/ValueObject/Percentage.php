<?php

declare(strict_types=1);

namespace Silao\Domain\Common\ValueObject;

use Silao\Domain\Common\Exception\InvalidPercentageException;

final readonly class Percentage
{
    public int $basisPoints;

    /**
     * @throws InvalidPercentageException
     */
    private function __construct(int $basisPoints)
    {
        if ($basisPoints < 0) {
            throw new InvalidPercentageException(
                sprintf('Percentage basis points must be greater than or equal to 0, %d given.', $basisPoints)
            );
        }

        $this->basisPoints = $basisPoints;
    }

    /**
     * @throws InvalidPercentageException
     */
    public static function fromBasisPoints(int $basisPoints): self
    {
        return new self($basisPoints);
    }

    /**
     * @throws InvalidPercentageException
     */
    public static function fromPercent(int $percent): self
    {
        if ($percent < 0) {
            throw new InvalidPercentageException(
                sprintf('Percentage must be greater than or equal to 0, %d given.', $percent)
            );
        }

        if ($percent > intdiv(PHP_INT_MAX, 100)) {
            throw new InvalidPercentageException(
                sprintf('Percentage %d causes 64-bit integer overflow when converted to basis points.', $percent)
            );
        }

        return new self($percent * 100);
    }

    public static function zero(): self
    {
        return new self(0);
    }

    public static function oneHundred(): self
    {
        return new self(10000);
    }

    public function toBasisPoints(): int
    {
        return $this->basisPoints;
    }

    public function isZero(): bool
    {
        return $this->basisPoints === 0;
    }

    public function isGreaterThan(self $other): bool
    {
        return $this->basisPoints > $other->basisPoints;
    }

    public function equals(self $other): bool
    {
        return $this->basisPoints === $other->basisPoints;
    }
}