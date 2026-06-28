<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

/**
 * Value object representing the result of alt text validation.
 *
 * @spec-ref FR-010
 */
final readonly class AltTextValidationResult
{
    public function __construct(
        public AltTextStatus $status,
        public int $attachmentId,
        public string $mimeType,
        public string $altText,
        public string $message,
    ) {}

    /**
     * Whether this result should block the operation.
     * Only MISSING status is blocking.
     */
    public function isBlocking(): bool
    {
        return $this->status === AltTextStatus::MISSING;
    }
}
