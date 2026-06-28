<?php declare(strict_types=1);

namespace WpAcessivelJinc\Modules\SemanticEnforcer;

/**
 * Detects missing ARIA landmarks and injects them into the DOMDocument.
 * Never duplicates landmarks that already exist.
 *
 * @spec-ref FR-002, BR-SE-002
 *
 * Algorithm:
 *   1. Scan document for existing landmarks (semantic tags + ARIA roles)
 *   2. If <article> or role="article" is absent: wrap root content in <article role="article">
 *   3. If navigation-like list detected and no <nav>: wrap in <nav aria-label="...">
 *   4. For multiple nav-like structures: each gets unique aria-label
 *   5. Return modified DOMDocument
 *
 * Invariants:
 *   - Never duplicates existing landmarks
 *   - Never removes existing landmarks or ARIA attributes
 *   - Preserves all existing attributes on wrapped elements
 *   - Idempotent: inject(inject(dom)) === inject(dom)
 */
final class LandmarkInjector
{
    private const MIN_NAV_ITEMS = 3;
    private const MIN_LINK_RATIO = 0.5;

    /**
     * @param \DOMDocument $dom The parsed HTML document.
     * @return \DOMDocument The document with ARIA landmarks injected.
     */
    public function inject(\DOMDocument $dom): \DOMDocument
    {
        $this->injectArticleLandmark($dom);
        $this->injectNavLandmarks($dom);

        return $dom;
    }

    /**
     * Analyze landmark coverage without modifying.
     *
     * @param \DOMDocument $dom The parsed HTML document.
     * @return LandmarkAnalysis Analysis result with present and missing landmarks.
     */
    public function analyze(\DOMDocument $dom): LandmarkAnalysis
    {
        $present = [];
        $missing = [];

        if ($this->hasArticleLandmark($dom)) {
            $present[] = 'article';
        } else {
            $missing[] = 'article';
        }

        if ($this->hasNavLandmark($dom)) {
            $present[] = 'nav';
        }

        return new LandmarkAnalysis(
            presentLandmarks: $present,
            missingLandmarks: $missing,
            isComplete: count($missing) === 0,
        );
    }

    /**
     * Check if the document already has an <article> tag or role="article".
     */
    private function hasArticleLandmark(\DOMDocument $dom): bool
    {
        // Check semantic <article> tag
        $articles = $dom->getElementsByTagName('article');
        if ($articles->length > 0) {
            return true;
        }

        // Check role="article" via XPath
        $xpath = new \DOMXPath($dom);
        $roleArticles = $xpath->query('//*[@role="article"]');

        return $roleArticles !== false && $roleArticles->length > 0;
    }

    /**
     * Check if the document already has a <nav> tag.
     */
    private function hasNavLandmark(\DOMDocument $dom): bool
    {
        return $dom->getElementsByTagName('nav')->length > 0;
    }

    /**
     * Wrap root content in <article role="article"> if not already present.
     */
    private function injectArticleLandmark(\DOMDocument $dom): void
    {
        if ($this->hasArticleLandmark($dom)) {
            return;
        }

        $article = $dom->createElement('article');
        $article->setAttribute('role', 'article');

        // Determine the root container — could be <body> or the document element
        $root = $dom->getElementsByTagName('body')->item(0) ?? $dom->documentElement;
        if ($root === null) {
            return;
        }

        // Move all children into the <article> wrapper
        while ($root->firstChild !== null) {
            $article->appendChild($root->firstChild);
        }

        $root->appendChild($article);
    }

    /**
     * Detect and wrap navigation-like list structures in <nav>.
     *
     * Detection heuristic:
     *   - <ul> or <ol> where > 50% of <li> children contain an <a> element
     *   - Minimum 3 link items to qualify as navigation
     */
    private function injectNavLandmarks(\DOMDocument $dom): void
    {
        $navCounter = 0;

        // Collect all <ul> and <ol> elements
        $lists = [];
        foreach (['ul', 'ol'] as $tag) {
            $elements = $dom->getElementsByTagName($tag);
            for ($i = 0; $i < $elements->length; $i++) {
                $element = $elements->item($i);
                if ($element instanceof \DOMElement) {
                    $lists[] = $element;
                }
            }
        }

        foreach ($lists as $list) {
            // Skip if already inside a <nav>
            if ($this->isInsideNav($list)) {
                continue;
            }

            // Count <li> children and how many contain <a>
            $totalItems = 0;
            $linkItems = 0;

            foreach ($list->childNodes as $child) {
                if ($child instanceof \DOMElement && $child->tagName === 'li') {
                    $totalItems++;
                    if ($child->getElementsByTagName('a')->length > 0) {
                        $linkItems++;
                    }
                }
            }

            // Apply heuristic: >= 3 items AND > 50% have links
            if ($totalItems >= self::MIN_NAV_ITEMS && ($linkItems / $totalItems) > self::MIN_LINK_RATIO) {
                $navCounter++;
                $this->wrapInNav($dom, $list, $navCounter);
            }
        }
    }

    /**
     * Check if an element is already inside a <nav> ancestor.
     */
    private function isInsideNav(\DOMElement $element): bool
    {
        $parent = $element->parentNode;
        while ($parent !== null) {
            if ($parent instanceof \DOMElement && $parent->tagName === 'nav') {
                return true;
            }
            $parent = $parent->parentNode;
        }
        return false;
    }

    /**
     * Wrap a list element in <nav aria-label="...">.
     */
    private function wrapInNav(\DOMDocument $dom, \DOMElement $list, int $counter): void
    {
        $nav = $dom->createElement('nav');
        $label = $counter === 1 ? 'Content navigation' : 'Content navigation ' . $counter;
        $nav->setAttribute('aria-label', $label);

        $list->parentNode?->replaceChild($nav, $list);
        $nav->appendChild($list);
    }
}
