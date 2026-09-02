<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container;

interface ServiceProviderInterface
{
    public function register(Container $container): void;
}