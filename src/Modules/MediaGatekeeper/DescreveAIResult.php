<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

/**
 * Value object for DescreveAI response (Phase 3).
 *
 * @spec-ref FR-020
 */
final readonly class DescreveAIResult
{
    public function __construct(
        public string $altText,
        public float $confidence,
        public string $model,
        public string $language,
    ) {}
}
