<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * @spec-ref FR-002
 */
final readonly class LandmarkAnalysis
{
    /**
     * @param list<string> $presentLandmarks Landmarks found (e.g., ['article', 'nav']).
     * @param list<string> $missingLandmarks Landmarks that should be injected.
     * @param bool $isComplete True if all recommended landmarks are present.
     */
    public function __construct(
        public array $presentLandmarks,
        public array $missingLandmarks,
        public bool $isComplete,
    ) {}
}
