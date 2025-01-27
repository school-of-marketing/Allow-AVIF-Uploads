<?php
/**
 * class API_Endpoints
 * 
 * Handles REST API endpoints for AVIF image operations.
 * 
 * @package Allow-AVIF-Uploads
 * @since 1.0.0
 */
class API_Endpoints {
    /**
     * Constructor - Initializes the REST API endpoints
     */
    public function __construct() {
        add_action('rest_api_init', [$this, 'register_endpoints']);
    }

    /**
     * Registers REST API endpoints for AVIF operations
     *
     * @return void
     */
    public function register_endpoints() {
        register_rest_route('avif/v1', '/optimize', [
            'methods'             => 'POST',
            'callback'           => [$this, 'handle_optimization'],
            'permission_callback' => [$this, 'check_permission'],
            'args'               => [
                'file_id' => [
                    'required'          => true,
                    'type'             => 'integer',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint',
                ]
            ]
        ]);

        register_rest_route('avif/v1', '/convert', [
            'methods'             => 'POST',
            'callback'           => [$this, 'handle_conversion'],
            'permission_callback' => [$this, 'check_permission'],
            'args'               => [
                'file_id' => [
                    'required'          => true,
                    'type'             => 'integer',
                    'validate_callback' => function($param) {
                        return is_numeric($param) && $param > 0;
                    },
                    'sanitize_callback' => 'absint',
                ]
            ]
        ]);
    }

    /**
     * Handles image optimization requests
     *
     * @param WP_REST_Request $request The request object
     * @return WP_REST_Response|WP_Error
     */
    public function handle_optimization($request) {
        try {
            $file_id = $request->get_param('file_id');
            
            if (!wp_attachment_is_image($file_id)) {
                return new WP_Error(
                    'invalid_image',
                    'The provided ID does not correspond to an image',
                    ['status' => 400]
                );
            }

            // Get file path
            $file_path = get_attached_file($file_id);
            if (!file_exists($file_path)) {
                return new WP_Error(
                    'file_not_found',
                    'Image file not found on server',
                    ['status' => 404]
                );
            }

            // Add optimization logic here
            // TODO: Implement actual optimization

            return rest_ensure_response([
                'success' => true,
                'file_id' => $file_id,
                'message' => 'Image optimization completed successfully'
            ]);

        } catch (Exception $e) {
            return new WP_Error(
                'optimization_failed',
                $e->getMessage(),
                ['status' => 500]
            );
        }
    }

    /**
     * Checks if the current user has permission to access the endpoints
     *
     * @return boolean
     */
    public function check_permission() {
        return current_user_can('upload_files');
    }
}