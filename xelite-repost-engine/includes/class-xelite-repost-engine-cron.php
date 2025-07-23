<?php
/**
 * WordPress Cron Scheduler for Repost Intelligence
 *
 * Handles automated scraping via WordPress cron with custom schedules,
 * locking mechanisms, monitoring, and admin configuration.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Cron Scheduler Class
 */
class XeliteRepostEngine_Cron extends XeliteRepostEngine_Abstract_Base {

    /**
     * Scraper instance
     *
     * @var XeliteRepostEngine_Scraper
     */
    private $scraper;

    /**
     * Database instance
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger
     */
    private $logger;

    /**
     * Cron hook names
     *
     * @var array
     */
    private $cron_hooks = array(
        'scraping' => 'xelite_repost_engine_scraping_cron',
        'cleanup' => 'xelite_repost_engine_cleanup_cron',
        'monitoring' => 'xelite_repost_engine_monitoring_cron'
    );

    /**
     * Lock file path
     *
     * @var string
     */
    private $lock_file;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Scraper $scraper Scraper service.
     * @param XeliteRepostEngine_Database $database Database service.
     * @param XeliteRepostEngine_Logger $logger Logger service.
     */
    public function __construct($scraper, $database, $logger = null) {
        parent::__construct();
        
        $this->scraper = $scraper;
        $this->database = $database;
        $this->logger = $logger;
        
        // Set lock file path
        $this->lock_file = WP_CONTENT_DIR . '/xelite-repost-engine-cron.lock';
    }

    /**
     * Initialize the class
     */
    protected function init() {
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Custom cron schedules
        add_filter('cron_schedules', array($this, 'add_custom_schedules'));
        
        // Cron event handlers
        add_action($this->cron_hooks['scraping'], array($this, 'run_scraping_cron'));
        add_action($this->cron_hooks['cleanup'], array($this, 'run_cleanup_cron'));
        add_action($this->cron_hooks['monitoring'], array($this, 'run_monitoring_cron'));
        
        // Activation/deactivation hooks
        register_activation_hook(XELITE_REPOST_ENGINE_FILE, array($this, 'activate_cron_jobs'));
        register_deactivation_hook(XELITE_REPOST_ENGINE_FILE, array($this, 'deactivate_cron_jobs'));
        
        // Admin hooks
        add_action('admin_init', array($this, 'handle_cron_actions'));
        add_action('wp_ajax_xelite_test_cron', array($this, 'ajax_test_cron'));
        add_action('wp_ajax_xelite_manual_scraping', array($this, 'ajax_manual_scraping'));
        
        // Health check
        add_action('init', array($this, 'check_cron_health'));
    }

    /**
     * Add custom cron schedules
     *
     * @param array $schedules Existing schedules.
     * @return array Modified schedules.
     */
    public function add_custom_schedules($schedules) {
        $schedules['xelite_hourly'] = array(
            'interval' => HOUR_IN_SECONDS,
            'display' => __('Every Hour (Xelite)', 'xelite-repost-engine')
        );
        
        $schedules['xelite_twice_daily'] = array(
            'interval' => 12 * HOUR_IN_SECONDS,
            'display' => __('Twice Daily (Xelite)', 'xelite-repost-engine')
        );
        
        $schedules['xelite_daily'] = array(
            'interval' => DAY_IN_SECONDS,
            'display' => __('Daily (Xelite)', 'xelite-repost-engine')
        );
        
        $schedules['xelite_weekly'] = array(
            'interval' => WEEK_IN_SECONDS,
            'display' => __('Weekly (Xelite)', 'xelite-repost-engine')
        );
        
        return $schedules;
    }

    /**
     * Activate cron jobs
     */
    public function activate_cron_jobs() {
        $this->log('info', 'Activating cron jobs');
        
        // Schedule scraping cron
        $this->schedule_scraping_cron();
        
        // Schedule cleanup cron (daily)
        if (!wp_next_scheduled($this->cron_hooks['cleanup'])) {
            wp_schedule_event(time(), 'daily', $this->cron_hooks['cleanup']);
        }
        
        // Schedule monitoring cron (hourly)
        if (!wp_next_scheduled($this->cron_hooks['monitoring'])) {
            wp_schedule_event(time(), 'hourly', $this->cron_hooks['monitoring']);
        }
        
        $this->log('info', 'Cron jobs activated successfully');
    }

