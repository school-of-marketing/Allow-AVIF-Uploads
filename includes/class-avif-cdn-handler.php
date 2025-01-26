<?php
// class-avif-cdn-handler.php
class AVIF_CDN_Handler {
    private $cdn_url;
    private $api_key;
    private $zone_id;

    public function __construct() {
        $this->cdn_url = get_option('avif_cdn_url');
        $this->api_key = get_option('avif_cdn_api_key');
        $this->zone_id = get_option('avif_cdn_zone_id');
    }

    public function push_to_cdn($file_path) {
        if (!$this->validate_credentials()) {
            throw new Exception('Invalid CDN credentials');
        }

        $response = wp_remote_post($this->get_upload_url(), [
            'headers' => $this->get_headers(),
            'body' => file_get_contents($file_path)
        ]);

        return $this->handle_response($response);
    }

    public function purge_cache($url) {
        $response = wp_remote_post($this->get_purge_url(), [
            'headers' => $this->get_headers(),
            'body' => json_encode(['files' => [$url]])
        ]);

        return $this->handle_response($response);
    }
}