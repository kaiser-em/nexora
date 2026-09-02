<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container;

use Silao\Infrastructure\Container\Exception\ContainerException;
use Silao\Infrastructure\Container\Exception\ServiceNotFoundException;
use Throwable;

final class Container implements ContainerInterface
{
    /** @var array<string, callable(self): object> */
    private array $factories = [];

    /** @var array<string, object> */
    private array $instances = [];

    /** @var array<string, bool> */
    private array $singletons = [];

    /**
     * @param callable(self): object $factory
     */
    public function singleton(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        $this->singletons[$id] = true;
        unset($this->instances[$id]);
    }

    /**
     * @param callable(self): object $factory
     */
    public function bind(string $id, callable $factory): void
    {
        $this->factories[$id] = $factory;
        $this->singletons[$id] = false;
        unset($this->instances[$id]);
    }

    public function instance(string $id, object $instance): void
    {
        $this->instances[$id] = $instance;
        $this->singletons[$id] = true;
    }

    /**
     * @template T of object
     * @param class-string<T>|string $id
     * @return ($id is class-string<T> ? T : object)
     * @throws ContainerException
     */
    public function get(string $id): object
    {
        if (isset($this->instances[$id])) {
            /** @var ($id is class-string<T> ? T : object) */
            return $this->instances[$id];
        }

        if (!isset($this->factories[$id])) {
            throw new ServiceNotFoundException(
                sprintf('Service "%s" is not registered in the container.', $id)
            );
        }

        try {
            $object = ($this->factories[$id])($this);
        } catch (Throwable $e) {
            throw new ContainerException(
                sprintf('Error resolving service "%s": %s', $id, $e->getMessage()),
                0,
                $e
            );
        }

        if ($this->singletons[$id] ?? false) {
            $this->instances[$id] = $object;
        }

        /** @var ($id is class-string<T> ? T : object) */
        return $object;
    }

    public function has(string $id): bool
    {
        return isset($this->instances[$id]) || isset($this->factories[$id]);
    }

    public function register(ServiceProviderInterface $provider): void
    {
        $provider->register($this);
    }
}