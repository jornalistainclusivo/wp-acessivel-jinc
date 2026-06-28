<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\ThemingEngine;

class SettingsPage
{
    private const OPTION_GROUP = 'jinc_theme_options_group';
    private const OPTION_NAME = 'jinc_theme_options';

    public function init(): void
    {
        add_action('admin_menu', [$this, 'add_admin_menu']);
        add_action('admin_init', [$this, 'register_settings']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
    }

    public function add_admin_menu(): void
    {
        add_options_page(
            'WP Acessível',
            'WP Acessível',
            'manage_options',
            'jinc-wp-acessivel',
            [$this, 'render_page']
        );
    }

    public function register_settings(): void
    {
        register_setting(
            self::OPTION_GROUP,
            self::OPTION_NAME,
            [
                'sanitize_callback' => [$this, 'sanitize_options']
            ]
        );

        add_settings_section(
            'jinc_theme_main_section',
            'Configurações Visuais',
            null,
            'jinc-wp-acessivel'
        );

        add_settings_field(
            'jinc_theme_layout',
            'Layout',
            [$this, 'render_layout_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section'
        );

        add_settings_field(
            'jinc_theme_position',
            'Posição (se Flutuante)',
            [$this, 'render_position_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section'
        );

        add_settings_field(
            'jinc_theme_bg_color',
            'Cor de Fundo',
            [$this, 'render_color_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section',
            ['key' => 'bg_color', 'default' => '#000000']
        );

        add_settings_field(
            'jinc_theme_text_color',
            'Cor do Texto',
            [$this, 'render_color_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section',
            ['key' => 'text_color', 'default' => '#FFFFFF']
        );

        add_settings_field(
            'jinc_theme_text_hover_color',
            'Cor do Texto (Hover)',
            [$this, 'render_color_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section',
            ['key' => 'text_hover_color', 'default' => '#E0E0E0']
        );

        add_settings_field(
            'jinc_theme_accent_color',
            'Cor de Destaque',
            [$this, 'render_color_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section',
            ['key' => 'accent_color', 'default' => '#0052CC']
        );

        add_settings_field(
            'jinc_theme_align',
            'Alinhamento',
            [$this, 'render_align_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section'
        );

        add_settings_field(
            'jinc_theme_font',
            'Fonte',
            [$this, 'render_font_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section'
        );

        add_settings_field(
            'jinc_theme_show_icons',
            'Exibir Ícones',
            [$this, 'render_show_icons_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section'
        );

        add_settings_field(
            'jinc_theme_button_style',
            'Estilo dos Botões',
            [$this, 'render_button_style_field'],
            'jinc-wp-acessivel',
            'jinc_theme_main_section'
        );
    }

    public function sanitize_options(array $input): array
    {
        $sanitized = [];
        
        $sanitized['layout'] = in_array($input['layout'] ?? '', ['top_bar', 'floating_pill'], true) 
            ? $input['layout'] 
            : 'top_bar';
            
        $sanitized['position'] = in_array($input['position'] ?? '', ['bottom_right', 'bottom_left'], true) 
            ? $input['position'] 
            : 'bottom_right';

        $sanitized['bg_color'] = sanitize_hex_color($input['bg_color'] ?? '') ?? '';
        $sanitized['text_color'] = sanitize_hex_color($input['text_color'] ?? '') ?? '';
        $sanitized['text_hover_color'] = sanitize_hex_color($input['text_hover_color'] ?? '') ?? '';
        $sanitized['accent_color'] = sanitize_hex_color($input['accent_color'] ?? '') ?? '';
        
        $sanitized['align'] = in_array($input['align'] ?? '', ['flex-start', 'center', 'flex-end'], true) 
            ? $input['align'] 
            : 'center';
            
        $sanitized['font'] = in_array($input['font'] ?? '', ['system-ui, -apple-system, sans-serif', 'sans-serif', 'serif'], true) 
            ? $input['font'] 
            : 'system-ui, -apple-system, sans-serif';
            
        $sanitized['show_icons'] = isset($input['show_icons']) && $input['show_icons'] === '1' ? '1' : '0';

        $sanitized['button_style'] = in_array($input['button_style'] ?? '', ['quadrado', 'arredondado', 'pilula'], true)
            ? $input['button_style']
            : 'arredondado'; // Let's use arredondado (8px) or 4px as default? The prompt says "var(--jinc-btn-radius, 4px)" so maybe arredondado is 4px or 8px. Let's just save default as empty or default string. Let's use 'default' or just not map it strictly. Actually the CSS fallback is 4px, but let's make the default 'arredondado'.

        return $sanitized;
    }

    public function enqueue_assets(string $hook_suffix): void
    {
        if (strpos($hook_suffix, 'jinc-wp-acessivel') !== false) {
            wp_enqueue_style('wp-color-picker');
            wp_enqueue_script('wp-color-picker');
        }
    }

    public function render_page(): void
    {
        ?>
        <div class="wrap">
            <h1>WP Acessível - Configurações Visuais</h1>
            <form method="post" action="options.php">
                <?php
                settings_fields(self::OPTION_GROUP);
                do_settings_sections('jinc-wp-acessivel');
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    public function render_layout_field(): void
    {
        $options = get_option(self::OPTION_NAME, []);
        $layout = $options['layout'] ?? 'top_bar';
        ?>
        <select name="jinc_theme_options[layout]" id="jinc_theme_layout">
            <option value="top_bar" <?php selected($layout, 'top_bar'); ?>>Barra no Topo</option>
            <option value="floating_pill" <?php selected($layout, 'floating_pill'); ?>>Pílula Flutuante</option>
        </select>
        <?php
    }

    public function render_position_field(): void
    {
        $options = get_option(self::OPTION_NAME, []);
        $position = $options['position'] ?? 'bottom_right';
        ?>
        <select name="jinc_theme_options[position]" id="jinc_theme_position">
            <option value="bottom_right" <?php selected($position, 'bottom_right'); ?>>Canto Inferior Direito</option>
            <option value="bottom_left" <?php selected($position, 'bottom_left'); ?>>Canto Inferior Esquerdo</option>
        </select>
        <?php
    }

    public function render_color_field(array $args): void
    {
        $key = $args['key'];
        $default = $args['default'];
        $options = get_option(self::OPTION_NAME, []);
        $val = $options[$key] ?? $default;
        ?>
        <input type="text" name="jinc_theme_options[<?php echo esc_attr($key); ?>]" value="<?php echo esc_attr($val); ?>" class="jinc-color-picker" data-default-color="<?php echo esc_attr($default); ?>" />
        <?php
    }

    public function render_align_field(): void
    {
        $options = get_option(self::OPTION_NAME, []);
        $align = $options['align'] ?? 'center';
        ?>
        <select name="jinc_theme_options[align]" id="jinc_theme_align">
            <option value="flex-start" <?php selected($align, 'flex-start'); ?>>Esquerda</option>
            <option value="center" <?php selected($align, 'center'); ?>>Centro</option>
            <option value="flex-end" <?php selected($align, 'flex-end'); ?>>Direita</option>
        </select>
        <?php
    }

    public function render_font_field(): void
    {
        $options = get_option(self::OPTION_NAME, []);
        $font = $options['font'] ?? 'system-ui, -apple-system, sans-serif';
        ?>
        <select name="jinc_theme_options[font]" id="jinc_theme_font">
            <option value="system-ui, -apple-system, sans-serif" <?php selected($font, 'system-ui, -apple-system, sans-serif'); ?>>System Default</option>
            <option value="sans-serif" <?php selected($font, 'sans-serif'); ?>>Sans-serif</option>
            <option value="serif" <?php selected($font, 'serif'); ?>>Serif</option>
        </select>
        <?php
    }

    public function render_show_icons_field(): void
    {
        $options = get_option(self::OPTION_NAME, []);
        // Defaults to true (checked) if not set, or checks if it's '1'
        $show_icons = isset($options['show_icons']) ? $options['show_icons'] : '1';
        ?>
        <input type="checkbox" name="jinc_theme_options[show_icons]" id="jinc_theme_show_icons" value="1" <?php checked($show_icons, '1'); ?> />
        <?php
    }

    public function render_button_style_field(): void
    {
        $options = get_option(self::OPTION_NAME, []);
        $button_style = $options['button_style'] ?? 'arredondado';
        ?>
        <select name="jinc_theme_options[button_style]" id="jinc_theme_button_style">
            <option value="quadrado" <?php selected($button_style, 'quadrado'); ?>>Quadrado (0px)</option>
            <option value="arredondado" <?php selected($button_style, 'arredondado'); ?>>Arredondado (8px)</option>
            <option value="pilula" <?php selected($button_style, 'pilula'); ?>>Pílula (50px)</option>
        </select>
        <?php
    }
}
