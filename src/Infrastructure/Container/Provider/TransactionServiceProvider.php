<?php

declare(strict_types=1);

namespace Silao\Infrastructure\Container\Provider;

use Silao\Application\Transaction\TransactionManagerInterface;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ServiceProviderInterface;
use Silao\Infrastructure\Transaction\WpTransactionManager;

final class TransactionServiceProvider implements ServiceProviderInterface
{
    public function register(Container $container): void
    {
        $container->singleton(TransactionManagerInterface::class, static function (Container $c): TransactionManagerInterface {
            return new WpTransactionManager($c->get("wpdb"));
        });
    }
}