    /**
     * Deactivate cron jobs
     */
    public function deactivate_cron_jobs() {
        $this->log('info', 'Deactivating cron jobs');
        
        // Clear all scheduled events
        foreach ($this->cron_hooks as $hook) {
            wp_clear_scheduled_hook($hook);
        }
        
        // Remove lock file
        $this->remove_lock_file();
        
        $this->log('info', 'Cron jobs deactivated successfully');
    }

    /**
     * Schedule scraping cron based on settings
     */
    public function schedule_scraping_cron() {
        $settings = $this->get_cron_settings();
        $schedule = $settings['scraping_frequency'] ?? 'xelite_daily';
        
        // Clear existing scraping cron
        wp_clear_scheduled_hook($this->cron_hooks['scraping']);
        
        // Schedule new scraping cron
        if (!wp_next_scheduled($this->cron_hooks['scraping'])) {
            wp_schedule_event(time(), $schedule, $this->cron_hooks['scraping']);
        }
        
        $this->log('info', "Scraping cron scheduled with frequency: {$schedule}");
    }

    /**
     * Run scraping cron job
     */
    public function run_scraping_cron() {
        // Check if already running
        if ($this->is_cron_running()) {
            $this->log('warning', 'Scraping cron already running, skipping execution');
            return;
        }
        
        // Create lock file
        $this->create_lock_file();
        
        try {
            $this->log('info', 'Starting automated scraping cron job');
            
            $start_time = microtime(true);
            
            // Get target accounts from settings
            $settings = $this->get_cron_settings();
            $target_accounts = $settings['target_accounts'] ?? array();
            
            if (empty($target_accounts)) {
                $this->log('warning', 'No target accounts configured for scraping');
                $this->remove_lock_file();
                return;
            }
            
            // Run scraping
            $results = $this->scraper->scrape_accounts_batch($target_accounts);
            
            $end_time = microtime(true);
            $execution_time = round($end_time - $start_time, 2);
            
            // Log results
            $this->log('info', "Scraping cron completed in {$execution_time}s", $results);
            
            // Update statistics
            $this->update_cron_statistics($results, $execution_time);
            
            // Send notifications if enabled
            if ($settings['notifications_enabled'] ?? false) {
                $this->send_cron_notification($results, $execution_time);
            }
            
        } catch (Exception $e) {
            $this->log('error', 'Scraping cron failed: ' . $e->getMessage());
            
            // Send error notification
            if ($settings['notifications_enabled'] ?? false) {
                $this->send_error_notification($e->getMessage());
            }
        }
        
        // Remove lock file
        $this->remove_lock_file();
    }

    /**
     * Run cleanup cron job
     */
    public function run_cleanup_cron() {
        $this->log('info', 'Starting cleanup cron job');
        
        try {
            $settings = $this->get_cron_settings();
            $cleanup_days = $settings['cleanup_days'] ?? 365;
            
            // Clean up old reposts
            $deleted_count = $this->database->cleanup_old_reposts($cleanup_days);
            
            // Clean up old logs
            $this->cleanup_old_logs();
            
            $this->log('info', "Cleanup cron completed: {$deleted_count} old reposts deleted");
            
        } catch (Exception $e) {
            $this->log('error', 'Cleanup cron failed: ' . $e->getMessage());
        }
    }

    /**
     * Run monitoring cron job
     */
    public function run_monitoring_cron() {
        $this->log('info', 'Starting monitoring cron job');
        
        try {
            // Check cron health
            $health_status = $this->check_cron_health_status();
            
            // Check for stuck processes
            $this->check_stuck_processes();
            
            // Update monitoring statistics
            $this->update_monitoring_statistics($health_status);
            
            $this->log('info', 'Monitoring cron completed', $health_status);
            
        } catch (Exception $e) {
            $this->log('error', 'Monitoring cron failed: ' . $e->getMessage());
        }
    }

