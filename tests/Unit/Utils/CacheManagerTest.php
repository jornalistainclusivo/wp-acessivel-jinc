<?php declare(strict_types=1);

namespace WpAcessivelJinc\Tests\Unit\Utils;

use PHPUnit\Framework\TestCase;
use WpAcessivelJinc\Utils\CacheManager;

final class CacheManagerTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();
        global $_jinc_transients, $_jinc_posts, $_jinc_post_meta;
        $_jinc_transients = [];
        $_jinc_posts = [];
        $_jinc_post_meta = [];
    }

    public function testGetReturnsFalseIfNoCacheExists(): void
    {
        global $_jinc_posts;
        $_jinc_posts[10] = ["ID" => 10, "post_modified" => "2023-01-01 12:00:00"];
        
        $cache = new CacheManager();
        $this->assertFalse($cache->get(10));
    }

    public function testSetStoresContentInTransientAndPostMeta(): void
    {
        global $_jinc_posts, $_jinc_transients, $_jinc_post_meta;
        $_jinc_posts[10] = ["ID" => 10, "post_modified" => "2023-01-01 12:00:00"];
        
        $cache = new CacheManager();
        $content = "<html>Cached</html>";
        $result = $cache->set(10, $content);
        
        $this->assertTrue($result);
        
        $timestamp = strtotime("2023-01-01 12:00:00");
        $expectedKey = "jinc_a11y_content_10_" . $timestamp;
        
        $this->assertArrayHasKey($expectedKey, $_jinc_transients);
        $this->assertEquals($content, $_jinc_transients[$expectedKey]);
        
        // Assert that the key is saved in post meta to track the latest cache
        $this->assertEquals($expectedKey, $_jinc_post_meta[10]["_jinc_a11y_cache_key"]);
    }

    public function testGetReturnsCachedContent(): void
    {
        global $_jinc_posts, $_jinc_transients;
        $_jinc_posts[10] = ["ID" => 10, "post_modified" => "2023-01-01 12:00:00"];
        
        $timestamp = strtotime("2023-01-01 12:00:00");
        $expectedKey = "jinc_a11y_content_10_" . $timestamp;
        
        $content = "<html>Cached from Transient</html>";
        $_jinc_transients[$expectedKey] = $content;
        
        $cache = new CacheManager();
        $this->assertEquals($content, $cache->get(10));
    }

    public function testPurgeDeletesCacheUsingPostMeta(): void
    {
        global $_jinc_posts, $_jinc_transients, $_jinc_post_meta;
        $_jinc_posts[10] = ["ID" => 10, "post_modified" => "2023-01-01 12:00:00"];
        
        $oldKey = "jinc_a11y_content_10_11111";
        $_jinc_transients[$oldKey] = "old_cache_1";
        $_jinc_post_meta[10]["_jinc_a11y_cache_key"] = $oldKey;

        // Post 11 has its own cache
        $_jinc_transients["jinc_a11y_content_11_33333"] = "other_post_cache"; 
        
        $cache = new CacheManager();
        $cache->purge(10);
        
        // Assert: The transient is deleted
        $this->assertArrayNotHasKey($oldKey, $_jinc_transients);
        
        // Other post cache remains
        $this->assertArrayHasKey("jinc_a11y_content_11_33333", $_jinc_transients);
    }
}

