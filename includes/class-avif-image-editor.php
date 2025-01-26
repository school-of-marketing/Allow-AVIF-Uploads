<?php
if ( ! class_exists( 'WP_Image_Editor' ) ) {
    require_once ABSPATH . WPINC . '/class-wp-image-editor.php';
}

if ( ! class_exists( 'WP_Image_Editor_Imagick' ) ) {
    require_once ABSPATH . WPINC . '/class-wp-image-editor-imagick.php';
}

class AVIF_Image_Editor extends WP_Image_Editor_Imagick {
    public static function test( $args = array() ) {
        if ( ! parent::test( $args ) ) {
            return false;
        }

        $imagick = new Imagick();
        $formats = $imagick->queryFormats();
        return in_array( 'AVIF', $formats );
    }

    public static function supports_mime_type( $mime_type ) {
        return 'image/avif' === $mime_type;
    }

    public function save( $destfilename = null, $mime_type = null ) {
        if ( 'image/avif' === $mime_type ) {
            $this->image->setImageFormat( 'avif' );
        }
        return parent::save( $destfilename, $mime_type );
    }
}