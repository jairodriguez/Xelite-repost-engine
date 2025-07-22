<?php
/**
 * Few-Shot Learning Data Collection System
 *
 * Collects and stores successful repost examples for use in few-shot learning prompts.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Few-Shot Learning Data Collector Class
 *
 * Handles collection, storage, and management of exemplary reposts for AI training.
 */
class XeliteRepostEngine_Few_Shot_Collector extends XeliteRepostEngine_Abstract_Base {

    /**
     * Database instance
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger|null
     */
    private $logger;

    /**
     * Table names
     *
     * @var array
     */
    private $tables = array();

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Database $database Database instance.
     * @param XeliteRepostEngine_Logger|null $logger Logger instance.
     */
    public function __construct($database, $logger = null) {
        parent::__construct($database);
        $this->database = $database;
        $this->logger = $logger;
        
        $this->init();
    }

    /**
     * Initialize the class
     */
    private function init() {
        global $wpdb;
        
        // Define table names
        $this->tables = array(
            'few_shot_examples' => $wpdb->prefix . 'xelite_few_shot_examples',
            'example_categories' => $wpdb->prefix . 'xelite_example_categories',
            'example_performance' => $wpdb->prefix . 'xelite_example_performance',
        );
        
        $this->init_hooks();
        $this->create_few_shot_tables();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Hook into repost detection to automatically identify high-performing content
        add_action('xelite_repost_detected', array($this, 'evaluate_repost_for_few_shot'), 10, 3);
        add_action('xelite_engagement_updated', array($this, 're_evaluate_example_performance'), 10, 3);
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_few_shot_menu'));
        add_action('admin_init', array($this, 'register_few_shot_settings'));
        
        // AJAX handlers
        add_action('wp_ajax_xelite_get_few_shot_examples', array($this, 'ajax_get_few_shot_examples'));
        add_action('wp_ajax_xelite_add_few_shot_example', array($this, 'ajax_add_few_shot_example'));
        add_action('wp_ajax_xelite_remove_few_shot_example', array($this, 'ajax_remove_few_shot_example'));
        add_action('wp_ajax_xelite_update_example_category', array($this, 'ajax_update_example_category'));
        add_action('wp_ajax_xelite_auto_identify_examples', array($this, 'ajax_auto_identify_examples'));
        
        // Scheduled tasks
        add_action('xelite_evaluate_few_shot_candidates', array($this, 'evaluate_few_shot_candidates'));
        
        // Schedule evaluation task
        if (!wp_next_scheduled('xelite_evaluate_few_shot_candidates')) {
            wp_schedule_event(time(), 'daily', 'xelite_evaluate_few_shot_candidates');
        }
    }

