<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

use WpAcessivelJinc\Utils\Logger;

/**
 * Intercepts REST API attachment creation to enforce alt text on images.
 * Returns WP_Error to Gutenberg when alt text is missing.
 * Blocking behavior is ABSOLUTE — no configuration toggle.
 *
 * @spec-ref FR-010, BR-MG-001
 */
final class RestUploadValidator
{
    public function __construct(
        private readonly AltTextValidator $validator,
        private readonly Logger $logger,
    ) {}

    /**
     * Register the REST API filter.
     */
    public function register(): void
    {
        if (function_exists('add_filter')) {
            add_filter('rest_pre_insert_attachment', [$this, 'validateRestInsert'], 10, 2);
        }
    }

    /**
     * Filter callback for rest_pre_insert_attachment.
     *
     * @param \stdClass|\WP_Error $prepared Prepared post data or existing WP_Error.
     * @param \WP_REST_Request $request The REST request object.
     * @return \stdClass|\WP_Error Pass-through or error.
     */
    public function validateRestInsert(
        \stdClass|\WP_Error $prepared,
        \WP_REST_Request $request,
    ): \stdClass|\WP_Error {
        // If already an error from a previous filter, pass through
        if ($prepared instanceof \WP_Error) {
            return $prepared;
        }

        $altText = '';
        if ($request->has_param('alt_text')) {
            $param = $request->get_param('alt_text');
            $altText = is_string($param) ? $param : '';
        }

        $mimeType = '';
        if (isset($prepared->post_mime_type) && is_string($prepared->post_mime_type)) {
            $mimeType = $prepared->post_mime_type;
        }

        $result = $this->validator->validateRaw(
            altText: $altText,
            mimeType: $mimeType,
        );

        if ($result->isBlocking()) {
            $this->logger->warning(
                'REST upload blocked: image missing alt text',
                ['mime_type' => $mimeType],
            );

            return new \WP_Error(
                'jinc_alt_text_missing',
                $result->message,
                ['status' => 403],
            );
        }

        return $prepared;
    }
}
