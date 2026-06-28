<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

/**
 * Validation status for alt text on image attachments.
 *
 * @spec-ref FR-010, BR-MG-001
 */
enum AltTextStatus: string
{
    case VALID = 'valid';       // Non-empty alt text present
    case MISSING = 'missing';     // Image with no alt text — blocking
    case DECORATIVE = 'decorative';  // Explicitly marked decorative (alt="")
    case SKIPPED = 'skipped';     // Non-image file (PDF, etc.) — no validation
}
