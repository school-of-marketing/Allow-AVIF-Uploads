<?php
/**
 * class WebP_Fallback
 * 
 * Handles fallback from AVIF to WebP images based on browser support.
 * 
 * @since 1.0.0
 */
class WebP_Fallback {
    /**
     * Initialize the fallback functionality.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter('wp_get_attachment_image_src', [$this, 'add_webp_fallback'], 10, 4);
    }

    /**
     * Add WebP fallback for AVIF images when necessary.
     *
     * @param array|false $image Array of image data or false if no image.
     * @param int         $attachment_id Attachment ID.
     * @param string|array $size Requested image size.
     * @param bool        $icon Whether the image should be treated as an icon.
     * @return array|false Modified image array or false.
     */
    public function add_webp_fallback($image, $attachment_id, $size, $icon) {
        if (empty($image) || !is_array($image) || !$this->is_avif_supported()) {
            return $image;
        }

        try {
            $avif_url = sanitize_url($image[0]);
            $webp_url = $this->get_webp_version($avif_url);

            if ($webp_url) {
                $image[0] = $webp_url;
            }
        } catch (Exception $e) {
            error_log('AVIF_WebP_Fallback error: ' . $e->getMessage());
        }

        return $image;
    }

    /**
     * Check if the current browser supports AVIF format.
     *
     * @return bool True if AVIF is supported, false otherwise.
     */
    private function is_avif_supported(): bool {
        if (!isset($_SERVER['HTTP_ACCEPT'])) {
            return false;
        }

        return str_contains($_SERVER['HTTP_ACCEPT'], 'image/avif');
    }

    /**
     * Get the WebP version of an AVIF image if it exists.
     *
     * @param string $avif_url The URL of the AVIF image.
     * @return string|false The WebP URL if exists, false otherwise.
     */
    private function get_webp_version(string $avif_url) {
        if (empty($avif_url)) {
            return false;
        }

        $webp_url = str_replace('.avif', '.webp', $avif_url);
        $file_path = wp_normalize_path(ABSPATH . str_replace(get_site_url(), '', $webp_url));

        return file_exists($file_path) ? $webp_url : false;
    }
}