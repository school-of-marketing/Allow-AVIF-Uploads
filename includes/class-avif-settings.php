<?php
class AVIF_Settings {
    private $settings_tabs;

    public function __construct() {
        add_action('admin_menu', [$this, 'add_settings_page']);
        add_action('admin_init', [$this, 'register_settings']);
        
        $this->settings_tabs = [
            'general' => 'General',
            'optimization' => 'Optimization',
            'cdn' => 'CDN',
            'ai' => 'AI Processing',
            'advanced' => 'Advanced'
        ];
    }

    public function add_settings_page() {
        add_options_page(
            'AVIF Settings',
            'AVIF Settings',
            'manage_options',
            'avif-settings',
            [$this, 'render_settings_page']
        );
    }

    public function render_settings_page() {
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';
        ?>
        <div class="wrap">
            <h1>AVIF Settings</h1>
            <nav class="nav-tab-wrapper">
                <?php foreach ($this->settings_tabs as $tab => $name): ?>
                    <a href="?page=avif-settings&tab=<?php echo esc_attr($tab); ?>" 
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

    public function register_settings() {
        // General Settings
        $this->register_general_settings();
        
        // Optimization Settings
        $this->register_optimization_settings();
        
        // CDN Settings
        $this->register_cdn_settings();
        
        // AI Settings
        $this->register_ai_settings();
        
        // Advanced Settings
        $this->register_advanced_settings();
    }

    private function register_general_settings() {
        register_setting('avif_general_settings', 'avif_lazy_loading');
        register_setting('avif_general_settings', 'avif_webp_fallback');
        register_setting('avif_general_settings', 'avif_animated_support');

        add_settings_section(
            'avif_general_section',
            'General Settings',
            null,
            'avif-general'
        );

        $general_fields = [
            'avif_lazy_loading' => [
                'title' => 'Enable Lazy Loading',
                'callback' => 'render_checkbox_field',
                'default' => '1'
            ],
            'avif_webp_fallback' => [
                'title' => 'Enable WebP Fallback',
                'callback' => 'render_checkbox_field',
                'default' => '1'
            ],
            'avif_animated_support' => [
                'title' => 'Enable Animated AVIF',
                'callback' => 'render_checkbox_field',
                'default' => '1'
            ]
        ];

        $this->register_settings_fields($general_fields, 'avif-general', 'avif_general_section');
    }

    private function register_optimization_settings() {
        register_setting('avif_optimization_settings', 'avif_compression_quality');
        register_setting('avif_optimization_settings', 'avif_enable_queue');
        register_setting('avif_optimization_settings', 'avif_queue_batch_size');

        add_settings_section(
            'avif_optimization_section',
            'Optimization Settings',
            null,
            'avif-optimization'
        );

        $optimization_fields = [
            'avif_compression_quality' => [
                'title' => 'Compression Quality',
                'callback' => 'render_number_field',
                'default' => '80',
                'args' => ['min' => 1, 'max' => 100]
            ],
            'avif_enable_queue' => [
                'title' => 'Enable Processing Queue',
                'callback' => 'render_checkbox_field',
                'default' => '1'
            ],
            'avif_queue_batch_size' => [
                'title' => 'Queue Batch Size',
                'callback' => 'render_number_field',
                'default' => '10',
                'args' => ['min' => 1, 'max' => 50]
            ]
        ];

        $this->register_settings_fields($optimization_fields, 'avif-optimization', 'avif_optimization_section');
    }

    private function register_cdn_settings() {
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

    private function register_ai_settings() {
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

    private function register_advanced_settings() {
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

    private function register_settings_fields($fields, $page, $section) {
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

    public function render_checkbox_field($args) {
        $option = get_option($args['id'], $args['default']);
        echo '<input type="checkbox" name="' . esc_attr($args['id']) . '" value="1" ' . 
             checked(1, $option, false) . '/>';
    }

    public function render_number_field($args) {
        $option = get_option($args['id'], $args['default']);
        echo '<input type="number" name="' . esc_attr($args['id']) . '" value="' . 
             esc_attr($option) . '" min="' . esc_attr($args['min']) . 
             '" max="' . esc_attr($args['max']) . '" />';
    }

    public function render_text_field($args) {
        $option = get_option($args['id'], $args['default']);
        echo '<input type="text" name="' . esc_attr($args['id']) . '" value="' . 
             esc_attr($option) . '" class="regular-text" />';
    }

    public function render_password_field($args) {
        $option = get_option($args['id'], $args['default']);
        echo '<input type="password" name="' . esc_attr($args['id']) . '" value="' . 
             esc_attr($option) . '" class="regular-text" />';
    }

    public function render_select_field($args) {
        $option = get_option($args['id'], $args['default']);
        echo '<select name="' . esc_attr($args['id']) . '">';
        foreach ($args['options'] as $value => $label) {
            echo '<option value="' . esc_attr($value) . '" ' . 
                 selected($option, $value, false) . '>' . 
                 esc_html($label) . '</option>';
        }
        echo '</select>';
    }
}