<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

/**
 * Core validation engine for alt text on image attachments.
 * Stateless — all state comes from function arguments.
 *
 * @spec-ref FR-010, BR-MG-001, BR-MG-002
 */
final class AltTextValidator
{
    private const DECORATIVE_KEYWORD = 'decorativo';

    /**
     * Validate alt text from a raw string value (for pre-insert validation
     * where the attachment may not yet exist in the database).
     *
     * @param string $altText The alt text string to validate.
     * @param string $mimeType The MIME type of the file being uploaded.
     * @param bool $isDecorative Whether the image is already marked as decorative.
     * @return AltTextValidationResult Validation result.
     *
     * Algorithm:
     *   1. IF mime type does NOT start with "image/" → return SKIPPED
     *   2. IF alt text (trimmed, case-insensitive) === "decorativo" → return DECORATIVE
     *   3. IF alt text is non-empty string after trim → return VALID
     *   4. IF isDecorative flag is true → return DECORATIVE
     *   5. ELSE → return MISSING
     */
    public function validateRaw(
        string $altText,
        string $mimeType,
        bool $isDecorative = false,
        int $attachmentId = 0,
    ): AltTextValidationResult {
        // Step 1: Non-image bypass
        if (!$this->isImageMimeType($mimeType)) {
            return new AltTextValidationResult(
                status: AltTextStatus::SKIPPED,
                attachmentId: $attachmentId,
                mimeType: $mimeType,
                altText: $altText,
                message: 'Arquivo não é imagem — validação ignorada.',
            );
        }

        $trimmedAlt = trim($altText);

        // Step 2: Semantic bypass — "decorativo" keyword
        if (mb_strtolower($trimmedAlt, 'UTF-8') === self::DECORATIVE_KEYWORD) {
            return new AltTextValidationResult(
                status: AltTextStatus::DECORATIVE,
                attachmentId: $attachmentId,
                mimeType: $mimeType,
                altText: '',
                message: 'Imagem marcada como decorativa.',
            );
        }

        // Step 3: Non-empty alt text
        if ($trimmedAlt !== '') {
            return new AltTextValidationResult(
                status: AltTextStatus::VALID,
                attachmentId: $attachmentId,
                mimeType: $mimeType,
                altText: $trimmedAlt,
                message: 'Texto alternativo válido.',
            );
        }

        // Step 4: Decorative flag (programmatic/API)
        if ($isDecorative) {
            return new AltTextValidationResult(
                status: AltTextStatus::DECORATIVE,
                attachmentId: $attachmentId,
                mimeType: $mimeType,
                altText: '',
                message: 'Imagem marcada como decorativa.',
            );
        }

        // Step 5: Missing — blocking
        return new AltTextValidationResult(
            status: AltTextStatus::MISSING,
            attachmentId: $attachmentId,
            mimeType: $mimeType,
            altText: '',
            message: 'Esta imagem requer texto alternativo (alt text) antes de ser inserida.',
        );
    }

    /**
     * Check if a MIME type is an image type.
     */
    private function isImageMimeType(string $mimeType): bool
    {
        return str_starts_with($mimeType, 'image/');
    }
}
