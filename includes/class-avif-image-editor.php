<?php
/**
 * AVIF Image Editor Class
 *
 * Extends WordPress Imagick Image Editor to provide AVIF support.
 *
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}



require_once(ABSPATH . WPINC . '/class-wp-image-editor.php');
require_once(ABSPATH . WPINC . '/class-wp-image-editor-imagick.php');
class Image_Editor extends WP_Image_Editor_Imagick
{


    public function __construct($file = null)
    {
        parent::__construct($file);
    }



    /**
     * Check if the server supports AVIF image manipulation.
     */
    public static function test($args = array())
    {


        try {
            if (!parent::test($args)) {
                return false;
            }

            if (!extension_loaded('imagick')) {
                return false;
            }

            $imagick = new Imagick();
            $formats = $imagick->queryFormats();

            return in_array('AVIF', $formats, true);
        } catch (Exception $e) {
            error_log('AVIF Image Editor Test Error: ' . $e->getMessage());
            return false;
        }
    }




    /**
     * Check if this editor supports the given MIME type.
     */
    public static function supports_mime_type($mime_type)
    {

        return 'image/avif' === $mime_type;
    }

    /**
     * Save image to file.
     */
    public function save($destfilename = null, $mime_type = null)
    {


        try {
            if ('image/avif' === $mime_type) {
                $this->image->setImageCompressionQuality(82);
                $this->image->setOption('avif:speed', '6');
                $this->image->setImageFormat('avif');
            }

            return parent::save($destfilename, $mime_type);
        } catch (Exception $e) {
            error_log('AVIF Image Save Error: ' . $e->getMessage());
            return new WP_Error('image_save_error', $e->getMessage());
        }
    }
}
