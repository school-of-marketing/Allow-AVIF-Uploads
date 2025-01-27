<?php
/**
 * class Queue_Manager
 * 
 * Manages the queuing system for AVIF image processing operations.
 * Handles image conversion, optimization, and queue management in WordPress.
 * 
 * @since 1.0.0
 */
class Queue_Manager {
    /** @var string Table name for queue storage */
    private $table_name;

    /** @var int Maximum retries for failed items */
    const MAX_RETRIES = 3;

    /** @var array Valid process types */
    const VALID_PROCESS_TYPES = ['optimize', 'convert'];

    /** @var array Valid status types */
    const VALID_STATUSES = ['pending', 'processing', 'completed', 'failed'];

    public function __construct() {
        global $wpdb;
        $this->table_name = $wpdb->prefix . 'avif_queue';
    }

    /**
     * Creates the queue table with proper indexes for optimization.
     *
     * @since 1.0.0
     * @return void
     */
    public function create_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE IF NOT EXISTS {$this->table_name} (
            id BIGINT(20) UNSIGNED NOT NULL AUTO_INCREMENT,
            file_path TEXT NOT NULL,
            process_type VARCHAR(50) NOT NULL,
            status VARCHAR(50) NOT NULL DEFAULT 'pending',
            retries INT UNSIGNED DEFAULT 0,
            created_at DATETIME NOT NULL,
            processed_at DATETIME DEFAULT NULL,
            error_message TEXT,
            PRIMARY KEY (id),
            INDEX status_idx (status),
            INDEX process_type_idx (process_type),
            INDEX created_at_idx (created_at)
        ) {$charset_collate};";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    /**
     * Adds a new item to the processing queue with validation.
     *
     * @since 1.0.0
     * @param string $file_path Absolute path to the file
     * @param string $process_type Type of process ('optimize' or 'convert')
     * @return int|WP_Error Item ID on success, WP_Error on failure
     */
    public function add_to_queue($file_path, $process_type) {
        if (!file_exists($file_path)) {
            return new WP_Error('invalid_file', 'File does not exist');
        }

        if (!in_array($process_type, self::VALID_PROCESS_TYPES)) {
            return new WP_Error('invalid_process', 'Invalid process type');
        }

        global $wpdb;
        
        // Check for duplicate entries
        $existing = $wpdb->get_var($wpdb->prepare(
            "SELECT id FROM {$this->table_name} 
            WHERE file_path = %s AND status = 'pending'",
            $file_path
        ));

        if ($existing) {
            return new WP_Error('duplicate_entry', 'Item already in queue');
        }

        $result = $wpdb->insert(
            $this->table_name,
            [
                'file_path' => $file_path,
                'process_type' => $process_type,
                'status' => 'pending',
                'created_at' => current_time('mysql'),
                'retries' => 0
            ],
            ['%s', '%s', '%s', '%s', '%d']
        );

        return $result ? $wpdb->insert_id : new WP_Error('insert_failed', 'Failed to add item to queue');
    }

    /**
     * Retrieves pending items with error handling and locking mechanism.
     *
     * @since 1.0.0
     * @param int $limit Maximum number of items to retrieve
     * @return array|WP_Error Array of items or WP_Error on failure
     */
    public function get_pending_items($limit = 10) {
        global $wpdb;

        // Start transaction
        $wpdb->query('START TRANSACTION');

        try {
            $items = $wpdb->get_results($wpdb->prepare(
                "SELECT * FROM {$this->table_name} 
                WHERE status = 'pending' 
                AND (retries < %d)
                ORDER BY created_at ASC LIMIT %d",
                self::MAX_RETRIES,
                $limit
            ));

            // Mark items as processing
            if ($items) {
                $ids = wp_list_pluck($items, 'id');
                $id_list = implode(',', array_map('intval', $ids));
                $wpdb->query("UPDATE {$this->table_name} 
                            SET status = 'processing' 
                            WHERE id IN ($id_list)");
            }

            $wpdb->query('COMMIT');
            return $items;

        } catch (Exception $e) {
            $wpdb->query('ROLLBACK');
            return new WP_Error('queue_error', $e->getMessage());
        }
    }

    /**
     * Updates item status with error handling.
     *
     * @since 1.0.0
     * @param int $id Item ID
     * @param string $status New status
     * @param string $error_message Optional error message
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    private function update_status($id, $status, $error_message = '') {
        if (!in_array($status, self::VALID_STATUSES)) {
            return new WP_Error('invalid_status', 'Invalid status provided');
        }

        global $wpdb;
        
        $data = [
            'status' => $status,
            'processed_at' => current_time('mysql')
        ];

        if ($status === 'failed') {
            $data['retries'] = $wpdb->get_var($wpdb->prepare(
                "SELECT retries + 1 FROM {$this->table_name} WHERE id = %d",
                $id
            ));
            $data['error_message'] = $error_message;
        }

        $result = $wpdb->update(
            $this->table_name,
            $data,
            ['id' => $id],
            ['%s', '%s', '%d', '%s'],
            ['%d']
        );

        return $result !== false ? true : new WP_Error('update_failed', 'Failed to update status');
    }
}