<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * @spec-ref FR-001
 */
final readonly class HeadingViolation
{
    public function __construct(
        public int $position,       // Which heading (0-indexed)
        public int $expectedLevel,  // What it should be
        public int $actualLevel,    // What it was
        public string $context,     // First 50 chars of heading text
    ) {}
}
