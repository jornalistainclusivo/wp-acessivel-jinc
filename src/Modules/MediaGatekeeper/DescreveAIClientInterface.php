<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

/**
 * Contract for the DescreveAI integration (Phase 3).
 *
 * @spec-ref FR-020 (deferred to Scale phase)
 */
interface DescreveAIClientInterface
{
    /**
     * Request AI-generated alt text for an image.
     *
     * @param int $attachmentId WordPress attachment post ID.
     * @param string $imageUrl Full URL to the image file.
     * @return DescreveAIResult|null Generated alt text, or null if service unavailable.
     */
    public function generateAltText(int $attachmentId, string $imageUrl): ?DescreveAIResult;
}
