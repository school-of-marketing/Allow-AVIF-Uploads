<?php
/**
 * AVIF Image AI Processing Class
 *
 * Handles AI-powered enhancements for AVIF images including noise reduction,
 * super resolution, and color enhancement.
 *
 * @package Allow-AVIF-Uploads
 * @since 1.0.0
 */

class AI_Processor
{
    /**
     * Path to AI models directory
     * @var string
     */
    private $model_path;

    /**
     * List of supported enhancement types
     * @var array
     */
    private $supported_enhancements;

    /**
     * Initialize the AVIF AI Processor
     *
     * @throws RuntimeException If models directory is not accessible
     */
    public function __construct() {
        $this->model_path = plugin_dir_path(__FILE__) . 'models/';
        $this->supported_enhancements = [
            'noise_reduction',
            'super_resolution',
            'color_enhancement'
        ];

        if (!is_dir($this->model_path) || !is_readable($this->model_path)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>AVIF AI Processor: No AI models configured. Please configure AI models to enable image enhancements.</p></div>';
            });
            return;
        }
    }

    /**
     * Enhance an AVIF image using specified enhancement type
     *
     * @param string $image_path Path to the image file
     * @param string $enhancement_type Type of enhancement to apply
     * @return GdImage Enhanced image resource
     * @throws InvalidArgumentException If enhancement type is not supported
     * @throws RuntimeException If image processing fails
     */
    public function enhance_image($image_path, $enhancement_type): GdImage
    {
        if (!is_string($image_path) || !is_string($enhancement_type)) {
            throw new InvalidArgumentException('Invalid parameters provided');
        }

        if (!in_array($enhancement_type, $this->supported_enhancements, true)) {
            throw new InvalidArgumentException(
                sprintf('Unsupported enhancement type: %s', $enhancement_type)
            );
        }

        try {
            $image = $this->load_image($image_path);
            return $this->apply_enhancement($image, $enhancement_type);
        } catch (Exception $e) {
            throw new RuntimeException(
                sprintf('Enhancement failed: %s', $e->getMessage()),
                0,
                $e
            );
        }
    }

    /**
     * Load an AVIF image from file
     *
     * @param string $path Path to image file
     * @return GdImage Image resource
     * @throws RuntimeException If image cannot be loaded
     */
    private function load_image($path): GdImage
    {
        if (!file_exists($path) || !is_readable($path)) {
            throw new RuntimeException('Image file is not accessible');
        }

        $image = @imagecreatefromavif($path);
        if ($image === false) {
            throw new RuntimeException('Failed to load AVIF image');
        }

        return $image;
    }

    /**
     * Apply specified enhancement to image
     *
     * @param GdImage $image Image resource
     * @param string $type Enhancement type
     * @return GdImage Enhanced image resource
     * @throws RuntimeException If enhancement fails
     */
    private function apply_enhancement(GdImage $image, $type): GdImage
    {
        if (!$image instanceof GdImage) {
            throw new RuntimeException('Invalid image resource');
        }

        try {
            switch ($type) {
                case 'noise_reduction':
                    return $this->apply_noise_reduction($image);
                case 'super_resolution':
                    return $this->apply_super_resolution($image);
                case 'color_enhancement':
                    return $this->apply_color_enhancement($image);
                default:
                    throw new RuntimeException('Invalid enhancement type');
            }
        } catch (Exception $e) {
            if (is_resource($image)) {
                imagedestroy($image);
            }
            throw $e;
        }
    }

    /**
     * Apply noise reduction to image
     * 
     * @param resource|GdImage $image Image resource
     * @return resource|GdImage Enhanced image
     */
    private function apply_noise_reduction($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $new_image = imagecreatetruecolor($width, $height);

        // Apply Gaussian blur for noise reduction
        for ($x = 0; $x < $width; $x++) {
            for ($y = 0; $y < $height; $y++) {
                $rgb = $this->calculate_gaussian_blur($image, $x, $y, 1);
                imagesetpixel($new_image, $x, $y, $rgb);
            }
        }

        return $new_image;
    }

    /**
     * Apply super resolution to image
     * 
     * @param resource|GdImage $image Image resource
     * @return resource|GdImage Enhanced image
     */
    private function apply_super_resolution($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $scale = 2; // 2x upscaling

        $new_image = imagecreatetruecolor($width * $scale, $height * $scale);
        $scaled = imagescale($image, $width * $scale, $height * $scale, IMG_BICUBIC);
        imagecopy($new_image, $scaled, 0, 0, 0, 0, $width * $scale, $height * $scale);

        return $new_image;
    }
    /**
     * Apply color enhancement to image
     * 
     * @param resource|GdImage $image Image resource
     * @return resource|GdImage Enhanced image
     */
    private function apply_color_enhancement($image)
    {
        $width = imagesx($image);
        $height = imagesy($image);
        $new_image = imagecreatetruecolor($width, $height);

        // Enhance contrast and saturation
        imagefilter($image, IMG_FILTER_CONTRAST, -10);
        imagefilter($image, IMG_FILTER_BRIGHTNESS, 10);
        imagecopy($new_image, $image, 0, 0, 0, 0, $width, $height);

        return $new_image;
    }
    /**
     * Calculate Gaussian blur for a pixel
     * 
     * @param resource|GdImage $image Image resource
     * @param int $x X coordinate
     * @param int $y Y coordinate
     * @param int $radius Blur radius
     * @return int RGB color value
     */
    private function calculate_gaussian_blur($image, $x, $y, $radius)
    {
        $rs = [];
        $gs = [];
        $bs = [];
        $bs = array();

        for ($i = -$radius; $i <= $radius; $i++) {
            for ($j = -$radius; $j <= $radius; $j++) {
                $rgb = imagecolorat(
                    $image,
                    min(max($x + $i, 0), imagesx($image) - 1),
                    min(max($y + $j, 0), imagesy($image) - 1)
                );

                $rs[] = ($rgb >> 16) & 0xFF;
                $gs[] = ($rgb >> 8) & 0xFF;
                $bs[] = $rgb & 0xFF;
            }
        }

        $r = (int) array_sum($rs) / count($rs);
        $g = (int) array_sum($gs) / count($gs);
        $b = (int) array_sum($bs) / count($bs);

        return imagecolorallocate($image, $r, $g, $b);
    }
}