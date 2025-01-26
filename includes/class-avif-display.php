<?php
class AVIF_Display {
    public function __construct() {
        add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_lazy_loading' ), 10, 3 );
        add_filter( 'the_content', array( $this, 'add_lazy_loading_to_content_images' ) );
    }

    public function add_lazy_loading( $attr, $attachment, $size ) {
        if ( get_option( 'avif_lazy_loading', '1' ) ) {
            $attr['loading'] = 'lazy';
        }
        return $attr;
    }

    public function add_lazy_loading_to_content_images( $content ) {
        if ( get_option( 'avif_lazy_loading', '1' ) ) {
            $content = preg_replace_callback( '/<img[^>]+>/', array( $this, 'add_lazy_loading_to_img_tag' ), $content );
        }
        return $content;
    }

    private function add_lazy_loading_to_img_tag( $matches ) {
        $img_tag = $matches[0];
        if ( strpos( $img_tag, 'loading=' ) === false ) {
            $img_tag = str_replace( '<img', '<img loading="lazy"', $img_tag );
        }
        return $img_tag;
    }
}