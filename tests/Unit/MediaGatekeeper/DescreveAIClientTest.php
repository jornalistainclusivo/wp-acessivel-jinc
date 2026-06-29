<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit\MediaGatekeeper;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\MediaGatekeeper\DescreveAIClient;

class DescreveAIClientTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        if (function_exists('jinc_reset_wp_stubs')) {
            jinc_reset_wp_stubs();
        }
    }

    protected function tearDown(): void
    {
        if (function_exists('jinc_reset_wp_stubs')) {
            jinc_reset_wp_stubs();
        }
        parent::tearDown();
    }

    public function testAnalyzeReturnsAltOnSuccess(): void
    {
        add_filter('pre_http_request', function ($preempt, $args, $url) {
            $this->assertArrayHasKey('Authorization', $args['headers']);
            $this->assertEquals('Bearer mock-api-key', $args['headers']['Authorization']);
            
            $this->assertArrayHasKey('Content-Type', $args['headers']);
            $this->assertStringContainsString('multipart/form-data; boundary=---JINC', $args['headers']['Content-Type']);
            
            // Mock success response
            return [
                'headers'  => [],
                'body'     => json_encode(["success" => true, "data" => ["description" => "Gato preto pulando"]]),
                'response' => ['code' => 200, 'message' => 'OK'],
                'cookies'  => [],
                'filename' => null,
            ];
        }, 10, 3);

        $client = new DescreveAIClient();
        
        $dummyFile = sys_get_temp_dir() . '/dummy.jpg';
        file_put_contents($dummyFile, 'dummy image content');

        $result = $client->analyze($dummyFile, 'https://mock.descreveai.com', 'mock-api-key', 15);

        unlink($dummyFile);

        $this->assertTrue($result['success']);
        $this->assertEquals('Gato preto pulando', $result['alt']);
        $this->assertEquals(200, $result['status_code']);
    }

    public function testAnalyzeReturnsErrorOnTimeout(): void
    {
        add_filter('pre_http_request', function ($preempt, $args, $url) {
            return new \WP_Error('http_request_failed', 'Timeout');
        }, 10, 3);

        $client = new DescreveAIClient();
        
        $dummyFile = sys_get_temp_dir() . '/dummy.jpg';
        file_put_contents($dummyFile, 'dummy image content');

        $result = $client->analyze($dummyFile, 'https://mock.descreveai.com', 'mock-api-key', 15);

        unlink($dummyFile);

        $this->assertFalse($result['success']);
        $this->assertEquals('Timeout', $result['error']);
        $this->assertEquals(500, $result['status_code']);
    }
}
