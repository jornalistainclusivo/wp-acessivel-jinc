<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\MediaGatekeeper\AltTextValidator;
use WpAcessivelJinc\Modules\MediaGatekeeper\RestUploadValidator;
use WpAcessivelJinc\Utils\Logger;

/**
 * @spec-source docs/SPEC_MediaGatekeeper.md
 * @covers \WpAcessivelJinc\Modules\MediaGatekeeper\RestUploadValidator
 */
class RestUploadValidatorTest extends TestCase
{
    private RestUploadValidator $restValidator;

    protected function setUp(): void
    {
        jinc_reset_wp_stubs();
        $this->restValidator = new RestUploadValidator(
            new AltTextValidator(),
            new Logger(),
        );
    }

    // ── BR-MG-001: REST insert returns WP_Error on missing alt ──

    /** @test */
    public function it_returns_wp_error_when_image_has_no_alt_via_rest(): void
    {
        $prepared = new \stdClass();
        $prepared->post_mime_type = 'image/jpeg';

        $request = new \WP_REST_Request('POST', '/wp/v2/media', [
            'alt_text' => '',
        ]);

        $result = $this->restValidator->validateRestInsert($prepared, $request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('jinc_alt_text_missing', $result->get_error_code());
        $data = $result->get_error_data();
        $this->assertSame(403, $data['status']);
    }

    /** @test */
    public function it_passes_through_when_image_has_alt_via_rest(): void
    {
        $prepared = new \stdClass();
        $prepared->post_mime_type = 'image/jpeg';

        $request = new \WP_REST_Request('POST', '/wp/v2/media', [
            'alt_text' => 'Valid description of the image',
        ]);

        $result = $this->restValidator->validateRestInsert($prepared, $request);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($prepared, $result);
    }

    /** @test */
    public function it_passes_through_for_non_image_uploads_via_rest(): void
    {
        $prepared = new \stdClass();
        $prepared->post_mime_type = 'application/pdf';

        $request = new \WP_REST_Request('POST', '/wp/v2/media', [
            'alt_text' => '',
        ]);

        $result = $this->restValidator->validateRestInsert($prepared, $request);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($prepared, $result);
    }

    /** @test */
    public function it_passes_through_when_existing_wp_error_is_present(): void
    {
        $existingError = new \WP_Error('previous_error', 'A previous filter blocked this');

        $request = new \WP_REST_Request('POST', '/wp/v2/media', [
            'alt_text' => '',
        ]);

        $result = $this->restValidator->validateRestInsert($existingError, $request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('previous_error', $result->get_error_code());
    }

    /** @test */
    public function it_accepts_decorativo_keyword_via_rest(): void
    {
        $prepared = new \stdClass();
        $prepared->post_mime_type = 'image/png';

        $request = new \WP_REST_Request('POST', '/wp/v2/media', [
            'alt_text' => 'decorativo',
        ]);

        $result = $this->restValidator->validateRestInsert($prepared, $request);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($prepared, $result);
    }

    /** @test */
    public function it_blocks_whitespace_only_alt_via_rest(): void
    {
        $prepared = new \stdClass();
        $prepared->post_mime_type = 'image/webp';

        $request = new \WP_REST_Request('POST', '/wp/v2/media', [
            'alt_text' => '   ',
        ]);

        $result = $this->restValidator->validateRestInsert($prepared, $request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('jinc_alt_text_missing', $result->get_error_code());
    }

    /** @test */
    public function it_handles_missing_alt_text_param(): void
    {
        $prepared = new \stdClass();
        $prepared->post_mime_type = 'image/jpeg';

        $request = new \WP_REST_Request('POST', '/wp/v2/media', []);

        $result = $this->restValidator->validateRestInsert($prepared, $request);

        $this->assertInstanceOf(\WP_Error::class, $result);
        $this->assertSame('jinc_alt_text_missing', $result->get_error_code());
    }

    /** @test */
    public function it_approves_upload_and_injects_quarantine_if_ai_is_active(): void
    {
        global $_jinc_options, $_jinc_post_meta;
        $_jinc_options['jinc_theme_options'] = [
            'descreveai_active' => true,
            'descreveai_endpoint' => 'https://mock.api',
            'descreveai_api_key' => 'key',
            'descreveai_timeout' => 15
        ];

        $prepared = new \stdClass();
        $prepared->post_mime_type = 'image/jpeg';
        $prepared->ID = 999;
        
        $request = new \WP_REST_Request('POST', '/wp/v2/media', [
            'alt_text' => '',
        ]);

        $result = $this->restValidator->validateRestInsert($prepared, $request);

        $this->assertInstanceOf(\stdClass::class, $result);
        $this->assertSame($prepared, $result);
        $this->assertEquals('[JINC: Processando IA...]', $_jinc_post_meta[999]['_wp_attachment_image_alt']);
        $this->assertEquals('pending', $_jinc_post_meta[999]['_jinc_ai_status']);
    }
}
