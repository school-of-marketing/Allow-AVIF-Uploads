<?php
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

// Delete plugin options
$options = [
    'avif_lazy_loading',
    'avif_compression_quality',
    'avif_enable_wasm',
    'avif_enable_ai',
    'avif_cdn_enabled',
    'avif_version_control',
    'avif_delete_settings_on_deactivate'
];

foreach ($options as $option) {
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