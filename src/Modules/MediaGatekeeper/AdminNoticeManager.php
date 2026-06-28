<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

/**
 * Manages persistent admin notices for alt text violations.
 * Uses user-specific transients to avoid cross-user pollution.
 *
 * @spec-ref FR-010, BR-MG-003
 *
 * Transient key: 'jinc_mg_notices_{user_id}'
 * Value: array<int> — list of attachment IDs missing alt text
 * TTL: 0 (no expiration — cleared on dismiss or alt text fix)
 *
 * NOTE: This class uses WordPress functions (get_transient, set_transient, etc.)
 * and is designed for integration testing. Unit tests should mock these calls.
 */
final class AdminNoticeManager
{
    private const TRANSIENT_PREFIX = 'jinc_mg_notices_';

    /**
     * Queue a notice for a specific attachment.
     * Idempotent — will not duplicate IDs.
     *
     * @param int $attachmentId Attachment post ID missing alt text.
     * @param int $userId User ID (defaults to current user if 0).
     */
    public function queueNotice(int $attachmentId, int $userId = 0): void
    {
        $userId = $userId > 0 ? $userId : $this->getCurrentUserId();
        if ($userId === 0) {
            return;
        }

        $key = self::TRANSIENT_PREFIX . $userId;
        $current = $this->getNoticeList($key);

        if (!in_array($attachmentId, $current, true)) {
            $current[] = $attachmentId;
            $this->setTransient($key, $current);
        }
    }

    /**
     * Display all pending notices for the current user.
     * Renders one consolidated notice, not one per attachment.
     */
    public function displayPendingNotices(): void
    {
        $userId = $this->getCurrentUserId();
        if ($userId === 0) {
            return;
        }

        $key = self::TRANSIENT_PREFIX . $userId;
        $attachmentIds = $this->getNoticeList($key);

        if (count($attachmentIds) === 0) {
            return;
        }

        $count = count($attachmentIds);
        $plural = $count === 1 ? '' : 's';

        $output = '<div class="notice notice-warning is-dismissible" role="alert" aria-live="polite">';
        $output .= '<p><strong>WP Acessível JINC:</strong> ';
        $output .= $count . ' imagem(ns) enviada' . $plural . ' sem texto alternativo (alt text).</p>';
        $output .= '<ul>';

        foreach ($attachmentIds as $id) {
            $filename = $this->getAttachmentFilename($id);
            $editLink = $this->getAttachmentEditLink($id);
            $output .= '<li><a href="' . $this->escapeAttribute($editLink) . '">';
            $output .= '"' . $this->escapeHtml($filename) . '" (ID: ' . $id . ')';
            $output .= '</a> — sem alt text</li>';
        }

        $output .= '</ul>';
        $output .= '<p><small>Adicione alt text descritivo no campo "Texto Alternativo" de cada imagem na ';
        $output .= '<a href="' . $this->escapeAttribute($this->getMediaLibraryUrl()) . '">Biblioteca de Mídia</a>.';
        $output .= '</small></p></div>';

        echo $output;
    }

    /**
     * Clear notice for a specific attachment.
     *
     * @param int $attachmentId Attachment post ID.
     * @param int $userId User ID (defaults to current user if 0).
     */
    public function clearNoticeForAttachment(int $attachmentId, int $userId = 0): void
    {
        $userId = $userId > 0 ? $userId : $this->getCurrentUserId();
        if ($userId === 0) {
            return;
        }

        $key = self::TRANSIENT_PREFIX . $userId;
        $current = $this->getNoticeList($key);
        $filtered = array_values(array_filter(
            $current,
            static fn(int $id): bool => $id !== $attachmentId,
        ));

        if (count($filtered) === 0) {
            $this->deleteTransient($key);
        } else {
            $this->setTransient($key, $filtered);
        }
    }

    /**
     * Clear all notices for a user.
     *
     * @param int $userId User ID (defaults to current user if 0).
     */
    public function clearAllNotices(int $userId = 0): void
    {
        $userId = $userId > 0 ? $userId : $this->getCurrentUserId();
        if ($userId === 0) {
            return;
        }

        $this->deleteTransient(self::TRANSIENT_PREFIX . $userId);
    }

    /**
     * Get the list of attachment IDs from a transient.
     *
     * @return list<int>
     */
    private function getNoticeList(string $key): array
    {
        if (!function_exists('get_transient')) {
            return [];
        }
        $value = get_transient($key);
        return is_array($value) ? array_map('intval', $value) : [];
    }

    private function setTransient(string $key, array $value): void
    {
        if (function_exists('set_transient')) {
            set_transient($key, $value, 0);
        }
    }

    private function deleteTransient(string $key): void
    {
        if (function_exists('delete_transient')) {
            delete_transient($key);
        }
    }

    private function getCurrentUserId(): int
    {
        if (function_exists('get_current_user_id')) {
            return get_current_user_id();
        }
        return 0;
    }

    private function getAttachmentFilename(int $attachmentId): string
    {
        if (function_exists('get_the_title')) {
            $title = get_the_title($attachmentId);
            return is_string($title) ? $title : 'attachment-' . $attachmentId;
        }
        return 'attachment-' . $attachmentId;
    }

    private function getAttachmentEditLink(int $attachmentId): string
    {
        if (function_exists('get_edit_post_link')) {
            $link = get_edit_post_link($attachmentId, 'raw');
            return is_string($link) ? $link : '#';
        }
        return '#';
    }

    private function getMediaLibraryUrl(): string
    {
        if (function_exists('admin_url')) {
            return admin_url('upload.php');
        }
        return '/wp-admin/upload.php';
    }

    private function escapeHtml(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }

    private function escapeAttribute(string $text): string
    {
        return htmlspecialchars($text, ENT_QUOTES | ENT_HTML5, 'UTF-8');
    }
}
