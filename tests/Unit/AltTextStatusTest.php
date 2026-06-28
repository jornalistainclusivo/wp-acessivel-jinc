<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\MediaGatekeeper\AltTextStatus;

/**
 * @spec-source docs/SPEC_MediaGatekeeper.md
 * @covers \WpAcessivelJinc\Modules\MediaGatekeeper\AltTextStatus
 */
class AltTextStatusTest extends TestCase
{
    /** @test */
    public function it_has_exactly_four_cases(): void
    {
        $cases = AltTextStatus::cases();
        $this->assertCount(4, $cases);
    }

    /** @test */
    public function it_has_correct_string_values(): void
    {
        $this->assertSame('valid', AltTextStatus::VALID->value);
        $this->assertSame('missing', AltTextStatus::MISSING->value);
        $this->assertSame('decorative', AltTextStatus::DECORATIVE->value);
        $this->assertSame('skipped', AltTextStatus::SKIPPED->value);
    }

    /** @test */
    public function it_can_be_instantiated_from_string(): void
    {
        $status = AltTextStatus::from('missing');
        $this->assertSame(AltTextStatus::MISSING, $status);
    }
}
