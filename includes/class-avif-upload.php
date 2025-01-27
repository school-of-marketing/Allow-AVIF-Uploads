<?php
class Upload {
    private $allowed_mime_types = [
        'image/avif' => 'avif',
        'image/avif-sequence' => 'avif',
        'image/svg+xml' => 'svg',
        'image/svg' => 'svg'
    ];

    public function __construct() {
        add_filter('upload_mimes', [$this, 'filter_allowed_mimes'], 1000);
        add_filter('getimagesize_mimes_to_exts', [$this, 'filter_mime_to_exts'], 1000);
        add_filter('mime_types', [$this, 'filter_mime_types'], 1000);
        add_filter('wp_check_filetype_and_ext', [$this, 'check_filetype'], 10, 5);
        add_filter('upload_mimes', [$this, 'allow_svg_upload'], 10, 1);
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

    public function check_filetype($data, $file, $filename, $mimes, $real_mime = null) {
        if (!empty($data['ext']) && !empty($data['type'])) {
            return $data;
        }

        $filetype = wp_check_filetype($filename, $mimes);

        if ('svg' === $filetype['ext']) {
            $data['type'] = 'image/svg+xml';
            $data['ext'] = 'svg';
        }

        if (in_array($real_mime, ['image/avif', 'image/avif-sequence'])) {
            $data['type'] = $real_mime;
            $data['ext'] = 'avif';
        }

        return $data;
    }

    public function allow_svg_upload($mimes) {
        $mimes['svg'] = 'image/svg+xml';
        return $mimes;
    }

    private function validate_file($file) {
        $filetype = wp_check_filetype($file['name']);
        $ext = strtolower($filetype['ext']);

        if ($ext === 'svg') {
            return $this->validate_svg($file);
        } elseif ($ext === 'avif') {
            return $this->validate_avif($file);
        }

        return $file;
    }

    private function validate_svg($file) {
        $file_content = file_get_contents($file['tmp_name']);
        
        // Basic SVG validation
        if (strpos($file_content, '<?xml') === false || 
            strpos($file_content, '<svg') === false) {
            $file['error'] = 'Invalid SVG file format.';
        }
        
        return $file;
    }

    private function validate_avif($file) {
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime_type = finfo_file($finfo, $file['tmp_name']);
        finfo_close($finfo);

        if (!in_array($mime_type, ['image/avif', 'image/avif-sequence'])) {
            $file['error'] = 'Invalid AVIF file format.';
        }

        return $file;
    }
}
