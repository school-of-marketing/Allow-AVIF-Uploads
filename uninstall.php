<?php
/**
 * AVIF Upload Plugin Uninstaller
 *
 * This file runs when the plugin is uninstalled via the WordPress admin panel.
 * It cleans up all plugin-related data from the database.
 *
 * @package     AllowAvifUploads
 * @version     1.0.0
 * @license     GPL-2.0+
 */

// Exit if accessed directly
if (!defined('WP_UNINSTALL_PLUGIN') || !WP_UNINSTALL_PLUGIN || !defined('ABSPATH')) {
    exit('Access Denied');
}

// Ensure we have access to WordPress functions
if (!function_exists('delete_option')) {
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
}

/**
 * Main uninstall function wrapped in try-catch for error handling
 */
function avif_uninstall_plugin()
{
    global $wpdb;

    // Define plugin options
    $avif_options = array(
        'avif_lazy_loading',
        'avif_compression_quality',
        'avif_enable_wasm',
        'avif_enable_ai',
        'avif_cdn_enabled',
        'avif_version_control',
        'avif_delete_settings_on_deactivate'
    );

    // Delete plugin options
    foreach ($avif_options as $option) {
        delete_option($option);
    }

    // Only proceed with table deletion if wpdb is available
    if (isset($wpdb) && $wpdb instanceof wpdb) {
        $tables = array(
            $wpdb->prefix . 'avif_optimization_queue',
            $wpdb->prefix . 'avif_version_control'
        );

        foreach ($tables as $table) {
            $wpdb->query(
                $wpdb->prepare(
                    "DROP TABLE IF EXISTS %i",
                    $table
                )
            );
        }

        // Clear any related transients
        $wpdb->query(
            "DELETE FROM {$wpdb->options} WHERE option_name LIKE '%_avif_%'"
        );
    }
}

// Execute uninstallation with error handling
try {
    avif_uninstall_plugin();
} catch (Exception $e) {
    if (function_exists('error_log')) {
        error_log('AVIF Plugin Uninstall Error: ' . $e->getMessage());
    }
    if (defined('WP_DEBUG') && WP_DEBUG) {
        trigger_error($e->getMessage(), E_USER_WARNING);
    }
}