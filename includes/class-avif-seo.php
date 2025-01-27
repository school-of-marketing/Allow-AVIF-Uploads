<?php
/**
 * class SEO
 * 
 * Handles SEO optimization for AVIF images in WordPress by adding necessary image attributes.
 * 
 * @since 1.0.0
 */
class SEO {
    /**
     * Constructor - Initializes filters for image attributes.
     *
     * @since 1.0.0
     */
    public function __construct() {
        if (!is_admin()) {
            add_filter('wp_get_attachment_image_attributes', array($this, 'add_seo_attributes'), 10, 3);
        }
    }

    /**
     * Adds SEO attributes (alt and title) to image attachments.
     *
     * @since 1.0.0
     * @param array   $attr       Array of image attributes.
     * @param object  $attachment Attachment object.
     * @param string  $size      Image size.
     * @return array  Modified array of image attributes.
     */
    public function add_seo_attributes($attr, $attachment, $size) {
        if (!is_object($attachment) || !isset($attachment->ID)) {
            return $attr;
        }

        try {
            $title = get_the_title($attachment->ID);
            
            if (!empty($title)) {
                // Only set alt if it's empty
                if (empty($attr['alt'])) {
                    $attr['alt'] = wp_strip_all_tags($title);
                }
                
                // Only set title if it's empty
                if (empty($attr['title'])) {
                    $attr['title'] = wp_strip_all_tags($title);
                }
            }
        } catch (Exception $e) {
            error_log('AVIF SEO Error: ' . $e->getMessage());
        }

        return $attr;
    }
}