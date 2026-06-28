<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\FrontendBar;

/**
 * Injects the accessibility toolbar into the frontend.
 *
 * Uses wp_body_open (WordPress 5.2+) to inject the bar at the top of <body>.
 * Falls back to wp_footer if the theme does not call wp_body_open().
 *
 * Enqueues CSS/JS assets via wp_enqueue_scripts hook.
 *
 * @spec-ref Phase 3 — Frontend Accessibility Bar
 */
final class BarInjector
{
    private const ASSET_HANDLE_CSS = 'jinc-bar-css';
    private const ASSET_HANDLE_JS  = 'jinc-bar-js';
    private const PLUGIN_VERSION   = '1.0.0';

    /** Tracks whether the bar has already been rendered (prevents double injection). */
    private bool $rendered = false;

    public function __construct(
        private readonly string $pluginFile,
    ) {}

    /**
     * Register all WordPress hooks for the frontend bar.
     */
    public function register(): void
    {
        if (!function_exists('add_action')) {
            return;
        }

        // Asset enqueue
        add_action('wp_enqueue_scripts', [$this, 'enqueueAssets']);

        // HTML injection: prefer wp_body_open (top of <body>), fallback to wp_footer
        add_action('wp_body_open', [$this, 'renderBar'], 1);
        add_action('wp_footer', [$this, 'renderBarFallback'], 1);
    }

    /**
     * Enqueue CSS and JS assets for the accessibility bar.
     * Only loads on the public frontend (not wp-admin).
     */
    public function enqueueAssets(): void
    {
        if ($this->isAdminContext()) {
            return;
        }

        $baseUrl = $this->getPluginUrl();

        $this->wpEnqueueStyle(
            self::ASSET_HANDLE_CSS,
            $baseUrl . 'assets/css/jinc-bar.css',
            [],
            self::PLUGIN_VERSION,
        );

        $options = function_exists('get_option') ? get_option('jinc_theme_options', []) : [];
        if (!is_array($options)) {
            $options = [];
        }
        $show_icons = isset($options['show_icons']) ? $options['show_icons'] : '1';
        
        if ($show_icons === '1') {
            $this->wpEnqueueStyle('dashicons', '', [], self::PLUGIN_VERSION);
        }

        $this->wpEnqueueScript(
            self::ASSET_HANDLE_JS,
            $baseUrl . 'assets/js/jinc-bar.js',
            [],
            self::PLUGIN_VERSION,
            true, // load in footer
        );
    }

    /**
     * Render the accessibility bar HTML.
     * Called by wp_body_open — injects at the very top of <body>.
     */
    public function renderBar(): void
    {
        if ($this->rendered) {
            return;
        }

        $this->rendered = true;
        echo $this->getBarHtml();
    }

    /**
     * Fallback renderer via wp_footer.
     * Only fires if wp_body_open did NOT inject the bar.
     */
    public function renderBarFallback(): void
    {
        if ($this->rendered) {
            return;
        }

        $this->rendered = true;
        echo $this->getBarHtml();
    }

    /**
     * Generate the accessibility bar HTML markup.
     *
     * Structure:
     *   <div id="jinc-a11y-bar"> wrapping <nav> with aria-label
     *     - Skip-to-content link
     *     - Toggle High Contrast button
     *     - Toggle Font Size button
     *
     * All interactive elements have unique IDs for testing and ARIA compliance.
     *
     * @return string Accessibility bar HTML.
     */
    public function getBarHtml(): string
    {
        $options = function_exists('get_option') ? get_option('jinc_theme_options', []) : [];
        if (!is_array($options)) {
            $options = [];
        }
        
        $layout = $options['layout'] ?? 'top_bar';
        $position = $options['position'] ?? 'bottom_right';
        $frontend_title = $options['frontend_title'] ?? '';
        $a11y_id = $options['a11y_id'] ?? '';
        
        $container_id = empty($a11y_id) ? 'jinc-a11y-bar' : $a11y_id;
        $aria_label = empty($frontend_title) ? 'Barra de Acessibilidade' : $frontend_title;
        $show_icons = isset($options['show_icons']) ? $options['show_icons'] : '1';
        
        $icons_class = $show_icons === '0' ? ' jinc-no-icons' : '';

        $html  = sprintf(
            '<div id="%s" class="%s" role="region" aria-label="%s" data-layout="%s" data-position="%s">',
            esc_attr($container_id),
            esc_attr(trim('jinc-a11y-wrapper jinc-bar ' . $icons_class)),
            esc_attr($aria_label),
            esc_attr($layout),
            esc_attr($position)
        );
        $html .= '<nav aria-label="Controles de Acessibilidade">';

        if (!empty($frontend_title)) {
            $html .= '<span class="jinc-bar-title">' . esc_html($frontend_title) . '</span>';
        }

        // 1. Skip-to-content link
        $html .= '<a href="#main" id="jinc-skip-link" class="jinc-bar-btn">';
        $html .= esc_html('Ir para o conteúdo principal');
        $html .= '</a>';

        // 2. Toggle High Contrast
        $html .= '<button type="button" id="jinc-toggle-contrast" class="jinc-bar-btn" ';
        $html .= 'aria-pressed="false">';
        $html .= '<span class="dashicons dashicons-visibility jinc-bar-icon" aria-hidden="true"></span> ';
        $html .= esc_html('Alternar contraste');
        $html .= '</button>';

        // 3. Toggle Font Size
        $html .= '<button type="button" id="jinc-toggle-fontsize" class="jinc-bar-btn" ';
        $html .= 'aria-pressed="false">';
        $html .= '<span class="dashicons dashicons-editor-textcolor jinc-bar-icon" aria-hidden="true"></span> ';
        $html .= esc_html('Alternar tamanho da fonte');
        $html .= '</button>';

        $html .= '</nav>';
        $html .= '</div>';

        return $html;
    }

    /**
     * Check if we are in a WordPress admin context.
     */
    private function isAdminContext(): bool
    {
        if (function_exists('is_admin')) {
            return is_admin();
        }
        return false;
    }

    /**
     * Get the plugin base URL.
     */
    private function getPluginUrl(): string
    {
        if (function_exists('plugin_dir_url')) {
            return plugin_dir_url($this->pluginFile);
        }
        return '/wp-content/plugins/wp-acessivel-jinc/';
    }

    // ── WordPress wrapper methods (testable seam) ──

    private function wpEnqueueStyle(string $handle, string $src, array $deps, string $ver): void
    {
        if (function_exists('wp_enqueue_style')) {
            wp_enqueue_style($handle, $src, $deps, $ver);
        }
    }

    private function wpEnqueueScript(string $handle, string $src, array $deps, string $ver, bool $inFooter): void
    {
        if (function_exists('wp_enqueue_script')) {
            wp_enqueue_script($handle, $src, $deps, $ver, $inFooter);
        }
    }
}