    /**
     * Check if cron is already running
     *
     * @return bool True if running.
     */
    private function is_cron_running() {
        if (!file_exists($this->lock_file)) {
            return false;
        }
        
        $lock_time = filemtime($this->lock_file);
        $current_time = time();
        
        // Consider lock stale after 1 hour
        if ($current_time - $lock_time > HOUR_IN_SECONDS) {
            $this->remove_lock_file();
            return false;
        }
        
        return true;
    }

    /**
     * Create lock file
     */
    private function create_lock_file() {
        $lock_content = array(
            'pid' => getmypid(),
            'timestamp' => time(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? 'unknown'
        );
        
        file_put_contents($this->lock_file, json_encode($lock_content));
    }

    /**
     * Remove lock file
     */
    private function remove_lock_file() {
        if (file_exists($this->lock_file)) {
            unlink($this->lock_file);
        }
    }

    /**
     * Check for stuck processes
     */
    private function check_stuck_processes() {
        if (!file_exists($this->lock_file)) {
            return;
        }
        
        $lock_content = json_decode(file_get_contents($this->lock_file), true);
        $pid = $lock_content['pid'] ?? 0;
        
        if ($pid && !posix_kill($pid, 0)) {
            // Process is dead, remove lock
            $this->log('warning', "Removing stale lock file for dead process {$pid}");
            $this->remove_lock_file();
        }
    }

    /**
     * Get cron settings
     *
     * @return array Settings array.
     */
    public function get_cron_settings() {
        $defaults = array(
            'scraping_frequency' => 'xelite_daily',
            'target_accounts' => array(),
            'notifications_enabled' => false,
            'notification_email' => get_option('admin_email'),
            'cleanup_days' => 365,
            'max_execution_time' => 300,
            'memory_limit' => '256M',
            'retry_failed' => true,
            'max_retries' => 3
        );
        
        $settings = get_option('xelite_repost_engine_cron_settings', array());
        
        return wp_parse_args($settings, $defaults);
    }

    /**
     * Update cron settings
     *
     * @param array $settings New settings.
     * @return bool Success status.
     */
    public function update_cron_settings($settings) {
        $current_settings = $this->get_cron_settings();
        $new_settings = wp_parse_args($settings, $current_settings);
        
        $result = update_option('xelite_repost_engine_cron_settings', $new_settings);
        
        if ($result) {
            // Reschedule cron if frequency changed
            if (isset($settings['scraping_frequency'])) {
                $this->schedule_scraping_cron();
            }
            
            $this->log('info', 'Cron settings updated', $new_settings);
        }
        
        return $result;
    }

    /**
     * Update cron statistics
     *
     * @param array $results Scraping results.
     * @param float $execution_time Execution time in seconds.
     */
    private function update_cron_statistics($results, $execution_time) {
        $stats = get_option('xelite_repost_engine_cron_stats', array());
        
        $stats['last_run'] = current_time('mysql');
        $stats['execution_time'] = $execution_time;
        $stats['total_runs'] = ($stats['total_runs'] ?? 0) + 1;
        $stats['total_reposts_scraped'] = ($stats['total_reposts_scraped'] ?? 0) + ($results['total_reposts'] ?? 0);
        $stats['total_errors'] = ($stats['total_errors'] ?? 0) + ($results['total_errors'] ?? 0);
        
        // Update success rate
        $total_attempts = $stats['total_runs'];
        $successful_runs = $total_attempts - $stats['total_errors'];
        $stats['success_rate'] = $total_attempts > 0 ? round(($successful_runs / $total_attempts) * 100, 2) : 0;
        
        update_option('xelite_repost_engine_cron_stats', $stats);
    }

    /**
     * Update monitoring statistics
     *
     * @param array $health_status Health status data.
     */
    private function update_monitoring_statistics($health_status) {
        $monitoring_stats = get_option('xelite_repost_engine_monitoring_stats', array());
        
        $monitoring_stats['last_check'] = current_time('mysql');
        $monitoring_stats['health_status'] = $health_status;
        $monitoring_stats['total_checks'] = ($monitoring_stats['total_checks'] ?? 0) + 1;
        
        update_option('xelite_repost_engine_monitoring_stats', $monitoring_stats);
    }

    /**
     * Check cron health status
     *
     * @return array Health status data.
     */
    public function check_cron_health_status() {
        $health = array(
            'status' => 'healthy',
            'issues' => array(),
            'last_run' => null,
            'next_run' => null,
            'lock_file_exists' => false,
            'memory_usage' => memory_get_usage(true),
            'peak_memory' => memory_get_peak_usage(true)
        );
        
        // Check last run
        $stats = get_option('xelite_repost_engine_cron_stats', array());
        $health['last_run'] = $stats['last_run'] ?? null;
        
        // Check next scheduled run
        $next_run = wp_next_scheduled($this->cron_hooks['scraping']);
        $health['next_run'] = $next_run ? date('Y-m-d H:i:s', $next_run) : null;
        
        // Check lock file
        $health['lock_file_exists'] = file_exists($this->lock_file);
        
        // Check for issues
        if ($health['lock_file_exists']) {
            $health['status'] = 'warning';
            $health['issues'][] = 'Lock file exists - cron may be stuck';
        }
        
        if (!$next_run) {
            $health['status'] = 'error';
            $health['issues'][] = 'No scraping cron scheduled';
        }
        
        // Check if last run was too long ago
        if ($health['last_run']) {
            $last_run_time = strtotime($health['last_run']);
            $time_since_last = time() - $last_run_time;
            
            if ($time_since_last > DAY_IN_SECONDS) {
                $health['status'] = 'warning';
                $health['issues'][] = 'Last run was more than 24 hours ago';
            }
        }
        
        return $health;
    }

    /**
     * Send cron notification
     *
     * @param array $results Scraping results.
     * @param float $execution_time Execution time.
     */
    private function send_cron_notification($results, $execution_time) {
        $settings = $this->get_cron_settings();
        $email = $settings['notification_email'] ?? get_option('admin_email');
        
        $subject = sprintf('[%s] Repost Scraping Completed', get_bloginfo('name'));
        
        $message = sprintf(
            "Repost scraping cron job completed successfully.\n\n" .
            "Execution Time: %.2f seconds\n" .
            "Total Reposts: %d\n" .
            "Total Errors: %d\n" .
            "Accounts Processed: %d\n\n" .
            "Results:\n%s",
            $execution_time,
            $results['total_reposts'] ?? 0,
            $results['total_errors'] ?? 0,
            count($results['accounts'] ?? array()),
            print_r($results, true)
        );
        
        wp_mail($email, $subject, $message);
    }

    /**
     * Send error notification
     *
     * @param string $error_message Error message.
     */
    private function send_error_notification($error_message) {
        $settings = $this->get_cron_settings();
        $email = $settings['notification_email'] ?? get_option('admin_email');
        
        $subject = sprintf('[%s] Repost Scraping Failed', get_bloginfo('name'));
        
        $message = sprintf(
            "Repost scraping cron job failed with the following error:\n\n%s\n\n" .
            "Please check the logs for more details.",
            $error_message
        );
        
        wp_mail($email, $subject, $message);
    }

    /**
     * Clean up old logs
     */
    private function cleanup_old_logs() {
        $settings = $this->get_cron_settings();
        $cleanup_days = $settings['cleanup_days'] ?? 365;
        
        // Clean up scraper logs
        $this->scraper->clear_logs($cleanup_days);
        
        // Clean up old statistics
        $this->cleanup_old_statistics($cleanup_days);
    }

    /**
     * Clean up old statistics
     *
     * @param int $days_old Number of days old to consider.
     */
    private function cleanup_old_statistics($days_old) {
        $cutoff_time = time() - ($days_old * DAY_IN_SECONDS);
        
        // Clean up old cron stats (keep only recent data)
        $stats = get_option('xelite_repost_engine_cron_stats', array());
        if (isset($stats['last_run']) && strtotime($stats['last_run']) < $cutoff_time) {
            // Reset stats if too old
            update_option('xelite_repost_engine_cron_stats', array());
        }
    }

    /**
     * Handle admin cron actions
     */
    public function handle_cron_actions() {
        if (!current_user_can('manage_options')) {
            return;
        }
        
        if (isset($_POST['xelite_cron_action'])) {
            $action = sanitize_text_field($_POST['xelite_cron_action']);
            
            switch ($action) {
                case 'test_cron':
                    $this->test_cron_manually();
                    break;
                    
                case 'manual_scraping':
                    $this->run_manual_scraping();
                    break;
                    
                case 'clear_lock':
                    $this->remove_lock_file();
                    wp_redirect(admin_url('admin.php?page=xelite-repost-engine&cron_lock_cleared=1'));
                    exit;
            }
        }
    }

    /**
     * Test cron manually
     */
    public function test_cron_manually() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        $this->log('info', 'Manual cron test initiated by admin');
        
        // Run a small test scraping
        $test_accounts = array('test_account');
        $results = $this->scraper->scrape_accounts_batch($test_accounts, 5);
        
        $this->log('info', 'Manual cron test completed', $results);
        
        wp_redirect(admin_url('admin.php?page=xelite-repost-engine&cron_test_completed=1'));
        exit;
    }