    /**
     * Create few-shot learning database tables
     */
    public function create_few_shot_tables() {
        global $wpdb;
        
        $charset_collate = $wpdb->get_charset_collate();
        
        // Few-shot examples table
        $examples_table = $this->tables['few_shot_examples'];
        $sql_examples = "CREATE TABLE $examples_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            repost_id bigint(20) NOT NULL COMMENT 'Reference to the original repost',
            source_handle varchar(255) NOT NULL COMMENT 'Source handle of the repost',
            original_text text NOT NULL COMMENT 'Original tweet content',
            category_id bigint(20) DEFAULT NULL COMMENT 'Category ID for classification',
            engagement_score decimal(10,4) DEFAULT 0.0000 COMMENT 'Calculated engagement score',
            repost_count int(11) DEFAULT 0 COMMENT 'Number of reposts',
            like_count int(11) DEFAULT 0 COMMENT 'Number of likes',
            retweet_count int(11) DEFAULT 0 COMMENT 'Number of retweets',
            reply_count int(11) DEFAULT 0 COMMENT 'Number of replies',
            view_count int(11) DEFAULT 0 COMMENT 'Number of views',
            content_length int(11) DEFAULT 0 COMMENT 'Length of the content',
            content_type varchar(50) DEFAULT 'text' COMMENT 'Type of content (text, image, video, etc.)',
            sentiment_score decimal(5,4) DEFAULT 0.0000 COMMENT 'Sentiment analysis score',
            hashtags json DEFAULT NULL COMMENT 'JSON array of hashtags used',
            mentions json DEFAULT NULL COMMENT 'JSON array of mentions',
            is_manually_selected tinyint(1) DEFAULT 0 COMMENT 'Whether manually selected as example',
            is_auto_identified tinyint(1) DEFAULT 0 COMMENT 'Whether automatically identified',
            selection_reason text DEFAULT NULL COMMENT 'Reason for selection as example',
            usage_count int(11) DEFAULT 0 COMMENT 'Number of times used in prompts',
            success_rate decimal(5,4) DEFAULT 0.0000 COMMENT 'Success rate when used in prompts',
            is_active tinyint(1) DEFAULT 1 COMMENT 'Whether this example is active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY repost_id (repost_id),
            KEY source_handle (source_handle),
            KEY category_id (category_id),
            KEY engagement_score (engagement_score),
            KEY content_type (content_type),
            KEY is_manually_selected (is_manually_selected),
            KEY is_auto_identified (is_auto_identified),
            KEY is_active (is_active),
            KEY created_at (created_at),
            KEY updated_at (updated_at),
            FOREIGN KEY (repost_id) REFERENCES {$wpdb->prefix}xelite_reposts(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Example categories table
        $categories_table = $this->tables['example_categories'];
        $sql_categories = "CREATE TABLE $categories_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            name varchar(255) NOT NULL COMMENT 'Category name',
            description text DEFAULT NULL COMMENT 'Category description',
            color varchar(7) DEFAULT '#0073aa' COMMENT 'Color for UI display',
            parent_id bigint(20) DEFAULT NULL COMMENT 'Parent category ID for hierarchy',
            example_count int(11) DEFAULT 0 COMMENT 'Number of examples in this category',
            is_active tinyint(1) DEFAULT 1 COMMENT 'Whether this category is active',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY parent_id (parent_id),
            KEY is_active (is_active),
            KEY created_at (created_at),
            FOREIGN KEY (parent_id) REFERENCES $categories_table(id) ON DELETE SET NULL
        ) $charset_collate;";
        
        // Example performance tracking table
        $performance_table = $this->tables['example_performance'];
        $sql_performance = "CREATE TABLE $performance_table (
            id bigint(20) NOT NULL AUTO_INCREMENT,
            example_id bigint(20) NOT NULL COMMENT 'Reference to few-shot example',
            prompt_id varchar(255) DEFAULT NULL COMMENT 'ID of the prompt where used',
            generated_content_id bigint(20) DEFAULT NULL COMMENT 'ID of generated content',
            engagement_metrics json DEFAULT NULL COMMENT 'JSON object with engagement data',
            performance_score decimal(5,4) DEFAULT 0.0000 COMMENT 'Performance score',
            feedback_rating int(11) DEFAULT NULL COMMENT 'User feedback rating (1-5)',
            feedback_notes text DEFAULT NULL COMMENT 'User feedback notes',
            created_at datetime DEFAULT CURRENT_TIMESTAMP,
            PRIMARY KEY (id),
            KEY example_id (example_id),
            KEY prompt_id (prompt_id),
            KEY generated_content_id (generated_content_id),
            KEY performance_score (performance_score),
            KEY created_at (created_at),
            FOREIGN KEY (example_id) REFERENCES $examples_table(id) ON DELETE CASCADE
        ) $charset_collate;";
        
        // Execute table creation
        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        
        dbDelta($sql_examples);
        dbDelta($sql_categories);
        dbDelta($sql_performance);
        
        // Insert default categories
        $this->insert_default_categories();
        
