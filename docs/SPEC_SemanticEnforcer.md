---
jinc-spec-version: 1.0.0
project-name: WP Acessível JINC
feature-name: Semantic Enforcer Module
status: draft
prd-ref: docs/PRD.md (Sections 3, 4, 5)
sdd-ref: docs/SDD.md (Sections 3, 4, 6 — Component Diagram, Hook Registry, Business Rules)
related-branch: feat/semantic-enforcer
coverage: "6/6 FRs mapped"
created-at: 2026-06-25
authors: JINC Apps (AI-assisted)
---

# Semantic Enforcer — Technical Specification

## Coverage Report

| FR     | Requirement Summary                                               | Spec Element                                                             | Status     |
| ------ | ----------------------------------------------------------------- | ------------------------------------------------------------------------ | ---------- |
| FR-001 | Corrigir hierarquia H1-H6 automaticamente no output               | `HeadingHierarchyFixer` class + BR-SE-001 + Gherkin scenarios            | 🟢 Covered |
| FR-002 | Envelopar blocos com landmarks ARIA sem duplicar existentes       | `LandmarkInjector` class + BR-SE-002 + Gherkin scenarios                 | 🟢 Covered |
| FR-003 | DOM manipulation via `DOMDocument` only — Regex proibido          | `DOMDocumentHelper` utility + ADR-001                                    | 🟢 Covered |
| FR-004 | Cache de DOM processado via Transients API                        | `CacheManager` utility + BR-CACHE-001                                    | 🟢 Covered |
| FR-005 | Zero tabelas customizadas no banco de dados                       | ADR-002. Verificação: `grep -r 'CREATE TABLE' src/` = 0 results         | 🟢 Covered |
| FR-006 | `declare(strict_types=1)` em todo arquivo PHP                     | ADR-004. Verificação: todo arquivo `.php` inicia com declaração         | 🟢 Covered |

**Coverage: 6/6 FRs mapped. 0 blocked upstream.**

---

## Architecture Snapshot (from SDD)

- **Stack:** PHP 8.1+ / WordPress 6.4+ / `ext-dom` (DOMDocument) / Transients API
- **Module location:** `src/Modules/SemanticEnforcer/`
- **Entry point:** `ContentFilter::filterContent()` registered via `add_filter('the_content', ..., PHP_INT_MAX - 10)`
- **Data flow:** `the_content` → Cache check → DOMDocument parse → HeadingHierarchyFixer → LandmarkInjector → DOMSerializer → Cache store → Return HTML
- **API style:** WordPress Filter API (no REST endpoints in this module)
- **ADRs applied:** ADR-001 (DOMDocument), ADR-003 (Transients), ADR-004 (strict_types), ADR-005 (filter priority), ADR-007 (UTF-8), ADR-008 (transient key)

---

## Type System — PHP Class Contracts

### ContentFilter

```php
<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * Entry point for Semantic Enforcer module.
 * Registers the_content filter and orchestrates DOM processing pipeline.
 *
 * @spec-ref FR-001, FR-002, FR-004
 */
final class ContentFilter
{
    /** Maximum content size in bytes before skipping processing (performance guard). */
    private const MAX_CONTENT_BYTES = 512_000; // 500KB

    public function __construct(
        private readonly HeadingHierarchyFixer $headingFixer,
        private readonly LandmarkInjector $landmarkInjector,
        private readonly \WpAcessivelJinc\Utils\DOMDocumentHelper $domHelper,
        private readonly \WpAcessivelJinc\Utils\CacheManager $cache,
        private readonly \WpAcessivelJinc\Utils\Logger $logger,
    ) {}

    /**
     * Register the_content filter.
     * Priority: PHP_INT_MAX - 10 (ADR-005) — runs after shortcodes/blocks, before output cache.
     */
    public function register(): void;

    /**
     * Main filter callback. Processes HTML content through the enforcement pipeline.
     *
     * @param string $content Raw HTML content from the_content filter chain.
     * @return string Processed HTML with corrected headings and ARIA landmarks.
     *
     * Preconditions:
     *   - Content is a string (may be empty)
     *   - WordPress filter system has already processed shortcodes and blocks
     *
     * Postconditions:
     *   - Heading hierarchy is sequential (no skips)
     *   - ARIA landmarks are present (no duplicates)
     *   - Output is idempotent: process(process(x)) === process(x)
     *   - On error: returns original content unmodified
     */
    public function filterContent(string $content): string;
}
```

