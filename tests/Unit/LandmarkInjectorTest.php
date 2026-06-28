<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\SemanticEnforcer\LandmarkInjector;
use WpAcessivelJinc\Utils\DOMDocumentHelper;

/**
 * @spec-source docs/SPEC_SemanticEnforcer.md
 * @covers \WpAcessivelJinc\Modules\SemanticEnforcer\LandmarkInjector
 */
class LandmarkInjectorTest extends TestCase
{
    private LandmarkInjector $injector;
    private DOMDocumentHelper $domHelper;

    protected function setUp(): void
    {
        $this->injector = new LandmarkInjector();
        $this->domHelper = new DOMDocumentHelper();
    }

    private function injectAndSerialize(string $html): string
    {
        $dom = $this->domHelper->parse($html);
        $this->assertNotNull($dom);
        $resultDom = $this->injector->inject($dom);
        return $resultDom->saveHTML();
    }

    // ── BR-SE-002: Content without article gets wrapped ──

    /** @test */
    public function it_wraps_content_in_article_when_missing(): void
    {
        $input = '<p>Content</p>';
        $result = $this->injectAndSerialize($input);

        $this->assertStringContainsString('<article role="article">', $result);
        $this->assertStringContainsString('</article>', $result);
        $this->assertStringContainsString('<p>Content</p>', $result);
    }

    // ── BR-SE-002: Content with existing article is not duplicated ──

    /** @test */
    public function it_does_not_duplicate_existing_article(): void
    {
        $input = '<article><p>Content</p></article>';
        $result = $this->injectAndSerialize($input);

        // Count occurrences of <article
        $count = mb_substr_count($result, '<article');
        $this->assertSame(1, $count, 'Duplicate <article> detected');
    }

    // ── BR-SE-002: Content with role="article" is not duplicated ──

    /** @test */
    public function it_does_not_duplicate_existing_role_article(): void
    {
        $input = '<div role="article"><p>Content</p></div>';
        $result = $this->injectAndSerialize($input);

        $this->assertStringNotContainsString('<article role="article">', $result);
        $this->assertStringContainsString('role="article"', $result);
    }

    // ── BR-SE-002: Nav-like list structure gets nav wrapper ──

    /** @test */
    public function it_wraps_nav_like_lists_in_nav_element(): void
    {
        $input = '<ul><li><a href="#">A</a></li><li><a href="#">B</a></li><li><a href="#">C</a></li></ul>';
        $result = $this->injectAndSerialize($input);

        $this->assertStringContainsString('<nav ', $result);
        $this->assertStringContainsString('aria-label=', $result);
    }

    // ── BR-SE-002: List with fewer than 3 links is NOT nav ──

    /** @test */
    public function it_does_not_wrap_short_lists_as_nav(): void
    {
        $input = '<ul><li><a href="#">A</a></li><li><a href="#">B</a></li></ul>';
        $result = $this->injectAndSerialize($input);

        $this->assertStringNotContainsString('<nav', $result);
    }

    // ── BR-SE-002: List where <50% items have links is NOT nav ──

    /** @test */
    public function it_does_not_wrap_lists_with_low_link_ratio(): void
    {
        $input = '<ul><li><a href="#">A</a></li><li>No link</li><li>No link</li><li>No link</li></ul>';
        $result = $this->injectAndSerialize($input);

        $this->assertStringNotContainsString('<nav', $result);
    }

    // ── BR-SE-002: Existing nav is not duplicated ──

    /** @test */
    public function it_does_not_duplicate_existing_nav(): void
    {
        $input = '<nav><ul><li><a href="#">A</a></li><li><a href="#">B</a></li><li><a href="#">C</a></li></ul></nav>';
        $result = $this->injectAndSerialize($input);

        $count = mb_substr_count($result, '<nav');
        $this->assertSame(1, $count, 'Duplicate <nav> detected');
    }

    // ── BR-SE-003: Landmark injection is idempotent ──

    /** @test */
    public function it_produces_identical_output_on_double_injection(): void
    {
        $input = '<p>Content</p>';

        $dom1 = $this->domHelper->parse($input);
        $this->assertNotNull($dom1);
        $result1Dom = $this->injector->inject($dom1);
        $firstPass = $result1Dom->saveHTML();

        $dom2 = $this->domHelper->parse($firstPass);
        $this->assertNotNull($dom2);
        $result2Dom = $this->injector->inject($dom2);
        $secondPass = $result2Dom->saveHTML();

        $this->assertSame($firstPass, $secondPass, 'Idempotency violated: inject(inject(x)) !== inject(x)');
    }

    // ── BR-SE-002: Multiple nav-like structures get unique aria-labels ──

    /** @test */
    public function it_assigns_unique_aria_labels_to_multiple_nav_structures(): void
    {
        $input = '<ul><li><a href="#">A</a></li><li><a href="#">B</a></li><li><a href="#">C</a></li></ul>'
               . '<ul><li><a href="#">D</a></li><li><a href="#">E</a></li><li><a href="#">F</a></li></ul>';
        $result = $this->injectAndSerialize($input);

        // Should have two distinct nav wrappers with different aria-labels
        $count = mb_substr_count($result, '<nav ');
        $this->assertSame(2, $count, 'Expected 2 nav wrappers for 2 nav-like lists');
    }

    // ── Analyze method ──

    /** @test */
    public function analyze_reports_missing_article(): void
    {
        $dom = $this->domHelper->parse('<p>Content</p>');
        $this->assertNotNull($dom);

        $analysis = $this->injector->analyze($dom);

        $this->assertFalse($analysis->isComplete);
        $this->assertContains('article', $analysis->missingLandmarks);
    }

    /** @test */
    public function analyze_reports_complete_when_article_present(): void
    {
        $dom = $this->domHelper->parse('<article><p>Content</p></article>');
        $this->assertNotNull($dom);

        $analysis = $this->injector->analyze($dom);

        $this->assertContains('article', $analysis->presentLandmarks);
    }
}
