<?php
/**
 * A/B Testing Framework for Few-Shot Prompt Optimization
 *
 * Manages A/B testing of different prompt configurations and tracks performance
 * to optimize few-shot learning effectiveness.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * A/B Testing Class for Prompt Optimization
 */
class XeliteRepostEngine_AB_Testing extends XeliteRepostEngine_Abstract_Base {

    /**
     * Database service
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;

    /**
     * Prompt builder service
     *
     * @var XeliteRepostEngine_Prompt_Builder
     */
    private $prompt_builder;

    /**
     * Few-shot collector service
     *
     * @var XeliteRepostEngine_Few_Shot_Collector
     */
    private $few_shot_collector;

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger
     */
    private $logger;

    /**
     * Database tables
     *
     * @var array
     */
    private $tables = array();

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Database $database Database service.
     * @param XeliteRepostEngine_Prompt_Builder $prompt_builder Prompt builder service.
     * @param XeliteRepostEngine_Few_Shot_Collector $few_shot_collector Few-shot collector service.
     * @param XeliteRepostEngine_Logger $logger Logger service.
     */
    public function __construct($database, $prompt_builder, $few_shot_collector, $logger = null) {
        $this->database = $database;
        $this->prompt_builder = $prompt_builder;
        $this->few_shot_collector = $few_shot_collector;
        $this->logger = $logger;
        
        $this->init();
    }

    /**
     * Initialize the A/B testing system
     */
    protected function init() {
        global $wpdb;
        $this->tables = array(
            'ab_tests' => $wpdb->prefix . 'xelite_ab_tests',
            'ab_variations' => $wpdb->prefix . 'xelite_ab_variations',
            'ab_results' => $wpdb->prefix . 'xelite_ab_results',
            'ab_assignments' => $wpdb->prefix . 'xelite_ab_assignments'
        );
        
        $this->create_ab_testing_tables();
        $this->init_hooks();
    }

    /**
     * Create A/B testing database tables
     */
    private function create_ab_testing_tables() {
        global $wpdb;
        $charset_collate = $wpdb->get_charset_collate();
        
        // A/B tests table
        $tests_table = $this->tables['ab_tests'];
        $sql_tests = "CREATE TABLE $tests_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_name varchar(255) NOT NULL COMMENT 'Human-readable test name',
            test_type varchar(100) NOT NULL COMMENT 'Type of test (prompt_template, example_count, etc.)',
            status varchar(50) DEFAULT 'active' COMMENT 'Test status (active, paused, completed)',
            traffic_split decimal(5,2) DEFAULT 50.00 COMMENT 'Traffic split percentage for each variation',
            confidence_level decimal(5,2) DEFAULT 95.00 COMMENT 'Statistical confidence level required',
            min_sample_size int(11) DEFAULT 100 COMMENT 'Minimum sample size for statistical significance',
            start_date datetime DEFAULT CURRENT_TIMESTAMP,
            end_date datetime DEFAULT NULL,
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_type (test_type),
            KEY status (status),
            KEY start_date (start_date)
        ) $charset_collate;";
        
