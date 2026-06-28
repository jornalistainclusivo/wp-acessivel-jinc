<?php declare(strict_types=1);

namespace WpAcessivelJinc\Utils;

class CacheManager
{
    private const CACHE_META_KEY = '_jinc_a11y_cache_key';
    
    public function register(): void
    {
        if (function_exists('add_action')) {
            add_action('save_post', [$this, 'purge'], 10, 1);
            add_action('post_updated', [$this, 'purge'], 10, 1);
        }
    }

    /**
     * Builds the dynamic cache key for a given post.
     */
    private function buildKey(int $postId): string
    {
        $post = get_post($postId);
        $modified = isset($post->post_modified) ? strtotime($post->post_modified) : time();
        return "jinc_a11y_content_{$postId}_{$modified}";
    }

    /**
     * Retrieves the cached content for a given post ID.
     */
    public function get(int $postId): string|false
    {
        $key = $this->buildKey($postId);
        return get_transient($key);
    }

    /**
     * Stores the HTML content in a transient and tracks the key in post meta.
     */
    public function set(int $postId, string $content): bool
    {
        $key = $this->buildKey($postId);
        
        // Save the transient for 30 days
        $result = set_transient($key, $content, 30 * DAY_IN_SECONDS);
        
        if ($result) {
            // Keep track of the key for purging later
            update_post_meta($postId, self::CACHE_META_KEY, $key);
        }
        
        return $result;
    }

    /**
     * Purges the cache for a given post ID.
     * Tied to save_post and post_updated hooks.
     */
    public function purge(int $postId, $post = null, $update = false): void
    {
        // Don't purge for autosaves or revisions
        if (wp_is_post_revision($postId) || wp_is_post_autosave($postId)) {
            return;
        }

        // Fetch the active cache key
        $oldKey = get_post_meta($postId, self::CACHE_META_KEY, true);
        
        if ($oldKey && is_string($oldKey)) {
            delete_transient($oldKey);
        }
    }
}
