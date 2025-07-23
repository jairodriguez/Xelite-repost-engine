<?php
/**
 * Xelite Repost Engine Scraper Class
 *
 * Main scraper class that handles fetching repost data from X accounts via the API
 * with authentication, rate limiting, logging, and error handling.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Main scraper class for fetching repost data
 */
class XeliteRepostEngine_Scraper extends XeliteRepostEngine_Abstract_Base {

    /**
     * X API service instance
     *
     * @var XeliteRepostEngine_X_API
     */
    private $x_api;

    /**
     * X Processor service instance
     *
     * @var XeliteRepostEngine_X_Processor
     */
    private $processor;

    /**
     * Database service instance
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;

    /**
     * Rate limiting settings
     *
     * @var array
     */
    private $rate_limits = array(
        'requests_per_15min' => 300,
        'requests_per_day' => 5000,
        'current_15min_count' => 0,
        'current_day_count' => 0,
        'last_reset_15min' => 0,
        'last_reset_day' => 0
    );

    /**
     * Scraping configuration
     *
     * @var array
     */
    private $config = array(
        'max_posts_per_account' => 100,
        'max_accounts_per_batch' => 10,
        'delay_between_requests' => 1, // seconds
        'retry_attempts' => 3,
        'retry_delay' => 5, // seconds
        'timeout' => 30, // seconds
        'enable_logging' => true,
        'log_level' => 'info'
    );

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_X_API $x_api X API service
     * @param XeliteRepostEngine_X_Processor $processor X Processor service
     * @param XeliteRepostEngine_Database $database Database service
     */
    public function __construct($x_api, $processor, $database) {
        parent::__construct();
        
        $this->x_api = $x_api;
        $this->processor = $processor;
        $this->database = $database;
        
        // Load rate limiting data
        $this->load_rate_limits();
        
        // Load configuration
        $this->load_configuration();
    }

    /**
     * Initialize the class
     */
    protected function init() {
        // Initialize hooks and setup
        add_action('xelite_scheduled_scraping', array($this, 'run_scheduled_scraping'));
        add_action('wp_ajax_xelite_test_scraper', array($this, 'ajax_test_scraper'));
        add_action('wp_ajax_xelite_get_scraping_stats', array($this, 'ajax_get_scraping_stats'));
    }

    /**
     * Load rate limiting data from database
     */
    private function load_rate_limits() {
        $stored_limits = get_option('xelite_scraper_rate_limits', array());
        
        if (!empty($stored_limits)) {
            $this->rate_limits = wp_parse_args($stored_limits, $this->rate_limits);
        }
        
        // Check if we need to reset counters
        $this->check_rate_limit_reset();
    }

    /**
     * Save rate limiting data to database
     */
    private function save_rate_limits() {
        update_option('xelite_scraper_rate_limits', $this->rate_limits);
    }

    /**
     * Check and reset rate limit counters if needed
     */
    private function check_rate_limit_reset() {
        $current_time = time();
        
        // Reset 15-minute counter
        if ($current_time - $this->rate_limits['last_reset_15min'] >= 900) {
            $this->rate_limits['current_15min_count'] = 0;
            $this->rate_limits['last_reset_15min'] = $current_time;
        }
        
        // Reset daily counter
        if ($current_time - $this->rate_limits['last_reset_day'] >= 86400) {
            $this->rate_limits['current_day_count'] = 0;
            $this->rate_limits['last_reset_day'] = $current_time;
        }
        
        $this->save_rate_limits();
    }

    /**
     * Load configuration from database
     */
    private function load_configuration() {
        $stored_config = get_option('xelite_scraper_config', array());
        
        if (!empty($stored_config)) {
            $this->config = wp_parse_args($stored_config, $this->config);
        }
    }

    /**
     * Save configuration to database
     */
    private function save_configuration() {
        update_option('xelite_scraper_config', $this->config);
    }

    /**
     * Check if we can make API requests based on rate limits
     *
     * @return bool
     */
    public function can_make_request() {
        $this->check_rate_limit_reset();
        
        return $this->rate_limits['current_15min_count'] < $this->rate_limits['requests_per_15min'] &&
               $this->rate_limits['current_day_count'] < $this->rate_limits['requests_per_day'];
    }