### HeadingHierarchyFixer

```php
<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * Analyzes and corrects heading hierarchy (H1-H6) in a DOMDocument.
 * Ensures sequential progression without level skips.
 *
 * @spec-ref FR-001, BR-SE-001
 *
 * Algorithm:
 *   1. Collect all <h[1-6]> elements in document order
 *   2. If zero headings: return DOMDocument unmodified
 *   3. Record the first heading's level as the "base level"
 *   4. Walk headings sequentially; for each heading:
 *      a. If current level > previous level + 1: remap to previous + 1
 *      b. If current level <= previous level: accept (valid sibling or ascension)
 *      c. Preserve all attributes (id, class, aria-*, data-*) during remap
 *   5. Return modified DOMDocument
 *
 * Invariants:
 *   - Never changes heading content (text, inner HTML)
 *   - Never removes headings
 *   - Never changes heading order
 *   - Preserves all attributes on heading elements
 *   - Idempotent: fix(fix(dom)) === fix(dom)
 */
final class HeadingHierarchyFixer
{
    /**
     * @param \DOMDocument $dom The parsed HTML document.
     * @return \DOMDocument The document with corrected heading hierarchy.
     */
    public function fix(\DOMDocument $dom): \DOMDocument;

    /**
     * Analyze heading structure without modifying.
     *
     * @param \DOMDocument $dom The parsed HTML document.
     * @return HeadingAnalysis Analysis result with current levels and violations.
     */
    public function analyze(\DOMDocument $dom): HeadingAnalysis;
}

/**
 * @spec-ref FR-001
 */
final readonly class HeadingAnalysis
{
    /**
     * @param list<HeadingInfo> $headings Ordered list of headings found.
     * @param list<HeadingViolation> $violations Level skip violations detected.
     * @param bool $isValid True if no violations found.
     */
    public function __construct(
        public array $headings,
        public array $violations,
        public bool $isValid,
    ) {}
}

/**
 * @spec-ref FR-001
 */
final readonly class HeadingInfo
{
    public function __construct(
        public int $level,           // 1-6
        public int $originalLevel,   // 1-6 (before fix)
        public string $textContent,  // Inner text
        public int $position,        // 0-indexed position in document order
    ) {}
}

/**
 * @spec-ref FR-001
 */
final readonly class HeadingViolation
{
    public function __construct(
        public int $position,       // Which heading (0-indexed)
        public int $expectedLevel,  // What it should be
        public int $actualLevel,    // What it was
        public string $context,     // First 50 chars of heading text
    ) {}
}
```

### LandmarkInjector

```php
<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * Detects missing ARIA landmarks and injects them into the DOMDocument.
 * Never duplicates landmarks that already exist.
 *
 * @spec-ref FR-002, BR-SE-002
 *
 * Algorithm:
 *   1. Scan document for existing landmarks:
 *      - Semantic: <main>, <nav>, <article>, <aside>, <header>, <footer>, <section>
 *      - ARIA roles: role="main", role="navigation", role="article", etc.
 *   2. If <article> or role="article" is absent:
 *      - Wrap the root content block in <article role="article">
 *   3. If navigation-like structure detected (list of links) and no <nav>:
 *      - Wrap in <nav aria-label="Content navigation">
 *   4. For multiple distinct content sections:
 *      - Each gets unique aria-label to differentiate for screen readers
 *   5. Return modified DOMDocument
 *
 * Detection heuristics for navigation:
 *   - <ul> or <ol> where > 50% of <li> children contain an <a> element
 *   - Minimum 3 link items to qualify as navigation
 *
 * Invariants:
 *   - Never duplicates existing landmarks
 *   - Never removes existing landmarks or ARIA attributes
 *   - Preserves all existing attributes on wrapped elements
 *   - Idempotent: inject(inject(dom)) === inject(dom)
 */
final class LandmarkInjector
{
    /**
     * @param \DOMDocument $dom The parsed HTML document.
     * @return \DOMDocument The document with ARIA landmarks injected.
     */
    public function inject(\DOMDocument $dom): \DOMDocument;

    /**
     * Analyze landmark coverage without modifying.
     *
     * @param \DOMDocument $dom The parsed HTML document.
     * @return LandmarkAnalysis Analysis result with present and missing landmarks.
     */
    public function analyze(\DOMDocument $dom): LandmarkAnalysis;
}

/**
 * @spec-ref FR-002
 */
final readonly class LandmarkAnalysis
{
    /**
     * @param list<string> $presentLandmarks Landmarks found (e.g., ['article', 'nav']).
     * @param list<string> $missingLandmarks Landmarks that should be injected.
     * @param bool $isComplete True if all recommended landmarks are present.
     */
    public function __construct(
        public array $presentLandmarks,
        public array $missingLandmarks,
        public bool $isComplete,
    ) {}
}
```

