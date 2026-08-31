<?php

declare(strict_types=1);

namespace Silao\Domain\Booking\ValueObject;

use Silao\Domain\Booking\Exception\InvalidFormDataSnapshotException;

final readonly class FormDataSnapshot
{
    /** @var array<string, mixed> */
    public array $data;

    /**
     * @param array<mixed, mixed> $data
     * @throws InvalidFormDataSnapshotException
     */
    public function __construct(array $data)
    {
        $normalized = [];
        foreach ($data as $key => $value) {
            if (!is_string($key) || trim($key) === '') {
                throw new InvalidFormDataSnapshotException('Form data snapshot keys must be non-empty strings.');
            }
            $normalized[$key] = $value;
        }

        $this->data = $normalized;
    }

    public function get(string $key, mixed $default = null): mixed
    {
        return array_key_exists($key, $this->data) ? $this->data[$key] : $default;
    }

    public function has(string $key): bool
    {
        return array_key_exists($key, $this->data);
    }

    /**
     * @return array<string, mixed>
     */
    public function all(): array
    {
        return $this->data;
    }
}