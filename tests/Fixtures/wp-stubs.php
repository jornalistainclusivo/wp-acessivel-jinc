<?php declare(strict_types=1);

/**
 * WordPress function stubs for unit testing outside a WordPress environment.
 * These provide minimal implementations so unit tests can exercise classes
 * that guard-check for WordPress functions with function_exists().
 *
 * This file is loaded by bootstrap.php BEFORE any test runs.
 */

// ── Transients ──

$_jinc_transients = [];

if (!function_exists('get_transient')) {
    function get_transient(string $key): mixed
    {
        global $_jinc_transients;
        return $_jinc_transients[$key] ?? false;
    }
}

if (!function_exists('set_transient')) {
    function set_transient(string $key, mixed $value, int $expiration = 0): bool
    {
        global $_jinc_transients;
        $_jinc_transients[$key] = $value;
        return true;
    }
}

if (!function_exists('delete_transient')) {
    function delete_transient(string $key): bool
    {
        global $_jinc_transients;
        unset($_jinc_transients[$key]);
        return true;
    }
}

// ── User ──

$_jinc_current_user_id = 1;

if (!function_exists('get_current_user_id')) {
    function get_current_user_id(): int
    {
        global $_jinc_current_user_id;
        return $_jinc_current_user_id;
    }
}

// ── Post Meta ──

$_jinc_post_meta = [];

if (!function_exists('get_post_meta')) {
    function get_post_meta(int $postId, string $key = '', bool $single = false): mixed
    {
        global $_jinc_post_meta;
        if ($key === '') {
            return $_jinc_post_meta[$postId] ?? [];
        }
        $value = $_jinc_post_meta[$postId][$key] ?? ($single ? '' : []);
        return $value;
    }
}

if (!function_exists('update_post_meta')) {
    function update_post_meta(int $postId, string $key, mixed $value): bool
    {
        global $_jinc_post_meta;
        $_jinc_post_meta[$postId][$key] = $value;
        return true;
    }
}

// ── Post Functions ──

$_jinc_posts = [];

if (!function_exists('get_post_mime_type')) {
    function get_post_mime_type(int $postId): string|false
    {
        global $_jinc_posts;
        return $_jinc_posts[$postId]['mime_type'] ?? false;
    }
}

if (!function_exists('get_the_title')) {
    function get_the_title(int $postId): string
    {
        global $_jinc_posts;
        return $_jinc_posts[$postId]['title'] ?? 'attachment-' . $postId;
    }
}

if (!function_exists('get_edit_post_link')) {
    function get_edit_post_link(int $postId, string $context = 'display'): string|null
    {
        return '/wp-admin/post.php?post=' . $postId . '&action=edit';
    }
}

// ── Admin ──

if (!function_exists('admin_url')) {
    function admin_url(string $path = ''): string
    {
        return '/wp-admin/' . ltrim($path, '/');
    }
}

// ── Asset Enqueue ──

$_jinc_enqueued_styles = [];
$_jinc_enqueued_scripts = [];

if (!function_exists('wp_enqueue_style')) {
    function wp_enqueue_style(string $handle, string $src = '', array $deps = [], string $ver = '', string $media = 'all'): void
    {
        global $_jinc_enqueued_styles;
        $_jinc_enqueued_styles[$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver];
    }
}

if (!function_exists('wp_enqueue_script')) {
    function wp_enqueue_script(string $handle, string $src = '', array $deps = [], string $ver = '', bool $inFooter = false): void
    {
        global $_jinc_enqueued_scripts;
        $_jinc_enqueued_scripts[$handle] = ['src' => $src, 'deps' => $deps, 'ver' => $ver, 'in_footer' => $inFooter];
    }
}

// ── Admin Context ──

$_jinc_is_admin = false;

if (!function_exists('is_admin')) {
    function is_admin(): bool
    {
        global $_jinc_is_admin;
        return $_jinc_is_admin;
    }
}

// ── Plugin URL ──

if (!function_exists('plugin_dir_url')) {
    function plugin_dir_url(string $file): string
    {
        return '/wp-content/plugins/wp-acessivel-jinc/';
    }
}

// ── Hooks (no-op for unit tests) ──

$_jinc_filters = [];
$_jinc_actions = [];