### DOMSerializer

```php
<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * Converts a DOMDocument back to an HTML string.
 * Strips <html>, <head>, <body> wrappers added by DOMDocument::loadHTML().
 *
 * @spec-ref FR-003 (DOMDocument pipeline), ADR-001
 */
final class DOMSerializer
{
    /**
     * @param \DOMDocument $dom The processed document.
     * @return string Clean HTML string without wrapper tags.
     *
     * Postconditions:
     *   - No <html>, <head>, <body>, <!DOCTYPE> tags in output
     *   - UTF-8 encoding preserved
     *   - Original whitespace pattern preserved as closely as possible
     */
    public function serialize(\DOMDocument $dom): string;
}
```

### DOMDocumentHelper (Utility)

```php
<?php declare(strict_types=1);

namespace WpAcessivelJinc\Utils;

/**
 * Wrapper around DOMDocument with UTF-8 handling and error suppression.
 *
 * @spec-ref FR-003, ADR-001, ADR-007
 *
 * Handles:
 *   - UTF-8 encoding via mb_encode_numericentity() + charset meta injection
 *   - libxml error suppression and collection
 *   - LIBXML_HTML_NOIMPLIED | LIBXML_HTML_NODEFDTD flags
 */
final class DOMDocumentHelper
{
    /**
     * Parse HTML string into DOMDocument with proper UTF-8 handling.
     *
     * @param string $html Raw HTML content.
     * @return \DOMDocument|null Parsed document, or null on fatal parse error.
     *
     * Preconditions:
     *   - $html is a non-empty string
     *
     * Postconditions:
     *   - UTF-8 characters preserved correctly
     *   - libxml errors captured in internal log, not emitted as PHP warnings
     */
    public function parse(string $html): ?\DOMDocument;

    /**
     * @return list<string> libxml errors from last parse operation.
     */
    public function getParseErrors(): array;
}
```

### CacheManager (Utility)

```php
<?php declare(strict_types=1);

namespace WpAcessivelJinc\Utils;

/**
 * Transients API wrapper for caching processed DOM content.
 *
 * @spec-ref FR-004, BR-CACHE-001, ADR-003, ADR-008
 *
 * Key format: jinc_se_{post_id}_{md5(post_modified)}
 * TTL: Configurable (default 86400s / 24h)
 */
final class CacheManager
{
    /**
     * Get cached content for a post.
     *
     * @param int $postId WordPress post ID.
     * @return string|null Cached HTML or null on miss.
     */
    public function get(int $postId): ?string;

    /**
     * Store processed content for a post.
     *
     * @param int $postId WordPress post ID.
     * @param string $content Processed HTML content.
     */
    public function set(int $postId, string $content): void;

    /**
     * Invalidate cache for a specific post. Called on save_post action.
     *
     * @param int $postId WordPress post ID.
     */
    public function invalidatePostTransient(int $postId): void;

    /**
     * Flush all plugin transients. Called on settings change or plugin deactivation.
     */
    public function flushAllTransients(): void;
}
```

---

## Business Rules

### BR-SE-001: Heading Hierarchy Must Be Sequential

```
BR-SE-001: Heading Hierarchy Sequential Enforcement
  Precondition:  Content contains one or more <h[1-6]> elements
  Input:         HTML content string via the_content filter
  Invariant:     No heading may skip more than 0 levels down from its predecessor
                 (H2→H4 is invalid; H2→H3 is valid; H3→H2 is valid — ascension)
  Output:        HTML with corrected heading levels; all attributes preserved
  Violation:     N/A — auto-corrected silently (enforcement, not blocking)
  I/O Example:
    Input:    "<h1>Title</h1><h4>Subsection</h4><h2>Section</h2>"
    Output:   "<h1>Title</h1><h2>Subsection</h2><h3>Section</h3>"
```

