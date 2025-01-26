<?php
class AVIF_WebP_Fallback {
    public function __construct() {
        add_filter( 'wp_get_attachment_image_src', array( $this, 'add_webp_fallback' ), 10, 4 );
    }

    public function add_webp_fallback( $image, $attachment_id, $size, $icon ) {
        if ( ! $image || ! $this->is_avif_supported() ) {
            return $image;
        }

        $avif_url = $image[0];
        $webp_url = $this->get_webp_version( $avif_url );

        if ( $webp_url ) {
            $image[0] = $webp_url;
        }

        return $image;
    }

    private function is_avif_supported() {
        // Check if the browser supports AVIF
        if ( isset( $_SERVER['HTTP_ACCEPT'] ) && strpos( $_SERVER['HTTP_ACCEPT'], 'image/avif' ) !== false ) {
            return true;
        }
        return false;
    }

    private function get_webp_version( $avif_url ) {
        $webp_url = str_replace( '.avif', '.webp', $avif_url );
        return file_exists( $webp_url ) ? $webp_url : false;
    }
}