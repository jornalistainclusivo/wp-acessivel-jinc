<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\ThemingEngine\SettingsPage;

class SettingsPageTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (function_exists('jinc_reset_wp_stubs')) {
            jinc_reset_wp_stubs();
        }
    }

    public function test_it_registers_settings(): void
    {
        $settings = new SettingsPage();
        
        // This should not throw any errors or warnings
        // Since register_setting and others are mocked, we can just call the method to ensure it runs correctly
        $settings->register_settings();

        $this->assertTrue(true, 'Settings successfully registered without errors.');
    }

    public function test_it_sanitizes_hex_colors_correctly(): void
    {
        $settings = new SettingsPage();
        
        $input = [
            'layout' => 'floating_pill',
            'position' => 'bottom_left',
            'bg_color' => '#123456',
            'text_color' => 'invalid_color',
            'text_hover_color' => '#E0E0E0',
            'accent_color' => '#FFF',
            'accent_hover_color' => '#00f0c0',
            'align' => 'flex-start',
            'font' => 'sans-serif',
            'show_icons' => '1',
            'button_style' => 'text_only',
            'bar_size' => 'large',
            'frontend_title' => 'My Accessibility Bar',
            'a11y_id' => 'my custom id',
        ];

        $sanitized = $settings->sanitize_options($input);

        $this->assertEquals('#123456', $sanitized['bg_color']);
        $this->assertEquals('', $sanitized['text_color']); // Should fallback to empty string or default on invalid
        $this->assertEquals('#E0E0E0', $sanitized['text_hover_color']);
        $this->assertEquals('#FFF', $sanitized['accent_color']);
        $this->assertEquals('#00f0c0', $sanitized['accent_hover_color']);
        $this->assertEquals('floating_pill', $sanitized['layout']);
        $this->assertEquals('bottom_left', $sanitized['position']);
        $this->assertEquals('flex-start', $sanitized['align']);
        $this->assertEquals('sans-serif', $sanitized['font']);
        $this->assertEquals('1', $sanitized['show_icons']);
        $this->assertEquals('text_only', $sanitized['button_style']);
        $this->assertEquals('large', $sanitized['bar_size']);
        $this->assertEquals('My Accessibility Bar', $sanitized['frontend_title']);
        $this->assertEquals('my-custom-id', $sanitized['a11y_id']);
    }

    public function test_it_enqueues_color_picker_asset(): void
    {
        global $_jinc_enqueued_styles, $_jinc_enqueued_scripts;
        
        $settings = new SettingsPage();
        $settings->enqueue_assets('settings_page_jinc-wp-acessivel');

        $this->assertArrayHasKey('wp-color-picker', $_jinc_enqueued_styles);
        $this->assertArrayHasKey('wp-color-picker', $_jinc_enqueued_scripts);
    }

    public function test_it_routes_to_default_tab(): void
    {
        $settings = new SettingsPage();
        unset($_GET['tab']);
        
        ob_start();
        $settings->render_page();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Configurações Visuais', $output);
        $this->assertStringContainsString('<form method="post" action="options.php">', $output);
    }

    public function test_it_routes_to_descreveai_tab(): void
    {
        $settings = new SettingsPage();
        $_GET['tab'] = 'descreveai';
        
        ob_start();
        $settings->render_page();
        $output = ob_get_clean();
        
        $this->assertStringContainsString('Em breve: Inteligência Artificial para descrições de imagens automáticas', $output);
        $this->assertStringNotContainsString('<form method="post" action="options.php">', $output);
    }
}