**Remapping Algorithm (detailed):**

```pseudo
FUNCTION fixHeadingHierarchy(headings: list[HeadingElement]) -> list[HeadingElement]:
  IF headings is empty:
    RETURN headings  // No modification

  expectedLevel = headings[0].level  // Accept first heading's level as base
  result = [headings[0]]  // First heading is always valid

  FOR i = 1 TO length(headings) - 1:
    current = headings[i]
    previous = result[i - 1]

    IF current.level > previous.level + 1:
      // SKIP DETECTED: current is too deep
      // Remap to previous + 1 (next valid descending level)
      newLevel = previous.level + 1
      current.setLevel(newLevel)  // Preserve all other attributes
    ELSE IF current.level <= previous.level:
      // VALID: sibling (same level) or ascension (going up)
      // Accept as-is — no modification
      PASS

    result.append(current)

  RETURN result
```

**Edge Cases Table:**

| Input HTML                            | Expected Output                      | Rule Applied                  |
| ------------------------------------- | ------------------------------------ | ----------------------------- |
| `<h1>A</h1><h4>B</h4>`               | `<h1>A</h1><h2>B</h2>`              | Skip H2,H3 → remap to H2     |
| `<h1>A</h1><h2>B</h2><h2>C</h2>`     | No change                            | Same level siblings valid     |
| `<h2>A</h2><h3>B</h3><h2>C</h2>`     | No change                            | Ascension from H3→H2 valid   |
| `<h1>A</h1><h3>B</h3><h6>C</h6>`     | `<h1>A</h1><h2>B</h2><h3>C</h3>`    | Double skip correction        |
| `<h3>A</h3><h5>B</h5>`               | `<h3>A</h3><h4>B</h4>`              | Base level H3 accepted        |
| (empty — no headings)                  | No change                            | Zero headings = passthrough   |
| `<h1 id="x" class="y">A</h1><h4>B</h4>` | `<h1 id="x" class="y">A</h1><h2>B</h2>` | Attributes preserved   |
| `<blockquote><h3>Q</h3></blockquote>` | Included in hierarchy analysis       | Context-independent           |

---

### BR-SE-002: Landmarks ARIA Must Not Duplicate

```
BR-SE-002: ARIA Landmark Idempotent Injection
  Precondition:  Content has been parsed into DOMDocument
  Input:         DOMDocument from DOMDocumentHelper::parse()
  Invariant:     No landmark tag or role attribute is duplicated
                 Running injection twice produces identical output
  Output:        DOMDocument with <article> wrapper (if missing) and <nav> wrapper (if nav detected)
  Violation:     N/A — auto-injected silently
  I/O Example:
    Input:    "<p>Intro</p><h2>Section</h2><p>Text</p>"
    Output:   "<article role=\"article\"><p>Intro</p><h2>Section</h2><p>Text</p></article>"
```

**Detection and Injection Rules:**

| Condition                                     | Check                                          | Action                                       |
| --------------------------------------------- | ---------------------------------------------- | -------------------------------------------- |
| No `<article>` and no `role="article"`        | `$dom->getElementsByTagName('article')` empty AND `//[@role='article']` empty | Wrap root content in `<article role="article">` |
| `<article>` already present                   | Element found                                  | No modification                              |
| `role="article"` already present              | XPath finds element                            | No modification                              |
| `<ul>` with >50% `<li>` containing `<a>`     | Count check: links/totalItems > 0.5 AND totalItems ≥ 3 | Wrap in `<nav aria-label="Content navigation">` |
| `<nav>` already wraps the list               | Parent or ancestor is `<nav>`                  | No modification                              |
| Multiple nav-like structures                  | 2+ qualifying lists                            | Each gets unique `aria-label` (e.g., "Content navigation 1", "Content navigation 2") |

---

### BR-SE-003: DOM Processing Must Be Idempotent

