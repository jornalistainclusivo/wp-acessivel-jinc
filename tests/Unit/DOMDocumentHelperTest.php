<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Utils\DOMDocumentHelper;

class DOMDocumentHelperTest extends TestCase
{
    public function test_parses_html_with_utf8_preserved(): void
    {
        $helper = new DOMDocumentHelper();
        $html = '<p>Acessível é maçã!</p>';
        
        $dom = $helper->parse($html);
        
        $this->assertNotNull($dom);
        // Ensure UTF-8 wasn't mangled into ISO-8859-1 gibberish
        $saved = mb_convert_encoding($dom->saveHTML(), 'UTF-8', 'HTML-ENTITIES');
        $this->assertStringContainsString('Acessível é maçã!', $saved);
    }

    public function test_suppresses_libxml_errors_and_collects_them(): void
    {
        $helper = new DOMDocumentHelper();
        // Malformed HTML (end tag without start tag)
        $html = '</p>';
        
        $dom = $helper->parse($html);
        
        $this->assertNotNull($dom);
        $errors = $helper->getParseErrors();
        
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Unexpected end tag', implode(' ', $errors));
    }

    public function test_returns_null_on_empty_string(): void
    {
        $helper = new DOMDocumentHelper();
        $dom = $helper->parse('');
        
        $this->assertNull($dom);
    }
}
