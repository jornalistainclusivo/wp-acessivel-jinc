<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Core\Gatekeeper;

class GatekeeperTest extends TestCase
{
    public function test_meets_php_version_requirement(): void
    {
        $gatekeeper = new Gatekeeper();
        
        // This should be true when running in PHP 8.1+
        $this->assertTrue($gatekeeper->hasValidPhpVersion());
    }

    public function test_meets_extension_requirements(): void
    {
        $gatekeeper = new Gatekeeper();
        
        // This should be true as ext-dom is required
        $this->assertTrue($gatekeeper->hasRequiredExtensions());
    }

    public function test_check_requirements_returns_true_if_all_met(): void
    {
        // Mocking native functions like phpversion or extension_loaded is hard in pure PHPUnit without extensions,
        // but we can test the behavior of the wrapper methods if we isolate them.
        // For simplicity in this test, assuming the current env is valid:
        $gatekeeper = new Gatekeeper();
        $this->assertTrue($gatekeeper->checkRequirements());
    }
}