```
BR-SE-003: Idempotency Guarantee
  Precondition:  Any valid HTML content string
  Input:         content string
  Invariant:     filterContent(filterContent(content)) === filterContent(content)
  Output:        Identical HTML on second pass
  Violation:     Test failure → P0 bug
  I/O Example:
    Input:    filterContent("<h1>A</h1><h4>B</h4>") => "<h1>A</h1><h2>B</h2>"
    Input2:   filterContent("<h1>A</h1><h2>B</h2>") => "<h1>A</h1><h2>B</h2>"
    Assert:   Input == Input2 ✅
```

---

### BR-CACHE-001: Cache Lifecycle

```
BR-CACHE-001: Transient Cache Lifecycle
  Precondition:  Plugin is active, cache setting enabled
  Input:         the_content filter invocation
  Invariant:     Cache key uniquely identifies post content state
  Output:        Cached or freshly processed HTML
  Violation:     Stale cache → mitigated by post_modified in key hash

  Flow:
    1. Compute key: "jinc_se_{post_id}_{md5(post_modified)}"
    2. get_transient(key)
       → HIT: return cached value (skip DOMDocument processing)
       → MISS: process content, set_transient(key, result, TTL), return result
    3. On save_post(post_id): delete_transient("jinc_se_{post_id}_*")
    4. On update_option(jinc_*): flush all jinc_se_* transients
```

---

## Critical Path — Gherkin

### Feature: Heading Hierarchy Enforcement

```gherkin
Feature: Heading Hierarchy Enforcement

  Scenario: Happy path — headings with level skip are corrected
    Given a WordPress post with content "<h1>Title</h1><p>Intro.</p><h4>Details</h4><p>More text.</p><h2>Conclusion</h2>"
    And the Semantic Enforcer module is enabled
    When the post is rendered via the_content filter
    Then the output HTML contains "<h1>Title</h1><p>Intro.</p><h2>Details</h2><p>More text.</p><h3>Conclusion</h3>"
    And all original CSS classes and IDs on heading elements are preserved
    And processing time is less than 50ms (uncached)

  Scenario: Edge case — content has no headings
    Given a WordPress post with content "<p>A paragraph without any headings.</p><ul><li>Item</li></ul>"
    And the Semantic Enforcer module is enabled
    When the post is rendered via the_content filter
    Then the output HTML is identical to the input (no modifications)
    And the DOMDocument parser was NOT invoked (short-circuit)

  Scenario: Edge case — DOMDocument fails on malformed HTML
    Given a WordPress post with content "<h1>Broken<h2>Nested</h1></h2><p>Malformed"
    And the Semantic Enforcer module is enabled
    When the post is rendered via the_content filter
    Then the original content is returned unmodified
    And an error is logged to error_log() with prefix "[WP-Acessível-JINC]"
    And no PHP warning or notice is emitted to the user
```

### Feature: ARIA Landmarks Injection

```gherkin
Feature: ARIA Landmarks Injection

  Scenario: Happy path — content without landmarks gets article wrapper
    Given a WordPress post with content "<p>Introduction paragraph.</p><h2>Section</h2><p>Section content.</p>"
    And the Semantic Enforcer module is enabled with landmark injection ON
    When the post is rendered via the_content filter
    Then the output HTML is wrapped in '<article role="article">...</article>'
    And no other landmarks are added (no nav-like structure detected)

  Scenario: Edge case — content already has article landmark
    Given a WordPress post with content "<article><h2>Title</h2><p>Content.</p></article>"
    And the Semantic Enforcer module is enabled with landmark injection ON
    When the post is rendered via the_content filter
    Then the output HTML is identical to the input (landmark already present)
    And no duplicate <article> tag is added
```

### Feature: Cache Layer

```gherkin
Feature: Transient Cache for DOM Processing

  Scenario: Happy path — cache miss then hit
    Given a WordPress post with ID 42 and post_modified "2026-06-25 10:00:00"
    And the post content is "<h1>Title</h1><h4>Sub</h4>"
    And no transient exists for key "jinc_se_42_{hash}"
    When the post is rendered via the_content filter for the first time
    Then the content is processed by DOMDocument (cache MISS)
    And the result is stored as transient "jinc_se_42_{hash}" with TTL 86400
    When the post is rendered again without changes
    Then the cached result is returned (cache HIT)
    And DOMDocument is NOT invoked

  Scenario: Edge case — post edited invalidates cache
    Given a cached transient exists for post ID 42
    When the post is saved (save_post action fires for post 42)
    Then all transients matching "jinc_se_42_*" are deleted
    And the next render invokes full DOMDocument processing
```

