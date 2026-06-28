<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit\SemanticEnforcer;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Modules\SemanticEnforcer\ContentFilter;
use WpAcessivelJinc\Modules\SemanticEnforcer\HeadingHierarchyFixer;
use WpAcessivelJinc\Modules\SemanticEnforcer\LandmarkInjector;
use WpAcessivelJinc\Modules\SemanticEnforcer\DOMSerializer;
use WpAcessivelJinc\Utils\DOMDocumentHelper;
use WpAcessivelJinc\Utils\CacheManager;
use WpAcessivelJinc\Utils\Logger;
use DOMDocument;

final class ContentFilterTest extends TestCase
{
    private ContentFilter $filter;
    private $headingFixerMock;
    private $landmarkInjectorMock;
    private $domHelperMock;
    private $serializerMock;
    private $cacheMock;
    private $loggerMock;

    protected function setUp(): void
    {
        parent::setUp();
        
        $headingFixer = new HeadingHierarchyFixer();
        $landmarkInjector = new LandmarkInjector();
        $domHelper = new DOMDocumentHelper();
        $serializer = new DOMSerializer();
        $this->cacheMock = $this->createMock(CacheManager::class);
        $logger = new Logger();

        $this->filter = new ContentFilter(
            $headingFixer,
            $landmarkInjector,
            $domHelper,
            $serializer,
            $this->cacheMock,
            $logger
        );
        
        global $_jinc_posts;
        $_jinc_posts[10] = ["ID" => 10, "post_modified" => "2023-01-01 12:00:00"];
        
        if (!function_exists("get_the_ID")) {
            eval('function get_the_ID() { return 10; }');
        }
        if (!function_exists("get_the_modified_date")) {
            eval('function get_the_modified_date($format) { return "2023-01-01 12:00:00"; }');
        }
    }

    public function testReturnsEarlyIfCacheHit(): void
    {
        // Assert cache GET is called
        $this->cacheMock->expects($this->once())
            ->method("get")
            ->with(10)
            ->willReturn("<html>Cached Early Return</html>");
            
        $this->cacheMock->expects($this->never())->method("set");
        
        $result = $this->filter->filterContent("<html>Original</html>");
        
        $this->assertEquals("<html>Cached Early Return</html>", $result);
    }
    
    public function testProcessesAndCachesOnMiss(): void
    {
        $this->cacheMock->expects($this->once())
            ->method("get")
            ->with(10)
            ->willReturn(false); // Cache Miss
            
        $this->cacheMock->expects($this->once())
            ->method("set")
            ->with(10, $this->anything());
            
        $result = $this->filter->filterContent("<html><h1>Original</h1></html>");
        
        $this->assertStringContainsString("<article", $result);
    }
}
