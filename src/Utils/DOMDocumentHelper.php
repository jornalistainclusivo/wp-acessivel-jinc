<?php declare(strict_types=1);

namespace WpAcessivelJinc\Utils;

/**
 * Wrapper around DOMDocument with UTF-8 handling and error suppression.
 */
final class DOMDocumentHelper
{
    /**
     * @var list<string>
     */
    private array $parseErrors = [];

    /**
     * Parse HTML string into DOMDocument with proper UTF-8 handling.
     *
     * @param string $html Raw HTML content.
     * @return \DOMDocument|null Parsed document, or null on fatal parse error.
     */
    public function parse(string $html): ?\DOMDocument
    {
        $this->parseErrors = [];

        if (trim($html) === '') {
            return null;
        }

        $encodedHtml = mb_encode_numericentity(
            $html,
            [0x80, 0x10FFFF, 0, ~0],
            'UTF-8'
        );

        $dom = new \DOMDocument('1.0', 'UTF-8');
        
        libxml_use_internal_errors(true);

        // Remove LIBXML_HTML_NOIMPLIED to prevent nested nodes bug on fragments
        $loaded = $dom->loadHTML($encodedHtml, LIBXML_NONET);
        
        $errors = libxml_get_errors();
        foreach ($errors as $error) {
            $this->parseErrors[] = trim($error->message);
        }
        
        libxml_clear_errors();
        libxml_use_internal_errors(false);

        if (!$loaded) {
            return null;
        }

        return $dom;
    }

    /**
     * @return list<string> libxml errors from last parse operation.
     */
    public function getParseErrors(): array
    {
        return $this->parseErrors;
    }
}
