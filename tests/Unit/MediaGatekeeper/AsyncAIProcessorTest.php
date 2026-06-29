<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit\MediaGatekeeper;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\MediaGatekeeper\AsyncAIProcessor;
use WpAcessivelJinc\Utils\Logger;

/**
 * @covers \WpAcessivelJinc\Modules\MediaGatekeeper\AsyncAIProcessor
 */
class AsyncAIProcessorTest extends TestCase
{
    private AsyncAIProcessor $processor;

    protected function setUp(): void
    {
        jinc_reset_wp_stubs();
        $this->processor = new AsyncAIProcessor(new Logger());
    }

    /** @test */
    public function it_processes_ai_success(): void
    {
        global $_jinc_options, $_jinc_post_meta, $_jinc_posts;

        $_jinc_options['jinc_theme_options'] = [
            'descreveai_active' => true,
            'descreveai_endpoint' => 'https://mock.api',
            'descreveai_api_key' => 'key',
            'descreveai_timeout' => 15
        ];

        $attachmentId = 42;
        $_POST['attachment_id'] = $attachmentId;
        $_POST['nonce'] = 'dummy_nonce';
        
        $tmpName = sys_get_temp_dir() . '/dummy_async_process.jpg';
        file_put_contents($tmpName, 'dummy');
        
        $_jinc_post_meta[$attachmentId]['_jinc_ai_status'] = 'pending';
        $_jinc_post_meta[$attachmentId]['_wp_attached_file'] = $tmpName;

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            return [
                'headers'  => [],
                'body'     => json_encode([
                    "success" => true, 
                    "data" => [
                        "description" => "A long description from AI",
                        "alt" => "A short alt from AI"
                    ]
                ]),
                'response' => ['code' => 200, 'message' => 'OK'],
            ];
        }, 10, 3);

        ob_start();
        $this->processor->process();
        $jsonResponse = ob_get_clean();

        unlink($tmpName);

        $this->assertStringContainsString('"success":true', $jsonResponse);
        
        // Assertions
        $this->assertEquals('A short alt from AI', $_jinc_post_meta[$attachmentId]['_wp_attachment_image_alt']);
        $this->assertArrayNotHasKey('_jinc_ai_status', $_jinc_post_meta[$attachmentId]);
        $this->assertEquals('A long description from AI', $_jinc_posts[$attachmentId]['post_content']);
    }
    
    /** @test */
    public function it_fails_if_not_pending(): void
    {
        global $_jinc_post_meta;
        
        $attachmentId = 42;
        $_POST['attachment_id'] = $attachmentId;
        $_POST['nonce'] = 'dummy_nonce';
        $_jinc_post_meta[$attachmentId]['_jinc_ai_status'] = 'failed';
        
        ob_start();
        $this->processor->process();
        $jsonResponse = ob_get_clean();
        
        $this->assertStringContainsString('"success":false', $jsonResponse);
        $this->assertStringContainsString('Not in pending state', $jsonResponse);
    }

    /** @test */
    public function it_processes_ai_failure(): void
    {
        global $_jinc_options, $_jinc_post_meta;

        $_jinc_options['jinc_theme_options'] = [
            'descreveai_active' => true,
        ];

        $attachmentId = 43;
        $_POST['attachment_id'] = $attachmentId;
        $_POST['nonce'] = 'dummy_nonce';
        
        $tmpName = sys_get_temp_dir() . '/dummy_async_process_fail.jpg';
        file_put_contents($tmpName, 'dummy');
        
        $_jinc_post_meta[$attachmentId]['_jinc_ai_status'] = 'pending';
        $_jinc_post_meta[$attachmentId]['_wp_attached_file'] = $tmpName;

        add_filter('pre_http_request', function ($preempt, $args, $url) {
            return new \WP_Error('http_request_failed', 'Timeout');
        }, 10, 3);

        ob_start();
        $this->processor->process();
        $jsonResponse = ob_get_clean();

        unlink($tmpName);

        $this->assertStringContainsString('"success":false', $jsonResponse);
        $this->assertEquals('failed', $_jinc_post_meta[$attachmentId]['_jinc_ai_status']);
    }
}
