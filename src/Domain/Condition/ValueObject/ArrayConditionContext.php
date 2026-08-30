<?php

declare(strict_types=1);

namespace Nexora\Domain\Condition\ValueObject;

use Nexora\Domain\Condition\Contract\ConditionContextInterface;

final readonly class ArrayConditionContext implements ConditionContextInterface
{
    /**
     * @param array<string, mixed> $data
     */
    public function __construct(public array $data = [])
    {
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }
}