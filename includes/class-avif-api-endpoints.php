<?php
// class-avif-api-endpoints.php
class AVIF_API_Endpoints {
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    public function register_endpoints() {
        register_rest_route('avif/v1', '/optimize', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_optimization'],
            'permission_callback' => [$this, 'check_permission']
        ]);

        register_rest_route('avif/v1', '/convert', [
            'methods' => 'POST',
            'callback' => [$this, 'handle_conversion'],
            'permission_callback' => [$this, 'check_permission']
        ]);
    }

    public function handle_optimization($request) {
        $params = $request->get_params();
        $file_id = isset($params['file_id']) ? absint($params['file_id']) : 0;
        
        if (!$file_id) {
            return new WP_Error('invalid_file', 'Invalid file ID');
        }

        // Optimization logic here
        return rest_ensure_response(['success' => true]);
    }
}