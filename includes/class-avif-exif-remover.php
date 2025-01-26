<?php
class AVIF_EXIF_Remover {
    public function __construct() {
        add_filter( 'wp_handle_upload', array( $this, 'remove_exif_data' ) );
    }

    /**
     * Remove EXIF data from uploaded images.
     *
     * @param array $file Uploaded file data.
     * @return array Modified file data.
     */
    public function remove_exif_data( $file ) {
        if ( ! isset( $file['file'] ) || ! $this->is_image( $file['type'] ) ) {
            return $file;
        }

        $file_path = $file['file'];

        // Use Imagick to remove EXIF data
        if ( class_exists( 'Imagick' ) ) {
            try {
                $imagick = new Imagick( $file_path );
                $imagick->stripImage(); // Remove EXIF and other metadata
                $imagick->writeImage( $file_path ); // Save the modified image
                $imagick->clear();
            } catch ( Exception $e ) {
                error_log( 'Failed to remove EXIF data: ' . $e->getMessage() );
            }
        }

        return $file;
    }

    /**
     * Check if the uploaded file is an image.
     *
     * @param string $mime_type The MIME type of the file.
     * @return bool True if the file is an image, false otherwise.
     */
    private function is_image( $mime_type ) {
        return strpos( $mime_type, 'image/' ) === 0;
    }
}