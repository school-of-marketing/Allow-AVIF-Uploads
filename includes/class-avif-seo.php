<?php
class AVIF_SEO {
    public function __construct() {
        add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_seo_attributes' ), 10, 3 );
    }

    public function add_seo_attributes( $attr, $attachment, $size ) {
        if ( empty( $attr['alt'] ) ) {
            $attr['alt'] = get_the_title( $attachment->ID );
        }

        if ( empty( $attr['title'] ) ) {
            $attr['title'] = get_the_title( $attachment->ID );
        }

        return $attr;
    }
}