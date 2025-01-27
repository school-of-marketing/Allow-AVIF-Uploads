<?php
/**
 * CDN Handler for AVIF images
 *
 * Handles CDN operations including file uploads and cache purging
 * for AVIF image format.
 *
 * @package Allow-AVIF-Uploads
 * @since 1.0.0
 */

class CDN_Handler {
    /** @var string The CDN URL */
    private $cdn_url;
    
    /** @var string The API key for CDN authentication */
    private $api_key;
    
    /** @var string The zone ID for CDN operations */
    private $zone_id;

    /**
     * Initialize the CDN handler with credentials from WordPress options
     */
    public function __construct() {
        $this->cdn_url = trim(get_option('avif_cdn_url', ''));
        $this->api_key = trim(get_option('avif_cdn_api_key', ''));
        $this->zone_id = trim(get_option('avif_cdn_zone_id', ''));
    }

    /**
     * Push a file to the CDN
     *
     * @param string $file_path Local path to the file
     * @return array|WP_Error Response from CDN or error
     * @throws Exception If credentials are invalid or file doesn't exist
     */
    public function push_to_cdn($file_path) {
        try {
            if (!$this->validate_credentials()) {
                throw new Exception('Invalid or missing CDN credentials');
            }

            if (!file_exists($file_path) || !is_readable($file_path)) {
                throw new Exception('File not found or not readable: ' . $file_path);
            }

            $file_content = file_get_contents($file_path);
            if ($file_content === false) {
                throw new Exception('Failed to read file: ' . $file_path);
            }

            $response = wp_remote_post($this->get_upload_url(), [
                'headers' => $this->get_headers(),
                'body' => $file_content,
                'timeout' => 30,
                'sslverify' => true
            ]);

            return $this->handle_response($response);
        } catch (Exception $e) {
            error_log('AVIF CDN Upload Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Purge CDN cache for specific URLs
     *
     * @param string|array $url URL or array of URLs to purge
     * @return array|WP_Error Response from CDN or error
     * @throws Exception If credentials are invalid
     */
    public function purge_cache($url) {
        try {
            if (!$this->validate_credentials()) {
                throw new Exception('Invalid or missing CDN credentials');
            }

            $urls = is_array($url) ? $url : [$url];
            $urls = array_map('esc_url_raw', $urls);

            $response = wp_remote_post($this->get_purge_url(), [
                'headers' => $this->get_headers(),
                'body' => json_encode(['files' => $urls]),
                'timeout' => 30,
                'sslverify' => true
            ]);

            return $this->handle_response($response);
        } catch (Exception $e) {
            error_log('AVIF CDN Cache Purge Error: ' . $e->getMessage());
            throw $e;
        }
    }

    /**
     * Validate CDN credentials
     *
     * @return bool True if credentials are valid
     */
    private function validate_credentials() {
        return !empty($this->cdn_url) && !empty($this->api_key) && !empty($this->zone_id);
    }

    /**
     * Get request headers for CDN API calls
     *
     * @return array Headers array
     */
    private function get_headers() {
        return [
            'Authorization' => 'Bearer ' . $this->api_key,
            'Content-Type' => 'application/json',
            'Accept' => 'application/json'
        ];
    }

    /**
     * Get the CDN upload URL
     *
     * @return string The upload URL
     */
    private function get_upload_url() {
        return rtrim($this->cdn_url, '/') . '/api/v1/zones/' . $this->zone_id . '/upload';
    }

    /**
     * Get the CDN purge URL
     *
     * @return string The purge URL
     */
    private function get_purge_url() {
        return rtrim($this->cdn_url, '/') . '/api/v1/zones/' . $this->zone_id . '/purge';
    }

    /**
     * Handle API response
     *
     * @param array|WP_Error $response Response from wp_remote_post
     * @return array|WP_Error Processed response or error
     * @throws Exception If response indicates an error
     */
    private function handle_response($response) {
        if (is_wp_error($response)) {
            throw new Exception($response->get_error_message());
        }

        $code = wp_remote_retrieve_response_code($response);
        if ($code < 200 || $code >= 300) {
            throw new Exception('CDN request failed with status: ' . $code);
        }

        return json_decode(wp_remote_retrieve_body($response), true);
    }
}