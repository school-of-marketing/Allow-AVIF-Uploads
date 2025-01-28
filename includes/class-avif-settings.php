<?php
/**
 * AVIF Settings Management Class
 *
 * Handles the administration settings interface for AVIF image processing
 * in WordPress. Provides a tabbed interface for managing various AVIF-related
 * configurations including general settings, optimization, CDN integration,
 * AI processing, and advanced options.
 *
 * @since 1.0.0
 * @package Allow-AVIF-Uploads
 */

class Settings
{
    /**
     * Holds the settings tabs configuration
     *
     * @var array
     */
    private $settings_tabs;

    /**
     * Initialize the settings class
     *
     * @since 1.0.0
     */
    public function __construct() {
        // Initialize hooks with priority to ensure proper loading
        add_action('admin_menu', [$this, 'add_settings_page'], 10);
        add_action('admin_init', [$this, 'register_settings'], 10);

        // Define settings tabs
        $this->settings_tabs = [
            'general' => __('General', 'avif-uploads'),
            'optimization' => __('Optimization', 'avif-uploads'),
            // 'cdn' => __('CDN', 'avif-uploads'),
            // 'ai' => __('AI Processing', 'avif-uploads'),
            // 'advanced' => __('Advanced', 'avif-uploads')
        ];
    }

    /**
     * Add settings page to WordPress admin menu
     *
     * @since 1.0.0
     * @return void
     */
    public function add_settings_page() {
        add_options_page(
            __('AVIF Settings', 'avif-uploads'),
            __('AVIF Settings', 'avif-uploads'),
            'manage_options',
            'avif-settings',
            [$this, 'render_settings_page']
        );
    }

