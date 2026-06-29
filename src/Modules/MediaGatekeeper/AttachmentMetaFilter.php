<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

use WpAcessivelJinc\Utils\Logger;

/**
 * Intercepts attachment metadata save (Classic Editor path).
 * Non-blocking: stores admin notice transient for display.
 *
 * @spec-ref FR-010, BR-MG-003
 */
final class AttachmentMetaFilter
{
    public function __construct(
        private readonly AltTextValidator $validator,
        private readonly AdminNoticeManager $noticeManager,
        private readonly Logger $logger,
    ) {}

    /**
     * Register WordPress hooks.
     */
    public function register(): void
    {
        if (function_exists('add_filter')) {
            add_filter('wp_update_attachment_metadata', [$this, 'validateOnSave'], 10, 2);
        }
        if (function_exists('add_action')) {
            add_action('add_attachment', [$this, 'onAttachmentAdded'], 10, 1);
        }
    }

    /**
     * Filter callback for wp_update_attachment_metadata.
     * NEVER modifies or blocks the metadata — only queues warnings.
     *
     * @param array<string, mixed> $metadata Attachment metadata array.
     * @param int $attachmentId Attachment post ID.
     * @return array<string, mixed> Unmodified metadata.
     */
    public function validateOnSave(array $metadata, int $attachmentId): array
    {
        $this->checkAttachment($attachmentId);
        return $metadata;
    }

    /**
     * Action callback for add_attachment.
     *
     * @param int $attachmentId Attachment post ID.
     */
    public function onAttachmentAdded(int $attachmentId): void
    {
        $this->checkAttachment($attachmentId);
    }

    /**
     * Internal validation logic — shared between both hooks.
     */
    private function checkAttachment(int $attachmentId): void
    {
        $mimeType = $this->getPostMimeType($attachmentId);
        $altText = $this->getAttachmentAlt($attachmentId);
        $isDecorative = $this->isDecorativeFlag($attachmentId);

        $result = $this->validator->validateRaw(
            altText: $altText,
            mimeType: $mimeType,
            isDecorative: $isDecorative,
            attachmentId: $attachmentId,
        );

        // Handle semantic bypass side-effects
        if ($result->status === AltTextStatus::DECORATIVE
            && mb_strtolower(trim($altText), 'UTF-8') === 'decorativo'
        ) {
            $this->updateAttachmentMeta($attachmentId, '_wp_attachment_image_alt', '');
            $this->updateAttachmentMeta($attachmentId, '_jinc_decorative', '1');
        }

        if ($result->isBlocking()) {
            $options = get_option('jinc_theme_options', []);
            if (!empty($options['descreveai_active'])) {
                $this->updateAttachmentMeta($attachmentId, '_wp_attachment_image_alt', '[JINC: Processando IA...]');
                $this->updateAttachmentMeta($attachmentId, '_jinc_ai_status', 'pending');
                $this->logger->info('Attachment quarantined for AI', ['attachment_id' => $attachmentId, 'mime_type' => $mimeType]);
                return;
            }

            $this->noticeManager->queueNotice($attachmentId);
            $this->logger->info(
                'Classic upload: image missing alt text',
                ['attachment_id' => $attachmentId, 'mime_type' => $mimeType],
            );
        }
    }

    private function getPostMimeType(int $attachmentId): string
    {
        if (function_exists('get_post_mime_type')) {
            $mime = get_post_mime_type($attachmentId);
            return is_string($mime) ? $mime : '';
        }
        return '';
    }

    private function getAttachmentAlt(int $attachmentId): string
    {
        if (function_exists('get_post_meta')) {
            $alt = get_post_meta($attachmentId, '_wp_attachment_image_alt', true);
            return is_string($alt) ? $alt : '';
        }
        return '';
    }

    private function isDecorativeFlag(int $attachmentId): bool
    {
        if (function_exists('get_post_meta')) {
            return get_post_meta($attachmentId, '_jinc_decorative', true) === '1';
        }
        return false;
    }

    private function updateAttachmentMeta(int $attachmentId, string $key, string $value): void
    {
        if (function_exists('update_post_meta')) {
            update_post_meta($attachmentId, $key, $value);
        }
    }
}
