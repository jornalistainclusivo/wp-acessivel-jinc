<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * @spec-ref FR-001
 */
final readonly class HeadingAnalysis
{
    /**
     * @param list<HeadingInfo> $headings Ordered list of headings found.
     * @param list<HeadingViolation> $violations Level skip violations detected.
     * @param bool $isValid True if no violations found.
     */
    public function __construct(
        public array $headings,
        public array $violations,
        public bool $isValid,
    ) {}
}