    /**
     * Increment rate limit counters
     */
    private function increment_rate_limits() {
        $this->rate_limits['current_15min_count']++;
        $this->rate_limits['current_day_count']++;
        $this->save_rate_limits();
    }

    /**
     * Get reposts by account
     *
     * @param string $account_handle Account handle (without @)
     * @param int $max_posts Maximum number of posts to fetch
     * @param string $since_id Fetch posts after this ID
     * @return array|WP_Error Array of reposts or WP_Error on failure
     */
    public function get_reposts_by_account($account_handle, $max_posts = null, $since_id = null) {
        if (!$this->can_make_request()) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Please try again later.');
        }

        if (empty($account_handle)) {
            return new WP_Error('invalid_account', 'Account handle is required.');
        }

        $max_posts = $max_posts ?: $this->config['max_posts_per_account'];
        
        $this->log('info', "Fetching reposts for account: @{$account_handle}, max posts: {$max_posts}");
        
        try {
            // Get user info first
            $user_info = $this->x_api->get_user_info($account_handle);
            
            if (is_wp_error($user_info)) {
                $this->log('error', "Failed to get user info for @{$account_handle}: " . $user_info->get_error_message());
                return $user_info;
            }
            
            $this->increment_rate_limits();
            
            // Get user timeline
            $timeline_params = array(
                'max_results' => min($max_posts, 100), // API limit per request
                'tweet.fields' => 'created_at,public_metrics,entities,context_annotations',
                'exclude' => 'retweets,replies'
            );
            
            if ($since_id) {
                $timeline_params['since_id'] = $since_id;
            }
            
            $posts = $this->x_api->get_user_timeline($user_info['id'], $timeline_params);
            
            if (is_wp_error($posts)) {
                $this->log('error', "Failed to get timeline for @{$account_handle}: " . $posts->get_error_message());
                return $posts;
            }
            
            $this->increment_rate_limits();
            
            // Process and filter reposts
            $reposts = $this->filter_reposts($posts['data'] ?? array());
            
            $this->log('info', "Found " . count($reposts) . " reposts for @{$account_handle}");
            
            return array(
                'account_handle' => $account_handle,
                'user_info' => $user_info,
                'reposts' => $reposts,
                'total_fetched' => count($posts['data'] ?? array()),
                'total_reposts' => count($reposts),
                'next_token' => $posts['meta']['next_token'] ?? null
            );
            
        } catch (Exception $e) {
            $this->log('error', "Exception while fetching reposts for @{$account_handle}: " . $e->getMessage());
            return new WP_Error('scraper_exception', $e->getMessage());
        }
    }

    /**
     * Filter posts to identify reposts
     *
     * @param array $posts Array of posts from API
     * @return array Array of reposts
     */
    private function filter_reposts($posts) {
        $reposts = array();
        
        foreach ($posts as $post) {
            if ($this->is_repost($post)) {
                $reposts[] = $this->process_repost_data($post);
            }
        }
        
        return $reposts;
    }

    /**
     * Check if a post is a repost
     *
     * @param array $post Post data from API
     * @return bool True if repost, false otherwise
     */
    private function is_repost($post) {
        // Check if post contains retweet indicators
        if (isset($post['referenced_tweets'])) {
            foreach ($post['referenced_tweets'] as $reference) {
                if ($reference['type'] === 'retweeted') {
                    return true;
                }
            }
        }
        
        // Check text for RT indicators
        $text = $post['text'] ?? '';
        if (preg_match('/^RT\s+@\w+:/i', $text)) {
            return true;
        }
        
        // Check for quote tweet indicators
        if (preg_match('/^QT\s+@\w+:/i', $text)) {
            return true;
        }
        
        return false;
    }

    /**
     * Process repost data into standardized format
     *
     * @param array $post Post data from API
     * @return array Processed repost data
     */
    private function process_repost_data($post) {
        $repost_data = array(
            'original_tweet_id' => $post['id'],
            'original_text' => $post['text'] ?? '',
            'created_at' => $post['created_at'] ?? '',
            'engagement_metrics' => array(
                'retweet_count' => $post['public_metrics']['retweet_count'] ?? 0,
                'like_count' => $post['public_metrics']['like_count'] ?? 0,
                'reply_count' => $post['public_metrics']['reply_count'] ?? 0,
                'quote_count' => $post['public_metrics']['quote_count'] ?? 0
            ),
            'entities' => $post['entities'] ?? array(),
            'context_annotations' => $post['context_annotations'] ?? array()
        );
        
        // Extract referenced tweet info
        if (isset($post['referenced_tweets'])) {
            foreach ($post['referenced_tweets'] as $reference) {
                if ($reference['type'] === 'retweeted') {
                    $repost_data['referenced_tweet_id'] = $reference['id'];
                    break;
                }
            }
        }
        
        return $repost_data;
    }

    /**
     * Save repost data to database
     *
     * @param array $repost_data Repost data
     * @param string $source_handle Source account handle
     * @return bool|WP_Error True on success, WP_Error on failure
     */
    public function save_to_database($repost_data, $source_handle) {
        if (empty($repost_data) || empty($source_handle)) {
            return new WP_Error('invalid_data', 'Repost data and source handle are required.');
        }
        
        try {
            // Add source handle to repost data
            $repost_data['source_handle'] = $source_handle;
            
            // Store using processor
            $result = $this->processor->store_repost_data($repost_data);
            
            if ($result) {
                $this->log('info', "Saved repost {$repost_data['original_tweet_id']} from @{$source_handle}");
                return true;
            } else {
                $this->log('error', "Failed to save repost {$repost_data['original_tweet_id']} from @{$source_handle}");
                return new WP_Error('save_failed', 'Failed to save repost data');
            }
            
        } catch (Exception $e) {
            $this->log('error', "Exception while saving repost: " . $e->getMessage());
            return new WP_Error('save_exception', $e->getMessage());
        }
    }

    /**
     * Save multiple reposts to database with duplicate checking
     *
     * @param array $reposts_array Array of repost data
     * @param string $source_handle Source account handle
     * @return array Results with success/error counts
     */
    public function save_reposts_batch($reposts_array, $source_handle) {
        if (empty($reposts_array) || empty($source_handle)) {
            return array('success' => 0, 'errors' => 0, 'results' => array());
        }

        // Add source handle to all reposts
        $processed_reposts = array();
        foreach ($reposts_array as $repost_data) {
            $repost_data['source_handle'] = $source_handle;
            $processed_reposts[] = $repost_data;
        }

        // Filter out duplicates
        $unique_reposts = $this->processor->filter_duplicates($processed_reposts);
        
        if (empty($unique_reposts)) {
            $this->log('info', "All reposts from @{$source_handle} were duplicates");
            return array('success' => 0, 'errors' => 0, 'duplicates' => count($processed_reposts), 'results' => array());
        }

        // Store unique reposts in batch
        $results = $this->processor->store_reposts_batch($unique_reposts);
        $results['duplicates'] = count($processed_reposts) - count($unique_reposts);
        
        $this->log('info', "Batch saved reposts from @{$source_handle}: {$results['success']} successful, {$results['errors']} errors, {$results['duplicates']} duplicates");
        
        return $results;
    }

    /**
     * Scrape multiple accounts in batch
     *
     * @param array $accounts Array of account handles
     * @param int $max_posts_per_account Maximum posts per account
     * @return array Results for each account
     */
    public function scrape_accounts_batch($accounts, $max_posts_per_account = null) {
        if (empty($accounts)) {
            return new WP_Error('no_accounts', 'No accounts provided for scraping.');
        }
        
        $max_posts_per_account = $max_posts_per_account ?: $this->config['max_posts_per_account'];
        $max_accounts = $this->config['max_accounts_per_batch'];
        
        // Limit number of accounts per batch
        $accounts = array_slice($accounts, 0, $max_accounts);
        
        $this->log('info', "Starting batch scrape for " . count($accounts) . " accounts");
        
        $results = array();
        $total_reposts = 0;
        $total_errors = 0;
        
        foreach ($accounts as $account) {
            $account = trim($account);
            if (empty($account)) {
                continue;
            }
            
            // Remove @ if present
            $account = ltrim($account, '@');
            
            $this->log('info', "Processing account: @{$account}");
            
            $result = $this->get_reposts_by_account($account, $max_posts_per_account);
            
            if (is_wp_error($result)) {
                $results[$account] = array(
                    'success' => false,
                    'error' => $result->get_error_message(),
                    'reposts' => array()
                );
                $total_errors++;
            } else {
                // Save reposts to database using batch storage
                $save_results = $this->save_reposts_batch($result['reposts'], $account);
                $saved_count = $save_results['success'];
                
                $results[$account] = array(
                    'success' => true,
                    'user_info' => $result['user_info'],
                    'total_fetched' => $result['total_fetched'],
                    'total_reposts' => $result['total_reposts'],
                    'saved_count' => $saved_count,
                    'reposts' => $result['reposts']
                );
                
                $total_reposts += $saved_count;
            }
            
            // Add delay between accounts to respect rate limits
            if ($this->config['delay_between_requests'] > 0) {
                sleep($this->config['delay_between_requests']);
            }
        }
        
        $this->log('info', "Batch scrape completed. Total reposts saved: {$total_reposts}, Errors: {$total_errors}");
        
        return array(
            'results' => $results,
            'summary' => array(
                'total_accounts' => count($accounts),
                'successful_accounts' => count($accounts) - $total_errors,
                'failed_accounts' => $total_errors,
                'total_reposts_saved' => $total_reposts
            )
        );
    }

    /**
     * Get scraping statistics
     *
     * @return array Statistics about scraping activity
     */
    public function get_scraping_statistics() {
        $stats = array(
            'rate_limits' => $this->rate_limits,
            'config' => $this->config,
            'can_make_request' => $this->can_make_request(),
            'last_scrape_time' => get_option('xelite_last_scrape_time', 0),
            'total_scrapes_today' => get_option('xelite_scrapes_today', 0),
            'total_reposts_scraped' => get_option('xelite_total_reposts_scraped', 0)
        );
        
        return $stats;
    }

    /**
     * Update scraping statistics
     *
     * @param int $reposts_count Number of reposts scraped
     */
    private function update_scraping_statistics($reposts_count) {
        update_option('xelite_last_scrape_time', time());
        
        $scrapes_today = get_option('xelite_scrapes_today', 0);
        $total_reposts = get_option('xelite_total_reposts_scraped', 0);
        
        update_option('xelite_scrapes_today', $scrapes_today + 1);
        update_option('xelite_total_reposts_scraped', $total_reposts + $reposts_count);
    }

    /**
     * Reset daily statistics
     */
    public function reset_daily_statistics() {
        update_option('xelite_scrapes_today', 0);
    }

    /**
     * Test API connection
     *
     * @return array|WP_Error Connection test results
     */
    public function test_api_connection() {
        if (!$this->can_make_request()) {
            return new WP_Error('rate_limit_exceeded', 'Rate limit exceeded. Cannot test connection.');
        }
        
        try {
            // Test with a known public account (Twitter's own account)
            $result = $this->x_api->get_user_info('twitter');
            
            if (is_wp_error($result)) {
                return $result;
            }
            
            $this->increment_rate_limits();
            
            return array(
                'success' => true,
                'message' => 'API connection successful',
                'user_info' => $result
            );
            
        } catch (Exception $e) {
            return new WP_Error('connection_failed', 'API connection failed: ' . $e->getMessage());
        }
    }

    /**
     * Get rate limit status
     *
     * @return array Rate limit information
     */
    public function get_rate_limit_status() {
        $this->check_rate_limit_reset();
        
        return array(
            'requests_per_15min' => $this->rate_limits['requests_per_15min'],
            'current_15min_count' => $this->rate_limits['current_15min_count'],
            'remaining_15min' => $this->rate_limits['requests_per_15min'] - $this->rate_limits['current_15min_count'],
            'requests_per_day' => $this->rate_limits['requests_per_day'],
            'current_day_count' => $this->rate_limits['current_day_count'],
            'remaining_day' => $this->rate_limits['requests_per_day'] - $this->rate_limits['current_day_count'],
            'next_reset_15min' => $this->rate_limits['last_reset_15min'] + 900,
            'next_reset_day' => $this->rate_limits['last_reset_day'] + 86400,
            'can_make_request' => $this->can_make_request()
        );
    }

    /**
     * Update configuration
     *
     * @param array $new_config New configuration values
     * @return bool True on success
     */
    public function update_configuration($new_config) {
        $this->config = wp_parse_args($new_config, $this->config);
        $this->save_configuration();
        
        $this->log('info', 'Configuration updated: ' . json_encode($new_config));
        
        return true;
    }

    /**
     * Get current configuration
     *
     * @return array Current configuration
     */
    public function get_configuration() {
        return $this->config;
    }

    /**
     * Log message
     *
     * @param string $level Log level (debug, info, warning, error)
     * @param string $message Log message
     */
    private function log($level, $message) {
        if (!$this->config['enable_logging']) {
            return;
        }
        
        $log_levels = array('debug' => 0, 'info' => 1, 'warning' => 2, 'error' => 3);
        $current_level = $log_levels[$this->config['log_level']] ?? 1;
        $message_level = $log_levels[$level] ?? 1;
        
        if ($message_level >= $current_level) {
            $log_entry = array(
                'timestamp' => current_time('mysql'),
                'level' => strtoupper($level),
                'message' => $message
            );
            
            // Store in WordPress options for now (could be enhanced with custom table)
            $logs = get_option('xelite_scraper_logs', array());
            $logs[] = $log_entry;
            
            // Keep only last 1000 log entries
            if (count($logs) > 1000) {
                $logs = array_slice($logs, -1000);
            }
            
            update_option('xelite_scraper_logs', $logs);
        }
    }

    /**
     * Get logs
     *
     * @param string $level Filter by log level
     * @param int $limit Number of log entries to return
     * @return array Log entries
     */
    public function get_logs($level = null, $limit = 100) {
        $logs = get_option('xelite_scraper_logs', array());
        
        if ($level) {
            $logs = array_filter($logs, function($log) use ($level) {
                return $log['level'] === strtoupper($level);
            });
        }
        
        return array_slice($logs, -$limit);
    }

    /**
     * Clear logs
     *
     * @return bool True on success
     */
    public function clear_logs() {
        delete_option('xelite_scraper_logs');
        return true;
    }

    /**
     * Schedule scraping job
     *
     * @param array $accounts Array of account handles
     * @param string $schedule WordPress cron schedule
     * @return bool True on success
     */
    public function schedule_scraping($accounts, $schedule = 'hourly') {
        if (empty($accounts)) {
            return false;
        }
        
        // Clear existing schedule
        wp_clear_scheduled_hook('xelite_scraper_cron');
        
        // Schedule new job
        $scheduled = wp_schedule_event(time(), $schedule, 'xelite_scraper_cron', array($accounts));
        
        if ($scheduled) {
            update_option('xelite_scraper_schedule', array(
                'accounts' => $accounts,
                'schedule' => $schedule,
                'next_run' => wp_next_scheduled('xelite_scraper_cron')
            ));
            
            $this->log('info', "Scheduled scraping for " . count($accounts) . " accounts with schedule: {$schedule}");
            return true;
        }
        
        return false;
    }

    /**
     * Cancel scheduled scraping
     *
     * @return bool True on success
     */
    public function cancel_scheduled_scraping() {
        wp_clear_scheduled_hook('xelite_scraper_cron');
        delete_option('xelite_scraper_schedule');
        
        $this->log('info', 'Cancelled scheduled scraping');
        
        return true;
    }

    /**
     * Get scheduled scraping info
     *
     * @return array|false Scheduled scraping information or false if not scheduled
     */
    public function get_scheduled_scraping() {
        $schedule = get_option('xelite_scraper_schedule', false);
        
        if ($schedule) {
            $schedule['next_run'] = wp_next_scheduled('xelite_scraper_cron');
            $schedule['is_scheduled'] = wp_next_scheduled('xelite_scraper_cron') !== false;
        }
        
        return $schedule;
    }

    /**
     * Run scheduled scraping job
     *
     * @param array $accounts Array of account handles
     * @return array Results
     */
    public function run_scheduled_scraping($accounts) {
        $this->log('info', 'Running scheduled scraping for ' . count($accounts) . ' accounts');
        
        $results = $this->scrape_accounts_batch($accounts);
        
        if (!is_wp_error($results)) {
            $this->update_scraping_statistics($results['summary']['total_reposts_saved']);
        }
        
        return $results;
    }
} 