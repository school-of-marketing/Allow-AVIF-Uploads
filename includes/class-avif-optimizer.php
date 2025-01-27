<?php
/**
 * class Optimizer
 * 
 * Handles image optimization for uploaded files in WordPress.
 * Requires Imagick PHP extension to be installed with appropriate image format support.
 * 
 * @since 1.0.0
 */
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

        if (!$this->check_imagick_support()) {
            return $file;
        }

        $mime_type = $file['type'];
        $format = $this->mime_type_to_imagick_format($mime_type);
        if (!$format) {
            error_log("Optimizer: Unsupported MIME type: {$mime_type}");
            return $file;
        }

        if (!$this->imagick_supports_format($format)) {
            error_log("Optimizer: Imagick cannot write format: {$format}");
            return $file;
        }

        try {
            // Load and optimize the image
            $imagick = new Imagick();
            $imagick->readImage($file_path);
            error_log("Optimizer: Image dimensions: {$imagick->getImageWidth()}x{$imagick->getImageHeight()}");

            // Validate dimensions
            if ($imagick->getImageWidth() > self::MAX_DIMENSION || $imagick->getImageHeight() > self::MAX_DIMENSION) {
                throw new Exception("Image exceeds maximum dimensions");
            }

            // Optimization settings
            $imagick->setImageCompressionQuality($this->get_compression_quality());
            $imagick->setOption('jpeg:optimize', 'TRUE');
            $imagick->stripImage();

            // Write to temporary file
            $temp_path = "{$file_path}.optimized.tmp";
            error_log("Optimizer: Writing temp file: {$temp_path}");
            if (!$imagick->writeImage($temp_path)) {
                throw new Exception("Failed to write temp file");
            }
            $imagick->clear();

            // Verify temp file exists
            if (!file_exists($temp_path)) {
                throw new Exception("Temp file not created");
            }

            // Replace original file
            error_log("Optimizer: Replacing original file: {$file_path}");
            if (!rename($temp_path, $file_path)) {
                // Fallback to copy + unlink for cross-device issues
                error_log("Optimizer: rename() failed, trying copy()");
                if (!copy($temp_path, $file_path)) {
                    throw new Exception("Failed to replace file (copy failed)");
                }
                unlink($temp_path);
            }

            // Update file metadata
            clearstatcache(true, $file_path);
            $file['size'] = filesize($file_path);
            error_log("Optimizer: New file size: {$file['size']} bytes");

            // Update WordPress attachment metadata
            $attachment_id = attachment_url_to_postid($file['url']);
            if ($attachment_id) {
                error_log("Optimizer: Updating metadata for Attachment ID: {$attachment_id}");
                $metadata = wp_generate_attachment_metadata($attachment_id, $file_path);
                wp_update_attachment_metadata($attachment_id, $metadata);
            } else {
                error_log("Optimizer: Attachment ID not found for URL: {$file['url']}");
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

    private function check_imagick_support()
    {
        if (!extension_loaded('imagick') || !class_exists('Imagick')) {
            error_log('Optimizer: Imagick extension not loaded');
            return false;
        }
        return true;
    }

    private function mime_type_to_imagick_format($mime_type)
    {
        $mime_map = [
            'image/jpeg' => 'JPEG',
            'image/jpg' => 'JPEG',
            'image/png' => 'PNG',
            'image/webp' => 'WEBP',
            'image/avif' => 'AVIF',
        ];
        return $mime_map[$mime_type] ?? null;
    }

    private function imagick_supports_format($format)
    {
        $imagick = new Imagick();
        $writable_formats = $imagick->queryFormats("*");
        error_log(print_r($writable_formats, true));
        $imagick->clear();
        return in_array($format, $writable_formats);
    }
}