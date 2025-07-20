<?php
/**
 * Admin interface for Cron Configuration
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cron Admin Class
 */
class XeliteRepostEngine_Cron_Admin {

    /**
     * Cron service instance
     *
     * @var XeliteRepostEngine_Cron
     */
    private $cron;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Cron $cron Cron service.
     */
    public function __construct($cron) {
        $this->cron = $cron;
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('admin_init', array($this, 'register_settings'));
        add_action('admin_enqueue_scripts', array($this, 'enqueue_scripts'));
        add_action('wp_ajax_xelite_get_cron_status', array($this, 'ajax_get_cron_status'));
        add_action('wp_ajax_xelite_test_cron_connection', array($this, 'ajax_test_cron_connection'));
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'xelite-repost-engine',
            __('Cron Settings', 'xelite-repost-engine'),
            __('Cron Settings', 'xelite-repost-engine'),
            'manage_options',
            'xelite-repost-engine-cron',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Register settings
     */
    public function register_settings() {
        register_setting(
            'xelite_repost_engine_cron_settings',
            'xelite_repost_engine_cron_settings',
            array($this, 'sanitize_settings')
        );

        add_settings_section(
            'xelite_cron_general',
            __('General Settings', 'xelite-repost-engine'),
            array($this, 'render_general_section'),
            'xelite_repost_engine_cron_settings'
        );

        add_settings_field(
            'scraping_frequency',
            __('Scraping Frequency', 'xelite-repost-engine'),
            array($this, 'render_frequency_field'),
            'xelite_repost_engine_cron_settings',
            'xelite_cron_general'
        );

        add_settings_field(
            'target_accounts',
            __('Target Accounts', 'xelite-repost-engine'),
            array($this, 'render_accounts_field'),
            'xelite_repost_engine_cron_settings',
            'xelite_cron_general'
        );

        add_settings_section(
            'xelite_cron_notifications',
            __('Notifications', 'xelite-repost-engine'),
            array($this, 'render_notifications_section'),
            'xelite_repost_engine_cron_settings'
        );

        add_settings_field(
            'notifications_enabled',
            __('Enable Notifications', 'xelite-repost-engine'),
            array($this, 'render_notifications_field'),
            'xelite_repost_engine_cron_settings',
            'xelite_cron_notifications'
        );

        add_settings_field(
            'notification_email',
            __('Notification Email', 'xelite-repost-engine'),
            array($this, 'render_email_field'),
            'xelite_repost_engine_cron_settings',
            'xelite_cron_notifications'
        );

        add_settings_section(
            'xelite_cron_advanced',
            __('Advanced Settings', 'xelite-repost-engine'),
            array($this, 'render_advanced_section'),
            'xelite_repost_engine_cron_settings'
        );

        add_settings_field(
            'cleanup_days',
            __('Cleanup Old Data (Days)', 'xelite-repost-engine'),
            array($this, 'render_cleanup_field'),
            'xelite_repost_engine_cron_settings',
            'xelite_cron_advanced'
        );

        add_settings_field(
            'max_execution_time',
            __('Max Execution Time (Seconds)', 'xelite-repost-engine'),
            array($this, 'render_execution_time_field'),
            'xelite_repost_engine_cron_settings',
            'xelite_cron_advanced'
        );
    }

    /**
     * Enqueue admin scripts
     */
    public function enqueue_scripts($hook) {
        if ('xelite-repost-engine_page_xelite-repost-engine-cron' !== $hook) {
            return;
        }

        wp_enqueue_script(
            'xelite-cron-admin',
            plugin_dir_url(XELITE_REPOST_ENGINE_FILE) . 'assets/js/cron-admin.js',
            array('jquery'),
            XELITE_REPOST_ENGINE_VERSION,
            true
        );

        wp_localize_script('xelite-cron-admin', 'xeliteCronAdmin', array(
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('xelite_cron_nonce'),
            'strings' => array(
                'testing' => __('Testing...', 'xelite-repost-engine'),
                'success' => __('Success!', 'xelite-repost-engine'),
                'error' => __('Error!', 'xelite-repost-engine'),
                'confirmManual' => __('Are you sure you want to run manual scraping?', 'xelite-repost-engine'),
                'confirmTest' => __('Are you sure you want to test the cron connection?', 'xelite-repost-engine')
            )
        ));

        wp_enqueue_style(
            'xelite-cron-admin',
            plugin_dir_url(XELITE_REPOST_ENGINE_FILE) . 'assets/css/cron-admin.css',
            array(),
            XELITE_REPOST_ENGINE_VERSION
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $cron_status = $this->cron->get_cron_status();
        $settings = $this->cron->get_cron_settings();
        ?>
        <div class="wrap">
            <h1><?php _e('Cron Settings', 'xelite-repost-engine'); ?></h1>
            
            <?php $this->render_status_overview($cron_status); ?>
            
            <div class="xelite-cron-admin-container">
                <div class="xelite-cron-settings">
                    <form method="post" action="options.php">
                        <?php
                        settings_fields('xelite_repost_engine_cron_settings');
                        do_settings_sections('xelite_repost_engine_cron_settings');
                        submit_button();
                        ?>
                    </form>
                </div>
                
                <div class="xelite-cron-actions">
                    <?php $this->render_action_buttons(); ?>
                </div>
            </div>
            
            <?php $this->render_statistics($cron_status); ?>
        </div>
        <?php
    }

    /**
     * Render status overview
     */
    private function render_status_overview($cron_status) {
        $health = $cron_status['health_status'];
        $status_class = 'xelite-status-' . $health['status'];
        ?>
        <div class="xelite-status-overview <?php echo esc_attr($status_class); ?>">
            <h2><?php _e('Cron Health Status', 'xelite-repost-engine'); ?></h2>
            <div class="xelite-status-grid">
                <div class="xelite-status-item">
                    <strong><?php _e('Overall Status:', 'xelite-repost-engine'); ?></strong>
                    <span class="xelite-status-badge"><?php echo esc_html(ucfirst($health['status'])); ?></span>
                </div>
                
                <div class="xelite-status-item">
                    <strong><?php _e('Last Run:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo $health['last_run'] ? esc_html($health['last_run']) : __('Never', 'xelite-repost-engine'); ?></span>
                </div>
                
                <div class="xelite-status-item">
                    <strong><?php _e('Next Run:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo $health['next_run'] ? esc_html($health['next_run']) : __('Not scheduled', 'xelite-repost-engine'); ?></span>
                </div>
                
                <div class="xelite-status-item">
                    <strong><?php _e('Lock File:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo $health['lock_file_exists'] ? __('Exists (may be stuck)', 'xelite-repost-engine') : __('None', 'xelite-repost-engine'); ?></span>
                </div>
            </div>
            
            <?php if (!empty($health['issues'])): ?>
                <div class="xelite-status-issues">
                    <h3><?php _e('Issues Found:', 'xelite-repost-engine'); ?></h3>
                    <ul>
                        <?php foreach ($health['issues'] as $issue): ?>
                            <li><?php echo esc_html($issue); ?></li>
                        <?php endforeach; ?>
                    </ul>
                </div>
            <?php endif; ?>
        </div>
        <?php
    }

    /**
     * Render action buttons
     */
    private function render_action_buttons() {
        ?>
        <div class="xelite-cron-actions-panel">
            <h3><?php _e('Quick Actions', 'xelite-repost-engine'); ?></h3>
            
            <div class="xelite-action-buttons">
                <button type="button" class="button button-primary" id="xelite-test-cron">
                    <?php _e('Test Cron Connection', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="xelite-manual-scraping">
                    <?php _e('Run Manual Scraping', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="xelite-refresh-status">
                    <?php _e('Refresh Status', 'xelite-repost-engine'); ?>
                </button>
                
                <a href="<?php echo wp_nonce_url(admin_url('admin.php?page=xelite-repost-engine-cron&action=clear_lock'), 'xelite_clear_lock'); ?>" 
                   class="button button-secondary" 
                   onclick="return confirm('<?php esc_attr_e('Are you sure you want to clear the lock file?', 'xelite-repost-engine'); ?>')">
                    <?php _e('Clear Lock File', 'xelite-repost-engine'); ?>
                </a>
            </div>
            
            <div id="xelite-action-results"></div>
        </div>
        <?php
    }

    /**
     * Render statistics
     */
    private function render_statistics($cron_status) {
        $stats = $cron_status['statistics'];
        ?>
        <div class="xelite-cron-statistics">
            <h2><?php _e('Cron Statistics', 'xelite-repost-engine'); ?></h2>
            
            <div class="xelite-stats-grid">
                <div class="xelite-stat-item">
                    <strong><?php _e('Total Runs:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo esc_html($stats['total_runs'] ?? 0); ?></span>
                </div>
                
                <div class="xelite-stat-item">
                    <strong><?php _e('Success Rate:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo esc_html(($stats['success_rate'] ?? 0) . '%'); ?></span>
                </div>
                
                <div class="xelite-stat-item">
                    <strong><?php _e('Total Reposts Scraped:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo esc_html($stats['total_reposts_scraped'] ?? 0); ?></span>
                </div>
                
                <div class="xelite-stat-item">
                    <strong><?php _e('Total Errors:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo esc_html($stats['total_errors'] ?? 0); ?></span>
                </div>
                
                <div class="xelite-stat-item">
                    <strong><?php _e('Average Execution Time:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo esc_html(($stats['execution_time'] ?? 0) . 's'); ?></span>
                </div>
                
                <div class="xelite-stat-item">
                    <strong><?php _e('Memory Usage:', 'xelite-repost-engine'); ?></strong>
                    <span><?php echo esc_html($this->format_bytes($cron_status['health_status']['memory_usage'])); ?></span>
                </div>
            </div>
        </div>
        <?php
    }

    /**
     * Render general section
     */
    public function render_general_section() {
        echo '<p>' . __('Configure the automated scraping schedule and target accounts.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * Render frequency field
     */
    public function render_frequency_field() {
        $settings = $this->cron->get_cron_settings();
        $frequency = $settings['scraping_frequency'] ?? 'xelite_daily';
        
        $schedules = array(
            'xelite_hourly' => __('Every Hour', 'xelite-repost-engine'),
            'xelite_twice_daily' => __('Twice Daily', 'xelite-repost-engine'),
            'xelite_daily' => __('Daily', 'xelite-repost-engine'),
            'xelite_weekly' => __('Weekly', 'xelite-repost-engine')
        );
        
        echo '<select name="xelite_repost_engine_cron_settings[scraping_frequency]">';
        foreach ($schedules as $value => $label) {
            $selected = selected($frequency, $value, false);
            echo "<option value=\"{$value}\" {$selected}>{$label}</option>";
        }
        echo '</select>';
        
        echo '<p class="description">' . __('How often should the scraper run automatically?', 'xelite-repost-engine') . '</p>';
    }

    /**
     * Render accounts field
     */
    public function render_accounts_field() {
        $settings = $this->cron->get_cron_settings();
        $accounts = $settings['target_accounts'] ?? array();
        
        echo '<textarea name="xelite_repost_engine_cron_settings[target_accounts]" rows="5" cols="50" placeholder="@account1&#10;@account2&#10;@account3">';
        echo esc_textarea(implode("\n", $accounts));
        echo '</textarea>';
        
        echo '<p class="description">' . __('Enter one X account handle per line (without @ symbol).', 'xelite-repost-engine') . '</p>';
    }

    /**
     * Render notifications section
     */
    public function render_notifications_section() {
        echo '<p>' . __('Configure email notifications for cron job results.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * Render notifications field
     */
    public function render_notifications_field() {
        $settings = $this->cron->get_cron_settings();
        $enabled = $settings['notifications_enabled'] ?? false;
        
        echo '<label>';
        echo '<input type="checkbox" name="xelite_repost_engine_cron_settings[notifications_enabled]" value="1" ' . checked($enabled, true, false) . ' />';
        echo ' ' . __('Enable email notifications', 'xelite-repost-engine');
        echo '</label>';
    }

    /**
     * Render email field
     */
    public function render_email_field() {
        $settings = $this->cron->get_cron_settings();
        $email = $settings['notification_email'] ?? get_option('admin_email');
        
        echo '<input type="email" name="xelite_repost_engine_cron_settings[notification_email]" value="' . esc_attr($email) . '" class="regular-text" />';
        echo '<p class="description">' . __('Email address to receive cron notifications.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * Render advanced section
     */
    public function render_advanced_section() {
        echo '<p>' . __('Advanced configuration options for cron behavior.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * Render cleanup field
     */
    public function render_cleanup_field() {
        $settings = $this->cron->get_cron_settings();
        $days = $settings['cleanup_days'] ?? 365;
        
        echo '<input type="number" name="xelite_repost_engine_cron_settings[cleanup_days]" value="' . esc_attr($days) . '" min="1" max="3650" />';
        echo '<p class="description">' . __('Number of days to keep old repost data before cleanup.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * Render execution time field
     */
    public function render_execution_time_field() {
        $settings = $this->cron->get_cron_settings();
        $time = $settings['max_execution_time'] ?? 300;
        
        echo '<input type="number" name="xelite_repost_engine_cron_settings[max_execution_time]" value="' . esc_attr($time) . '" min="60" max="3600" />';
        echo '<p class="description">' . __('Maximum execution time for scraping jobs in seconds.', 'xelite-repost-engine') . '</p>';
    }

    /**
     * Sanitize settings
     */
    public function sanitize_settings($input) {
        $sanitized = array();
        
        // Scraping frequency
        $valid_frequencies = array('xelite_hourly', 'xelite_twice_daily', 'xelite_daily', 'xelite_weekly');
        $sanitized['scraping_frequency'] = in_array($input['scraping_frequency'], $valid_frequencies) 
            ? $input['scraping_frequency'] 
            : 'xelite_daily';
        
        // Target accounts
        $accounts = array();
        if (!empty($input['target_accounts'])) {
            $account_lines = explode("\n", $input['target_accounts']);
            foreach ($account_lines as $line) {
                $account = trim($line);
                if (!empty($account)) {
                    // Remove @ symbol if present
                    $account = ltrim($account, '@');
                    // Sanitize account name
                    $account = sanitize_text_field($account);
                    if (preg_match('/^[a-zA-Z0-9_]{1,15}$/', $account)) {
                        $accounts[] = $account;
                    }
                }
            }
        }
        $sanitized['target_accounts'] = array_unique($accounts);
        
        // Notifications
        $sanitized['notifications_enabled'] = !empty($input['notifications_enabled']);
        $sanitized['notification_email'] = sanitize_email($input['notification_email']);
        
        // Advanced settings
        $sanitized['cleanup_days'] = intval($input['cleanup_days']);
        $sanitized['max_execution_time'] = intval($input['max_execution_time']);
        
        // Validate ranges
        $sanitized['cleanup_days'] = max(1, min(3650, $sanitized['cleanup_days']));
        $sanitized['max_execution_time'] = max(60, min(3600, $sanitized['max_execution_time']));
        
        return $sanitized;
    }

    /**
     * AJAX get cron status
     */
    public function ajax_get_cron_status() {
        check_ajax_referer('xelite_cron_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        $status = $this->cron->get_cron_status();
        wp_send_json_success($status);
    }

    /**
     * AJAX test cron connection
     */
    public function ajax_test_cron_connection() {
        check_ajax_referer('xelite_cron_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        $health_status = $this->cron->check_cron_health_status();
        wp_send_json_success($health_status);
    }

    /**
     * Format bytes to human readable format
     */
    private function format_bytes($bytes) {
        $units = array('B', 'KB', 'MB', 'GB');
        
        for ($i = 0; $bytes > 1024 && $i < count($units) - 1; $i++) {
            $bytes /= 1024;
        }
        
        return round($bytes, 2) . ' ' . $units[$i];
    }
} 