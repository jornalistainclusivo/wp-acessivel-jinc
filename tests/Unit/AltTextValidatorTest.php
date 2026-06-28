<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\MediaGatekeeper\AltTextValidator;
use WpAcessivelJinc\Modules\MediaGatekeeper\AltTextValidationResult;
use WpAcessivelJinc\Modules\MediaGatekeeper\AltTextStatus;

/**
 * @spec-source docs/SPEC_MediaGatekeeper.md
 * @covers \WpAcessivelJinc\Modules\MediaGatekeeper\AltTextValidator
 */
class AltTextValidatorTest extends TestCase
{
    private AltTextValidator $validator;

    protected function setUp(): void
    {
        $this->validator = new AltTextValidator();
    }

    // ── BR-MG-001: Image with valid alt text passes ──

    /** @test */
    public function it_returns_valid_for_image_with_alt_text(): void
    {
        $result = $this->validator->validateRaw(
            altText: 'Um gato preto',
            mimeType: 'image/jpeg',
        );

        $this->assertSame(AltTextStatus::VALID, $result->status);
        $this->assertSame('Um gato preto', $result->altText);
        $this->assertFalse($result->isBlocking());
    }

    // ── BR-MG-001: Image with empty alt text returns MISSING ──

    /** @test */
    public function it_returns_missing_for_image_without_alt_text(): void
    {
        $result = $this->validator->validateRaw(
            altText: '',
            mimeType: 'image/png',
        );

        $this->assertSame(AltTextStatus::MISSING, $result->status);
        $this->assertTrue($result->isBlocking());
        $this->assertSame('image/png', $result->mimeType);
    }

    // ── BR-MG-001: Whitespace-only alt text is treated as empty ──

    /** @test */
    public function it_returns_missing_for_whitespace_only_alt_text(): void
    {
        $result = $this->validator->validateRaw(
            altText: "   \t  ",
            mimeType: 'image/jpeg',
        );

        $this->assertSame(AltTextStatus::MISSING, $result->status);
        $this->assertTrue($result->isBlocking());
    }

    // ── BR-MG-001: Non-image files bypass validation ──

    /** @test */
    public function it_returns_skipped_for_non_image_mime_types(): void
    {
        $result = $this->validator->validateRaw(
            altText: '',
            mimeType: 'application/pdf',
        );

        $this->assertSame(AltTextStatus::SKIPPED, $result->status);
        $this->assertFalse($result->isBlocking());
    }

    /** @test */
    public function it_returns_skipped_for_video_mime_types(): void
    {
        $result = $this->validator->validateRaw(
            altText: '',
            mimeType: 'video/mp4',
        );

        $this->assertSame(AltTextStatus::SKIPPED, $result->status);
        $this->assertFalse($result->isBlocking());
    }

    // ── BR-MG-002: Decorative images bypass with empty alt ──

    /** @test */
    public function it_returns_decorative_when_flag_is_set(): void
    {
        $result = $this->validator->validateRaw(
            altText: '',
            mimeType: 'image/png',
            isDecorative: true,
        );

        $this->assertSame(AltTextStatus::DECORATIVE, $result->status);
        $this->assertFalse($result->isBlocking());
        $this->assertSame('', $result->altText);
    }

    /** @test */
    public function it_returns_valid_even_if_decorative_and_alt_present(): void
    {
        $result = $this->validator->validateRaw(
            altText: 'Some text',
            mimeType: 'image/png',
            isDecorative: true,
        );

        // Alt text takes precedence — it is treated as VALID, not DECORATIVE
        $this->assertSame(AltTextStatus::VALID, $result->status);
        $this->assertSame('Some text', $result->altText);
    }

    // ── BR-MG-002: Semantic bypass — "decorativo" keyword ──

    /** @test */
    public function it_returns_decorative_for_decorativo_keyword(): void
    {
        $result = $this->validator->validateRaw(
            altText: 'decorativo',
            mimeType: 'image/jpeg',
        );

        $this->assertSame(AltTextStatus::DECORATIVE, $result->status);
        $this->assertSame('', $result->altText); // alt cleared
        $this->assertFalse($result->isBlocking());
    }

    /** @test */
    public function it_returns_decorative_for_decorativo_case_insensitive(): void
    {
        $result = $this->validator->validateRaw(
            altText: 'DECORATIVO',
            mimeType: 'image/png',
        );

        $this->assertSame(AltTextStatus::DECORATIVE, $result->status);
        $this->assertSame('', $result->altText);
    }

    /** @test */
    public function it_returns_decorative_for_decorativo_with_whitespace(): void
    {
        $result = $this->validator->validateRaw(
            altText: '  Decorativo  ',
            mimeType: 'image/webp',
        );

        $this->assertSame(AltTextStatus::DECORATIVE, $result->status);
        $this->assertSame('', $result->altText);
    }

    /** @test */
    public function it_does_not_treat_decorativo_as_keyword_for_non_images(): void
    {
        $result = $this->validator->validateRaw(
            altText: 'decorativo',
            mimeType: 'application/pdf',
        );

        $this->assertSame(AltTextStatus::SKIPPED, $result->status);
    }

    // ── BR-MG-001: SVG images are validated ──

    /** @test */
    public function it_validates_svg_images(): void
    {
        $result = $this->validator->validateRaw(
            altText: '',
            mimeType: 'image/svg+xml',
        );

        $this->assertSame(AltTextStatus::MISSING, $result->status);
    }

    // ── BR-MG-001: WebP images are validated ──

    /** @test */
    public function it_validates_webp_images(): void
    {
        $result = $this->validator->validateRaw(
            altText: 'A landscape photo',
            mimeType: 'image/webp',
        );

        $this->assertSame(AltTextStatus::VALID, $result->status);
    }

    // ── Idempotency ──

    /** @test */
    public function it_produces_identical_result_on_repeated_validation(): void
    {
        $result1 = $this->validator->validateRaw('Test alt', 'image/jpeg');
        $result2 = $this->validator->validateRaw('Test alt', 'image/jpeg');

        $this->assertSame($result1->status, $result2->status);
        $this->assertSame($result1->altText, $result2->altText);
        $this->assertSame($result1->message, $result2->message);
    }

    // ── AltTextValidationResult::isBlocking ──

    /** @test */
    public function is_blocking_returns_true_only_for_missing(): void
    {
        $missing = new AltTextValidationResult(
            AltTextStatus::MISSING, 1, 'image/jpeg', '', 'test',
        );
        $valid = new AltTextValidationResult(
            AltTextStatus::VALID, 1, 'image/jpeg', 'alt', 'test',
        );
        $decorative = new AltTextValidationResult(
            AltTextStatus::DECORATIVE, 1, 'image/png', '', 'test',
        );
        $skipped = new AltTextValidationResult(
            AltTextStatus::SKIPPED, 1, 'application/pdf', '', 'test',
        );

        $this->assertTrue($missing->isBlocking());
        $this->assertFalse($valid->isBlocking());
        $this->assertFalse($decorative->isBlocking());
        $this->assertFalse($skipped->isBlocking());
    }

    // ── Value object immutability ──

    /** @test */
    public function result_preserves_attachment_id(): void
    {
        $result = $this->validator->validateRaw(
            altText: 'Test',
            mimeType: 'image/jpeg',
            attachmentId: 42,
        );

        $this->assertSame(42, $result->attachmentId);
        $this->assertSame('image/jpeg', $result->mimeType);
    }
}
