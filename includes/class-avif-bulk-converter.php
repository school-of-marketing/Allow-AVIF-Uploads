<?php
/**
 * AVIF Bulk Converter Class
 * 
 * Handles bulk conversion of images to AVIF format in WordPress media library.
 * 
 * @since 1.0.0
 */
class Bulk_Converter
{
    /**
     * @var array Supported source image types
     */
    private $supported_types = ['image/jpeg', 'image/jpg', 'image/png'];


    /**
     * @var array Conversion statistics
     */
    private $stats = [
        'processed' => 0,
        'success' => 0,
        'failed' => 0
    ];

    /**
     * Initialize the bulk converter
     */
    public function __construct() {
        add_action('admin_menu', array($this, 'add_bulk_converter_page'));
        add_action('admin_notices', array($this, 'display_notices'));
    }

    /**
     * Add bulk converter page to media menu
     */
    public function add_bulk_converter_page() {
        add_media_page(
            __('Bulk Convert to AVIF', 'allow-avif-uploads'),
            __('Bulk Convert to AVIF', 'allow-avif-uploads'),
            'manage_options',
            'bulk-convert-avif',
            array($this, 'render_bulk_converter_page')
        );
    }

    /**
     * Render the bulk converter admin page
     */
    public function render_bulk_converter_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        if (isset($_POST['convert_to_avif']) && check_admin_referer('bulk_convert_avif_nonce')) {
            $this->convert_images_to_avif();
        }

        ?>
        <div class="wrap">
            <h1><?php _e('Bulk Convert to AVIF', 'allow-avif-uploads'); ?></h1>
            <form method="post">
                <?php
                wp_nonce_field('bulk_convert_avif_nonce');
                submit_button(__('Convert All Images to AVIF', 'allow-avif-uploads'), 'primary', 'convert_to_avif');
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Display admin notices for conversion results
     */
    public function display_notices()
    {
        if (!empty($this->stats['processed'])) {
            $message = sprintf(
                __('Conversion complete: %d processed, %d successful, %d failed', 'allow-avif-uploads'),
                $this->stats['processed'],
                $this->stats['success'],
                $this->stats['failed']
            );
            $class = ($this->stats['failed'] === 0) ? 'notice-success' : 'notice-warning';
            printf('<div class="notice %s is-dismissible"><p>%s</p></div>', $class, $message);
        }
    }

    /**
     * Convert all eligible images to AVIF
     */
    private function convert_images_to_avif() {
        $images = get_posts(array(
            'post_type' => 'attachment',
            'post_mime_type' => $this->supported_types,
            'posts_per_page' => -1,
            'fields' => 'ids'
        ));

        if (empty($images)) {
            wp_die(__('No eligible images found for conversion.', 'allow-avif-uploads'));
        }

        foreach ($images as $attachment_id) {
            $this->stats['processed']++;
            $result = $this->convert_image_to_avif($attachment_id);
            $result ? $this->stats['success']++ : $this->stats['failed']++;
        }
    }

    /**
     * Convert a single image to AVIF format
     *
     * @param int $attachment_id The attachment ID to convert
     * @return bool True on success, false on failure
     */
    private function convert_image_to_avif($attachment_id)
    {
        try {
            $file = get_attached_file($attachment_id);
            if (!$file || !file_exists($file)) {
                throw new Exception('File not found');
            }

            $editor = wp_get_image_editor($file);
            if (is_wp_error($editor)) {
                throw new Exception($editor->get_error_message());
            }

            $pathinfo = pathinfo($file);
            $avif_file = $pathinfo['dirname'] . '/' . $pathinfo['filename'] . '.avif';

            $result = $editor->save($avif_file, 'image/avif');
            if (is_wp_error($result)) {
                throw new Exception($result->get_error_message());
            }

            // Update file permissions to match WordPress defaults
            chmod($avif_file, 0644);

            return true;

        } catch (Exception $e) {
            error_log(sprintf(
                'AVIF conversion failed for attachment ID %d: %s',
                $attachment_id,
                $e->getMessage()
            ));
            return false;
        }
    }
}