---

## Error Code Registry

| Code                    | Context              | Trigger Condition                          | Behavior                                          |
| ----------------------- | -------------------- | ------------------------------------------ | ------------------------------------------------- |
| `JINC_DOM_PARSE_ERROR`  | DOMDocumentHelper    | `DOMDocument::loadHTML()` throws            | Return original content. Log error.               |
| `JINC_CONTENT_TOO_LARGE`| ContentFilter        | Content > 500KB (`MAX_CONTENT_BYTES`)       | Skip processing. Return original. Log warning.    |
| `JINC_CACHE_WRITE_FAIL` | CacheManager         | `set_transient()` returns false             | Continue without cache. Log warning.              |
| `JINC_CACHE_READ_FAIL`  | CacheManager         | `get_transient()` throws                    | Process without cache. Log warning.               |

All errors are **silent to the end user**. They are logged to `error_log()` with `[WP-Acessível-JINC]` prefix and relevant context (post_id, content length, error message).

---

## Test Scaffolding — PHPUnit

```php
<?php declare(strict_types=1);

/**
 * @spec-source docs/SPEC_SemanticEnforcer.md
 * @coverage 4 business rules, 7 Gherkin scenarios, 6 functional requirements
 *
 * Scaffold from Spec. Fill in test bodies.
 * Do not add tests not present in spec.md — update the Spec first.
 */

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\SemanticEnforcer\HeadingHierarchyFixer;
use WpAcessivelJinc\Modules\SemanticEnforcer\LandmarkInjector;
use WpAcessivelJinc\Modules\SemanticEnforcer\ContentFilter;
use WpAcessivelJinc\Utils\DOMDocumentHelper;
use WpAcessivelJinc\Utils\CacheManager;

class HeadingHierarchyFixerTest extends TestCase
{
    // ── Business Rules ──────────────────────────────────────

    /** @test BR-SE-001: Heading skip H1→H4 remapped to H1→H2 */
    public function it_remaps_heading_level_skips(): void
    {
        // Input:    <h1>A</h1><h4>B</h4>
        // Expected: <h1>A</h1><h2>B</h2>
    }

    /** @test BR-SE-001: Same-level siblings are not modified */
    public function it_preserves_same_level_siblings(): void
    {
        // Input:    <h2>A</h2><h2>B</h2><h2>C</h2>
        // Expected: No change
    }

    /** @test BR-SE-001: Ascending levels (H3→H2) are valid */
    public function it_allows_heading_level_ascension(): void
    {
        // Input:    <h2>A</h2><h3>B</h3><h2>C</h2>
        // Expected: No change
    }

    /** @test BR-SE-001: Double skip correction (H1→H3→H6) */
    public function it_corrects_multiple_consecutive_skips(): void
    {
        // Input:    <h1>A</h1><h3>B</h3><h6>C</h6>
        // Expected: <h1>A</h1><h2>B</h2><h3>C</h3>
    }

    /** @test BR-SE-001: Attributes (id, class, aria-*) preserved on remapped headings */
    public function it_preserves_attributes_on_remapped_headings(): void
    {
        // Input:    <h1 id="title">A</h1><h4 class="sub" data-x="1">B</h4>
        // Expected: <h1 id="title">A</h1><h2 class="sub" data-x="1">B</h2>
    }

    /** @test BR-SE-001: Empty content (no headings) passes through */
    public function it_passes_through_content_without_headings(): void
    {
        // Input:    <p>No headings here.</p>
        // Expected: No change
    }

    /** @test BR-SE-003: Processing is idempotent */
    public function it_produces_identical_output_on_double_processing(): void
    {
        // Input:    <h1>A</h1><h4>B</h4>
        // Assert:   fix(fix(input)) === fix(input)
    }
}

class LandmarkInjectorTest extends TestCase
{
    /** @test BR-SE-002: Content without article gets wrapped */
    public function it_wraps_content_in_article_when_missing(): void
    {
        // Input:    <p>Content</p>
        // Expected: <article role="article"><p>Content</p></article>
    }

    /** @test BR-SE-002: Content with existing article is not duplicated */
    public function it_does_not_duplicate_existing_article(): void
    {
        // Input:    <article><p>Content</p></article>
        // Expected: No change
    }

    /** @test BR-SE-002: Content with role="article" is not duplicated */
    public function it_does_not_duplicate_existing_role_article(): void
    {
        // Input:    <div role="article"><p>Content</p></div>
        // Expected: No change
    }

    /** @test BR-SE-002: Nav-like list structure gets nav wrapper */
    public function it_wraps_nav_like_lists_in_nav_element(): void
    {
        // Input:    <ul><li><a href="#">A</a></li><li><a href="#">B</a></li><li><a href="#">C</a></li></ul>
        // Expected: <nav aria-label="Content navigation"><ul>...</ul></nav>
    }

    /** @test BR-SE-002: Existing nav is not duplicated */
    public function it_does_not_duplicate_existing_nav(): void
    {
        // Input:    <nav><ul><li><a href="#">A</a></li></ul></nav>
        // Expected: No change
    }

    /** @test BR-SE-003: Landmark injection is idempotent */
    public function it_produces_identical_output_on_double_injection(): void
    {
        // Input:    <p>Content</p>
        // Assert:   inject(inject(input)) === inject(input)
    }
}

class ContentFilterTest extends TestCase
{
    /** @test Gherkin: Happy path — headings corrected */
    public function it_corrects_heading_hierarchy_in_full_pipeline(): void
    {
        // Input:    <h1>Title</h1><p>Intro.</p><h4>Details</h4>
        // Expected: <h1>Title</h1><p>Intro.</p><h2>Details</h2>
    }

    /** @test Gherkin: Edge case — no headings, content unchanged */
    public function it_returns_unchanged_content_without_headings(): void
    {
        // Input:    <p>Just text.</p>
        // Expected: Identical output
    }

    /** @test Gherkin: Edge case — malformed HTML returns original */
    public function it_returns_original_on_dom_parse_failure(): void
    {
        // Input:    Severely malformed HTML
        // Expected: Original content returned, error logged
    }

    /** @test JINC_CONTENT_TOO_LARGE — large content skipped */
    public function it_skips_processing_for_oversized_content(): void
    {
        // Input:    > 500KB content
        // Expected: Original content returned, warning logged
    }
}

class CacheManagerTest extends TestCase
{
    /** @test BR-CACHE-001: Cache miss triggers processing, stores result */
    public function it_stores_result_on_cache_miss(): void
    {
        // Assert: get() returns null, then set() is called
    }

    /** @test BR-CACHE-001: Cache hit returns stored content */
    public function it_returns_cached_content_on_hit(): void
    {
        // Assert: get() returns stored value, DOMDocument not invoked
    }

    /** @test BR-CACHE-001: save_post invalidates post cache */
    public function it_invalidates_cache_on_save_post(): void
    {
        // Assert: After invalidatePostTransient(), get() returns null
    }

    /** @test BR-CACHE-001: settings change flushes all caches */
    public function it_flushes_all_caches_on_settings_change(): void
    {
        // Assert: After flushAllTransients(), all get() return null
    }
}
```

