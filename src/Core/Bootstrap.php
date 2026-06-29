<?php declare(strict_types=1);

namespace WpAcessivelJinc\Core;

use WpAcessivelJinc\Modules\SemanticEnforcer\ContentFilter;
use WpAcessivelJinc\Modules\SemanticEnforcer\DOMSerializer;
use WpAcessivelJinc\Modules\SemanticEnforcer\HeadingHierarchyFixer;
use WpAcessivelJinc\Modules\SemanticEnforcer\LandmarkInjector;
use WpAcessivelJinc\Modules\FrontendBar\BarInjector;
use WpAcessivelJinc\Utils\CacheManager;
use WpAcessivelJinc\Utils\DOMDocumentHelper;
use WpAcessivelJinc\Utils\Logger;
use WpAcessivelJinc\Modules\MediaGatekeeper\AdminNoticeManager;
use WpAcessivelJinc\Modules\MediaGatekeeper\AltTextValidator;
use WpAcessivelJinc\Modules\MediaGatekeeper\AsyncAIProcessor;
use WpAcessivelJinc\Modules\MediaGatekeeper\AttachmentMetaFilter;
use WpAcessivelJinc\Modules\MediaGatekeeper\RestUploadValidator;

/**
 * Plugin initialization, module loading, and hook registration.
 *
 * @spec-ref SDD Section 8 (Phase 0)
 */
final class Bootstrap
{
    private static ?self $instance = null;

    private function __construct() {}

    /**
     * Singleton initializer. Called from wp-acessivel-jinc.php on plugins_loaded.
     */
    public static function init(): void
    {
        if (self::$instance !== null) {
            return;
        }

        self::$instance = new self();
        self::$instance->registerModules();
    }

    /**
     * Wire up modules with their dependencies and register WordPress hooks.
     */
    private function registerModules(): void
    {
        // Shared utilities
        $logger = new Logger();
        $domHelper = new DOMDocumentHelper();
        $cacheManager = new CacheManager();

        // ── Semantic Enforcer module ──
        $headingFixer = new HeadingHierarchyFixer();
        $landmarkInjector = new LandmarkInjector();
        $serializer = new DOMSerializer();

        $contentFilter = new ContentFilter(
            headingFixer: $headingFixer,
            landmarkInjector: $landmarkInjector,
            domHelper: $domHelper,
            serializer: $serializer,
            cache: $cacheManager,
            logger: $logger,
        );

        $contentFilter->register();

        // ── Media Gatekeeper module (Descreve AI) ──
        $altTextValidator = new AltTextValidator();
        $adminNoticeManager = new AdminNoticeManager();
        
        $attachmentMetaFilter = new AttachmentMetaFilter($altTextValidator, $adminNoticeManager, $logger);
        $attachmentMetaFilter->register();
        
        $restUploadValidator = new RestUploadValidator($altTextValidator, $logger);
        $restUploadValidator->register();
        
        $asyncAiProcessor = new AsyncAIProcessor($logger);
        $asyncAiProcessor->register();

        // ── Frontend Accessibility Bar module (Phase 3) ──
        $pluginFile = dirname(__DIR__, 2) . '/wp-acessivel-jinc.php';
        $barInjector = new BarInjector(pluginFile: $pluginFile);
        $barInjector->register();

        // ── Theming Engine module (Phase 3.5) ──
        $settingsPage = new \WpAcessivelJinc\Modules\ThemingEngine\SettingsPage();
        $settingsPage->init();

        $themeEngine = new \WpAcessivelJinc\Modules\ThemingEngine\ThemeEngine();
        $themeEngine->init();

        // ── Cache invalidation hooks ──
        if (function_exists('add_action')) {
            add_action('save_post', [$cacheManager, 'invalidatePostTransient']);
            add_action('update_option_wp_acessivel_jinc_settings', [$cacheManager, 'flushAllTransients']);
        }
    }
}

