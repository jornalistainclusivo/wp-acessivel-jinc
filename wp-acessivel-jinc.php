<?php
/**
 * Plugin Name: WP Acessível JINC
 * Plugin URI: https://github.com/jornalistainclusivo/wp-acessivel-jinc
 * Description: WordPress plugin that enforces native accessibility (WCAG 2.2 AAA) on the DOM, with zero overlays.
 * Version: 1.2.0
 * Requires at least: 6.4
 * Requires PHP: 8.1
 * Author: JINC Apps
 * Author URI: https://jornalistainclusivo.com
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: wp-acessivel-jinc
 */

declare(strict_types=1);

if (!defined('ABSPATH')) {
    exit;
}

// 1. Composer Autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// 2. Gatekeeper Verification (Environment validation before anything else)
require_once __DIR__ . '/src/Core/Gatekeeper.php';
$gatekeeper = new \WpAcessivelJinc\Core\Gatekeeper();

if (!$gatekeeper->checkRequirements()) {
    return; // Stop execution if requirements are not met (PHP 8.1+ and ext-dom)
}

// 3. Plugin Bootstrap
add_action('plugins_loaded', [\WpAcessivelJinc\Core\Bootstrap::class, 'init']);