        // A/B variations table
        $variations_table = $this->tables['ab_variations'];
        $sql_variations = "CREATE TABLE $variations_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_id bigint(20) NOT NULL COMMENT 'Reference to A/B test',
            variation_name varchar(255) NOT NULL COMMENT 'Variation name (A, B, C, etc.)',
            variation_config json NOT NULL COMMENT 'JSON configuration for this variation',
            is_control tinyint(1) DEFAULT 0 COMMENT 'Whether this is the control variation',
            sample_size int(11) DEFAULT 0 COMMENT 'Number of users assigned to this variation',
            conversion_rate decimal(5,4) DEFAULT 0.0000 COMMENT 'Conversion rate for this variation',
            engagement_score decimal(10,4) DEFAULT 0.0000 COMMENT 'Average engagement score',
            is_winner tinyint(1) DEFAULT 0 COMMENT 'Whether this variation won the test',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id),
            KEY is_control (is_control),
            KEY is_winner (is_winner),
            FOREIGN KEY (test_id) REFERENCES $tests_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // A/B results table
        $results_table = $this->tables['ab_results'];
        $sql_results = "CREATE TABLE $results_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_id bigint(20) NOT NULL COMMENT 'Reference to A/B test',
            variation_id bigint(20) NOT NULL COMMENT 'Reference to variation',
            user_id bigint(20) DEFAULT NULL COMMENT 'User who generated content',
            content_id bigint(20) DEFAULT NULL COMMENT 'Generated content ID',
            prompt_used text NOT NULL COMMENT 'Prompt that was used',
            generated_content text NOT NULL COMMENT 'Generated content',
            engagement_score decimal(10,4) DEFAULT 0.0000 COMMENT 'Engagement score achieved',
            was_reposted tinyint(1) DEFAULT 0 COMMENT 'Whether content was reposted',
            repost_count int(11) DEFAULT 0 COMMENT 'Number of reposts',
            like_count int(11) DEFAULT 0 COMMENT 'Number of likes',
            retweet_count int(11) DEFAULT 0 COMMENT 'Number of retweets',
            reply_count int(11) DEFAULT 0 COMMENT 'Number of replies',
            view_count int(11) DEFAULT 0 COMMENT 'Number of views',
            feedback_rating int(11) DEFAULT NULL COMMENT 'User feedback rating (1-5)',
            feedback_notes text DEFAULT NULL COMMENT 'User feedback notes',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY test_id (test_id),
            KEY variation_id (variation_id),
            KEY user_id (user_id),
            KEY engagement_score (engagement_score),
            KEY was_reposted (was_reposted),
            KEY created_at (created_at),
            FOREIGN KEY (test_id) REFERENCES $tests_table(id) ON DELETE CASCADE,
            FOREIGN KEY (variation_id) REFERENCES $variations_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // A/B assignments table
        $assignments_table = $this->tables['ab_assignments'];
        $sql_assignments = "CREATE TABLE $assignments_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            test_id bigint(20) NOT NULL COMMENT 'Reference to A/B test',
            variation_id bigint(20) NOT NULL COMMENT 'Reference to variation',
            user_id bigint(20) NOT NULL COMMENT 'User assigned to variation',
            session_id varchar(255) DEFAULT NULL COMMENT 'Session identifier',
            assigned_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            UNIQUE KEY user_test (user_id, test_id),
            KEY test_id (test_id),
            KEY variation_id (variation_id),
            KEY user_id (user_id),
            FOREIGN KEY (test_id) REFERENCES $tests_table(id) ON DELETE CASCADE,
            FOREIGN KEY (variation_id) REFERENCES $variations_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql_tests);
        dbDelta($sql_variations);
        dbDelta($sql_results);
        dbDelta($sql_assignments);
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Admin hooks
        add_action('admin_menu', array($this, 'add_ab_testing_menu'));
        add_action('admin_init', array($this, 'register_ab_testing_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_xelite_create_ab_test', array($this, 'ajax_create_ab_test'));
        add_action('wp_ajax_xelite_get_ab_test_results', array($this, 'ajax_get_ab_test_results'));
        add_action('wp_ajax_xelite_stop_ab_test', array($this, 'ajax_stop_ab_test'));
        add_action('wp_ajax_xelite_get_ab_test_analytics', array($this, 'ajax_get_ab_test_analytics'));
        
        // Content generation hooks
        add_action('xelite_content_generated', array($this, 'track_content_generation'), 10, 3);
        add_action('xelite_engagement_updated', array($this, 'track_engagement_update'), 10, 2);
        
        // Scheduled tasks
        add_action('xelite_ab_test_analysis', array($this, 'run_ab_test_analysis'));
        if (!wp_next_scheduled('xelite_ab_test_analysis')) {
            wp_schedule_event(time(), 'hourly', 'xelite_ab_test_analysis');
        }
    }

    /**
     * Create a new A/B test
     *
     * @param array $test_config Test configuration.
     * @return array|WP_Error Test data or error.
     */
    public function create_ab_test($test_config) {
        global $wpdb;
        
        $required_fields = array('test_name', 'test_type', 'variations');
        foreach ($required_fields as $field) {
            if (empty($test_config[$field])) {
                return new WP_Error('missing_field', "Missing required field: {$field}");
            }
        }
        
        // Validate variations
        if (count($test_config['variations']) < 2) {
            return new WP_Error('invalid_variations', 'At least 2 variations are required');
        }
        
        // Insert test
        $test_data = array(
            'test_name' => sanitize_text_field($test_config['test_name']),
            'test_type' => sanitize_text_field($test_config['test_type']),
            'status' => 'active',
            'traffic_split' => $test_config['traffic_split'] ?? 50.00,
            'confidence_level' => $test_config['confidence_level'] ?? 95.00,
            'min_sample_size' => $test_config['min_sample_size'] ?? 100
        );
        
        $result = $wpdb->insert($this->tables['ab_tests'], $test_data);
        if (!$result) {
            return new WP_Error('db_error', 'Failed to create test');
        }
        
        $test_id = $wpdb->insert_id;
        
        // Insert variations
        foreach ($test_config['variations'] as $index => $variation) {
            $variation_data = array(
                'test_id' => $test_id,
                'variation_name' => $variation['name'] ?? chr(65 + $index), // A, B, C, etc.
                'variation_config' => json_encode($variation['config']),
                'is_control' => $variation['is_control'] ?? ($index === 0)
            );
            
            $wpdb->insert($this->tables['ab_variations'], $variation_data);
        }
        
        $this->log('info', 'Created A/B test', array(
            'test_id' => $test_id,
            'test_name' => $test_config['test_name'],
            'variations_count' => count($test_config['variations'])
        ));
        
        return $this->get_ab_test($test_id);
    }

    /**
     * Get A/B test by ID
     *
     * @param int $test_id Test ID.
     * @return array|false Test data or false.
     */
    public function get_ab_test($test_id) {
        global $wpdb;
        
        $test = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['ab_tests']} WHERE id = %d",
            $test_id
        ), ARRAY_A);
        
        if (!$test) {
            return false;
        }
        
        // Get variations
        $variations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['ab_variations']} WHERE test_id = %d ORDER BY id",
            $test_id
        ), ARRAY_A);
        
        $test['variations'] = $variations;
        
        return $test;
    }

    /**
     * Get variation for a user
     *
     * @param int $test_id Test ID.
     * @param int $user_id User ID.
     * @return array|false Variation data or false.
     */
    public function get_user_variation($test_id, $user_id) {
        global $wpdb;
        
        // Check if user is already assigned
        $assignment = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['ab_assignments']} WHERE test_id = %d AND user_id = %d",
            $test_id, $user_id
        ), ARRAY_A);
        
        if ($assignment) {
            return $this->get_variation($assignment['variation_id']);
        }
        
        // Assign user to a variation
        $variations = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['ab_variations']} WHERE test_id = %d",
            $test_id
        ), ARRAY_A);
        
        if (empty($variations)) {
            return false;
        }
        
        // Simple random assignment (can be enhanced with weighted assignment)
        $variation = $variations[array_rand($variations)];
        
        // Record assignment
        $wpdb->insert($this->tables['ab_assignments'], array(
            'test_id' => $test_id,
            'variation_id' => $variation['id'],
            'user_id' => $user_id,
            'session_id' => session_id()
        ));
        
        // Update sample size
        $wpdb->query($wpdb->prepare(
            "UPDATE {$this->tables['ab_variations']} SET sample_size = sample_size + 1 WHERE id = %d",
            $variation['id']
        ));
        
        return $variation;
    }

    /**
     * Get variation by ID
     *
     * @param int $variation_id Variation ID.
     * @return array|false Variation data or false.
     */
    public function get_variation($variation_id) {
        global $wpdb;
        
        return $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['ab_variations']} WHERE id = %d",
            $variation_id
        ), ARRAY_A);
    }

    /**
     * Track content generation for A/B testing
     *
     * @param array $content_data Generated content data.
     * @param array $prompt_data Prompt data used.
     * @param int $user_id User ID.
     */
    public function track_content_generation($content_data, $prompt_data, $user_id) {
        // Find active tests for this user
        $active_tests = $this->get_active_tests_for_user($user_id);
        
        foreach ($active_tests as $test) {
            $variation = $this->get_user_variation($test['id'], $user_id);
            if (!$variation) {
                continue;
            }
            
            // Record result
            $this->record_ab_test_result($test['id'], $variation['id'], array(
                'user_id' => $user_id,
                'prompt_used' => $prompt_data['prompt'] ?? '',
                'generated_content' => $content_data['content'] ?? '',
                'variation_config' => json_decode($variation['variation_config'], true)
            ));
        }
    }

    /**
     * Track engagement update for A/B testing
     *
     * @param int $content_id Content ID.
     * @param array $engagement_data Engagement data.
     */
    public function track_engagement_update($content_id, $engagement_data) {
        global $wpdb;
        
        // Find A/B test result for this content
        $result = $wpdb->get_row($wpdb->prepare(
            "SELECT * FROM {$this->tables['ab_results']} WHERE content_id = %d",
            $content_id
        ), ARRAY_A);
        
        if (!$result) {
            return;
        }
        
        // Update engagement metrics
        $update_data = array(
            'engagement_score' => $engagement_data['engagement_score'] ?? 0,
            'was_reposted' => $engagement_data['was_reposted'] ?? 0,
            'repost_count' => $engagement_data['repost_count'] ?? 0,
            'like_count' => $engagement_data['like_count'] ?? 0,
            'retweet_count' => $engagement_data['retweet_count'] ?? 0,
            'reply_count' => $engagement_data['reply_count'] ?? 0,
            'view_count' => $engagement_data['view_count'] ?? 0
        );
        
        $wpdb->update(
            $this->tables['ab_results'],
            $update_data,
            array('id' => $result['id'])
        );
        
        // Update variation averages
        $this->update_variation_averages($result['variation_id']);
    }

    /**
     * Record A/B test result
     *
     * @param int $test_id Test ID.
     * @param int $variation_id Variation ID.
     * @param array $result_data Result data.
     * @return bool Success status.
     */
    private function record_ab_test_result($test_id, $variation_id, $result_data) {
        global $wpdb;
        
        $result = $wpdb->insert($this->tables['ab_results'], array(
            'test_id' => $test_id,
            'variation_id' => $variation_id,
            'user_id' => $result_data['user_id'] ?? null,
            'content_id' => $result_data['content_id'] ?? null,
            'prompt_used' => $result_data['prompt_used'],
            'generated_content' => $result_data['generated_content'],
            'created_at' => current_time('mysql')
        ));
        
        return $result !== false;
    }

    /**
     * Update variation averages
     *
     * @param int $variation_id Variation ID.
     */
    private function update_variation_averages($variation_id) {
        global $wpdb;
        
        $averages = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                AVG(engagement_score) as avg_engagement,
                AVG(CASE WHEN was_reposted = 1 THEN 1 ELSE 0 END) as conversion_rate
            FROM {$this->tables['ab_results']} 
            WHERE variation_id = %d",
            $variation_id
        ), ARRAY_A);
        
        if ($averages) {
            $wpdb->update(
                $this->tables['ab_variations'],
                array(
                    'engagement_score' => $averages['avg_engagement'],
                    'conversion_rate' => $averages['conversion_rate']
                ),
                array('id' => $variation_id)
            );
        }
    }

    /**
     * Get active tests for a user
     *
     * @param int $user_id User ID.
     * @return array Active tests.
     */
    private function get_active_tests_for_user($user_id) {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->tables['ab_tests']} WHERE status = 'active'",
            ARRAY_A
        );
    }

    /**
     * Run A/B test analysis
     */
    public function run_ab_test_analysis() {
        $active_tests = $this->get_active_tests();
        
        foreach ($active_tests as $test) {
            $this->analyze_test($test['id']);
        }
    }

    /**
     * Analyze a specific test
     *
     * @param int $test_id Test ID.
     */
    private function analyze_test($test_id) {
        $test = $this->get_ab_test($test_id);
        if (!$test || $test['status'] !== 'active') {
            return;
        }
        
        $variations = $test['variations'];
        $total_samples = array_sum(array_column($variations, 'sample_size'));
        
        // Check if we have enough samples
        if ($total_samples < $test['min_sample_size']) {
            return;
        }
        
        // Perform statistical analysis
        $analysis = $this->perform_statistical_analysis($variations, $test['confidence_level']);
        
        if ($analysis['is_significant']) {
            $this->declare_winner($test_id, $analysis['winner_id']);
        }
    }

    /**
     * Perform statistical analysis
     *
     * @param array $variations Variations data.
     * @param float $confidence_level Confidence level.
     * @return array Analysis results.
     */
    private function perform_statistical_analysis($variations, $confidence_level) {
        // Simple analysis - can be enhanced with more sophisticated statistical tests
        $best_variation = null;
        $best_score = 0;
        
        foreach ($variations as $variation) {
            $score = ($variation['engagement_score'] * 0.7) + ($variation['conversion_rate'] * 0.3);
            if ($score > $best_score) {
                $best_score = $score;
                $best_variation = $variation;
            }
        }
        
        return array(
            'is_significant' => true, // Simplified for now
            'winner_id' => $best_variation['id'],
            'winner_score' => $best_score
        );
    }

    /**
     * Declare a test winner
     *
     * @param int $test_id Test ID.
     * @param int $winner_id Winning variation ID.
     */
    private function declare_winner($test_id, $winner_id) {
        global $wpdb;
        
        // Mark winner
        $wpdb->update(
            $this->tables['ab_variations'],
            array('is_winner' => 1),
            array('id' => $winner_id)
        );
        
        // Mark test as completed
        $wpdb->update(
            $this->tables['ab_tests'],
            array(
                'status' => 'completed',
                'end_date' => current_time('mysql')
            ),
            array('id' => $test_id)
        );
        
        $this->log('info', 'A/B test completed with winner', array(
            'test_id' => $test_id,
            'winner_id' => $winner_id
        ));
    }

    /**
     * Get active tests
     *
     * @return array Active tests.
     */
    public function get_active_tests() {
        global $wpdb;
        
        return $wpdb->get_results(
            "SELECT * FROM {$this->tables['ab_tests']} WHERE status = 'active'",
            ARRAY_A
        );
    }

    /**
     * Get A/B test analytics
     *
     * @param int $test_id Test ID.
     * @return array Analytics data.
     */
    public function get_ab_test_analytics($test_id) {
        $test = $this->get_ab_test($test_id);
        if (!$test) {
            return false;
        }
        
        $analytics = array(
            'test' => $test,
            'variations' => array(),
            'summary' => array()
        );
        
        foreach ($test['variations'] as $variation) {
            $variation_analytics = $this->get_variation_analytics($variation['id']);
            $analytics['variations'][] = array_merge($variation, $variation_analytics);
        }
        
        $analytics['summary'] = $this->calculate_test_summary($test_id);
        
        return $analytics;
    }

    /**
     * Get variation analytics
     *
     * @param int $variation_id Variation ID.
     * @return array Analytics data.
     */
    private function get_variation_analytics($variation_id) {
        global $wpdb;
        
        $results = $wpdb->get_results($wpdb->prepare(
            "SELECT * FROM {$this->tables['ab_results']} WHERE variation_id = %d ORDER BY created_at DESC",
            $variation_id
        ), ARRAY_A);
        
        return array(
            'results_count' => count($results),
            'recent_results' => array_slice($results, 0, 10)
        );
    }

    /**
     * Calculate test summary
     *
     * @param int $test_id Test ID.
     * @return array Summary data.
     */
    private function calculate_test_summary($test_id) {
        global $wpdb;
        
        $summary = $wpdb->get_row($wpdb->prepare(
            "SELECT 
                COUNT(*) as total_results,
                AVG(engagement_score) as avg_engagement,
                AVG(CASE WHEN was_reposted = 1 THEN 1 ELSE 0 END) as overall_conversion_rate
            FROM {$this->tables['ab_results']} 
            WHERE test_id = %d",
            $test_id
        ), ARRAY_A);
        
        return $summary;
    }

    /**
     * Add A/B testing menu
     */
    public function add_ab_testing_menu() {
        add_submenu_page(
            'xelite-repost-engine',
            __('A/B Testing', 'xelite-repost-engine'),
            __('A/B Testing', 'xelite-repost-engine'),
            'manage_options',
            'xelite-ab-testing',
            array($this, 'render_ab_testing_page')
        );
    }

    /**
     * Register A/B testing settings
     */
    public function register_ab_testing_settings() {
        register_setting('xelite_ab_testing', 'xelite_ab_testing_options');
    }

    /**
     * Render A/B testing admin page
     */
    public function render_ab_testing_page() {
        require_once XELITE_REPOST_ENGINE_PLUGIN_DIR . 'admin/partials/ab-testing.php';
    }

    /**
     * AJAX handlers
     */

    /**
     * AJAX handler for creating A/B test
     */
    public function ajax_create_ab_test() {
        check_ajax_referer('xelite_ab_testing_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $test_config = json_decode(stripslashes($_POST['test_config'] ?? '{}'), true);
        
        if (empty($test_config)) {
            wp_send_json_error('Test configuration is required');
        }

        $result = $this->create_ab_test($test_config);
        
        if (is_wp_error($result)) {
            wp_send_json_error($result->get_error_message());
        } else {
            wp_send_json_success($result);
        }
    }

    /**
     * AJAX handler for getting A/B test results
     */
    public function ajax_get_ab_test_results() {
        check_ajax_referer('xelite_ab_testing_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $test_id = intval($_POST['test_id'] ?? 0);
        
        if (!$test_id) {
            wp_send_json_error('Test ID is required');
        }

        $analytics = $this->get_ab_test_analytics($test_id);
        
        if (!$analytics) {
            wp_send_json_error('Test not found');
        }
        
        wp_send_json_success($analytics);
    }

    /**
     * AJAX handler for stopping A/B test
     */
    public function ajax_stop_ab_test() {
        check_ajax_referer('xelite_ab_testing_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $test_id = intval($_POST['test_id'] ?? 0);
        
        if (!$test_id) {
            wp_send_json_error('Test ID is required');
        }

        global $wpdb;
        $result = $wpdb->update(
            $this->tables['ab_tests'],
            array(
                'status' => 'paused',
                'end_date' => current_time('mysql')
            ),
            array('id' => $test_id)
        );
        
        if ($result === false) {
            wp_send_json_error('Failed to stop test');
        }
        
        wp_send_json_success('Test stopped successfully');
    }

    /**
     * AJAX handler for getting A/B test analytics
     */
    public function ajax_get_ab_test_analytics() {
        check_ajax_referer('xelite_ab_testing_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $test_id = intval($_POST['test_id'] ?? 0);
        
        if (!$test_id) {
            wp_send_json_error('Test ID is required');
        }

        $analytics = $this->get_ab_test_analytics($test_id);
        
        if (!$analytics) {
            wp_send_json_error('Test not found');
        }
        
        wp_send_json_success($analytics);
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
            $this->logger->log($level, "[A/B Testing] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine A/B Testing] {$message}");
        }
    }
} 