<?php

declare(strict_types=1);

namespace Nexora\Domain\Booking\ValueObject;

use Nexora\Domain\Booking\Enum\QuoteLineType;
use Nexora\Domain\Booking\Exception\InvalidQuoteException;
use Nexora\Domain\Common\Exception\CurrencyMismatchException;
use Nexora\Domain\Common\Exception\MoneyOverflowException;
use Nexora\Domain\Common\ValueObject\Money;

final readonly class QuoteLine
{
    private string $id;
    private QuoteLineType $type;
    private string $description;
    private int $quantity;
    private Money $unitPrice;
    private Money $total;
    /** @var array<string, mixed> */
    private array $metadata;

    /**
     * @param array<string, mixed> $metadata
     * @throws InvalidQuoteException
     */
    public function __construct(
        string $id,
        QuoteLineType $type,
        string $description,
        int $quantity,
        Money $unitPrice,
        Money $total,
        array $metadata = []
    ) {
        $trimmedId = trim($id);
        if ($trimmedId === '') {
            throw new InvalidQuoteException('Quote line ID cannot be empty.');
        }

        $trimmedDescription = trim($description);
        if ($trimmedDescription === '') {
            throw new InvalidQuoteException('Quote line description cannot be empty.');
        }

        if ($quantity <= 0) {
            throw new InvalidQuoteException(
                sprintf('Quote line quantity must be strictly greater than 0, %d given.', $quantity)
            );
        }

        try {
            $expectedTotal = $unitPrice->multiply($quantity);
            if (!$total->equals($expectedTotal)) {
                throw new InvalidQuoteException(
                    sprintf('Quote line total (%d) does not match expected unitPrice * quantity (%d).', $total->amount, $expectedTotal->amount)
                );
            }
        } catch (CurrencyMismatchException | MoneyOverflowException $e) {
            throw new InvalidQuoteException('Error calculating quote line total: ' . $e->getMessage(), 0, $e);
        }

        $this->id = $trimmedId;
        $this->type = $type;
        $this->description = $trimmedDescription;
        $this->quantity = $quantity;
        $this->unitPrice = $unitPrice;
        $this->total = $total;
        $this->metadata = $metadata;
    }

    public function id(): string
    {
        return $this->id;
    }

    public function type(): QuoteLineType
    {
        return $this->type;
    }

    public function description(): string
    {
        return $this->description;
    }

    public function quantity(): int
    {
        return $this->quantity;
    }

    public function unitPrice(): Money
    {
        return $this->unitPrice;
    }

    public function total(): Money
    {
        return $this->total;
    }

    /**
     * @return array<string, mixed>
     */
    public function metadata(): array
    {
        return $this->metadata;
    }
}