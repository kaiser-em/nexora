<?php

declare(strict_types=1);

namespace Silao\Domain\Model\ValueObject;

use DateTimeImmutable;
use Silao\Domain\Common\ValueObject\Currency;
use Silao\Domain\Common\ValueObject\ZonedDateTimeRange;
use Silao\Domain\Condition\Contract\ConditionContextInterface;
use Silao\Domain\Model\Exception\InvalidPricingContextException;
use Silao\Domain\Resource\ValueObject\ResourceId;

final readonly class PricingContext implements ConditionContextInterface
{
    /** @var array<string, SelectedOptionInput> */
    public array $selectedOptions;
    /** @var array<string, mixed> */
    public array $formData;
    /** @var array<string, mixed>|null */
    public ?array $customerContext;

    /**
     * @param array<mixed> $selectedOptions
     * @param array<string, mixed> $formData
     * @param array<string, mixed>|null $customerContext
     * @throws InvalidPricingContextException
     */
    public function __construct(
        public BookingModelId $modelId,
        public ?ResourceId $resourceId,
        public ZonedDateTimeRange $dateTimeRange,
        array $selectedOptions,
        array $formData,
        ?array $customerContext,
        public Currency $currency
    ) {
        $mappedOptions = [];
        foreach ($selectedOptions as $option) {
            if (!$option instanceof SelectedOptionInput) {
                throw new InvalidPricingContextException('Each element of selectedOptions must be an instance of SelectedOptionInput.');
            }
            $mappedOptions[$option->optionId] = $option;
        }

        $this->selectedOptions = $mappedOptions;
        $this->formData = $formData;
        $this->customerContext = $customerContext;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        // 1. Direct form data resolution
        if (array_key_exists($key, $this->formData)) {
            return $this->formData[$key];
        }

        // 2. Temporal resolution
        $localStart = $this->getStartsAtLocal();
        switch ($key) {
            case 'time.hour':
                return (int) $localStart->format('G');
            case 'time.minute':
                return (int) $localStart->format('i');
            case 'date.day_of_week':
                return (int) $localStart->format('N');
            case 'date.is_weekend':
                return ((int) $localStart->format('N')) >= 6;
            case 'duration_minutes':
                return $this->getDurationInMinutes();
        }

        // 3. Options quantity resolution (pattern: options.{optionId}.quantity)
        if (preg_match('/^options\.(.+)\.quantity$/', $key, $matches) === 1) {
            return $this->getSelectedOptionQuantity($matches[1]);
        }

        // 4. Customer context resolution (pattern: customer.{subKey})
        if (
            $this->customerContext !== null
            && preg_match('/^customer\.(.+)$/', $key, $matches) === 1
            && array_key_exists($matches[1], $this->customerContext)
        ) {
            return $this->customerContext[$matches[1]];
        }

        return $default;
    }

    public function has(string $key): bool
    {
        if (array_key_exists($key, $this->formData)) {
            return true;
        }

        if (in_array($key, ['time.hour', 'time.minute', 'date.day_of_week', 'date.is_weekend', 'duration_minutes'], true)) {
            return true;
        }

        if (str_starts_with($key, 'options.') && str_ends_with($key, '.quantity')) {
            return true;
        }

        if ($this->customerContext !== null && str_starts_with($key, 'customer.')) {
            $subKey = substr($key, 9);
            return array_key_exists($subKey, $this->customerContext);
        }

        return false;
    }

    public function getSelectedOptionQuantity(string $optionId): int
    {
        return isset($this->selectedOptions[$optionId]) ? $this->selectedOptions[$optionId]->quantity : 0;
    }

    public function getDurationInMinutes(): int
    {
        return $this->dateTimeRange->durationInMinutes();
    }

    public function getStartsAtLocal(): DateTimeImmutable
    {
        return $this->dateTimeRange->startsAtLocal();
    }

    public function getEndsAtLocal(): DateTimeImmutable
    {
        return $this->dateTimeRange->endsAtLocal();
    }
}