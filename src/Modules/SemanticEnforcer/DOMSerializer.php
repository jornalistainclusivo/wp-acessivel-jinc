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
    public function serialize(\DOMDocument $dom): string
    {
        // Try to extract content from <body> if present (DOMDocument adds wrappers)
        $body = $dom->getElementsByTagName('body')->item(0);

        if ($body !== null) {
            $inner = '';
            foreach ($body->childNodes as $child) {
                $inner .= $dom->saveHTML($child);
            }
            return $this->decodeEntities($inner);
        }

        // If no <body>, get the raw HTML and strip wrappers manually
        $html = $dom->saveHTML();

        // Strip DOCTYPE, <html>, <head>, <body> wrapper tags that DOMDocument may add
        // Using string operations (not regex) for ADR-001 compliance
        $html = $this->stripWrapperTags($html);

        return $this->decodeEntities($html);
    }

    /**
     * Strip common DOMDocument wrapper tags using string operations.
     */
    private function stripWrapperTags(string $html): string
    {
        // Remove <!DOCTYPE ...>
        $doctypePos = mb_stripos($html, '<!doctype');
        if ($doctypePos !== false) {
            $endPos = mb_strpos($html, '>', $doctypePos);
            if ($endPos !== false) {
                $html = mb_substr($html, 0, $doctypePos) . mb_substr($html, $endPos + 1);
            }
        }

        // Remove <html> and </html>
        $html = str_ireplace(['<html>', '</html>'], '', $html);

        // Remove <head>...</head> block
        $headStart = mb_stripos($html, '<head>');
        if ($headStart !== false) {
            $headEnd = mb_stripos($html, '</head>');
            if ($headEnd !== false) {
                $html = mb_substr($html, 0, $headStart) . mb_substr($html, $headEnd + 7);
            }
        }

        // Remove <body> and </body>
        $html = str_ireplace(['<body>', '</body>'], '', $html);

        return trim($html);
    }

    /**
     * Decode numeric HTML entities back to UTF-8 characters.
     */
    private function decodeEntities(string $html): string
    {
        return mb_convert_encoding($html, 'UTF-8', 'HTML-ENTITIES');
    }
}
