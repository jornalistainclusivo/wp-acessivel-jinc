<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

use WpAcessivelJinc\Utils\CacheManager;
use WpAcessivelJinc\Utils\DOMDocumentHelper;
use WpAcessivelJinc\Utils\Logger;

/**
 * Entry point for Semantic Enforcer module.
 * Registers the_content filter and orchestrates DOM processing pipeline.
 *
 * @spec-ref FR-001, FR-002, FR-004
 */
final class ContentFilter
{
    /** Maximum content size in bytes before skipping processing (performance guard). */
    private const MAX_CONTENT_BYTES = 512_000; // 500KB

    public function __construct(
        private readonly HeadingHierarchyFixer $headingFixer,
        private readonly LandmarkInjector $landmarkInjector,
        private readonly DOMDocumentHelper $domHelper,
        private readonly DOMSerializer $serializer,
        private readonly CacheManager $cache,
        private readonly Logger $logger,
    ) {}

    /**
     * Register the_content filter.
     * Priority: PHP_INT_MAX - 10 (ADR-005).
     */
    public function register(): void
    {
        if (function_exists('add_filter')) {
            add_filter('the_content', [$this, 'filterContent'], PHP_INT_MAX - 10);
        }
    }

    /**
     * Main filter callback. Processes HTML content through the enforcement pipeline.
     *
     * @param string $content Raw HTML content from the_content filter chain.
     * @return string Processed HTML with corrected headings and ARIA landmarks.
     */
    public function filterContent(string $content): string
    {
        // Short-circuit: empty or whitespace-only content
        if (trim($content) === '') {
            return $content;
        }

        // Performance guard: skip oversized content
        if (strlen($content) > self::MAX_CONTENT_BYTES) {
            $this->logger->warning('JINC_CONTENT_TOO_LARGE', [
                'content_length' => strlen($content),
                'max_bytes' => self::MAX_CONTENT_BYTES,
            ]);
            return $content;
        }

        // Cache check
        $postId = $this->getCurrentPostId();
        $postModified = $this->getCurrentPostModified();

        if ($postId > 0 && $postModified !== '') {
            $cached = $this->cache->get($postId, $postModified);
            if ($cached !== null) {
                $this->logger->debug('Cache HIT', ['post_id' => $postId]);
                return $cached;
            }
        }

        // DOM Processing Pipeline
        try {
            $dom = $this->domHelper->parse($content);

            if ($dom === null) {
                $this->logger->error('JINC_DOM_PARSE_ERROR', [
                    'post_id' => $postId,
                    'parse_errors' => $this->domHelper->getParseErrors(),
                ]);
                return $content; // Return original on parse failure
            }

            // Step 1: Fix heading hierarchy
            $dom = $this->headingFixer->fix($dom);

            // Step 2: Inject ARIA landmarks
            $dom = $this->landmarkInjector->inject($dom);

            // Step 3: Serialize back to HTML
            $result = $this->serializer->serialize($dom);

            // Cache store
            if ($postId > 0 && $postModified !== '') {
                $this->cache->set($postId, $postModified, $result);
                $this->logger->debug('Cache STORE', ['post_id' => $postId]);
            }

            return $result;
        } catch (\Throwable $e) {
            $this->logger->error('JINC_DOM_PARSE_ERROR', [
                'post_id' => $postId,
                'exception' => $e->getMessage(),
            ]);
            return $content; // Return original on any error
        }
    }

    /**
     * Get current post ID from WordPress global context.
     */
    private function getCurrentPostId(): int
    {
        if (function_exists('get_the_ID')) {
            return (int) get_the_ID();
        }
        return 0;
    }

    /**
     * Get current post's modified date from WordPress global context.
     */
    private function getCurrentPostModified(): string
    {
        if (function_exists('get_the_modified_date')) {
            return (string) get_the_modified_date('Y-m-d H:i:s');
        }
        return '';
    }
}
