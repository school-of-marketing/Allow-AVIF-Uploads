<?php
class AVIF_Bulk_Converter {
    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_bulk_converter_page' ) );
    }

    public function add_bulk_converter_page() {
        add_media_page(
            'Bulk Convert to AVIF',
            'Bulk Convert to AVIF',
            'manage_options',
            'bulk-convert-avif',
            array( $this, 'render_bulk_converter_page' )
        );
    }

    public function render_bulk_converter_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        if ( isset( $_POST['convert_to_avif'] ) ) {
            $this->convert_images_to_avif();
        }

        ?>
        <div class="wrap">
            <h1>Bulk Convert to AVIF</h1>
            <form method="post">
                <?php submit_button( 'Convert All Images to AVIF', 'primary', 'convert_to_avif' ); ?>
            </form>
        </div>
        <?php
    }

    private function convert_images_to_avif() {
        $images = get_posts( array(
            'post_type'      => 'attachment',
            'post_mime_type' => 'image',
            'posts_per_page' => -1,
        ) );

        foreach ( $images as $image ) {
            $this->convert_image_to_avif( $image->ID );
        }
    }

    private function convert_image_to_avif( $attachment_id ) {
        $file = get_attached_file( $attachment_id );
        if ( ! $file ) {
            return;
        }

        $editor = wp_get_image_editor( $file );
        if ( is_wp_error( $editor ) ) {
            return;
        }

        $avif_file = str_replace( '.jpg', '.avif', $file );
        $editor->save( $avif_file, 'image/avif' );
    }
}