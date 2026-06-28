<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * @spec-ref FR-001
 */
final readonly class HeadingInfo
{
    public function __construct(
        public int $level,           // 1-6
        public int $originalLevel,   // 1-6 (before fix)
        public string $textContent,  // Inner text
        public int $position,        // 0-indexed position in document order
    ) {}
}
