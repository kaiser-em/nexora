<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container;

interface ContainerInterface
{
    /**
     * @template T of object
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : object)
     */
    public function get(string $id): object;

    public function has(string $id): bool;
}