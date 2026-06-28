<?php declare(strict_types=1);

namespace WpAcessivelJinc\Utils;

/**
 * Transients API wrapper for caching processed DOM content.
 */
final class CacheManager
{
    private const PREFIX = 'jinc_se_';
    private const DEFAULT_TTL = 86400; // 24h

    /**
     * Get cached content for a post.
     *
     * @param int $postId WordPress post ID.
     * @param string $postModified Modified date string to hash.
     * @return string|null Cached HTML or null on miss.
     */
    public function get(int $postId, string $postModified): ?string
    {
        if (!function_exists('get_transient')) {
            return null;
        }

        $key = $this->generateKey($postId, $postModified);
        $cached = get_transient($key);

        return $cached !== false ? (string) $cached : null;
    }

    /**
     * Store processed content for a post.
     *
     * @param int $postId WordPress post ID.
     * @param string $postModified Modified date string to hash.
     * @param string $content Processed HTML content.
     */
    public function set(int $postId, string $postModified, string $content): void
    {
        if (!function_exists('set_transient')) {
            return;
        }

        $key = $this->generateKey($postId, $postModified);
        set_transient($key, $content, self::DEFAULT_TTL);
    }

    /**
     * Invalidate cache for a specific post. Called on save_post action.
     *
     * @param int $postId WordPress post ID.
     */
    public function invalidatePostTransient(int $postId): void
    {
        global $wpdb;
        if (!isset($wpdb)) {
            return;
        }

        // We delete all transients that match our prefix for this post ID.
        // Since transients can be stored in options table (if no object cache)
        $like = '_transient_' . self::PREFIX . $postId . '_%';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ));
    }

    /**
     * Flush all plugin transients. Called on settings change or plugin deactivation.
     */
    public function flushAllTransients(): void
    {
        global $wpdb;
        if (!isset($wpdb)) {
            return;
        }

        $like = '_transient_' . self::PREFIX . '%';
        $wpdb->query($wpdb->prepare(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE %s",
            $like
        ));
    }

    private function generateKey(int $postId, string $postModified): string
    {
        return self::PREFIX . $postId . '_' . md5($postModified);
    }
}
