<?php
/**
 * class Optimizer
 * 
 * Handles image optimization for uploaded files in WordPress.
 * Requires Imagick PHP extension to be installed with appropriate image format support.
 * 
 * @since 1.0.0
 */
use \Intervention\Image\ImageManager;
class Optimizer
{
    const MAX_DIMENSION = 8192;

    private $supported_types = [
        'image/jpeg',
        'image/png',
        'image/webp',
        'image/avif'
    ];

    public function __construct()
    {
        add_filter('wp_handle_upload', array($this, 'optimize_uploaded_image'));
    }

    public function optimize_uploaded_image($file)
    {
        if (!isset($file['file']) || !$this->is_image($file['type'])) {
            return $file;
        }

        $file_path = $file['file'];
        error_log("Optimizer: Processing file: {$file_path}");

        if (!is_readable($file_path)) {
            error_log("Optimizer: File not readable: {$file_path}");
            return $file;
        }

        try {
            $imageManager = new ImageManager(
                new Intervention\Image\Drivers\Gd\Driver()
            );
            // Initialize Intervention Image with GD driver
            $image = $imageManager->read($file_path);

            // Validate dimensions
            if ($image->width() > self::MAX_DIMENSION || $image->height() > self::MAX_DIMENSION) {
                throw new Exception("Image exceeds maximum dimensions");
            }

            error_log("Optimizer: Image dimensions: {$image->width()}x{$image->height()}");

            // Apply optimization
            $quality = $this->get_compression_quality();

            // Save optimized image
            $temp_path = "{$file_path}.optimized.tmp";
            error_log("Optimizer: Writing temp file: {$temp_path}");

            $image->save($temp_path, $quality);

            // Replace original file
            if (!rename($temp_path, $file_path)) {
                if (!copy($temp_path, $file_path)) {
                    throw new Exception("Failed to replace file");
                }
                unlink($temp_path);
            }

            // Update file metadata
            clearstatcache(true, $file_path);
            $file['size'] = filesize($file_path);

            // Update WordPress attachment metadata
            $attachment_id = attachment_url_to_postid($file['url']);
            if ($attachment_id) {
                $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $metadata);
            }

        } catch (Exception $e) {
            error_log("Optimizer ERROR: " . $e->getMessage());
            if (isset($temp_path) && file_exists($temp_path)) {
                @unlink($temp_path);
            }
        }

        return $file;
    }

    private function is_image($mime_type)
    {
        return in_array($mime_type, $this->supported_types, true);
    }

    private function get_compression_quality()
    {
        $quality = get_option('avif_compression_quality', 80);
        return max(1, min(100, intval($quality)));
    }
}