---

## Downstream Pipeline

This Spec is ready for implementation:

```
PRD (docs/PRD.md) ──► SDD (docs/SDD.md) ──► Spec (this document) ──► Code
                                                    ↑ you are here
```

**Implementation Order:**

1. `Utils/DOMDocumentHelper.php` — foundation, no dependencies
2. `Utils/CacheManager.php` — depends on WordPress Transients API
3. `Utils/Logger.php` — simple error_log wrapper
4. `Modules/SemanticEnforcer/HeadingHierarchyFixer.php` — pure logic, depends on DOMDocument
5. `Modules/SemanticEnforcer/LandmarkInjector.php` — pure logic, depends on DOMDocument
6. `Modules/SemanticEnforcer/DOMSerializer.php` — depends on DOMDocument
7. `Modules/SemanticEnforcer/ContentFilter.php` — orchestrator, depends on all above
8. `Core/Bootstrap.php` — plugin entry point, registers hooks
9. Tests: Unit → Integration → Fixtures

| Status       | Value                                                  |
| ------------ | ------------------------------------------------------ |
| Spec Status  | draft                                                  |
| Ready for Code? | 🟢 Yes — all FRs covered, BRs defined, tests scaffolded |
| PRD Alignment | 🟢 No upstream conflicts                             |
| SDD Alignment | 🟢 Architecture, ADRs, and hook registry consistent  |
