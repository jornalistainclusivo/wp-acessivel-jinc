<?php declare(strict_types=1);

namespace WpAcessivelJinc\Core;

/**
 * Gatekeeper checks environment requirements (PHP 8.1+ and ext-dom).
 * If requirements are not met, it generates an admin notice and prevents further loading.
 */
final class Gatekeeper
{
    private const MIN_PHP_VERSION = '8.1.0';

    /**
     * Checks all requirements and registers an admin notice if they fail.
     *
     * @return bool True if all requirements are met, false otherwise.
     */
    public function checkRequirements(): bool
    {
        if (!$this->hasValidPhpVersion() || !$this->hasRequiredExtensions()) {
            // We use add_action to hook into admin_notices to display the error.
            if (function_exists('add_action')) {
                add_action('admin_notices', [$this, 'displayAdminNotice']);
            }
            return false;
        }

        return true;
    }

    /**
     * @return bool True if PHP version is >= 8.1.0
     */
    public function hasValidPhpVersion(): bool
    {
        return version_compare(PHP_VERSION, self::MIN_PHP_VERSION, '>=');
    }

    /**
     * @return bool True if required extensions are loaded
     */
    public function hasRequiredExtensions(): bool
    {
        return extension_loaded('dom');
    }

    /**
     * Callback for admin_notices. Displays the error message to the admin.
     */
    public function displayAdminNotice(): void
    {
        $message = '<strong>WP Acessível JINC</strong> falhou ao inicializar: ';
        
        if (!$this->hasValidPhpVersion()) {
            $message .= 'É necessário PHP ' . self::MIN_PHP_VERSION . ' ou superior (você está rodando ' . PHP_VERSION . '). ';
        }
        
        if (!$this->hasRequiredExtensions()) {
            $message .= 'A extensão <code>ext-dom</code> do PHP precisa estar ativada no seu servidor.';
        }

        echo '<div class="notice notice-error"><p>' . wp_kses_post($message) . '</p></div>';
    }
}
