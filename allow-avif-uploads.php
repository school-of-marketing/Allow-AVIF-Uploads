<?php
/*
Plugin Name: Allow AVIF Uploads (Advanced)
Description: Enables advanced AVIF support with AI optimization, CDN integration, and WebAssembly processing.
Version: 3.0
Author: SoM
*/

/**
 * Advanced AVIF image support plugin for WordPress
 * 
 * This plugin provides comprehensive AVIF image support with features including:
 * - AI-powered image optimization
 * - CDN integration
 * - WebAssembly processing
 * - Lazy loading
 * - Bulk conversion
 * - Version control
 * - EXIF data handling
 * - SEO optimization
 * 
 * @package AVIF
 * @version 3.0
 * 
 * @since 2.0 Added AI optimization, CDN integration, and WebAssembly processing
 * @since 1.0 Initial release with basic AVIF upload support
 * 
 * Requirements:
 * - WordPress 6.0 or higher
 * - PHP 8.1 or higher
 * - GD or Imagick extension
 * 
 * @author SoM
 * @copyright 2023 SoM
 * @license GPL-2.0-or-later
 */

namespace AVIF;


// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Define Constants
define('AVIF_VERSION', '2.0');
define('AVIF_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('AVIF_PLUGIN_URL', plugin_dir_url(__FILE__));

final class AVIFUploads
{
    private static $instance = null;
    private $plugin_classes = [];

    private function __construct()
    {
        $this->init_hooks();
        $this->load_classes();
    }

    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function init_hooks(): void
    {
        add_action('plugins_loaded', [$this, 'init_plugin']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    private function load_classes(): void
    {
        $core_classes_map = [
            'AI_Processor' => 'ai-processor',
            'Animated' => 'animated',
            'API_Endpoints' => 'api-endpoints',
            // 'Bulk_Converter' => 'bulk-converter',
            'CDN_Handler' => 'cdn-handler',
            'Display' => 'display',
            'EXIF_Remover' => 'exif-remover',
            // 'Image_Editor' => 'image-editor',
            'Lazy_Loading' => 'lazy-loading',
            // 'Metadata' => 'metadata',
            // 'Optimizer' => 'optimizer',
            'Queue_Manager' => 'queue-manager',
            'SEO' => 'seo',
            // 'Server_Check' => 'server-check',
            'Settings' => 'settings',
            'Upload' => 'upload',
            'Version_Control' => 'version-control',
            'WASM_Processor' => 'wasm-processor',
            'WebP_Fallback' => 'webp-fallback'
        ];

        foreach ($core_classes_map as $class => $filename) {
            $file = AVIF_PLUGIN_DIR . "/includes/class-avif-{$filename}.php";
            if (file_exists($file)) {
                require_once $file;
                if (class_exists($class)) {
                    $this->plugin_classes[$class] = new $class();
                }
            }
        }
    }

    public function init_plugin(): void
    {
        load_plugin_textdomain('allow-avif-uploads', false, dirname(plugin_basename(__FILE__)) . '/languages');

        foreach ($this->plugin_classes as $class) {
            if (method_exists($class, 'init')) {
                $class->init();
            }
        }

        add_filter('wp_image_editors', [$this, 'register_avif_image_editor']);
    }

    /**
     * Registers the AVIF image editor with WordPress
     * 
     * @param array $editors Array of image editor class names
     * @return array Modified array of image editor class names
     */
    public function register_avif_image_editor(array $editors): array
    {
        array_unshift($editors, 'Image_Editor');
        return $editors;
    }


    public function enqueue_assets(string $hook): void
    {
        if (!in_array($hook, ['settings_page_avif-settings', 'media_page_bulk-convert-avif'])) {
            return;
        }

        wp_enqueue_style(
            'avif-admin-css',
            AVIF_PLUGIN_URL . 'assets/css/admin.css',
            [],
            AVIF_VERSION
        );

        wp_enqueue_script(
            'avif-admin-js',
            AVIF_PLUGIN_URL . 'assets/js/admin.js',
            ['jquery'],
            AVIF_VERSION,
            true
        );

        if (get_option('avif_enable_wasm', true)) {
            wp_enqueue_script(
                'avif-wasm',
                AVIF_PLUGIN_URL . 'assets/js/avif-wasm.js',
                [],
                AVIF_VERSION,
                true
            );

            wp_localize_script('avif-wasm', 'avifWasmVars', [
                'wasmUrl' => AVIF_PLUGIN_URL . 'assets/wasm/avif.wasm'
            ]);
        }
    }

    public function activate(): void
    {
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
            add_option($option, $value);
        }

        if (isset($this->plugin_classes['QueueManager'])) {
            $this->plugin_classes['QueueManager']->create_tables();
        }

        if (isset($this->plugin_classes['VersionControl'])) {
            $this->plugin_classes['VersionControl']->create_tables();
        }

        flush_rewrite_rules();
    }

    public function deactivate(): void
    {
        if (!get_option('avif_delete_settings_on_deactivate', false)) {
            return;
        }

        $cleanup_options = [
            'avif_lazy_loading',
            'avif_compression_quality',
            'avif_enable_wasm',
            'avif_enable_ai',
            'avif_cdn_enabled',
            'avif_version_control',
            'avif_delete_settings_on_deactivate'
        ];

        array_map('delete_option', $cleanup_options);

        global $wpdb;
        $tables = [
            $wpdb->prefix . 'avif_optimization_queue',
            $wpdb->prefix . 'avif_version_control'
        ];

        foreach ($tables as $table) {
            $wpdb->query("DROP TABLE IF EXISTS $table");
        }

        flush_rewrite_rules();
    }
}

// Initialize plugin
AVIFUploads::get_instance();

