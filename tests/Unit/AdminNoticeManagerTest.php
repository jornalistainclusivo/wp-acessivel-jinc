<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\MediaGatekeeper\AdminNoticeManager;

/**
 * @spec-source docs/SPEC_MediaGatekeeper.md
 * @covers \WpAcessivelJinc\Modules\MediaGatekeeper\AdminNoticeManager
 */
class AdminNoticeManagerTest extends TestCase
{
    private AdminNoticeManager $manager;

    protected function setUp(): void
    {
        jinc_reset_wp_stubs();
        $this->manager = new AdminNoticeManager();
    }

    // ── BR-MG-003: Notice rendering ──

    /** @test */
    public function it_renders_consolidated_notice_for_multiple_attachments(): void
    {
        global $_jinc_posts;
        $_jinc_posts[42] = ['title' => 'foto-evento.jpg', 'mime_type' => 'image/jpeg'];
        $_jinc_posts[55] = ['title' => 'banner-home.png', 'mime_type' => 'image/png'];

        $this->manager->queueNotice(42);
        $this->manager->queueNotice(55);

        ob_start();
        $this->manager->displayPendingNotices();
        $output = ob_get_clean();

        $this->assertStringContainsString('notice notice-warning is-dismissible', $output);
        $this->assertStringContainsString('role="alert"', $output);
        $this->assertStringContainsString('aria-live="polite"', $output);
        $this->assertStringContainsString('2 imagem(ns)', $output);
        $this->assertStringContainsString('foto-evento.jpg', $output);
        $this->assertStringContainsString('banner-home.png', $output);
        $this->assertStringContainsString('ID: 42', $output);
        $this->assertStringContainsString('ID: 55', $output);
    }

    /** @test */
    public function it_renders_nothing_when_no_pending_notices(): void
    {
        ob_start();
        $this->manager->displayPendingNotices();
        $output = ob_get_clean();

        $this->assertSame('', $output);
    }

    /** @test */
    public function it_clears_specific_attachment_from_notice_list(): void
    {
        $this->manager->queueNotice(42);
        $this->manager->queueNotice(55);

        $this->manager->clearNoticeForAttachment(42);

        $transient = get_transient('jinc_mg_notices_1');
        $this->assertIsArray($transient);
        $this->assertNotContains(42, $transient);
        $this->assertContains(55, $transient);
    }

    /** @test */
    public function it_clears_all_notices_for_current_user(): void
    {
        $this->manager->queueNotice(42);
        $this->manager->queueNotice(55);

        $this->manager->clearAllNotices();

        $transient = get_transient('jinc_mg_notices_1');
        $this->assertFalse($transient);
    }

    /** @test */
    public function it_does_not_duplicate_attachment_ids_in_queue(): void
    {
        $this->manager->queueNotice(42);
        $this->manager->queueNotice(42);

        $transient = get_transient('jinc_mg_notices_1');
        $this->assertIsArray($transient);
        $this->assertCount(1, $transient);
        $this->assertSame(42, $transient[0]);
    }

    // ── Accessibility of notice HTML ──

    /** @test */
    public function it_renders_notice_with_aria_attributes(): void
    {
        global $_jinc_posts;
        $_jinc_posts[42] = ['title' => 'test.jpg', 'mime_type' => 'image/jpeg'];

        $this->manager->queueNotice(42);

        ob_start();
        $this->manager->displayPendingNotices();
        $output = ob_get_clean();

        $this->assertStringContainsString('role="alert"', $output);
        $this->assertStringContainsString('aria-live="polite"', $output);
        $this->assertStringContainsString('Biblioteca de Mídia', $output);
    }

    /** @test */
    public function it_deletes_transient_when_last_attachment_is_cleared(): void
    {
        $this->manager->queueNotice(42);
        $this->manager->clearNoticeForAttachment(42);

        $transient = get_transient('jinc_mg_notices_1');
        $this->assertFalse($transient);
    }

    /** @test */
    public function it_renders_single_notice_for_one_attachment(): void
    {
        global $_jinc_posts;
        $_jinc_posts[42] = ['title' => 'logo.png', 'mime_type' => 'image/png'];

        $this->manager->queueNotice(42);

        ob_start();
        $this->manager->displayPendingNotices();
        $output = ob_get_clean();

        $this->assertStringContainsString('1 imagem(ns)', $output);
        $this->assertStringContainsString('logo.png', $output);
    }
}
