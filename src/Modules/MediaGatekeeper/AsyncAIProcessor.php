<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\MediaGatekeeper;

use WpAcessivelJinc\Utils\Logger;

/**
 * Handles the asynchronous execution of DescreveAI.
 */
class AsyncAIProcessor
{
    public function __construct(
        private readonly Logger $logger,
    ) {}

    public function register(): void
    {
        if (function_exists('add_action')) {
            add_action('wp_ajax_jinc_process_ai', [$this, 'process']);
            add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        }
    }

    public function enqueueScripts(string $hook): void
    {
        wp_enqueue_script(
            'jinc-media-ai',
            plugin_dir_url(__FILE__) . '../../../assets/js/jinc-media-ai.js',
            ['jquery'],
            '1.2.0',
            true
        );

        wp_localize_script('jinc-media-ai', 'jincAiData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce'   => wp_create_nonce('jinc_process_ai_nonce'),
        ]);
    }

    public function process(): void
    {
        if (!isset($_POST['attachment_id'])) {
            wp_send_json_error(['message' => 'Attachment ID missing.']);
            return; // Usually unreachable after wp_send_json_error if running natively
        }

        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'jinc_process_ai_nonce')) {
            wp_send_json_error(['message' => 'Invalid nonce.']);
            return;
        }

        $attachmentId = (int) $_POST['attachment_id'];

        $aiStatus = get_post_meta($attachmentId, '_jinc_ai_status', true);
        if ($aiStatus !== 'pending') {
            wp_send_json_error(['message' => 'Not in pending state.']);
            return;
        }

        $filePath = get_attached_file($attachmentId);
        if (!$filePath || !file_exists($filePath)) {
            wp_send_json_error(['message' => 'File not found.']);
            return;
        }

        $options = get_option('jinc_theme_options', []);
        $endpoint = $options['descreveai_endpoint'] ?? '';
        $apiKey = $options['descreveai_api_key'] ?? '';
        $timeout = (int) ($options['descreveai_timeout'] ?? 15);

        $client = new DescreveAIClient();
        $aiResult = $client->analyze($filePath, $endpoint, $apiKey, $timeout);

        if (isset($aiResult['success']) && $aiResult['success'] === true && !empty($aiResult['alt'])) {
            update_post_meta($attachmentId, '_wp_attachment_image_alt', $aiResult['alt']);
            delete_post_meta($attachmentId, '_jinc_ai_status');

            if (!empty($aiResult['description'])) {
                wp_update_post([
                    'ID' => $attachmentId,
                    'post_content' => $aiResult['description'],
                ]);
            }

            $this->logger->info('AJAX AI processing success', ['attachment_id' => $attachmentId]);
            wp_send_json_success([
                'message' => 'AI processing complete.',
                'alt' => $aiResult['alt'],
                'description' => $aiResult['description'] ?? '',
            ]);
            return;
        }

        update_post_meta($attachmentId, '_jinc_ai_status', 'failed');
        $this->logger->warning('AJAX AI processing failed', [
            'attachment_id' => $attachmentId, 
            'error' => $aiResult['error'] ?? 'Unknown error'
        ]);
        wp_send_json_error(['message' => 'AI processing failed.']);
    }
}
