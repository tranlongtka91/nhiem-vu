<?php
/**
 * Plugin Name: My SEO Task
 * Description: Mini-game nhiệm vụ SEO (Start Button, Flow, Overlay, Validators, Telemetry).
 * Version: 0.6.0
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'MST_PLUGIN_FILE', __FILE__ );
define( 'MST_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'MST_PLUGIN_URL', plugin_dir_url( __FILE__ ) );
define( 'MST_PLUGIN_VERSION', '0.6.0' );

// Core
require_once MST_PLUGIN_DIR . 'includes/Core/Config.php';
require_once MST_PLUGIN_DIR . 'includes/Core/TasksCatalog.php';
require_once MST_PLUGIN_DIR . 'includes/Core/Assets.php';
require_once MST_PLUGIN_DIR . 'includes/Core/Plugin.php';

// Front
require_once MST_PLUGIN_DIR . 'includes/Front/Bootstrap.php';

// Admin
require_once MST_PLUGIN_DIR . 'includes/Admin/Menu.php';
require_once MST_PLUGIN_DIR . 'includes/Admin/Pages/SettingsPage.php';
require_once MST_PLUGIN_DIR . 'includes/Admin/Pages/TasksPage.php';
require_once MST_PLUGIN_DIR . 'includes/Admin/Pages/RulesPage.php';
require_once MST_PLUGIN_DIR . 'includes/Admin/Pages/UIPage.php';
require_once MST_PLUGIN_DIR . 'includes/Admin/Pages/GuidesPage.php';
require_once MST_PLUGIN_DIR . 'includes/Admin/Pages/AnalyticsPage.php';

// REST
require_once MST_PLUGIN_DIR . 'includes/Rest/Routes.php';
require_once MST_PLUGIN_DIR . 'includes/Rest/Controllers/ConfigController.php';
require_once MST_PLUGIN_DIR . 'includes/Rest/Controllers/EventsController.php';

// Telemetry / Data
require_once MST_PLUGIN_DIR . 'includes/Telemetry/EventService.php';
require_once MST_PLUGIN_DIR . 'includes/Telemetry/Aggregator.php';
require_once MST_PLUGIN_DIR . 'includes/Telemetry/Cleanup.php';
require_once MST_PLUGIN_DIR . 'includes/Data/Migrations.php';

// Ensure admin_post handlers are registered.
MySeoTask\Admin\Pages\TasksPage::register_hooks();
MySeoTask\Admin\Pages\GuidesPage::register_hooks();

MySeoTask\Core\Plugin::init();
