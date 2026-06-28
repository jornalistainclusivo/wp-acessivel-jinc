<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\MediaGatekeeper\AltTextValidator;
use WpAcessivelJinc\Modules\MediaGatekeeper\AdminNoticeManager;
use WpAcessivelJinc\Modules\MediaGatekeeper\AttachmentMetaFilter;
use WpAcessivelJinc\Utils\Logger;

/**
 * @spec-source docs/SPEC_MediaGatekeeper.md
 * @covers \WpAcessivelJinc\Modules\MediaGatekeeper\AttachmentMetaFilter
 */
class AttachmentMetaFilterTest extends TestCase
{
    private AttachmentMetaFilter $filter;
    private AdminNoticeManager $noticeManager;

    protected function setUp(): void
    {
        jinc_reset_wp_stubs();
        $this->noticeManager = new AdminNoticeManager();
        $this->filter = new AttachmentMetaFilter(
            new AltTextValidator(),
            $this->noticeManager,
            new Logger(),
        );
    }

    // ── BR-MG-003: Classic upload queues admin notice ──

    /** @test */
    public function it_queues_notice_when_image_missing_alt_on_metadata_save(): void
    {
        global $_jinc_posts, $_jinc_post_meta;
        $_jinc_posts[42] = ['mime_type' => 'image/jpeg', 'title' => 'photo.jpg'];
        $_jinc_post_meta[42] = ['_wp_attachment_image_alt' => ''];

        $metadata = ['width' => 800, 'height' => 600, 'file' => 'photo.jpg'];
        $result = $this->filter->validateOnSave($metadata, 42);

        // Metadata must be returned unmodified
        $this->assertSame($metadata, $result);

        // Notice should be queued
        $transient = get_transient('jinc_mg_notices_1');
        $this->assertIsArray($transient);
        $this->assertContains(42, $transient);
    }

    /** @test */
    public function it_does_not_queue_notice_for_non_image_attachments(): void
    {
        global $_jinc_posts, $_jinc_post_meta;
        $_jinc_posts[43] = ['mime_type' => 'application/pdf', 'title' => 'report.pdf'];
        $_jinc_post_meta[43] = ['_wp_attachment_image_alt' => ''];

        $metadata = ['file' => 'report.pdf'];
        $this->filter->validateOnSave($metadata, 43);

        $transient = get_transient('jinc_mg_notices_1');
        $this->assertFalse($transient);
    }

    /** @test */
    public function it_returns_metadata_unmodified_always(): void
    {
        global $_jinc_posts, $_jinc_post_meta;
        $_jinc_posts[44] = ['mime_type' => 'image/png', 'title' => 'banner.png'];
        $_jinc_post_meta[44] = ['_wp_attachment_image_alt' => 'Valid alt text'];

        $metadata = ['width' => 1200, 'height' => 630, 'file' => 'banner.png'];
        $result = $this->filter->validateOnSave($metadata, 44);

        $this->assertSame($metadata, $result);
    }

    // ── BR-MG-002: Semantic bypass side-effects ──

    /** @test */
    public function it_stores_decorative_flag_on_decorativo_keyword(): void
    {
        global $_jinc_posts, $_jinc_post_meta;
        $_jinc_posts[45] = ['mime_type' => 'image/png', 'title' => 'divider.png'];
        $_jinc_post_meta[45] = ['_wp_attachment_image_alt' => 'Decorativo'];

        $this->filter->validateOnSave([], 45);

        // Alt should be cleared and decorative flag set
        $this->assertSame('', $_jinc_post_meta[45]['_wp_attachment_image_alt']);
        $this->assertSame('1', $_jinc_post_meta[45]['_jinc_decorative']);

        // No notice should be queued for decorative images
        $transient = get_transient('jinc_mg_notices_1');
        $this->assertFalse($transient);
    }

    /** @test */
    public function it_does_not_queue_notice_when_image_has_valid_alt(): void
    {
        global $_jinc_posts, $_jinc_post_meta;
        $_jinc_posts[46] = ['mime_type' => 'image/jpeg', 'title' => 'team-photo.jpg'];
        $_jinc_post_meta[46] = ['_wp_attachment_image_alt' => 'Equipe em reunião'];

        $this->filter->validateOnSave([], 46);

        $transient = get_transient('jinc_mg_notices_1');
        $this->assertFalse($transient);
    }
}
