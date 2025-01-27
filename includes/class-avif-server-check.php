<?php

/**
 * class Server_Check
 * 
 * Handles server-side validation for AVIF image support.
 * 
 * @since 1.0.0
 */
class Server_Check
{


    /**
     * Display admin notice if AVIF support is not available.
     *
     * @since 1.0.0
     * @return void
     */
    public function check_avif_support() {


        try {
            if (!$this->is_avif_supported()) {
                $message = sprintf(
                    '<div class="notice notice-warning"><p>%s</p></div>',
                    esc_html__('Your server does not support AVIF. Please ensure Imagick is installed with AVIF support.', 'allow-avif-uploads')
                );
                echo wp_kses_post($message);
            }
        } catch (Exception $e) {
            error_log('AVIF Support Check Error: ' . $e->getMessage());
        }
    }

    /**
     * Check if server supports AVIF image format.
     *
     * @since 1.0.0
     * @return boolean True if AVIF is supported, false otherwise.
     */
    public function is_avif_supported() {


        try {
            if (!extension_loaded('imagick') || !class_exists('Imagick')) {
                return false;
            }

            $imagick = new Imagick();
            $formats = $imagick->queryFormats();
            return in_array('AVIF', array_map('strtoupper', $formats), true);

        } catch (Exception $e) {
            error_log('General Error: ' . $e->getMessage());
            return false;
        }
    }
}