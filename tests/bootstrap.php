<?php declare(strict_types=1);

/**
 * PHPUnit test bootstrap file for WP Acessível JINC.
 */

// Load WordPress function stubs BEFORE autoloader
// so function_exists() checks pass during class loading.
require_once __DIR__ . '/Fixtures/wp-stubs.php';

require_once __DIR__ . '/../vendor/autoload.php';

