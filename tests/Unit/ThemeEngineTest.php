<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\ThemingEngine\ThemeEngine;

class ThemeEngineTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (function_exists('jinc_reset_wp_stubs')) {
            jinc_reset_wp_stubs();
        }
    }

    public function test_it_loads_default_options_when_none_exist(): void
    {
        // option does not exist yet (get_option returns false by default)
        
        $engine = new ThemeEngine();
        $engine->inject_dynamic_css();

        global $_jinc_inline_styles;
        
        // Assert that wp_add_inline_style was called for our stylesheet
        $this->assertArrayHasKey('jinc-bar-css', $_jinc_inline_styles);
        
        $css = $_jinc_inline_styles['jinc-bar-css'];
        
        // Defaults: #000000 for bg, #FFFFFF for text, #0052CC for accent
        $this->assertStringContainsString('--jinc-bar-bg: #000000;', $css);
        $this->assertStringContainsString('--jinc-bar-text: #FFFFFF;', $css);
        $this->assertStringContainsString('--jinc-bar-text-hover: #E0E0E0;', $css);
        $this->assertStringContainsString('--jinc-bar-accent: #0052CC;', $css);
        $this->assertStringContainsString('--jinc-bar-accent-hover: #003d99;', $css);
        $this->assertStringContainsString('--jinc-bar-size: medium;', $css);
        // Defaults for new fields
        $this->assertStringContainsString('--jinc-bar-align: center;', $css);
        $this->assertStringContainsString('--jinc-bar-font: system-ui, -apple-system, sans-serif;', $css);
        $this->assertStringContainsString('--jinc-btn-radius: 8px;', $css);
    }

    public function test_it_injects_custom_options(): void
    {
        update_option('jinc_theme_options', [
            'bg_color' => '#FF0000',
            'text_color' => '#00FF00',
            'text_hover_color' => '#CCCCCC',
            'accent_color' => '#0000FF',
            'accent_hover_color' => '#0000AA',
            'layout' => 'floating_pill',
            'position' => 'bottom_left',
            'align' => 'flex-start',
            'font' => 'serif',
            'show_icons' => '0',
            'button_style' => 'text_only',
            'bar_size' => 'large',
        ]);

        $engine = new ThemeEngine();
        $engine->inject_dynamic_css();

        global $_jinc_inline_styles;
        $css = $_jinc_inline_styles['jinc-bar-css'];

        $this->assertStringContainsString('--jinc-bar-bg: #FF0000;', $css);
        $this->assertStringContainsString('--jinc-bar-text: #00FF00;', $css);
        $this->assertStringContainsString('--jinc-bar-text-hover: #CCCCCC;', $css);
        $this->assertStringContainsString('--jinc-bar-accent: #0000FF;', $css);
        $this->assertStringContainsString('--jinc-bar-accent-hover: #0000AA;', $css);
        $this->assertStringContainsString('--jinc-bar-align: flex-start;', $css);
        $this->assertStringContainsString('--jinc-bar-font: serif;', $css);
        $this->assertStringContainsString('--jinc-btn-radius: 4px;', $css); // text_only fallback radius
        $this->assertStringContainsString('--jinc-bar-size: large;', $css);
        $this->assertStringContainsString('.jinc-a11y-wrapper .jinc-bar-btn {
                background: transparent !important;
                border: none !important;
            }', $css);
        $this->assertStringContainsString('html.jinc-high-contrast .jinc-a11y-wrapper .jinc-bar-btn[aria-pressed="true"]', $css);
        $this->assertStringContainsString('color: #ffff00 !important;', $css);
    }
}
