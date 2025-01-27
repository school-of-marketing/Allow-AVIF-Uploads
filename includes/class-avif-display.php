<?php
/**
 * class Display
 * 
 * Handles lazy loading functionality for AVIF images in WordPress.
 * 
 * @since 1.0.0
 */
class Display {
    /**
     * Initialize hooks for lazy loading functionality.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter('wp_get_attachment_image_attributes', [$this, 'add_lazy_loading'], 10, 3);
        add_filter('the_content', [$this, 'add_lazy_loading_to_content_images']);
    }

    /**
     * Add lazy loading attribute to attachment images.
     *
     * @param array   $attr       Array of attribute values for the image markup.
     * @param WP_Post $attachment Image attachment post.
     * @param string  $size       Requested image size.
     * @return array Modified attributes array.
     */
    public function add_lazy_loading($attr, $attachment, $size) {
        if (!is_admin() && $this->is_lazy_loading_enabled()) {
            $attr['loading'] = 'lazy';
            // Add decoding attribute for better performance
            $attr['decoding'] = 'async';
        }
        return $attr;
    }

    /**
     * Add lazy loading to images in post content.
     *
     * @param string $content The post content.
     * @return string Modified content with lazy loading attributes.
     */
    public function add_lazy_loading_to_content_images($content) {
        if (!is_admin() && $this->is_lazy_loading_enabled() && !empty($content)) {
            $content = preg_replace_callback(
                '/<img[^>]+>/', 
                [$this, 'add_lazy_loading_to_img_tag'], 
                $content
            );
        }
        return $content;
    }

    /**
     * Add lazy loading attribute to individual image tags.
     *
     * @param array $matches Array of matched elements from preg_replace_callback.
     * @return string Modified image tag with lazy loading.
     */
    private function add_lazy_loading_to_img_tag($matches) {
        if (empty($matches[0])) {
            return '';
        }

        $img_tag = $matches[0];
        
        // Skip if loading attribute already exists
        if (strpos($img_tag, 'loading=') === false) {
            $img_tag = str_replace('<img', '<img loading="lazy" decoding="async"', $img_tag);
        }
        
        return $img_tag;
    }

    /**
     * Check if lazy loading is enabled in options.
     *
     * @return boolean True if lazy loading is enabled.
     */
    private function is_lazy_loading_enabled() {
        return (bool) get_option('avif_lazy_loading', '1');
    }
}