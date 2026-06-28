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
    public function fix(\DOMDocument $dom): \DOMDocument
    {
        $headings = $this->collectHeadings($dom);

        if (count($headings) === 0) {
            return $dom;
        }

        // Build the level mapping using the remapping algorithm from the SPEC
        $previousLevel = (int) $headings[0]->getAttribute('data-original-level');
        if ($previousLevel === 0) {
            $previousLevel = $this->getHeadingLevel($headings[0]);
        }

        // First heading always stays as-is (base level)
        $mappedLevels = [$previousLevel];

        for ($i = 1, $count = count($headings); $i < $count; $i++) {
            $currentLevel = $this->getHeadingLevel($headings[$i]);

            if ($currentLevel > $mappedLevels[$i - 1] + 1) {
                // SKIP DETECTED: remap to previous + 1
                $newLevel = $mappedLevels[$i - 1] + 1;
            } elseif ($currentLevel <= $mappedLevels[$i - 1]) {
                // VALID: sibling or ascension — accept as-is
                $newLevel = $currentLevel;
            } else {
                // currentLevel === previousMappedLevel + 1 — exactly one step down, valid
                $newLevel = $currentLevel;
            }

            $mappedLevels[] = $newLevel;
        }

        // Now apply the remapping by renaming DOM elements
        for ($i = 0, $count = count($headings); $i < $count; $i++) {
            $currentLevel = $this->getHeadingLevel($headings[$i]);
            $targetLevel = $mappedLevels[$i];

            if ($currentLevel !== $targetLevel) {
                $this->renameHeading($dom, $headings[$i], $targetLevel);
            }
        }

        return $dom;
    }

    /**
     * Analyze heading structure without modifying.
     *
     * @param \DOMDocument $dom The parsed HTML document.
     * @return HeadingAnalysis Analysis result with current levels and violations.
     */
    public function analyze(\DOMDocument $dom): HeadingAnalysis
    {
        $elements = $this->collectHeadings($dom);

        if (count($elements) === 0) {
            return new HeadingAnalysis(
                headings: [],
                violations: [],
                isValid: true,
            );
        }

        $headings = [];
        $violations = [];
        $previousLevel = $this->getHeadingLevel($elements[0]);

        $headings[] = new HeadingInfo(
            level: $previousLevel,
            originalLevel: $previousLevel,
            textContent: mb_substr($elements[0]->textContent, 0, 50),
            position: 0,
        );

        for ($i = 1, $count = count($elements); $i < $count; $i++) {
            $currentLevel = $this->getHeadingLevel($elements[$i]);

            $headings[] = new HeadingInfo(
                level: $currentLevel,
                originalLevel: $currentLevel,
                textContent: mb_substr($elements[$i]->textContent, 0, 50),
                position: $i,
            );

            if ($currentLevel > $previousLevel + 1) {
                $expectedLevel = $previousLevel + 1;
                $violations[] = new HeadingViolation(
                    position: $i,
                    expectedLevel: $expectedLevel,
                    actualLevel: $currentLevel,
                    context: mb_substr($elements[$i]->textContent, 0, 50),
                );
            }

            $previousLevel = $currentLevel;
        }

        return new HeadingAnalysis(
            headings: $headings,
            violations: $violations,
            isValid: count($violations) === 0,
        );
    }

    /**
     * Collect all heading elements (h1-h6) in document order.
     *
     * @return list<\DOMElement>
     */
    private function collectHeadings(\DOMDocument $dom): array
    {
        $headings = [];
        $xpath = new \DOMXPath($dom);
        $nodes = $xpath->query('//h1|//h2|//h3|//h4|//h5|//h6');

        if ($nodes !== false) {
            foreach ($nodes as $node) {
                if ($node instanceof \DOMElement) {
                    $headings[] = $node;
                }
            }
        }

        return $headings;
    }

    /**
     * Extract heading level from tag name (e.g., "h2" → 2).
     */
    private function getHeadingLevel(\DOMElement $element): int
    {
        return (int) mb_substr($element->tagName, 1);
    }

    /**
     * Rename a heading element to a new level while preserving all attributes and children.
     * Uses DOMDocument::createElement + attribute/child migration (no regex).
     */
    private function renameHeading(\DOMDocument $dom, \DOMElement $oldElement, int $newLevel): void
    {
        $newTag = 'h' . $newLevel;
        $newElement = $dom->createElement($newTag);

        // Copy all attributes
        foreach ($oldElement->attributes as $attr) {
            if ($attr instanceof \DOMAttr) {
                $newElement->setAttribute($attr->name, $attr->value);
            }
        }

        // Move all child nodes
        while ($oldElement->firstChild !== null) {
            $newElement->appendChild($oldElement->firstChild);
        }

        // Replace in DOM tree
        $oldElement->parentNode?->replaceChild($newElement, $oldElement);
    }
}
