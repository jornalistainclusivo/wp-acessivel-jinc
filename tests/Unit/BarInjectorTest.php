<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\FrontendBar\BarInjector;

/**
 * @covers \WpAcessivelJinc\Modules\FrontendBar\BarInjector
 */
class BarInjectorTest extends TestCase
{
    private BarInjector $injector;

    protected function setUp(): void
    {
        jinc_reset_wp_stubs();
        $this->injector = new BarInjector(__DIR__ . '/../../wp-acessivel-jinc.php');
    }

    // ── Registration ──

    /** @test */
    public function it_registers_wp_enqueue_scripts_action(): void
    {
        $this->injector->register();

        global $_jinc_actions;
        $hookNames = array_keys($_jinc_actions);
        $this->assertContains('wp_enqueue_scripts', $hookNames);
    }

    /** @test */
    public function it_registers_wp_body_open_action(): void
    {
        $this->injector->register();

        global $_jinc_actions;
        $hookNames = array_keys($_jinc_actions);
        $this->assertContains('wp_body_open', $hookNames);
    }

    /** @test */
    public function it_registers_wp_footer_fallback_action(): void
    {
        $this->injector->register();

        global $_jinc_actions;
        $hookNames = array_keys($_jinc_actions);
        $this->assertContains('wp_footer', $hookNames);
    }

    // ── Asset Enqueue ──

    /** @test */
    public function it_enqueues_css_and_js_assets(): void
    {
        global $_jinc_enqueued_styles, $_jinc_enqueued_scripts;

        $this->injector->enqueueAssets();

        $this->assertArrayHasKey('jinc-bar-css', $_jinc_enqueued_styles);
        $this->assertStringContainsString('jinc-bar.css', $_jinc_enqueued_styles['jinc-bar-css']['src']);

        $this->assertArrayHasKey('jinc-bar-js', $_jinc_enqueued_scripts);
        $this->assertStringContainsString('jinc-bar.js', $_jinc_enqueued_scripts['jinc-bar-js']['src']);
    }

    /** @test */
    public function it_does_not_enqueue_assets_in_admin_context(): void
    {
        global $_jinc_enqueued_styles, $_jinc_enqueued_scripts, $_jinc_is_admin;
        $_jinc_is_admin = true;

        $this->injector->enqueueAssets();

        $this->assertEmpty($_jinc_enqueued_styles);
        $this->assertEmpty($_jinc_enqueued_scripts);
    }

    // ── HTML Output ──

    /** @test */
    public function it_renders_nav_with_correct_aria_label(): void
    {
        $html = $this->injector->getBarHtml();

        $this->assertStringContainsString('<nav aria-label="Controles de Acessibilidade">', $html);
    }

    /** @test */
    public function it_renders_skip_to_content_link(): void
    {
        $html = $this->injector->getBarHtml();

        $this->assertStringContainsString('id="jinc-skip-link"', $html);
        $this->assertStringContainsString('href="#main"', $html);
        $this->assertStringContainsString('Ir para o conteúdo principal', $html);
    }

    /** @test */
    public function it_renders_contrast_toggle_button(): void
    {
        $html = $this->injector->getBarHtml();

        $this->assertStringContainsString('id="jinc-toggle-contrast"', $html);
        $this->assertStringContainsString('aria-pressed="false"', $html);
        $this->assertStringContainsString('Alternar contraste', $html);
    }

    /** @test */
    public function it_renders_fontsize_toggle_button(): void
    {
        $html = $this->injector->getBarHtml();

        $this->assertStringContainsString('id="jinc-toggle-fontsize"', $html);
        $this->assertStringContainsString('Alternar tamanho da fonte', $html);
    }

    /** @test */
    public function it_wraps_bar_in_region_with_aria_label(): void
    {
        $html = $this->injector->getBarHtml();

        $this->assertStringContainsString('id="jinc-a11y-bar"', $html);
        $this->assertStringContainsString('jinc-a11y-wrapper', $html);
        $this->assertStringContainsString('role="region"', $html);
        $this->assertStringContainsString('aria-label="Barra de Acessibilidade"', $html);
    }

    /** @test */
    public function it_renders_custom_frontend_title_and_id(): void
    {
        update_option('jinc_theme_options', [
            'frontend_title' => 'My Custom Bar Title',
            'a11y_id' => 'my-custom-wrapper',
        ]);

        $html = $this->injector->getBarHtml();

        $this->assertStringContainsString('id="my-custom-wrapper"', $html);
        $this->assertStringContainsString('aria-label="My Custom Bar Title"', $html);
        $this->assertStringContainsString('<span class="jinc-bar-title">My Custom Bar Title</span>', $html);
    }

    /** @test */
    public function it_renders_buttons_with_type_button(): void
    {
        $html = $this->injector->getBarHtml();

        // Both toggle buttons must have type="button" to prevent form submission
        $this->assertSame(2, substr_count($html, 'type="button"'));
    }

    /** @test */
    public function it_renders_icons_with_aria_hidden(): void
    {
        $html = $this->injector->getBarHtml();

        // Decorative icons must be hidden from screen readers
        $this->assertSame(2, substr_count($html, 'aria-hidden="true"'));
    }

    // ── Idempotency ──

    /** @test */
    public function it_renders_bar_only_once_via_body_open(): void
    {
        ob_start();
        $this->injector->renderBar();
        $this->injector->renderBar();
        $output = ob_get_clean();

        // Should appear exactly once
        $this->assertSame(1, substr_count($output, 'jinc-a11y-wrapper'));
    }

    /** @test */
    public function it_does_not_render_fallback_if_body_open_fired(): void
    {
        ob_start();
        $this->injector->renderBar();
        $this->injector->renderBarFallback();
        $output = ob_get_clean();

        // Should appear exactly once (renderBar), fallback should be suppressed
        $this->assertSame(1, substr_count($output, 'jinc-a11y-wrapper'));
    }

    /** @test */
    public function it_renders_via_fallback_when_body_open_not_called(): void
    {
        ob_start();
        $this->injector->renderBarFallback();
        $output = ob_get_clean();

        $this->assertStringContainsString('jinc-a11y-wrapper', $output);
        $this->assertStringContainsString('Controles de Acessibilidade', $output);
    }
}
