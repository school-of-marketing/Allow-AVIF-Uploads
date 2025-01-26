<?php
class AVIF_Upload {
    private $allowed_mime_types = [
        'image/avif' => 'avif',
        'image/avif-sequence' => 'avif'
    ];

    public function __construct() {
        add_filter('upload_mimes', [$this, 'filter_allowed_mimes'], 1000);
        add_filter('getimagesize_mimes_to_exts', [$this, 'filter_mime_to_exts'], 1000);
        add_filter('mime_types', [$this, 'filter_mime_types'], 1000);
        add_filter('wp_handle_upload_prefilter', [$this, 'validate_avif_upload']);
        add_filter('wp_generate_attachment_metadata', [$this, 'process_avif_upload'], 10, 2);
    }

    public function filter_allowed_mimes($mime_types) {
        return array_merge($mime_types, $this->allowed_mime_types);
    }

    public function filter_mime_to_exts($mime_to_exts) {
        return array_merge($mime_to_exts, $this->allowed_mime_types);
    }

    public function filter_mime_types($mimes) {
        return array_merge($mimes, array_flip($this->allowed_mime_types));
    }

    public function validate_avif_upload($file) {
        if (!$this->is_avif_file($file['tmp_name'])) {
            $file['error'] = 'Invalid AVIF file format.';
        }
        return $file;
    }

    public function process_avif_upload($metadata, $attachment_id) {
        if (!$this->is_avif_attachment($attachment_id)) {
            return $metadata;
        }

        $file_path = get_attached_file($attachment_id);

        // Process image based on settings
        if (get_option('avif_enable_ai', false)) {
            $this->process_with_ai($file_path);
        }

        if (get_option('avif_enable_optimization', true)) {
            $this->optimize_image($file_path);
        }

        if (get_option('avif_cdn_enabled', false)) {
            $this->push_to_cdn($file_path, $attachment_id);
        }

        return $this->generate_metadata($metadata, $file_path);
    }

    private function is_avif_file($file_path) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file_path);
        finfo_close($finfo);

        return in_array($mime_type, array_keys($this->allowed_mime_types));
    }

    private function is_avif_attachment($attachment_id) {
        return strpos(get_post_mime_type($attachment_id), 'image/avif') !== false;
    }

    private function process_with_ai($file_path) {
        if (!class_exists('AVIF_AI_Processor')) {
            return;
        }

        $ai_processor = new AVIF_AI_Processor();
        $ai_processor->process_image($file_path);
    }

    private function optimize_image($file_path) {
        if (!class_exists('AVIF_Optimizer')) {
            return;
        }

        $optimizer = new AVIF_Optimizer();
        $optimizer->optimize($file_path);
    }

    private function push_to_cdn($file_path, $attachment_id) {
        if (!class_exists('AVIF_CDN_Handler')) {
            return;
        }

        $cdn_handler = new AVIF_CDN_Handler();
        $cdn_url = $cdn_handler->push_file($file_path);

        if ($cdn_url) {
            update_post_meta($attachment_id, '_avif_cdn_url', $cdn_url);
        }
    }

    private function generate_metadata($metadata, $file_path) {
        if (!function_exists('getimagesize')) {
            return $metadata;
        }

        $size_data = getimagesize($file_path);
        if ($size_data) {
            $metadata['width'] = $size_data[0];
            $metadata['height'] = $size_data[1];
        }

        return $metadata;
    }
}