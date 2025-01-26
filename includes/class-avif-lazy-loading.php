<?php
class AVIF_Lazy_Loading {
    public function __construct() {
        add_filter( 'wp_get_attachment_image_attributes', array( $this, 'add_lazy_loading' ), 10, 3 );
    }

    public function add_lazy_loading( $attr, $attachment, $size ) {
        $attr['loading'] = 'lazy';
        return $attr;
    }
}