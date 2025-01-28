<?php

/**
 * Plugin Name: Allow AVIF Uploads (Advanced)  
 * Plugin URI: https://github.com/school-of-marketing/Allow-AVIF-Uploads/
 * Description: Enables advanced AVIF support for WordPress.
 * Version: 3.1
 * Author: SoM
 * Author URI: https://www.school-of-marketing.com/
 * Text Domain: allow-avif-uploads
 * Domain Path: /languages
 * License: GPL-2.0-or-later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Requires at least: 6.7
 * Requires PHP: 8.1
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
 * @version 3.1
 * 
 * @since 3.0 Added EXIF data handling and SEO optimization
 * @since 2.0 Added AI optimization, CDN integration, and WebAssembly processing
 * @since 1.0 Initial release with basic AVIF upload support
 * 
 * Requirements:
 * - WordPress 6.7 or higher
 * - PHP 8.1 or higher
 * - GD extension
 * 
 * @author SoM
 * @copyright 2025 SoM
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


require_once(__DIR__ . '/vendor/autoload.php');
/**
 * Main plugin class for handling AVIF image uploads in WordPress.
 * 
 * This class implements the Singleton pattern and manages the core functionality
 * of the AVIF uploads plugin including initialization, hooks, asset loading,
 * and plugin lifecycle management.
 * 
 * @since 1.0.0
 * @final
 */
final class AVIFUploads
{
    private static $instance = null;
    private $plugin_classes = [];
    private $is_enabled = false;

    /**
     * Constructor for the class.
     * 
     * Initializes the plugin by checking if AVIF uploads are enabled through WordPress options.
     * If enabled, it initializes necessary hooks and loads required classes.
     * 
     * @since 1.0.0
     * @access private
     */
    private function __construct()
    {
        $this->is_enabled = (bool) get_option('avif_enable_uploads', false);

        if ($this->is_enabled) {
            $this->init_hooks();
            $this->load_classes();
        }
    }

    /**
     * Returns the singleton instance of the class.
     * 
     * This method implements the Singleton pattern ensuring only one instance
     * of the class exists throughout the application's lifecycle.
     * 
     * @since 1.0.0
     * @access public
     * @static
     *
     * @return self The single instance of this class.
     */
    public static function get_instance(): self
    {
        if (null === self::$instance) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    /**
     * Initialize WordPress hooks and actions for the plugin.
     * 
     * If the plugin is not enabled, no hooks will be registered.
     * Registers the following hooks:
     * - 'plugins_loaded' for plugin initialization
     * - 'admin_enqueue_scripts' for loading admin assets
     * - Activation hook for plugin setup
     * - Deactivation hook for cleanup
     * 
     * @return void
     */
    private function init_hooks(): void
    {
        if (!$this->is_enabled) {
            return;
        }

        add_action('plugins_loaded', [$this, 'init_plugin']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_assets']);
        register_activation_hook(__FILE__, [$this, 'activate']);
        register_deactivation_hook(__FILE__, [$this, 'deactivate']);
    }

    /**
     * Loads core plugin classes dynamically based on a predefined map.
     * 
     * This method initializes the plugin's core functionality by loading classes
     * from the includes directory. Each class corresponds to a specific feature
     * or functionality of the plugin.
     * 
     * @since 1.0.0
     * @access private
     * 
     * @uses AVIF_PLUGIN_DIR Constant containing plugin's root directory path
     * 
     * The method:
     * 1. Checks if plugin is enabled before proceeding
     * 2. Maps class names to their corresponding file names
     * 3. Loads each class file if it exists
     * 4. Instantiates each class if successfully loaded
     * 
     * @return void
     */
    private function load_classes(): void
    {
        if (!$this->is_enabled) {
            return;
        }

        $core_classes_map = [
            'Bulk_Converter' => 'bulk-converter',
            'Display' => 'display',
            'Lazy_Loading' => 'lazy-loading',
            'Metadata' => 'metadata',
            'Optimizer' => 'optimizer',
            'Queue_Manager' => 'queue-manager',
            'SEO' => 'seo',
            'Server_Check' => 'server-check',
            'Settings' => 'settings',
            'Upload' => 'upload',
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

    /**
     * Initializes the plugin functionality.
     * 
     * This method performs the following tasks:
     * - Checks if the plugin is enabled before proceeding
     * - Loads the plugin's text domain for internationalization
     * - Initializes all registered plugin classes that have an 'init' method
     * 
     * @since 1.0.0
     * @access public
     * 
     * @return void
     */
    public function init_plugin(): void
    {
        if (!$this->is_enabled) {
            return;
        }

        load_plugin_textdomain('allow-avif-uploads', false, dirname(plugin_basename(__FILE__)) . '/languages');

        foreach ($this->plugin_classes as $class) {
            if (method_exists($class, 'init')) {
                $class->init();
            }
        }
    }

    /**
     * Enqueues necessary CSS and JavaScript assets for the plugin's admin pages.
     *
     * Loads the required stylesheets and scripts only on specific admin pages:
     * - AVIF settings page
     * - Bulk convert AVIF page
     *
     * Additionally loads WebAssembly assets if WASM support is enabled in plugin settings.
     *
     * @param string $hook The current admin page hook suffix
     * @return void
     *
     * @see wp_enqueue_style()
     * @see wp_enqueue_script()
     * @see wp_localize_script()
     * @see get_option()
     */
    public function enqueue_assets(string $hook): void
    {
        if (!$this->is_enabled || !in_array($hook, ['settings_page_avif-settings', 'media_page_bulk-convert-avif'])) {
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

    /**
     * Activates the plugin and sets up default options.
     * 
     * This method performs the following tasks:
     * - Sets default plugin options including AVIF upload settings, lazy loading,
     *   compression quality, WebAssembly support, AI features, CDN settings,
     *   version control, and cleanup settings
     * - Creates required database tables through Queue_Manager if available
     * - Flushes WordPress rewrite rules
     *
     * @since 1.0.0
     * @access public
     * @return void
     *
     * @uses add_option() to add plugin settings to WordPress options table
     * @uses flush_rewrite_rules() to reset WordPress permalink structure
     */
    public function activate(): void
    {
        $default_options = [
            'avif_enable_uploads' => '1',
            'avif_lazy_loading' => '1',
            'avif_compression_quality' => '80',
            'avif_enable_wasm' => '1',
            'avif_enable_ai' => '0',
            'avif_cdn_enabled' => '0',
            'avif_version_control' => '1',
            'avif_delete_settings_on_deactivate' => '1'
        ];

        foreach ($default_options as $option => $value) {
            add_option($option, $value);
        }

        if (isset($this->plugin_classes['Queue_Manager'])) {
            $this->plugin_classes['Queue_Manager']->create_tables();
        }

        flush_rewrite_rules();
    }

    /**
     * Handles the plugin deactivation process.
     * 
     * This method performs cleanup operations when the plugin is deactivated:
     * - If enabled in settings, removes all plugin-related options from wp_options table
     * - Deletes the following plugin-specific database tables:
     *   - {prefix}_avif_optimization_queue
     *   - {prefix}_avif_version_control
     * - Flushes WordPress rewrite rules
     *
     * The cleanup only occurs if the 'avif_delete_settings_on_deactivate' option is set to true.
     *
     * @return void
     */
    public function deactivate(): void
    {
        if (!get_option('avif_delete_settings_on_deactivate', false)) {
            return;
        }

        $cleanup_options = [
            'avif_enable_uploads',
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

