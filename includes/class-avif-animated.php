<?php
/**
 * class Animated
 * 
 * Handles AVIF image format support in WordPress, including animated AVIF files.
 * 
 * @since 1.0.0
 */
class Animated {
    /**
     * Initialize the class and set up WordPress hooks.
     *
     * @since 1.0.0
     */
    public function __construct() {
        add_filter('wp_check_filetype_and_ext', array($this, 'allow_animated_avif'), 10, 4);
    }

    /**
     * Allows AVIF file uploads by modifying WordPress file type checking.
     *
     * @param array  $types    File data array containing 'ext', 'type', and 'proper_filename' keys.
     * @param string $file     Full path to the file.
     * @param string $filename The name of the file (may differ from $file due to $file being in a tmp directory).
     * @param array  $mimes    Array of mime types keyed by their file extension regex.
     *
     * @return array Modified file data array.
     * @since 1.0.0
     */
    public function allow_animated_avif($types, $file, $filename, $mimes) {
        if (empty($filename)) {
            return $types;
        }

        try {
            if (is_string($filename) && strtolower(pathinfo($filename, PATHINFO_EXTENSION)) === 'avif') {
                // Verify file exists and is readable
                if (!empty($file) && file_exists($file) && is_readable($file)) {
                    $types['ext'] = 'avif';
                    $types['type'] = 'image/avif';
                }
            }
        } catch (Exception $e) {
            error_log('AVIF_Animated: Error processing file: ' . $e->getMessage());
        }

        return $types;
    }
}