    /**
     * Run manual scraping
     */
    public function run_manual_scraping() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        $this->log('info', 'Manual scraping initiated by admin');
        
        $settings = $this->get_cron_settings();
        $target_accounts = $settings['target_accounts'] ?? array();
        
        if (empty($target_accounts)) {
            wp_redirect(admin_url('admin.php?page=xelite-repost-engine&manual_scraping_no_accounts=1'));
            exit;
        }
        
        $results = $this->scraper->scrape_accounts_batch($target_accounts);
        
        $this->log('info', 'Manual scraping completed', $results);
        
        wp_redirect(admin_url('admin.php?page=xelite-repost-engine&manual_scraping_completed=1'));
        exit;
    }

    /**
     * AJAX test cron
     */
    public function ajax_test_cron() {
        check_ajax_referer('xelite_cron_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        $health_status = $this->check_cron_health_status();
        
        wp_send_json_success($health_status);
    }

    /**
     * AJAX manual scraping
     */
    public function ajax_manual_scraping() {
        check_ajax_referer('xelite_cron_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have permission to perform this action.', 'xelite-repost-engine'));
        }
        
        $settings = $this->get_cron_settings();
        $target_accounts = $settings['target_accounts'] ?? array();
        
        if (empty($target_accounts)) {
            wp_send_json_error(__('No target accounts configured.', 'xelite-repost-engine'));
        }
        
        $results = $this->scraper->scrape_accounts_batch($target_accounts);
        
        wp_send_json_success($results);
    }

    /**
     * Get cron status information
     *
     * @return array Status information.
     */
    public function get_cron_status() {
        $status = array(
            'scraping_scheduled' => wp_next_scheduled($this->cron_hooks['scraping']),
            'cleanup_scheduled' => wp_next_scheduled($this->cron_hooks['cleanup']),
            'monitoring_scheduled' => wp_next_scheduled($this->cron_hooks['monitoring']),
            'health_status' => $this->check_cron_health_status(),
            'statistics' => get_option('xelite_repost_engine_cron_stats', array()),
            'settings' => $this->get_cron_settings()
        );
        
        return $status;
    }

    /**
     * Check cron health on init
     */
    public function check_cron_health() {
        // Only check occasionally to avoid performance impact
        if (rand(1, 100) > 5) {
            return;
        }
        
        $health_status = $this->check_cron_health_status();
        
        if ($health_status['status'] === 'error') {
            $this->log('error', 'Cron health check failed', $health_status);
        }
    }

    /**
     * Log message
     *
     * @param string $level Log level.
     * @param string $message Log message.
     * @param array $context Additional context.
     */
    private function log($level, $message, $context = array()) {
        if ($this->logger) {
            $this->logger->log($level, "[Cron] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine Cron] {$message}");
        }
    }
} 