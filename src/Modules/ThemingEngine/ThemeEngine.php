<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\ThemingEngine;

class ThemeEngine
{
    private const OPTION_NAME = 'jinc_theme_options';

    public function init(): void
    {
        // Use a late priority so it injects after the main css is enqueued
        add_action('wp_enqueue_scripts', [$this, 'inject_dynamic_css'], 20);
    }

    public function inject_dynamic_css(): void
    {
        $options = $this->get_options();

        $custom_css = sprintf(
            ':root {
                --jinc-bar-bg: %s;
                --jinc-bar-text: %s;
                --jinc-bar-text-hover: %s;
                --jinc-bar-accent: %s;
                --jinc-bar-align: %s;
                --jinc-bar-font: %s;
                --jinc-btn-radius: %s;
            }',
            esc_attr($options['bg_color']),
            esc_attr($options['text_color']),
            esc_attr($options['text_hover_color']),
            esc_attr($options['accent_color']),
            esc_attr($options['align']),
            esc_attr($options['font']),
            esc_attr($this->map_radius($options['button_style']))
        );

        wp_add_inline_style('jinc-bar-css', $custom_css);
    }

    private function get_options(): array
    {
        $options = get_option(self::OPTION_NAME);
        
        if (!is_array($options)) {
            $options = [];
        }

        return [
            'layout'           => $options['layout'] ?? 'top_bar',
            'position'         => $options['position'] ?? 'bottom_right',
            'bg_color'         => $options['bg_color'] ?? '#000000',
            'text_color'       => $options['text_color'] ?? '#FFFFFF',
            'text_hover_color' => $options['text_hover_color'] ?? '#E0E0E0',
            'accent_color'     => $options['accent_color'] ?? '#0052CC',
            'align'            => $options['align'] ?? 'center',
            'font'             => $options['font'] ?? 'system-ui, -apple-system, sans-serif',
            'show_icons'       => isset($options['show_icons']) ? $options['show_icons'] : '1',
            'button_style'     => $options['button_style'] ?? 'arredondado',
        ];
    }

    private function map_radius(string $style): string
    {
        return match ($style) {
            'quadrado' => '0px',
            'pilula'   => '50px',
            'arredondado' => '8px',
            default    => '4px', // Fallback as requested in the css "var(--jinc-btn-radius, 4px)"
        };
    }
}