if (!function_exists('add_filter')) {
    function add_filter(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
    {
        global $_jinc_filters;
        $_jinc_filters[$hookName][] = ['callback' => $callback, 'priority' => $priority];
        return true;
    }
}

if (!function_exists('add_action')) {
    function add_action(string $hookName, callable $callback, int $priority = 10, int $acceptedArgs = 1): bool
    {
        global $_jinc_actions;
        $_jinc_actions[$hookName][] = ['callback' => $callback, 'priority' => $priority];
        return true;
    }
}

// ── Sanitization ──

if (!function_exists('wp_kses_post')) {
    function wp_kses_post(string $data): string
    {
        return $data;
    }
}

if (!function_exists('esc_attr')) {
    function esc_attr(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
    }
}

if (!function_exists('wp_json_encode')) {
    function wp_json_encode(mixed $data, int $options = 0, int $depth = 512): string|false
    {
        return json_encode($data, $options, $depth);
    }
}

// ── WP_Error ──

if (!class_exists('WP_Error')) {
    class WP_Error
    {
        private string $code;
        private string $message;
        private mixed $data;

        public function __construct(string $code = '', string $message = '', mixed $data = '')
        {
            $this->code = $code;
            $this->message = $message;
            $this->data = $data;
        }

        public function get_error_code(): string
        {
            return $this->code;
        }

        public function get_error_message(string $code = ''): string
        {
            return $this->message;
        }

        public function get_error_data(string $code = ''): mixed
        {
            return $this->data;
        }
    }
}

// ── WP_REST_Request ──

if (!class_exists('WP_REST_Request')) {
    class WP_REST_Request
    {
        /** @var array<string, mixed> */
        private array $params = [];

        /**
         * @param array<string, mixed> $params
         */
        public function __construct(string $method = 'GET', string $route = '', array $params = [])
        {
            $this->params = $params;
        }

        public function has_param(string $key): bool
        {
            return array_key_exists($key, $this->params);
        }

        public function get_param(string $key): mixed
        {
            return $this->params[$key] ?? null;
        }

        /**
         * @param array<string, mixed> $params
         */
        public function set_params(array $params): void
        {
            $this->params = $params;
        }
    }
}

// ── Options API ──

$_jinc_options = [];

if (!function_exists('get_option')) {
    function get_option(string $option, mixed $default = false): mixed
    {
        global $_jinc_options;
        return $_jinc_options[$option] ?? $default;
    }
}

if (!function_exists('update_option')) {
    function update_option(string $option, mixed $value, string|bool $autoload = 'yes'): bool
    {
        global $_jinc_options;
        $_jinc_options[$option] = $value;
        return true;
    }
}

// ── Settings API ──

if (!function_exists('add_options_page')) {
    function add_options_page(string $page_title, string $menu_title, string $capability, string $menu_slug, ?callable $callback = null, ?int $position = null): string|false
    {
        return 'settings_page_' . $menu_slug;
    }
}

if (!function_exists('register_setting')) {
    function register_setting(string $option_group, string $option_name, array $args = []): void
    {
        // No-op for tests
    }
}

if (!function_exists('add_settings_section')) {
    function add_settings_section(string $id, string $title, ?callable $callback, string $page): void
    {
        // No-op for tests
    }
}

if (!function_exists('add_settings_field')) {
    function add_settings_field(string $id, string $title, callable $callback, string $page, string $section = 'default', array $args = []): void
    {
        // No-op for tests
    }
}

// ── Color Sanitization ──

if (!function_exists('sanitize_hex_color')) {
    function sanitize_hex_color(string $color): string|null
    {
        if (preg_match('|^#([A-Fa-f0-9]{3}){1,2}$|', $color)) {
            return $color;
        }
        return null;
    }
}

// ── Inline Style ──

$_jinc_inline_styles = [];

if (!function_exists('wp_add_inline_style')) {
    function wp_add_inline_style(string $handle, string $data): bool
    {
        global $_jinc_inline_styles;
        $_jinc_inline_styles[$handle] = ($_jinc_inline_styles[$handle] ?? '') . $data;
        return true;
    }
}

/**
 * Reset all WordPress stubs state. Call this in setUp().
 */
function jinc_reset_wp_stubs(): void
{
    global $_jinc_transients, $_jinc_post_meta, $_jinc_posts,
           $_jinc_current_user_id, $_jinc_filters, $_jinc_actions,
           $_jinc_enqueued_styles, $_jinc_enqueued_scripts, $_jinc_is_admin,
           $_jinc_options, $_jinc_inline_styles;

    $_jinc_transients = [];
    $_jinc_post_meta = [];
    $_jinc_posts = [];
    $_jinc_current_user_id = 1;
    $_jinc_filters = [];
    $_jinc_actions = [];
    $_jinc_enqueued_styles = [];
    $_jinc_enqueued_scripts = [];
    $_jinc_is_admin = false;
    $_jinc_options = [];
    $_jinc_inline_styles = [];
}
