<?php
/*
Plugin Name: Allow AVIF Uploads (Advanced)
Description: Enables advanced AVIF support with AI optimization, CDN integration, and WebAssembly processing.
Version: 2.0
Author: SoM
*/

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('AVIF_UPLOADS_VERSION', '2.0');
define('AVIF_UPLOADS_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AVIF_UPLOADS_PLUGIN_URL', plugin_dir_url(__FILE__));

// Load core plugin classes
$core_classes = [
    'class-avif-upload.php',
    'class-avif-metadata.php',
    'class-avif-display.php',
    'class-avif-settings.php',
    'class-avif-optimizer.php',
    'class-avif-server-check.php',
    'class-avif-image-editor.php',
    'class-avif-exif-remover.php',
    'class-avif-webp-fallback.php',
    'class-avif-bulk-converter.php',
    'class-avif-lazy-loading.php',
    'class-avif-animated.php',
    'class-avif-seo.php',
    'class-avif-ai-processor.php',
    'class-avif-cdn-handler.php',
    'class-avif-api-endpoints.php',
    'class-avif-queue-manager.php',
    'class-avif-version-control.php',
    'class-avif-wasm-processor.php'
];

foreach ($core_classes as $class) {
    require_once AVIF_UPLOADS_PLUGIN_DIR . 'includes/' . $class;
}

// Initialize the plugin
function avif_uploads_init() {
    // Load translations
    load_plugin_textdomain('allow-avif-uploads', false, dirname(plugin_basename(__FILE__)) . '/languages');

    // Initialize classes
    $plugin_classes = [
        'upload' => new AVIF_Upload(),
        'metadata' => new AVIF_Metadata(),
        'display' => new AVIF_Display(),
        'settings' => new AVIF_Settings(),
        'optimizer' => new AVIF_Optimizer(),
        'server_check' => new AVIF_Server_Check(),
        'exif_remover' => new AVIF_EXIF_Remover(),
        'webp_fallback' => new AVIF_WebP_Fallback(),
        'bulk_converter' => new AVIF_Bulk_Converter(),
        'lazy_loading' => new AVIF_Lazy_Loading(),
        'animated' => new AVIF_Animated(),
        'seo' => new AVIF_SEO(),
        'ai_processor' => new AVIF_AI_Processor(),
        'cdn_handler' => new AVIF_CDN_Handler(),
        'api_endpoints' => new AVIF_API_Endpoints(),
        'queue_manager' => new AVIF_Queue_Manager(),
        'version_control' => new AVIF_Version_Control(),
        'wasm_processor' => new AVIF_WASM_Processor()
    ];

    foreach ($plugin_classes as $class) {
        if (method_exists($class, 'init')) {
            $class->init();
        }
    }

    // Register the AVIF image editor
    add_filter('wp_image_editors', 'register_avif_image_editor');
}
add_action('plugins_loaded', 'avif_uploads_init');

// Register the AVIF image editor
function register_avif_image_editor($editors) {
    array_unshift($editors, 'AVIF_Image_Editor');
    return $editors;
}

// Enqueue admin assets
function avif_uploads_enqueue_assets($hook) {
    if ('settings_page_avif-settings' === $hook || 'media_page_bulk-convert-avif' === $hook) {
        wp_enqueue_style('avif-admin-css', AVIF_UPLOADS_PLUGIN_URL . 'assets/css/admin.css', [], AVIF_UPLOADS_VERSION);
        wp_enqueue_script('avif-admin-js', AVIF_UPLOADS_PLUGIN_URL . 'assets/js/admin.js', ['jquery'], AVIF_UPLOADS_VERSION, true);

        // Load WASM assets if enabled
        if (get_option('avif_enable_wasm', true)) {
            wp_enqueue_script('avif-wasm', AVIF_UPLOADS_PLUGIN_URL . 'assets/js/avif-wasm.js', [], AVIF_UPLOADS_VERSION, true);
            wp_localize_script('avif-wasm', 'avifWasmVars', [
                'wasmUrl' => AVIF_UPLOADS_PLUGIN_URL . 'assets/wasm/avif.wasm'
            ]);
        }
    }
}
add_action('admin_enqueue_scripts', 'avif_uploads_enqueue_assets');

// Activation hook
function avif_uploads_activate() {
    // Add default plugin options on activation
    $default_options = [
        'avif_lazy_loading' => '1',
        'avif_compression_quality' => '80',
        'avif_enable_wasm' => '1',
        'avif_enable_ai' => '0',
        'avif_cdn_enabled' => '0',
        'avif_version_control' => '1',
        'avif_delete_settings_on_deactivate' => '0'
    ];

    foreach ($default_options as $option => $value) {
        if (!get_option($option)) {
            update_option($option, $value);
        }
    }

    // Initialize database tables for queue and version control
    $queue_manager = new AVIF_Queue_Manager();
    $version_control = new AVIF_Version_Control();

    if (method_exists($queue_manager, 'create_tables')) {
        $queue_manager->create_tables();
    }

    if (method_exists($version_control, 'create_tables')) {
        $version_control->create_tables();
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_activation_hook(__FILE__, 'avif_uploads_activate');

// Deactivation hook
function avif_uploads_deactivate() {
    // Remove plugin options on deactivation (if enabled)
    if (get_option('avif_delete_settings_on_deactivate', false)) {
        $cleanup_options = [
            'avif_lazy_loading',
            'avif_compression_quality',
            'avif_enable_wasm',
            'avif_enable_ai',
            'avif_cdn_enabled',
            'avif_version_control',
            'avif_delete_settings_on_deactivate'
        ];

        foreach ($cleanup_options as $option) {
            delete_option($option);
        }

        // Drop custom database tables
        global $wpdb;
        $tables = [
            $wpdb->prefix . 'avif_optimization_queue',
            $wpdb->prefix . 'avif_version_control'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }
    }

    // Flush rewrite rules
    flush_rewrite_rules();
}
register_deactivation_hook(__FILE__, 'avif_uploads_deactivate');