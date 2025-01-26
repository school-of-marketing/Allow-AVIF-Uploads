<?php
class AVIF_Server_Check {
    public function __construct() {
        add_action( 'admin_notices', array( $this, 'check_avif_support' ) );
    }

    public function check_avif_support() {
        if ( ! $this->is_avif_supported() ) {
            echo '<div class="notice notice-warning"><p>';
            echo 'Your server does not support AVIF. Please ensure Imagick is installed with AVIF support.';
            echo '</p></div>';
        }
    }

    public function is_avif_supported() {
        if ( ! class_exists( 'Imagick' ) ) {
            return false;
        }

        $imagick = new Imagick();
        $formats = $imagick->queryFormats();
        return in_array( 'AVIF', $formats );
    }
}