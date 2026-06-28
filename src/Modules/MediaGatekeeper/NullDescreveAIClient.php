<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

/**
 * Null implementation for Phase 2 (no-op).
 * Injected by default until DescreveAI integration is activated.
 *
 * @spec-ref FR-020
 */
final class NullDescreveAIClient implements DescreveAIClientInterface
{
    public function generateAltText(int $attachmentId, string $imageUrl): ?DescreveAIResult
    {
        return null;
    }
}
