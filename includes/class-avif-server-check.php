<?php

/**
 * class Server_Check
 * 
 * Handles server-side validation for AVIF image support.
 * 
 * @since 1.0.0
 */
use \Intervention\Image\Drivers\GD\Driver;
class Server_Check
{
    /**
     * Display admin notice if AVIF support is not available
     */
    public function check_avif_support()
    {
        try {
            if (!$this->is_avif_supported()) {
                $message = sprintf(
                    '<div class="notice notice-warning"><p>%s</p></div>',
                    esc_html__('AVIF support requires GD (PHP 8.1+ with libavif)', 'allow-avif-uploads')
                );
                echo wp_kses_post($message);
            }
        } catch (Exception $e) {
            error_log('AVIF Support Check Error: ' . $e->getMessage());
        }
    }

    /**
     * Check AVIF support using GD
     */
    public function is_avif_supported()
    {
        try {
            return $this->check_gd_support();
        } catch (Exception $e) {
            error_log('AVIF Support Check Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Verify GD driver capabilities
     */
    private function check_gd_support()
    {
        return extension_loaded('gd') &&
            function_exists('imageavif');
    }
}
