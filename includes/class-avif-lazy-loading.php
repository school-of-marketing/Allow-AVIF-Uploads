<?php
/**
 * class Lazy_Loading
 * 
 * Handles lazy loading functionality for AVIF images in WordPress
 * 
 * @since 1.0.0
 */
class Lazy_Loading {
    /**
     * Initialize the lazy loading functionality
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Add filter for image attributes with priority 10 and 3 parameters
        if (function_exists('add_filter')) {
            add_filter('wp_get_attachment_image_attributes', array($this, 'add_lazy_loading'), 10, 3);
        }
    }

    /**
     * Adds lazy loading attribute to images
     *
     * @param array $attr       Attributes for the image markup
     * @param object $attachment WordPress attachment object
     * @param string|array $size Requested size
     * @return array Modified attributes
     * 
     * @since 1.0.0
     */
    public function add_lazy_loading($attr, $attachment, $size) {
        if (!is_array($attr)) {
            $attr = array();
        }

        // Add lazy loading attribute if not already set
        if (!isset($attr['loading'])) {
            $attr['loading'] = 'lazy';
        }

        // Ensure alt text for accessibility
        if (!isset($attr['alt']) && $attachment) {
            $attr['alt'] = get_post_meta($attachment->ID, '_wp_attachment_image_alt', true);
        }

        return $attr;
    }
}