    /**
     * Render the settings page HTML
     *
     * @since 1.0.0
     * @return void
     */
    public function render_settings_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        // Verify active tab exists in settings_tabs
        if (!array_key_exists($active_tab, $this->settings_tabs)) {
            $active_tab = 'general';
        }
        ?>
        <div class="wrap">
            <h1><?php echo esc_html__('AVIF Settings', 'avif-uploads'); ?></h1>
            <nav class="nav-tab-wrapper">
                <?php foreach ($this->settings_tabs as $tab => $name): ?>
                                            <a href="<?php echo esc_url(add_query_arg(['page' => 'avif-settings', 'tab' => $tab])); ?>"
                       class="nav-tab <?php echo $active_tab === $tab ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html($name); ?>
                    </a>
                <?php endforeach; ?>
            </nav>
            <form method="post" action="options.php">
                <?php
                settings_fields("avif_{$active_tab}_settings");
                do_settings_sections("avif-{$active_tab}");
                submit_button();
                ?>
            </form>
        </div>
        <?php
    }

    private function register_cdn_settings()
    {
        register_setting('avif_cdn_settings', 'avif_cdn_enabled');
        register_setting('avif_cdn_settings', 'avif_cdn_url');
        register_setting('avif_cdn_settings', 'avif_cdn_key');

        add_settings_section(
            'avif_cdn_section',
            'CDN Settings',
            null,
            'avif-cdn'
        );

        $cdn_fields = [
            'avif_cdn_enabled' => [
                'title' => 'Enable CDN',
                'callback' => 'render_checkbox_field',
                'default' => '0'
            ],
            'avif_cdn_url' => [
                'title' => 'CDN URL',
                'callback' => 'render_text_field',
                'default' => ''
            ],
            'avif_cdn_key' => [
                'title' => 'API Key',
                'callback' => 'render_password_field',
                'default' => ''
            ]
        ];

        $this->register_settings_fields($cdn_fields, 'avif-cdn', 'avif_cdn_section');
    }

    private function register_ai_settings()
    {
        register_setting('avif_ai_settings', 'avif_enable_ai');
        register_setting('avif_ai_settings', 'avif_ai_model');

        add_settings_section(
            'avif_ai_section',
            'AI Processing Settings',
            null,
            'avif-ai'
        );

        $ai_fields = [
            'avif_enable_ai' => [
                'title' => 'Enable AI Processing',
                'callback' => 'render_checkbox_field',
                'default' => '0'
            ],
            'avif_ai_model' => [
                'title' => 'AI Model',
                'callback' => 'render_select_field',
                'default' => 'balanced',
                'args' => [
                    'options' => [
                        'speed' => 'Speed Optimized',
                        'balanced' => 'Balanced',
                        'quality' => 'Quality Optimized'
                    ]
                ]
            ]
        ];

        $this->register_settings_fields($ai_fields, 'avif-ai', 'avif_ai_section');
    }

    private function register_advanced_settings()
    {
        register_setting('avif_advanced_settings', 'avif_enable_wasm');
        register_setting('avif_advanced_settings', 'avif_version_control');

        add_settings_section(
            'avif_advanced_section',
            'Advanced Settings',
            null,
            'avif-advanced'
        );

        $advanced_fields = [
            'avif_enable_wasm' => [
                'title' => 'Enable WebAssembly',
                'callback' => 'render_checkbox_field',
                'default' => '1'
            ],
            'avif_version_control' => [
                'title' => 'Enable Version Control',
                'callback' => 'render_checkbox_field',
                'default' => '1'
            ]
        ];

        $this->register_settings_fields($advanced_fields, 'avif-advanced', 'avif_advanced_section');
    }

    private function register_settings_fields($fields, $page, $section)
    {
        foreach ($fields as $id => $field) {
            add_settings_field(
                $id,
                $field['title'],
                [$this, $field['callback']],
                $page,
                $section,
                array_merge(['id' => $id, 'default' => $field['default']], $field['args'] ?? [])
            );
        }
    }
    public function render_text_field($args)
    {
        $option = get_option($args['id'], $args['default']);
        echo '<input type="text" name="' . esc_attr($args['id']) . '" value="' .
            esc_attr($option) . '" class="regular-text" />';
    }

    public function render_password_field($args)
    {
        $option = get_option($args['id'], $args['default']);
        echo '<input type="password" name="' . esc_attr($args['id']) . '" value="' .
            esc_attr($option) . '" class="regular-text" />';
    }

    public function render_select_field($args)
    {
        $option = get_option($args['id'], $args['default']);
        echo '<select name="' . esc_attr($args['id']) . '">';
        foreach ($args['options'] as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' .
                selected($option, $value, false) . '>' .
                esc_html($label) . '</option>';
        }
        echo '</select>';
    }
    /**
     * Render a checkbox field
     *
     * @since 1.0.0
     * @param array $args Field arguments
     * @return void
     */
    public function render_checkbox_field($args)
    {
        $id = esc_attr($args['id']);
        $option = get_option($args['id'], $args['default']);
        $checked = checked(1, $option, false);

        printf(
            '<input type="checkbox" id="%1$s" name="%1$s" value="1" %2$s />',
            $id,
            $checked
        );
    }

    /**
     * Render a number input field
     *
     * @since 1.0.0
     * @param array $args Field arguments
     * @return void
     */
    public function render_number_field($args)
    {
        $id = esc_attr($args['id']);
        $option = get_option($args['id'], $args['default']);
        $min = isset($args['min']) ? esc_attr($args['min']) : '';
        $max = isset($args['max']) ? esc_attr($args['max']) : '';

        printf(
            '<input type="number" id="%1$s" name="%1$s" value="%2$s" min="%3$s" max="%4$s" class="small-text" />',
            $id,
            esc_attr($option),
            $min,
            $max
        );
    }

    /**
     * Register plugin settings and sections
     *
     * @since 1.0.0
     * @return void
     */
    public function register_settings()
    {
        // Register settings for each tab
        foreach ($this->settings_tabs as $tab => $name) {
            $this->{"register_{$tab}_settings"}();
        }
    }

    /**
     * Register general settings
     *
     * @since 1.0.0
     * @return void
     */
    private function register_general_settings()
    {
        register_setting('avif_general_settings', 'avif_enable_uploads', [
            'type' => 'boolean',
            'default' => true,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ]);

        add_settings_section(
            'avif_general_section',
            __('General Settings', 'avif-uploads'),
            [$this, 'render_general_section'],
            'avif-general'
        );

        add_settings_field(
            'avif_enable_uploads',
            __('Enable AVIF Uploads', 'avif-uploads'),
            [$this, 'render_checkbox_field'],
            'avif-general',
            'avif_general_section',
            [
                'id' => 'avif_enable_uploads',
                'default' => false
            ]
        );

        register_setting('avif_general_settings', 'avif_delete_settings_on_deactivate', [
            'type' => 'boolean',
            'default' => false,
            'sanitize_callback' => 'rest_sanitize_boolean'
        ]);

        add_settings_field(
            'avif_delete_settings_on_deactivate',
            __('Delete Settings on Deactivation', 'avif-uploads'),
            [$this, 'render_checkbox_field'],
            'avif-general',
            'avif_general_section',
            [
                'id' => 'avif_delete_settings_on_deactivate',
                'default' => false
            ]
        );
    }

    /**
     * Register optimization settings
     *
     * @since 1.0.0
     * @return void
     */
    private function register_optimization_settings()
    {
        register_setting('avif_optimization_settings', 'avif_compression_quality', [
            'type' => 'integer',
            'default' => 85,
            'sanitize_callback' => 'absint'
        ]);

        add_settings_section(
            'avif_optimization_section',
            __('Optimization Settings', 'avif-uploads'),
            [$this, 'render_optimization_section'],
            'avif-optimization'
        );

        add_settings_field(
            'avif_compression_quality',
            __('AVIF Quality', 'avif-uploads'),
            [$this, 'render_number_field'],
            'avif-optimization',
            'avif_optimization_section',
            [
                'id' => 'avif_compression_quality',
                'default' => 85,
                'min' => 0,
                'max' => 100
            ]
        );
    }

    /**
     * Render general section description
     *
     * @since 1.0.0
     * @return void
     */
    public function render_general_section()
    {
        echo '<p>' . esc_html__('Configure general AVIF upload settings.', 'avif-uploads') . '</p>';
    }

    /**
     * Render optimization section description
     *
     * @since 1.0.0
     * @return void
     */
    public function render_optimization_section()
    {
        echo '<p>' . esc_html__('Configure AVIF optimization settings.', 'avif-uploads') . '</p>';
    }

    /**
     * Validate settings before save
     *
     * @since 1.0.0
     * @param mixed $input The value to validate
     * @return mixed
     */
    public function validate_settings($input)
    {
        // Implement validation logic here
        return $input;
    }

}