        $this->log_debug('Few-shot learning tables created successfully');
    }

    /**
     * Insert default categories
     */
    private function insert_default_categories() {
        $default_categories = array(
            array(
                'name' => 'High Engagement',
                'description' => 'Posts with exceptional engagement rates',
                'color' => '#00a32a'
            ),
            array(
                'name' => 'Viral Content',
                'description' => 'Content that went viral or had massive reach',
                'color' => '#dba617'
            ),
            array(
                'name' => 'Educational',
                'description' => 'Educational or informative content',
                'color' => '#0073aa'
            ),
            array(
                'name' => 'Entertainment',
                'description' => 'Entertaining or humorous content',
                'color' => '#dc3232'
            ),
            array(
                'name' => 'Inspirational',
                'description' => 'Motivational or inspirational content',
                'color' => '#826eb4'
            ),
            array(
                'name' => 'Business',
                'description' => 'Business-related content',
                'color' => '#f56e28'
            )
        );
        
        foreach ($default_categories as $category) {
            $this->add_category($category);
        }
    }

    /**
     * Evaluate a repost for inclusion in few-shot examples
     *
     * @param int $original_post_id Original post ID.
     * @param int $repost_post_id Repost post ID.
     * @param array $repost_data Repost data.
     */
    public function evaluate_repost_for_few_shot($original_post_id, $repost_post_id, $repost_data) {
        // Get engagement metrics
        $engagement_data = $this->get_engagement_metrics($repost_post_id);
        
        if (empty($engagement_data)) {
            return;
        }
        
        // Calculate engagement score
        $engagement_score = $this->calculate_engagement_score($engagement_data);
        
        // Check if this meets the threshold for automatic inclusion
        $auto_threshold = get_option('xelite_few_shot_auto_threshold', 0.75);
        
        if ($engagement_score >= $auto_threshold) {
            $this->add_few_shot_example($repost_post_id, $repost_data, $engagement_score, 'auto');
        }
    }

    /**
     * Re-evaluate example performance when engagement updates
     *
     * @param int $post_id Post ID.
     * @param array $engagement_data Engagement data.
     * @param string $update_type Update type.
     */
    public function re_evaluate_example_performance($post_id, $engagement_data, $update_type) {
        // Check if this post is already a few-shot example
        $example = $this->get_example_by_post_id($post_id);
        
        if (!$example) {
            return;
        }
        
        // Recalculate engagement score
        $new_engagement_score = $this->calculate_engagement_score($engagement_data);
        
        // Update the example
        $this->update_example_engagement($example['id'], $new_engagement_score, $engagement_data);
        
        // Check if it should be deactivated due to poor performance
        $deactivation_threshold = get_option('xelite_few_shot_deactivation_threshold', 0.3);
        
        if ($new_engagement_score < $deactivation_threshold) {
            $this->deactivate_example($example['id'], 'Poor performance');
        }
    }

    /**
     * Add a few-shot example
     *
     * @param int $repost_id Repost ID.
     * @param array $repost_data Repost data.
     * @param float $engagement_score Engagement score.
     * @param string $selection_method Selection method (manual/auto).
     * @param string $reason Selection reason.
     * @return int|false Example ID on success, false on failure.
     */
    public function add_few_shot_example($repost_id, $repost_data, $engagement_score = 0, $selection_method = 'manual', $reason = '') {
        global $wpdb;
        
        // Check if example already exists
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$this->tables['few_shot_examples']} WHERE repost_id = %d",
                $repost_id
            )
        );
        
        if ($existing) {
            return false;
        }
        
        // Extract content information
        $content_length = strlen($repost_data['original_text']);
        $content_type = $this->determine_content_type($repost_data);
        $hashtags = $this->extract_hashtags($repost_data['original_text']);
        $mentions = $this->extract_mentions($repost_data['original_text']);
        
        // Insert example
        $result = $wpdb->insert(
            $this->tables['few_shot_examples'],
            array(
                'repost_id' => $repost_id,
                'source_handle' => $repost_data['source_handle'],
                'original_text' => $repost_data['original_text'],
                'engagement_score' => $engagement_score,
                'repost_count' => $repost_data['repost_count'] ?? 0,
                'like_count' => $repost_data['engagement_metrics']['likes'] ?? 0,
                'retweet_count' => $repost_data['engagement_metrics']['retweets'] ?? 0,
                'reply_count' => $repost_data['engagement_metrics']['replies'] ?? 0,
                'view_count' => $repost_data['engagement_metrics']['views'] ?? 0,
                'content_length' => $content_length,
                'content_type' => $content_type,
                'hashtags' => json_encode($hashtags),
                'mentions' => json_encode($mentions),
                'is_manually_selected' => ($selection_method === 'manual') ? 1 : 0,
                'is_auto_identified' => ($selection_method === 'auto') ? 1 : 0,
                'selection_reason' => $reason,
                'created_at' => current_time('mysql')
            ),
            array(
                '%d', '%s', '%s', '%f', '%d', '%d', '%d', '%d', '%d', '%d', '%s', '%s', '%s', '%d', '%d', '%s', '%s'
            )
        );
        
        if ($result) {
            $example_id = $wpdb->insert_id;
            $this->log_debug("Added few-shot example ID: $example_id for repost ID: $repost_id");
            return $example_id;
        }
        
        return false;
    }

    /**
     * Get few-shot examples for a specific context
     *
     * @param array $filters Filters for example selection.
     * @param int $limit Number of examples to return.
     * @return array Array of examples.
     */
    public function get_few_shot_examples($filters = array(), $limit = 10) {
        global $wpdb;
        
        $where_conditions = array('is_active = 1');
        $where_values = array();
        
        // Apply filters
        if (!empty($filters['category_id'])) {
            $where_conditions[] = 'category_id = %d';
            $where_values[] = $filters['category_id'];
        }
        
        if (!empty($filters['source_handle'])) {
            $where_conditions[] = 'source_handle = %s';
            $where_values[] = $filters['source_handle'];
        }
        
        if (!empty($filters['content_type'])) {
            $where_conditions[] = 'content_type = %s';
            $where_values[] = $filters['content_type'];
        }
        
        if (!empty($filters['min_engagement'])) {
            $where_conditions[] = 'engagement_score >= %f';
            $where_values[] = $filters['min_engagement'];
        }
        
        if (!empty($filters['max_length'])) {
            $where_conditions[] = 'content_length <= %d';
            $where_values[] = $filters['max_length'];
        }
        
        $where_clause = implode(' AND ', $where_conditions);
        
        $sql = "SELECT e.*, c.name as category_name, c.color as category_color 
                FROM {$this->tables['few_shot_examples']} e
                LEFT JOIN {$this->tables['example_categories']} c ON e.category_id = c.id
                WHERE $where_clause
                ORDER BY e.engagement_score DESC, e.usage_count ASC
                LIMIT %d";
        
        $where_values[] = $limit;
        
        $examples = $wpdb->get_results(
            $wpdb->prepare($sql, $where_values),
            ARRAY_A
        );
        
        // Decode JSON fields
        foreach ($examples as &$example) {
            $example['hashtags'] = json_decode($example['hashtags'], true) ?: array();
            $example['mentions'] = json_decode($example['mentions'], true) ?: array();
        }
        
        return $examples;
    }

    /**
     * Add a new category
     *
     * @param array $category_data Category data.
     * @return int|false Category ID on success, false on failure.
     */
    public function add_category($category_data) {
        global $wpdb;
        
        $result = $wpdb->insert(
            $this->tables['example_categories'],
            array(
                'name' => $category_data['name'],
                'description' => $category_data['description'] ?? '',
                'color' => $category_data['color'] ?? '#0073aa',
                'parent_id' => $category_data['parent_id'] ?? null,
                'created_at' => current_time('mysql')
            ),
            array('%s', '%s', '%s', '%d', '%s')
        );
        
        if ($result) {
            return $wpdb->insert_id;
        }
        
        return false;
    }

    /**
     * Get all categories
     *
     * @param bool $active_only Whether to return only active categories.
     * @return array Array of categories.
     */
    public function get_categories($active_only = true) {
        global $wpdb;
        
        $where_clause = $active_only ? 'WHERE is_active = 1' : '';
        
        $sql = "SELECT * FROM {$this->tables['example_categories']} $where_clause ORDER BY name ASC";
        
        return $wpdb->get_results($sql, ARRAY_A);
    }

    /**
     * Calculate engagement score from engagement data
     *
     * @param array $engagement_data Engagement data.
     * @return float Engagement score.
     */
    private function calculate_engagement_score($engagement_data) {
        $likes = $engagement_data['likes'] ?? 0;
        $retweets = $engagement_data['retweets'] ?? 0;
        $replies = $engagement_data['replies'] ?? 0;
        $views = $engagement_data['views'] ?? 1; // Avoid division by zero
        
        // Weighted engagement calculation
        $weighted_engagement = ($likes * 1) + ($retweets * 2) + ($replies * 3);
        
        // Calculate engagement rate
        $engagement_rate = $weighted_engagement / $views;
        
        // Normalize to 0-1 scale
        $normalized_score = min(1.0, $engagement_rate * 100);
        
        return round($normalized_score, 4);
    }

    /**
     * Determine content type from repost data
     *
     * @param array $repost_data Repost data.
     * @return string Content type.
     */
    private function determine_content_type($repost_data) {
        $text = $repost_data['original_text'] ?? '';
        
        // Check for media indicators
        if (strpos($text, 'http') !== false && preg_match('/\.(jpg|jpeg|png|gif|mp4|mov|avi)$/i', $text)) {
            return 'media';
        }
        
        // Check for video indicators
        if (strpos($text, 'video') !== false || strpos($text, 'watch') !== false) {
            return 'video';
        }
        
        // Check for image indicators
        if (strpos($text, 'image') !== false || strpos($text, 'photo') !== false) {
            return 'image';
        }
        
        return 'text';
    }

    /**
     * Extract hashtags from text
     *
     * @param string $text Text to extract hashtags from.
     * @return array Array of hashtags.
     */
    private function extract_hashtags($text) {
        preg_match_all('/#(\w+)/', $text, $matches);
        return $matches[1] ?? array();
    }

    /**
     * Extract mentions from text
     *
     * @param string $text Text to extract mentions from.
     * @return array Array of mentions.
     */
    private function extract_mentions($text) {
        preg_match_all('/@(\w+)/', $text, $matches);
        return $matches[1] ?? array();
    }

    /**
     * Get engagement metrics for a post
     *
     * @param int $post_id Post ID.
     * @return array|false Engagement data or false if not found.
     */
    private function get_engagement_metrics($post_id) {
        // This would typically come from the analytics collector
        // For now, return a placeholder
        return array(
            'likes' => 0,
            'retweets' => 0,
            'replies' => 0,
            'views' => 0
        );
    }

    /**
     * Update example engagement data
     *
     * @param int $example_id Example ID.
     * @param float $engagement_score Engagement score.
     * @param array $engagement_data Engagement data.
     */
    private function update_example_engagement($example_id, $engagement_score, $engagement_data) {
        global $wpdb;
        
        $wpdb->update(
            $this->tables['few_shot_examples'],
            array(
                'engagement_score' => $engagement_score,
                'like_count' => $engagement_data['likes'] ?? 0,
                'retweet_count' => $engagement_data['retweets'] ?? 0,
                'reply_count' => $engagement_data['replies'] ?? 0,
                'view_count' => $engagement_data['views'] ?? 0,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $example_id),
            array('%f', '%d', '%d', '%d', '%d', '%s'),
            array('%d')
        );
    }

    /**
     * Deactivate an example
     *
     * @param int $example_id Example ID.
     * @param string $reason Deactivation reason.
     */
    private function deactivate_example($example_id, $reason = '') {
        global $wpdb;
        
        $wpdb->update(
            $this->tables['few_shot_examples'],
            array(
                'is_active' => 0,
                'selection_reason' => $reason,
                'updated_at' => current_time('mysql')
            ),
            array('id' => $example_id),
            array('%d', '%s', '%s'),
            array('%d')
        );
    }

    /**
     * Get example by post ID
     *
     * @param int $post_id Post ID.
     * @return array|false Example data or false if not found.
     */
    private function get_example_by_post_id($post_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$this->tables['few_shot_examples']} WHERE repost_id = %d",
                $post_id
            ),
            ARRAY_A
        );
    }

    /**
     * Add admin menu for few-shot learning management
     */
    public function add_few_shot_menu() {
        add_submenu_page(
            'xelite-repost-engine',
            __('Few-Shot Learning', 'xelite-repost-engine'),
            __('Few-Shot Learning', 'xelite-repost-engine'),
            'manage_options',
            'xelite-few-shot-learning',
            array($this, 'render_few_shot_page')
        );
    }

    /**
     * Register few-shot learning settings
     */
    public function register_few_shot_settings() {
        register_setting('xelite_few_shot_settings', 'xelite_few_shot_auto_threshold');
        register_setting('xelite_few_shot_settings', 'xelite_few_shot_deactivation_threshold');
        register_setting('xelite_few_shot_settings', 'xelite_few_shot_max_examples');
    }

    /**
     * Render few-shot learning admin page
     */
    public function render_few_shot_page() {
        // Include the admin template
        include XELITE_REPOST_ENGINE_PLUGIN_PATH . 'admin/partials/few-shot-learning.php';
    }

    /**
     * AJAX handler for getting few-shot examples
     */
    public function ajax_get_few_shot_examples() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_few_shot_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $filters = array();
        $limit = 20;
        
        // Parse filters from request
        if (!empty($_POST['category_id'])) {
            $filters['category_id'] = intval($_POST['category_id']);
        }
        
        if (!empty($_POST['source_handle'])) {
            $filters['source_handle'] = sanitize_text_field($_POST['source_handle']);
        }
        
        if (!empty($_POST['content_type'])) {
            $filters['content_type'] = sanitize_text_field($_POST['content_type']);
        }
        
        if (!empty($_POST['min_engagement'])) {
            $filters['min_engagement'] = floatval($_POST['min_engagement']);
        }
        
        if (!empty($_POST['limit'])) {
            $limit = intval($_POST['limit']);
        }
        
        $examples = $this->get_few_shot_examples($filters, $limit);
        $categories = $this->get_categories();
        
        wp_send_json_success(array(
            'examples' => $examples,
            'categories' => $categories
        ));
    }

    /**
     * AJAX handler for adding a few-shot example
     */
    public function ajax_add_few_shot_example() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_few_shot_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $repost_id = intval($_POST['repost_id']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        $reason = sanitize_textarea_field($_POST['reason'] ?? '');
        
        // Get repost data
        $repost_data = $this->get_repost_data($repost_id);
        
        if (!$repost_data) {
            wp_send_json_error('Repost not found');
        }
        
        // Add the example
        $example_id = $this->add_few_shot_example($repost_id, $repost_data, 0, 'manual', $reason);
        
        if ($example_id) {
            // Update category if provided
            if ($category_id) {
                $this->update_example_category($example_id, $category_id);
            }
            
            wp_send_json_success(array(
                'example_id' => $example_id,
                'message' => 'Example added successfully'
            ));
        } else {
            wp_send_json_error('Failed to add example');
        }
    }

    /**
     * AJAX handler for removing a few-shot example
     */
    public function ajax_remove_few_shot_example() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_few_shot_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $example_id = intval($_POST['example_id']);
        
        $result = $this->remove_few_shot_example($example_id);
        
        if ($result) {
            wp_send_json_success('Example removed successfully');
        } else {
            wp_send_json_error('Failed to remove example');
        }
    }

    /**
     * AJAX handler for updating example category
     */
    public function ajax_update_example_category() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_few_shot_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $example_id = intval($_POST['example_id']);
        $category_id = !empty($_POST['category_id']) ? intval($_POST['category_id']) : null;
        
        $result = $this->update_example_category($example_id, $category_id);
        
        if ($result) {
            wp_send_json_success('Category updated successfully');
        } else {
            wp_send_json_error('Failed to update category');
        }
    }

    /**
     * AJAX handler for auto-identifying examples
     */
    public function ajax_auto_identify_examples() {
        // Verify nonce
        if (!wp_verify_nonce($_POST['nonce'], 'xelite_few_shot_nonce')) {
            wp_die('Security check failed');
        }
        
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_die('Insufficient permissions');
        }
        
        $threshold = floatval($_POST['threshold'] ?? 0.75);
        $limit = intval($_POST['limit'] ?? 50);
        
        $results = $this->auto_identify_examples($threshold, $limit);
        
        wp_send_json_success(array(
            'identified' => $results['identified'],
            'total_processed' => $results['total_processed'],
            'message' => "Identified {$results['identified']} new examples from {$results['total_processed']} reposts"
        ));
    }

    /**
     * Remove a few-shot example
     *
     * @param int $example_id Example ID.
     * @return bool Success status.
     */
    public function remove_few_shot_example($example_id) {
        global $wpdb;
        
        $result = $wpdb->delete(
            $this->tables['few_shot_examples'],
            array('id' => $example_id),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Update example category
     *
     * @param int $example_id Example ID.
     * @param int|null $category_id Category ID.
     * @return bool Success status.
     */
    public function update_example_category($example_id, $category_id) {
        global $wpdb;
        
        $result = $wpdb->update(
            $this->tables['few_shot_examples'],
            array('category_id' => $category_id),
            array('id' => $example_id),
            array('%d'),
            array('%d')
        );
        
        return $result !== false;
    }

    /**
     * Auto-identify examples based on engagement threshold
     *
     * @param float $threshold Engagement threshold.
     * @param int $limit Maximum number of reposts to process.
     * @return array Results array.
     */
    public function auto_identify_examples($threshold = 0.75, $limit = 50) {
        global $wpdb;
        
        // Get high-performing reposts that aren't already examples
        $sql = "SELECT r.*, 
                       (r.like_count + r.retweet_count * 2 + r.reply_count * 3) / GREATEST(r.view_count, 1) as engagement_rate
                FROM {$wpdb->prefix}xelite_reposts r
                LEFT JOIN {$this->tables['few_shot_examples']} e ON r.id = e.repost_id
                WHERE e.id IS NULL
                  AND r.view_count > 0
                ORDER BY engagement_rate DESC
                LIMIT %d";
        
        $reposts = $wpdb->get_results($wpdb->prepare($sql, $limit), ARRAY_A);
        
        $identified = 0;
        $total_processed = count($reposts);
        
        foreach ($reposts as $repost) {
            $engagement_rate = $repost['engagement_rate'];
            
            if ($engagement_rate >= $threshold) {
                $repost_data = array(
                    'source_handle' => $repost['source_handle'],
                    'original_text' => $repost['original_text'],
                    'repost_count' => $repost['repost_count'],
                    'engagement_metrics' => array(
                        'likes' => $repost['like_count'] ?? 0,
                        'retweets' => $repost['retweet_count'] ?? 0,
                        'replies' => $repost['reply_count'] ?? 0,
                        'views' => $repost['view_count'] ?? 0
                    )
                );
                
                $example_id = $this->add_few_shot_example(
                    $repost['id'],
                    $repost_data,
                    $engagement_rate,
                    'auto',
                    'Auto-identified based on high engagement rate'
                );
                
                if ($example_id) {
                    $identified++;
                }
            }
        }
        
        return array(
            'identified' => $identified,
            'total_processed' => $total_processed
        );
    }

    /**
     * Evaluate few-shot candidates (scheduled task)
     */
    public function evaluate_few_shot_candidates() {
        $threshold = get_option('xelite_few_shot_auto_threshold', 0.75);
        $limit = get_option('xelite_few_shot_max_examples', 100);
        
        $results = $this->auto_identify_examples($threshold, $limit);
        
        $this->log_debug("Scheduled evaluation identified {$results['identified']} new examples from {$results['total_processed']} reposts");
    }

    /**
     * Get repost data by ID
     *
     * @param int $repost_id Repost ID.
     * @return array|false Repost data or false if not found.
     */
    private function get_repost_data($repost_id) {
        global $wpdb;
        
        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$wpdb->prefix}xelite_reposts WHERE id = %d",
                $repost_id
            ),
            ARRAY_A
        );
    }

    /**
     * Get few-shot learning statistics
     *
     * @return array Statistics array.
     */
    public function get_few_shot_stats() {
        global $wpdb;
        
        $stats = array();
        
        // Total examples
        $stats['total_examples'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['few_shot_examples']}");
        
        // Active examples
        $stats['active_examples'] = $wpdb->get_var("SELECT COUNT(*) FROM {$this->tables['few_shot_examples']} WHERE is_active = 1");
        
        // Examples by category
        $stats['by_category'] = $wpdb->get_results("
            SELECT c.name, COUNT(e.id) as count
            FROM {$this->tables['example_categories']} c
            LEFT JOIN {$this->tables['few_shot_examples']} e ON c.id = e.category_id AND e.is_active = 1
            WHERE c.is_active = 1
            GROUP BY c.id, c.name
            ORDER BY count DESC
        ", ARRAY_A);
        
        // Examples by source
        $stats['by_source'] = $wpdb->get_results("
            SELECT source_handle, COUNT(*) as count
            FROM {$this->tables['few_shot_examples']}
            WHERE is_active = 1
            GROUP BY source_handle
            ORDER BY count DESC
            LIMIT 10
        ", ARRAY_A);
        
        // Average engagement score
        $stats['avg_engagement'] = $wpdb->get_var("
            SELECT AVG(engagement_score) 
            FROM {$this->tables['few_shot_examples']} 
            WHERE is_active = 1
        ");
        
        return $stats;
    }

    /**
     * Track example usage in prompts
     *
     * @param int $example_id Example ID.
     * @param string $prompt_id Prompt ID.
     * @param int|null $generated_content_id Generated content ID.
     * @return bool Success status.
     */
    public function track_example_usage($example_id, $prompt_id, $generated_content_id = null) {
        global $wpdb;
        
        // Update usage count
        $wpdb->query($wpdb->prepare("
            UPDATE {$this->tables['few_shot_examples']}
            SET usage_count = usage_count + 1
            WHERE id = %d
        ", $example_id));
        
        // Record performance tracking entry
        $result = $wpdb->insert(
            $this->tables['example_performance'],
            array(
                'example_id' => $example_id,
                'prompt_id' => $prompt_id,
                'generated_content_id' => $generated_content_id,
                'created_at' => current_time('mysql')
            ),
            array('%d', '%s', '%d', '%s')
        );
        
        return $result !== false;
    }

    /**
     * Update example performance with feedback
     *
     * @param int $performance_id Performance tracking ID.
     * @param array $engagement_data Engagement data.
     * @param int|null $feedback_rating User feedback rating.
     * @param string|null $feedback_notes User feedback notes.
     * @return bool Success status.
     */
    public function update_example_performance($performance_id, $engagement_data, $feedback_rating = null, $feedback_notes = null) {
        global $wpdb;
        
        $performance_score = $this->calculate_engagement_score($engagement_data);
        
        $result = $wpdb->update(
            $this->tables['example_performance'],
            array(
                'engagement_metrics' => json_encode($engagement_data),
                'performance_score' => $performance_score,
                'feedback_rating' => $feedback_rating,
                'feedback_notes' => $feedback_notes
            ),
            array('id' => $performance_id),
            array('%s', '%f', '%d', '%s'),
            array('%d')
        );
        
        if ($result) {
            // Update example success rate
            $this->update_example_success_rate($performance_id);
        }
        
        return $result !== false;
    }

    /**
     * Update example success rate based on performance
     *
     * @param int $performance_id Performance tracking ID.
     */
    private function update_example_success_rate($performance_id) {
        global $wpdb;
        
        // Get the example ID
        $example_id = $wpdb->get_var($wpdb->prepare("
            SELECT example_id FROM {$this->tables['example_performance']} WHERE id = %d
        ", $performance_id));
        
        if (!$example_id) {
            return;
        }
        
        // Calculate average performance score for this example
        $avg_score = $wpdb->get_var($wpdb->prepare("
            SELECT AVG(performance_score) 
            FROM {$this->tables['example_performance']} 
            WHERE example_id = %d AND performance_score > 0
        ", $example_id));
        
        // Update the example's success rate
        $wpdb->update(
            $this->tables['few_shot_examples'],
            array('success_rate' => $avg_score),
            array('id' => $example_id),
            array('%f'),
            array('%d')
        );
    }
} 