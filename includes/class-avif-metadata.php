<?php
class AVIF_Metadata {
    public function __construct() {
        add_filter( 'wp_generate_attachment_metadata', array( $this, 'fix_avif_metadata' ), 1, 3 );
    }

    public function fix_avif_metadata( $metadata, $attachment_id, $context ) {
        if ( empty( $metadata ) ) {
            return $metadata;
        }

        $attachment = get_post( $attachment_id );
        if ( ! $attachment || is_wp_error( $attachment ) ) {
            return $metadata;
        }

        if ( 'image/avif' !== $attachment->post_mime_type ) {
            return $metadata;
        }

        if ( ( ! empty( $metadata['width'] ) && ( 0 !== $metadata['width'] ) ) && ( ! empty( $metadata['height'] ) && 0 !== $metadata['height'] ) ) {
            return $metadata;
        }

        $file = get_attached_file( $attachment_id );
        if ( ! $file ) {
            return $metadata;
        }

        if ( class_exists( 'Imagick' ) ) {
            try {
                $imagick = new Imagick( $file );
                $dimensions = $imagick->getImageGeometry();
                $imagick->clear();

                $metadata['width']  = $dimensions['width'];
                $metadata['height'] = $dimensions['height'];
            } catch ( Exception $e ) {
                error_log( 'Failed to process AVIF image: ' . $e->getMessage() );
            }
        }

        return $metadata;
    }
}