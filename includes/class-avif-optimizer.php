<?php
class AVIF_Optimizer {
    public function __construct() {
        add_filter( 'wp_handle_upload', array( $this, 'optimize_uploaded_image' ) );
    }

    public function optimize_uploaded_image( $file ) {
        if ( ! isset( $file['file'] ) || ! $this->is_image( $file['type'] ) ) {
            return $file;
        }

        $file_path = $file['file'];

        if ( class_exists( 'Imagick' ) ) {
            try {
                $imagick = new Imagick( $file_path );
                $imagick->setImageCompressionQuality( $this->get_compression_quality() );
                $imagick->writeImage( $file_path );
                $imagick->clear();
            } catch ( Exception $e ) {
                error_log( 'Failed to optimize image: ' . $e->getMessage() );
            }
        }

        return $file;
    }

    private function is_image( $mime_type ) {
        return strpos( $mime_type, 'image/' ) === 0;
    }

    private function get_compression_quality() {
        return get_option( 'avif_compression_quality', 80 );
    }
}