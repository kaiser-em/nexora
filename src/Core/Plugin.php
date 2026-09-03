<?php

declare(strict_types=1);

namespace Silao\Core;

use Silao\Admin\AdminAssets;
use Silao\Admin\AdminMenu;
use Silao\Infrastructure\Container\Container;
use Silao\Infrastructure\Container\ContainerInterface;
use Silao\Infrastructure\Container\Provider\AdminServiceProvider;
use Silao\Infrastructure\Container\Provider\ApplicationServiceProvider;
use Silao\Infrastructure\Container\Provider\DatabaseServiceProvider;
use Silao\Infrastructure\Container\Provider\EventServiceProvider;
use Silao\Infrastructure\Container\Provider\RepositoryServiceProvider;
use Silao\Infrastructure\Container\Provider\RestServiceProvider;
use Silao\Infrastructure\Container\Provider\TransactionServiceProvider;
use Silao\REST\RestServer;

final class Plugin
{
    private static ?self $instance = null;
    private static ?Container $container = null;
    private bool $booted = false;

    public static function instance(): self
    {
        if (self::$instance === null) {
            self::$instance = new self();
        }

        return self::$instance;
    }

    public static function container(): ContainerInterface
    {
        if (self::$container === null) {
            self::$container = new Container();
            self::$container->register(new DatabaseServiceProvider());
            self::$container->register(new RepositoryServiceProvider());
            self::$container->register(new TransactionServiceProvider());
            self::$container->register(new EventServiceProvider());
            self::$container->register(new ApplicationServiceProvider());
            self::$container->register(new RestServiceProvider());
            self::$container->register(new AdminServiceProvider());
        }

        return self::$container;
    }

    private function __construct()
    {
    }

    public function boot(): void
    {
        if ($this->booted) {
            return;
        }

        $this->booted = true;
        self::container();

        $this->registerHooks();
    }

    private function registerHooks(): void
    {
        add_action('init', [$this, 'onInit']);
        add_action('rest_api_init', [$this, 'onRestApiInit']);

        if (is_admin()) {
            add_action('admin_menu', [$this, 'onAdminMenu']);
            add_action('admin_enqueue_scripts', [$this, 'onAdminEnqueueScripts']);
        }
    }

    public function onInit(): void
    {
        load_plugin_textdomain('silao', false, dirname(SILAO_PLUGIN_BASENAME) . '/languages');
    }

    public function onRestApiInit(): void
    {
        $server = self::container()->get(RestServer::class);
        $server->registerRoutes();
    }

    public function onAdminMenu(): void
    {
        $menu = self::container()->get(AdminMenu::class);
        $menu->registerMenus();
    }

    public function onAdminEnqueueScripts(string $hookSuffix): void
    {
        $assets = self::container()->get(AdminAssets::class);
        $assets->enqueue($hookSuffix);
    }
}