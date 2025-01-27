<?php
/**
 * class Metadata
 * 
 * Handles metadata extraction and fixing for AVIF image uploads in WordPress.
 * 
 * @since 1.0.0
 */

class Metadata
{
    /**
     * Constructor: Initialize filters
     */
    public function __construct() {
        add_filter('wp_generate_attachment_metadata', [$this, 'fix_avif_metadata'], 1, 3);
    }

    /**
     * Fixes metadata for AVIF images by extracting correct dimensions.
     * 
     * @param array  $metadata      Attachment metadata array
     * @param int    $attachment_id Attachment post ID
     * @param string $context       Additional context (full or thumb)
     * 
     * @return array Modified metadata array
     */
    public function fix_avif_metadata($metadata, $attachment_id, $context)
    {
        // Early return if no metadata
        if (empty($metadata)) {
            return $metadata;
        }

        try {
            // Get attachment post
            $attachment = get_post($attachment_id);
            if (!$attachment || is_wp_error($attachment)) {
                throw new Exception('Invalid attachment');
            }

            // Check if it's an AVIF image
            if ('image/avif' !== $attachment->post_mime_type) {
                return $metadata;
            }

            // Return if dimensions are already set correctly
            if (
                !empty($metadata['width']) && !empty($metadata['height']) &&
                $metadata['width'] > 0 && $metadata['height'] > 0
            ) {
                return $metadata;
            }

            // Get file path
            $file = get_attached_file($attachment_id);
            if (!$file || !file_exists($file)) {
                throw new Exception('File not found: ' . $file);
            }

            // Extract dimensions using Imagick if available
            if (class_exists('Imagick') && extension_loaded('imagick')) {
                $imagick = new Imagick($file);
                $dimensions = $imagick->getImageGeometry();
                $imagick->clear();
                $imagick->destroy();

                if (!empty($dimensions['width']) && !empty($dimensions['height'])) {
                    $metadata['width'] = (int) $dimensions['width'];
                    $metadata['height'] = (int) $dimensions['height'];
                }
            } else {
                throw new Exception('Imagick not available');
            }

        } catch (Exception $e) {
            error_log(sprintf(
                '[AVIF Metadata] Error processing image (ID: %d): %s',
                $attachment_id,
                $e->getMessage()
            ));
        }

        return $metadata;
    }
}