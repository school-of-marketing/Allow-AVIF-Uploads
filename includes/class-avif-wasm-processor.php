<?php
/**
 * AVIF WebAssembly Image Processor
 *
 * Handles AVIF image processing operations using WebAssembly technology.
 * Supports encoding, decoding, and optimization of AVIF images.
 *
 * @package Allow-AVIF-Uploads
 * @since 1.0.0
 */

class WASM_Processor
{
    /** @var string Path to WASM files directory */
    private $wasm_path;

    /** @var string URL to WASM files directory */
    private $wasm_url;

    /** @var array List of supported image processing operations */
    private $supported_operations;

    /** @var string Version number for cache busting */
    private const VERSION = '1.0.0';

    /** @var string Output directory name */
    private const OUTPUT_DIR = 'output';

    /**
     * Initialize the AVIF WASM processor with necessary paths and supported operations.
     *
     * @since 1.0.0
     * @throws RuntimeException If WASM directory is not accessible.
     */
    public function __construct() {
        $this->wasm_path = rtrim(plugin_dir_path(__FILE__), '/') . '/../assets/wasm/';
        $this->wasm_url = rtrim(plugin_dir_url(__FILE__), '/') . '/../assets/wasm/';
        $this->supported_operations = ['encode', 'decode', 'optimize'];

        // Check WASM directory and show admin notice if not configured
        if (!is_dir($this->wasm_path) || !is_writable($this->wasm_path)) {
            add_action('admin_notices', function () {
                echo '<div class="notice notice-error"><p>' .
                    esc_html__('AVIF WASM Processor: Required directory is not configured or writable.', 'allow-avif-uploads') .
                    '</p></div>';
            });
            return;
        }

        // Ensure output directory exists
        $this->ensure_output_directory();
    }

    /**
     * Initialize hooks and filters.
     *
     * @since 1.0.0
     * @return void
     */
    public function init() {
        add_action('wp_enqueue_scripts', [$this, 'enqueue_wasm_scripts']);
        add_action('admin_enqueue_scripts', [$this, 'enqueue_wasm_scripts']);
    }

    /**
     * Enqueue necessary WASM scripts and their dependencies.
     *
     * @since 1.0.0
     * @return void
     */
    public function enqueue_wasm_scripts() {
        $script_path = $this->wasm_url . 'processor.js';
        $dependencies = [];

        wp_enqueue_script(
            'avif-wasm-processor',
            $script_path,
            $dependencies,
            self::VERSION,
            true
        );

        wp_localize_script('avif-wasm-processor', 'avifWasmVars', [
            'wasmUrl' => $this->wasm_url . 'avif.wasm',
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('avif_wasm_nonce')
        ]);
    }

    /**
     * Process an image using WebAssembly.
     *
     * @since 1.0.0
     * @param string $file_path Full path to the image file.
     * @param string $operation Operation to perform (encode|decode|optimize).
     * @return string|false Processed file path or false on failure.
     * @throws InvalidArgumentException For unsupported operations.
     * @throws RuntimeException If file processing fails.
     */
    public function process_image($file_path, $operation) {
        if (!in_array($operation, $this->supported_operations, true)) {
            throw new InvalidArgumentException('Unsupported operation: ' . $operation);
        }

        if (!file_exists($file_path) || !is_readable($file_path)) {
            throw new RuntimeException('File is not accessible: ' . $file_path);
        }

        try {
            return $this->execute_wasm_operation($file_path, $operation);
        } catch (Exception $e) {
            error_log('AVIF WASM Processing Error: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Execute a WASM operation on an image file.
     *
     * @since 1.0.0
     * @param string $file_path Source file path.
     * @param string $operation Operation to perform.
     * @return string|false Path to processed file or false on failure.
     * @throws RuntimeException If operation fails.
     */
    private function execute_wasm_operation($file_path, $operation) {
        $output_path = $this->get_output_path($file_path);

        // Implement actual WASM processing logic here
        switch ($operation) {
            case 'encode':
                // TODO: Implement actual AVIF encoding
                if (!copy($file_path, $output_path)) {
                    throw new RuntimeException('Failed to encode file');
                }
                break;

            case 'decode':
                // TODO: Implement actual AVIF decoding
                if (!copy($file_path, $output_path)) {
                    throw new RuntimeException('Failed to decode file');
                }
                break;

            case 'optimize':
                // TODO: Implement actual AVIF optimization
                if (!copy($file_path, $output_path)) {
                    throw new RuntimeException('Failed to optimize file');
                }
                break;
        }

        return $output_path;
    }

    /**
     * Check if WebAssembly is supported in the current environment.
     *
     * @since 1.0.0
     * @return bool True if WebAssembly is supported.
     */
    public function is_wasm_supported()
    {
        return (
            isset($_SERVER['HTTP_USER_AGENT']) &&
            (
                strpos($_SERVER['HTTP_USER_AGENT'], 'Chrome') !== false ||
                strpos($_SERVER['HTTP_USER_AGENT'], 'Firefox') !== false ||
                strpos($_SERVER['HTTP_USER_AGENT'], 'Safari') !== false
            )
        );
    }

    /**
     * Ensure the output directory exists and is writable.
     *
     * @since 1.0.0
     * @return void
     * @throws RuntimeException If directory cannot be created or is not writable.
     */
    private function ensure_output_directory()
    {
        $output_dir = $this->wasm_path . self::OUTPUT_DIR;

        if (!file_exists($output_dir)) {
            if (!mkdir($output_dir, 0755, true)) {
                throw new RuntimeException('Failed to create output directory');
            }
        }

        if (!is_writable($output_dir)) {
            throw new RuntimeException('Output directory is not writable');
        }
    }

    /**
     * Generate output path for processed files.
     *
     * @since 1.0.0
     * @param string $source_path Original file path.
     * @return string Generated output path.
     */
    private function get_output_path($source_path)
    {
        return $this->wasm_path . self::OUTPUT_DIR . '/' .
            wp_unique_filename($this->wasm_path . self::OUTPUT_DIR, basename($source_path));
    }
}