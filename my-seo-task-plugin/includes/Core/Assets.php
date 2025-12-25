<?php
namespace MySeoTask\Core;

use MySeoTask\Core\TasksCatalog;

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

class Assets {

    public static function register_hooks() {
        add_action( 'wp_enqueue_scripts', [ __CLASS__, 'enqueue_front' ] );
    }

    private static function is_bot_user_agent() {
        if ( empty( $_SERVER['HTTP_USER_AGENT'] ) ) {
            return false;
        }
        $ua = strtolower( (string) $_SERVER['HTTP_USER_AGENT'] );
        $bot_signatures = [
            'googlebot','bingbot','slurp','duckduckbot','baiduspider','yandexbot','sogou','exabot',
            'facebookexternalhit','facebot','ia_archiver','ahrefsbot','semrushbot','mj12bot',
            'bot/','crawler','spider','uptimerobot','pingdom','chrome-lighthouse',
        ];
        foreach ( $bot_signatures as $sig ) {
            if ( strpos( $ua, $sig ) !== false ) {
                return true;
            }
        }
        return false;
    }

    private static function passes_targeting() {
        $rules = get_option( 'myseotask_rules', [] );
        if ( empty( $rules ) || ! is_array( $rules ) ) {
            return true;
        }

        $current_url  = isset( $_SERVER['REQUEST_URI'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '';
        $is_logged_in = is_user_logged_in();
        $is_mobile    = function_exists( 'wp_is_mobile' ) ? wp_is_mobile() : false;

        $matches = function( $url, $patterns ) {
            if ( empty( $patterns ) ) return false;
            $lines = array_filter( array_map( 'trim', preg_split( '/\r\n|\r|\n/', (string) $patterns ) ) );
            if ( empty( $lines ) ) return false;
            foreach ( $lines as $pat ) {
                $regex = '/^' . str_replace( '\*', '.*', preg_quote( $pat, '/' ) ) . '$/i';
                if ( preg_match( $regex, $url ) ) return true;
            }
            return false;
        };

        if ( ! empty( $rules['include_urls'] ) ) {
            if ( ! $matches( $current_url, $rules['include_urls'] ) ) return false;
        }
        if ( ! empty( $rules['exclude_urls'] ) && $matches( $current_url, $rules['exclude_urls'] ) ) {
            return false;
        }

        $page_type = self::detect_page_type();
        if ( ! empty( $rules['page_types'] ) && is_array( $rules['page_types'] ) ) {
            if ( ! in_array( $page_type, $rules['page_types'], true ) ) return false;
        }

        $has_user_pref = array_key_exists( 'allow_logged_in', $rules ) || array_key_exists( 'allow_guests', $rules );
        if ( $has_user_pref ) {
            if ( $is_logged_in && empty( $rules['allow_logged_in'] ) ) return false;
            if ( ! $is_logged_in && empty( $rules['allow_guests'] ) ) return false;
        }

        if ( ! empty( $rules['devices'] ) && is_array( $rules['devices'] ) ) {
            if ( $is_mobile && ! in_array( 'mobile', $rules['devices'], true ) ) return false;
            if ( ! $is_mobile && ! in_array( 'desktop', $rules['devices'], true ) ) return false;
        }

        return true;
    }

    private static function detect_page_type() {
        if ( function_exists( 'is_cart' ) && is_cart() ) return 'cart';
        if ( function_exists( 'is_checkout' ) && is_checkout() ) return 'checkout';
        if ( function_exists( 'is_search' ) && is_search() ) return 'search';
        if ( function_exists( 'is_product' ) && is_product() ) return 'product';
        if ( function_exists( 'is_product_category' ) && is_product_category() ) return 'category';
        if ( function_exists( 'is_product_tag' ) && is_product_tag() ) return 'category';

        if ( is_category() || is_archive() ) return 'category';
        if ( is_single() ) return 'post';

        return 'generic';
    }

    public static function enqueue_front() {
        if ( is_admin() ) return;
        if ( self::is_bot_user_agent() ) return;

        $cfg = Config::all();
        if ( empty( $cfg['enabled'] ) ) return;
        if ( ! self::passes_targeting() ) return;

        // Canonical registry injected => JS & Admin never drift.
        $cfg['tasks_registry'] = TasksCatalog::registry();

        wp_register_script( 'my-seo-task-config', '', [], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-config' );
        wp_add_inline_script(
            'my-seo-task-config',
            'window.__MYSEOTASK_CONFIG__ = ' . wp_json_encode( $cfg ) . ';',
            'before'
        );

        // Page detector must load before modules that read pageType.
        wp_enqueue_script( 'my-seo-task-page-detector', MST_PLUGIN_URL . 'assets/js/page-detector.js', [], MST_PLUGIN_VERSION, true );

        // CSS
        wp_enqueue_style( 'my-seo-task-progress-css', MST_PLUGIN_URL . 'assets/css/progress-bar.css', [], MST_PLUGIN_VERSION );
        wp_enqueue_style( 'my-seo-task-start-button-css', MST_PLUGIN_URL . 'assets/css/start-button.css', [], MST_PLUGIN_VERSION );
        wp_enqueue_style( 'my-seo-task-tasks-overlay-css', MST_PLUGIN_URL . 'assets/css/tasks-overlay.css', [], MST_PLUGIN_VERSION );
        wp_enqueue_style( 'my-seo-task-diamond-css', MST_PLUGIN_URL . 'assets/css/diamond.css', [], MST_PLUGIN_VERSION );
        wp_enqueue_style( 'my-seo-task-internal-link-hint-css', MST_PLUGIN_URL . 'assets/css/internal-link-hint.css', [], MST_PLUGIN_VERSION );

        // JS core
        wp_enqueue_script( 'my-seo-task-session-manager', MST_PLUGIN_URL . 'assets/js/session-manager.js', [], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-progress-bar', MST_PLUGIN_URL . 'assets/js/progress-bar.js', [ 'my-seo-task-session-manager', 'my-seo-task-page-detector' ], MST_PLUGIN_VERSION, true );

        // Task engine: tasks-registry.js reads injected tasks_registry
        wp_enqueue_script( 'my-seo-task-tasks-registry', MST_PLUGIN_URL . 'assets/js/tasks-registry.js', [ 'my-seo-task-page-detector' ], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-tasks-generator', MST_PLUGIN_URL . 'assets/js/tasks-generator.js', [ 'my-seo-task-tasks-registry', 'my-seo-task-page-detector' ], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-task-telemetry', MST_PLUGIN_URL . 'assets/js/task-telemetry.js', [], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-task-guide-popup', MST_PLUGIN_URL . 'assets/js/task-guide-popup.js', [ 'my-seo-task-task-telemetry', 'my-seo-task-page-detector' ], MST_PLUGIN_VERSION, true );

        // Diamond & validators
        wp_enqueue_script( 'my-seo-task-diamond-manager', MST_PLUGIN_URL . 'assets/js/diamond-manager.js', [], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-validator-utils', MST_PLUGIN_URL . 'assets/js/validators/validator-utils.js', [], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-validator-state', MST_PLUGIN_URL . 'assets/js/validators/validator-state.js', [ 'my-seo-task-validator-utils' ], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-validator-telemetry-bridge', MST_PLUGIN_URL . 'assets/js/validators/validator-telemetry-bridge.js', [ 'my-seo-task-validator-utils', 'my-seo-task-task-telemetry' ], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-validator-guided-arrow', MST_PLUGIN_URL . 'assets/js/validators/validator-guided-arrow.js', [ 'my-seo-task-validator-utils', 'my-seo-task-validator-state' ], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-validator-diamonds', MST_PLUGIN_URL . 'assets/js/validators/validator-diamonds.js', [ 'my-seo-task-validator-utils', 'my-seo-task-validator-state', 'my-seo-task-diamond-manager', 'my-seo-task-validator-telemetry-bridge' ], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-validator-internal-link', MST_PLUGIN_URL . 'assets/js/validators/validator-internal-link.js', [ 'my-seo-task-validator-utils', 'my-seo-task-validator-state', 'my-seo-task-validator-guided-arrow' ], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-validator-image', MST_PLUGIN_URL . 'assets/js/validators/validator-image.js', [ 'my-seo-task-validator-utils', 'my-seo-task-validator-state', 'my-seo-task-validator-guided-arrow', 'my-seo-task-validator-telemetry-bridge' ], MST_PLUGIN_VERSION, true );
        wp_enqueue_script( 'my-seo-task-validator-scroll', MST_PLUGIN_URL . 'assets/js/validators/validator-scroll.js', [ 'my-seo-task-validator-utils', 'my-seo-task-validator-telemetry-bridge' ], MST_PLUGIN_VERSION, true );

        wp_enqueue_script( 'my-seo-task-tasks-validator', MST_PLUGIN_URL . 'assets/js/validators/validator-orchestrator.js', [
            'my-seo-task-validator-utils',
            'my-seo-task-validator-state',
            'my-seo-task-validator-telemetry-bridge',
            'my-seo-task-validator-guided-arrow',
            'my-seo-task-validator-diamonds',
            'my-seo-task-validator-internal-link',
            'my-seo-task-validator-image',
            'my-seo-task-validator-scroll',
            'my-seo-task-page-detector',
        ], MST_PLUGIN_VERSION, true );

        wp_enqueue_script( 'my-seo-task-tasks-overlay', MST_PLUGIN_URL . 'assets/js/tasks-ui-overlay.js', [ 'my-seo-task-task-telemetry', 'my-seo-task-page-detector' ], MST_PLUGIN_VERSION, true );

        wp_enqueue_script( 'my-seo-task-tasks-flow', MST_PLUGIN_URL . 'assets/js/tasks-flow-manager.js', [
            'my-seo-task-tasks-generator',
            'my-seo-task-tasks-overlay',
            'my-seo-task-tasks-validator',
            'my-seo-task-progress-bar',
            'my-seo-task-session-manager',
            'my-seo-task-task-guide-popup',
            'my-seo-task-page-detector',
        ], MST_PLUGIN_VERSION, true );

        wp_enqueue_script( 'my-seo-task-start-button', MST_PLUGIN_URL . 'assets/js/start-button.js', [
            'my-seo-task-session-manager',
            'my-seo-task-progress-bar',
            'my-seo-task-tasks-flow',
            'my-seo-task-page-detector',
        ], MST_PLUGIN_VERSION, true );

        wp_enqueue_script( 'my-seo-task-popup-totp', MST_PLUGIN_URL . 'assets/js/popup-totp.js', [], MST_PLUGIN_VERSION, true );
    }
}
