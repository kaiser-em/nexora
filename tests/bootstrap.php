<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/vendor/autoload.php';

if (!defined('ABSPATH')) {
    define('ABSPATH', sys_get_temp_dir() . '/');
}

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        /** @var array<string, array<string>> */
        public array $errors = [];
        /** @var array<string, mixed> */
        public array $error_data = [];

        public function __construct(string $code = '', string $message = '', mixed $data = '')
        {
            if ($code !== '') {
                $this->errors[$code][] = $message;
                if ($data !== '') {
                    $this->error_data[$code] = $data;
                }
            }
        }

        public function get_error_code(): string
        {
            $codes = array_keys($this->errors);
            return $codes[0] ?? '';
        }

        public function get_error_message(string $code = ''): string
        {
            $c = $code !== '' ? $code : $this->get_error_code();
            return $this->errors[$c][0] ?? '';
        }

        public function get_error_data(string $code = ''): mixed
        {
            $c = $code !== '' ? $code : $this->get_error_code();
            return $this->error_data[$c] ?? null;
        }

        public function get_status(): int
        {
            $data = $this->get_error_data();
            return is_array($data) && isset($data['status']) ? (int) $data['status'] : 500;
        }
    }
}

if (!class_exists('WP_REST_Server')) {
    class WP_REST_Server
    {
        public const string READABLE = 'GET';
        public const string CREATABLE = 'POST';
        public const string EDITABLE = 'POST, PUT, PATCH';
        public const string DELETABLE = 'DELETE';
        public const string ALLMETHODS = 'GET, POST, PUT, PATCH, DELETE';
    }
}

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @var array<string, mixed> */
        private array $params = [];
        /** @var array<string, mixed> */
        private array $bodyParams = [];

        public function __construct(
            private string $method = 'GET',
            private string $route = ''
        ) {
        }

        public function get_method(): string
        {
            return $this->method;
        }

        public function get_route(): string
        {
            return $this->route;
        }

        /** @param array<string, mixed> $params */
        public function set_body_params(array $params): void
        {
            $this->bodyParams = $params;
        }

        public function set_param(string $key, mixed $value): void
        {
            $this->params[$key] = $value;
        }

        public function get_param(string $key): mixed
        {
            return $this->params[$key] ?? $this->bodyParams[$key] ?? null;
        }

        /** @return array<string, mixed> */
        public function get_params(): array
        {
            return array_merge($this->params, $this->bodyParams);
        }

        /** @return array<string, mixed> */
        public function get_json_params(): array
        {
            return $this->bodyParams;
        }
    }
}

if (!class_exists('WP_REST_Response')) {
    class WP_REST_Response
    {
        /**
         * @param array<string, string> $headers
         */
        public function __construct(
            private mixed $data = null,
            private int $status = 200,
            private array $headers = []
        ) {
        }

        public function get_status(): int
        {
            return $this->status;
        }

        public function get_data(): mixed
        {
            return $this->data;
        }

        public function set_status(int $status): void
        {
            $this->status = $status;
        }
    }
}

if (!class_exists('WP_REST_Controller')) {
    abstract class WP_REST_Controller
    {
        protected string $namespace = '';
        protected string $rest_base = '';
    }
}

if (!function_exists('register_rest_route')) {
    /**
     * @param array<string, mixed> $args
     */
    function register_rest_route(string $namespace, string $route, array $args = [], bool $override = false): bool
    {
        global $silao_registered_rest_routes;
        if (!is_array($silao_registered_rest_routes)) {
            $silao_registered_rest_routes = [];
        }
        $silao_registered_rest_routes[$namespace][$route] = $args;
        return true;
    }
}

if (!function_exists('current_user_can')) {
    function current_user_can(string $capability, mixed ...$args): bool
    {
        global $silao_current_user_capabilities;
        if (is_array($silao_current_user_capabilities)) {
            return $silao_current_user_capabilities[$capability] ?? false;
        }
        return false;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html')) {
    function esc_html(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_html__')) {
    function esc_html__(string $text, string $domain = 'default'): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('esc_url_raw')) {
    function esc_url_raw(string $url): string
    {
        return filter_var($url, FILTER_SANITIZE_URL) ?: $url;
    }
}

if (!function_exists('add_shortcode')) {
    function add_shortcode(string $tag, callable $callback): void
    {
        global $silao_shortcodes;
        $silao_shortcodes[$tag] = $callback;
    }
}

if (!function_exists('has_shortcode')) {
    function has_shortcode(string $content, string $tag): bool
    {
        return str_contains($content, '[' . $tag);
    }
}

if (!function_exists('plugins_url')) {
    function plugins_url(string $path = '', string $plugin = ''): string
    {
        return 'http://localhost/wp-content/plugins/silao/' . ltrim($path, '/');
    }
}

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], mixed $ver = false, string $media = 'all'): void
    {
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], mixed $ver = false, mixed $args = []): void
    {
    }
}

if (!function_exists('wp_add_inline_script')) {
    function wp_add_inline_script(string $handle, string $data, string $position = 'after'): bool
    {
        return true;
    }
}

if (!function_exists('wp_set_script_translations')) {
    function wp_set_script_translations(string $handle, string $domain = 'default', ?string $path = null): bool
    {
        return true;
    }
}