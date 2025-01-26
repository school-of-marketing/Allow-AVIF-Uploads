<?php
class AVIF_Animated {
    public function __construct() {
        add_filter( 'wp_check_filetype_and_ext', array( $this, 'allow_animated_avif' ), 10, 4 );
    }

    public function allow_animated_avif( $types, $file, $filename, $mimes ) {
        if ( str_ends_with( $filename, '.avif' ) ) {
            $types['ext']  = 'avif';
            $types['type'] = 'image/avif';
        }
        return $types;
    }
}