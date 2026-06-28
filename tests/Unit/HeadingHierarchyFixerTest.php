<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\SemanticEnforcer\HeadingHierarchyFixer;
use WpAcessivelJinc\Modules\SemanticEnforcer\HeadingAnalysis;
use WpAcessivelJinc\Utils\DOMDocumentHelper;

/**
 * @spec-source docs/SPEC_SemanticEnforcer.md
 * @covers \WpAcessivelJinc\Modules\SemanticEnforcer\HeadingHierarchyFixer
 */
class HeadingHierarchyFixerTest extends TestCase
{
    private HeadingHierarchyFixer $fixer;
    private DOMDocumentHelper $domHelper;

    protected function setUp(): void
    {
        $this->fixer = new HeadingHierarchyFixer();
        $this->domHelper = new DOMDocumentHelper();
    }

    private function fixHtml(string $html): string
    {
        $dom = $this->domHelper->parse($html);
        $this->assertNotNull($dom, 'DOMDocumentHelper::parse() returned null');
        $fixedDom = $this->fixer->fix($dom);

        // Extract inner HTML of body (strip wrapper tags)
        $body = $fixedDom->getElementsByTagName('body')->item(0);
        if ($body === null) {
            // LIBXML_HTML_NOIMPLIED means there might not be a body wrapper
            return $fixedDom->saveHTML();
        }
        $inner = '';
        foreach ($body->childNodes as $child) {
            $inner .= $fixedDom->saveHTML($child);
        }
        return $inner;
    }

    // ── BR-SE-001: Heading skip H1→H4 remapped to H1→H2 ──

    /** @test */
    public function it_remaps_heading_level_skips(): void
    {
        $input = '<h1>A</h1><h4>B</h4>';
        $result = $this->fixHtml($input);

        $this->assertStringContainsString('<h1>A</h1>', $result);
        $this->assertStringContainsString('<h2>B</h2>', $result);
        $this->assertStringNotContainsString('<h4>', $result);
    }

    // ── BR-SE-001: Same-level siblings are not modified ──

    /** @test */
    public function it_preserves_same_level_siblings(): void
    {
        $input = '<h2>A</h2><h2>B</h2><h2>C</h2>';
        $result = $this->fixHtml($input);

        $this->assertStringContainsString('<h2>A</h2>', $result);
        $this->assertStringContainsString('<h2>B</h2>', $result);
        $this->assertStringContainsString('<h2>C</h2>', $result);
    }

    // ── BR-SE-001: Ascending levels (H3→H2) are valid ──

    /** @test */
    public function it_allows_heading_level_ascension(): void
    {
        $input = '<h2>A</h2><h3>B</h3><h2>C</h2>';
        $result = $this->fixHtml($input);

        $this->assertStringContainsString('<h2>A</h2>', $result);
        $this->assertStringContainsString('<h3>B</h3>', $result);
        $this->assertStringContainsString('<h2>C</h2>', $result);
    }

    // ── BR-SE-001: Double skip correction (H1→H3→H6) ──

    /** @test */
    public function it_corrects_multiple_consecutive_skips(): void
    {
        $input = '<h1>A</h1><h3>B</h3><h6>C</h6>';
        $result = $this->fixHtml($input);

        $this->assertStringContainsString('<h1>A</h1>', $result);
        $this->assertStringContainsString('<h2>B</h2>', $result);
        $this->assertStringContainsString('<h3>C</h3>', $result);
    }

    // ── BR-SE-001: Attributes (id, class, aria-*, data-*) preserved ──

    /** @test */
    public function it_preserves_attributes_on_remapped_headings(): void
    {
        $input = '<h1 id="title">A</h1><h4 class="sub" data-x="1">B</h4>';
        $result = $this->fixHtml($input);

        $this->assertStringContainsString('<h1 id="title">A</h1>', $result);
        // h4 should become h2, keeping attributes
        $this->assertStringContainsString('class="sub"', $result);
        $this->assertStringContainsString('data-x="1"', $result);
        $this->assertStringContainsString('<h2 ', $result);
        $this->assertStringNotContainsString('<h4', $result);
    }

    // ── BR-SE-001: Empty content (no headings) passes through ──

    /** @test */
    public function it_passes_through_content_without_headings(): void
    {
        $input = '<p>No headings here.</p>';
        $result = $this->fixHtml($input);

        $this->assertStringContainsString('<p>No headings here.</p>', $result);
    }

    // ── BR-SE-001: Base level H3 accepted ──

    /** @test */
    public function it_accepts_non_h1_base_level_and_fixes_skips(): void
    {
        $input = '<h3>A</h3><h5>B</h5>';
        $result = $this->fixHtml($input);

        $this->assertStringContainsString('<h3>A</h3>', $result);
        $this->assertStringContainsString('<h4>B</h4>', $result);
    }

    // ── BR-SE-001: Heading inside blockquote included in hierarchy ──

    /** @test */
    public function it_includes_headings_inside_blockquote_in_hierarchy(): void
    {
        $input = '<h1>Title</h1><blockquote><h4>Quote</h4></blockquote>';
        $result = $this->fixHtml($input);

        $this->assertStringContainsString('<h1>Title</h1>', $result);
        $this->assertStringContainsString('<h2>Quote</h2>', $result);
        $this->assertStringNotContainsString('<h4>', $result);
    }

    // ── BR-SE-003: Processing is idempotent ──

    /** @test */
    public function it_produces_identical_output_on_double_processing(): void
    {
        $input = '<h1>A</h1><h4>B</h4>';

        $firstPass = $this->fixHtml($input);
        // Parse the first pass result and fix again
        $secondPass = $this->fixHtml($firstPass);

        $this->assertSame($firstPass, $secondPass, 'Idempotency violated: fix(fix(x)) !== fix(x)');
    }

    // ── BR-SE-001: Full Gherkin happy path scenario ──

    /** @test */
    public function it_corrects_full_gherkin_happy_path(): void
    {
        $input = '<h1>Title</h1><p>Intro.</p><h4>Details</h4><p>More text.</p><h2>Conclusion</h2>';
        $result = $this->fixHtml($input);

        // h4 (skip from h1) → h2; h2 after h2 → h2 (valid sibling)
        $this->assertStringContainsString('<h1>Title</h1>', $result);
        $this->assertStringContainsString('<h2>Details</h2>', $result);
        $this->assertStringContainsString('<h2>Conclusion</h2>', $result);
        $this->assertStringNotContainsString('<h4>', $result);
    }

    // ── HeadingAnalysis ──

    /** @test */
    public function analyze_detects_violations(): void
    {
        $dom = $this->domHelper->parse('<h1>A</h1><h4>B</h4>');
        $this->assertNotNull($dom);
        
        $analysis = $this->fixer->analyze($dom);
        
        $this->assertFalse($analysis->isValid);
        $this->assertNotEmpty($analysis->violations);
        $this->assertCount(2, $analysis->headings);
    }

    /** @test */
    public function analyze_reports_valid_for_correct_hierarchy(): void
    {
        $dom = $this->domHelper->parse('<h1>A</h1><h2>B</h2><h3>C</h3>');
        $this->assertNotNull($dom);
        
        $analysis = $this->fixer->analyze($dom);
        
        $this->assertTrue($analysis->isValid);
        $this->assertEmpty($analysis->violations);
    }
}
