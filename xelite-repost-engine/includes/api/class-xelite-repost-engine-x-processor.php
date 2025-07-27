<?php
/**
 * X (Twitter) API Data Processor
 *
 * Handles storage and processing of fetched X (Twitter) data in the WordPress database.
 * Includes data analysis, scheduled fetching, and admin interfaces.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * X API Data Processor Class
 */
class XeliteRepostEngine_X_Processor {

    /**
     * Database instance
     *
     * @var XeliteRepostEngine_Database
     */
    private $database;

    /**
     * X API instance
     *
     * @var XeliteRepostEngine_X_API
     */
    private $x_api;

    /**
     * Logger instance
     *
     * @var XeliteRepostEngine_Logger
     */
    private $logger;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Database $database Database service.
     * @param XeliteRepostEngine_X_API    $x_api    X API service.
     * @param XeliteRepostEngine_Logger   $logger   Logger service.
     */
    public function __construct($database, $x_api, $logger = null) {
        $this->database = $database;
        $this->x_api = $x_api;
        $this->logger = $logger;
        
        // Initialize hooks
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        // Schedule cron events
        add_action('init', array($this, 'schedule_cron_events'));
        
        // Cron event handlers
        add_action('xelite_repost_engine_fetch_posts', array($this, 'fetch_and_store_posts'));
        add_action('xelite_repost_engine_analyze_data', array($this, 'analyze_stored_data'));
        
        // Admin hooks
        add_action('admin_menu', array($this, 'add_admin_menu'));
        add_action('wp_ajax_xelite_export_repost_data', array($this, 'ajax_export_data'));
        add_action('wp_ajax_xelite_clear_repost_data', array($this, 'ajax_clear_data'));
        
        // Deactivation cleanup
        register_deactivation_hook(XELITE_REPOST_ENGINE_PLUGIN_BASENAME, array($this, 'clear_cron_events'));
    }

    /**
     * Schedule cron events
     */
    public function schedule_cron_events() {
        if (!wp_next_scheduled('xelite_repost_engine_fetch_posts')) {
            wp_schedule_event(time(), 'hourly', 'xelite_repost_engine_fetch_posts');
        }
        
        if (!wp_next_scheduled('xelite_repost_engine_analyze_data')) {
            wp_schedule_event(time(), 'daily', 'xelite_repost_engine_analyze_data');
        }
    }

    /**
     * Clear cron events on deactivation
     */
    public function clear_cron_events() {
        wp_clear_scheduled_hook('xelite_repost_engine_fetch_posts');
        wp_clear_scheduled_hook('xelite_repost_engine_analyze_data');
    }

    /**
     * Fetch and store posts from X API
     *
     * @param array $target_accounts Array of target account handles.
     * @return array|WP_Error Results or error.
     */
    public function fetch_and_store_posts($target_accounts = null) {
        if (!$target_accounts) {
            $target_accounts = $this->get_target_accounts();
        }

        if (empty($target_accounts)) {
            return new WP_Error('no_targets', 'No target accounts configured');
        }

        $results = array(
            'processed' => 0,
            'stored' => 0,
            'errors' => 0,
            'accounts' => array(),
        );

        foreach ($target_accounts as $account) {
            $account_result = $this->process_account_posts($account);
            $results['accounts'][$account] = $account_result;
            $results['processed'] += $account_result['processed'];
            $results['stored'] += $account_result['stored'];
            $results['errors'] += $account_result['errors'];
        }

        $this->log('info', "Fetch and store completed: " . json_encode($results));
        return $results;
    }

    /**
     * Process posts for a specific account
     *
     * @param string $account_handle Account handle.
     * @return array Processing results.
     */
    private function process_account_posts($account_handle) {
        $result = array(
            'processed' => 0,
            'stored' => 0,
            'errors' => 0,
        );

        try {
            // Get user info first
            $user_info = $this->x_api->get_user_info($account_handle, true);
            if (is_wp_error($user_info)) {
                $result['errors']++;
                $this->log('error', "Failed to get user info for {$account_handle}: " . $user_info->get_error_message());
                return $result;
            }

            // Get user timeline
            $timeline = $this->x_api->get_user_timeline($user_info['id'], 100);
            if (is_wp_error($timeline)) {
                $result['errors']++;
                $this->log('error', "Failed to get timeline for {$account_handle}: " . $timeline->get_error_message());
                return $result;
            }

            $result['processed'] = count($timeline['tweets']);

            // Process each tweet
            foreach ($timeline['tweets'] as $tweet) {
                $stored = $this->store_tweet_data($tweet, $user_info);
                if ($stored) {
                    $result['stored']++;
                } else {
                    $result['errors']++;
                }
            }

        } catch (Exception $e) {
            $result['errors']++;
            $this->log('error', "Exception processing account {$account_handle}: " . $e->getMessage());
        }

        return $result;
    }

    /**
     * Store repost data in database
     *
     * @param array $repost_data Repost data from scraper.
     * @return bool|WP_Error Success status or error.
     */
    public function store_repost_data($repost_data) {
        try {
            // Validate and sanitize data
            $validated_data = $this->validate_repost_data($repost_data);
            if (is_wp_error($validated_data)) {
                return $validated_data;
            }

            // Check for duplicates
            $existing = $this->database->get_repost_by_tweet($repost_data['original_tweet_id'], $repost_data['source_handle']);
            if ($existing) {
                // Update existing record
                return $this->update_repost_data($repost_data, $existing['id']);
            }

            // Prepare data for storage
            $data = $this->prepare_repost_data_for_storage($repost_data);
            
            // Insert into database
            $repost_id = $this->database->insert_repost($data);
            
            if ($repost_id) {
                $this->log('info', "Stored repost data: {$repost_id} for tweet {$repost_data['original_tweet_id']}");
                return true;
            } else {
                $this->log('error', "Failed to store repost data for tweet {$repost_data['original_tweet_id']}");
                return new WP_Error('storage_failed', 'Failed to store repost data');
            }

        } catch (Exception $e) {
            $this->log('error', "Exception storing repost data: " . $e->getMessage());
            return new WP_Error('storage_exception', $e->getMessage());
        }
    }

    /**
     * Validate repost data
     *
     * @param array $repost_data Repost data to validate.
     * @return array|WP_Error Validated data or error.
     */
    private function validate_repost_data($repost_data) {
        $required_fields = array('original_tweet_id', 'original_text', 'source_handle');
        
        foreach ($required_fields as $field) {
            if (empty($repost_data[$field])) {
                return new WP_Error('missing_field', "Missing required field: {$field}");
            }
        }

        // Validate tweet ID format
        if (!preg_match('/^\d+$/', $repost_data['original_tweet_id'])) {
            return new WP_Error('invalid_tweet_id', 'Invalid tweet ID format');
        }

        // Validate source handle
        if (!preg_match('/^[a-zA-Z0-9_]{1,15}$/', $repost_data['source_handle'])) {
            return new WP_Error('invalid_handle', 'Invalid source handle format');
        }

        return $repost_data;
    }

    /**
     * Prepare repost data for storage
     *
     * @param array $repost_data Raw repost data.
     * @return array Prepared data for database storage.
     */
    private function prepare_repost_data_for_storage($repost_data) {
        $data = array(
            'source_handle' => sanitize_text_field($repost_data['source_handle']),
            'original_tweet_id' => sanitize_text_field($repost_data['original_tweet_id']),
            'original_text' => sanitize_textarea_field($repost_data['original_text']),
            'platform' => 'x',
            'repost_date' => current_time('mysql'),
            'engagement_metrics' => json_encode($repost_data['engagement_metrics'] ?? array()),
            'content_variations' => json_encode(array()),
            'post_id' => null,
            'original_post_id' => null,
            'user_id' => null,
            'repost_count' => 0,
            'is_analyzed' => 0,
            'analysis_data' => json_encode(array()),
            'created_at' => current_time('mysql'),
            'updated_at' => current_time('mysql')
        );

        // Add additional metadata if available
        if (isset($repost_data['created_at'])) {
            $data['repost_date'] = $this->format_date_for_mysql($repost_data['created_at']);
        }

        if (isset($repost_data['entities'])) {
            $data['analysis_data'] = json_encode(array(
                'entities' => $repost_data['entities'],
                'context_annotations' => $repost_data['context_annotations'] ?? array()
            ));
        }

        if (isset($repost_data['referenced_tweet_id'])) {
            $data['analysis_data'] = json_encode(array_merge(
                json_decode($data['analysis_data'], true) ?: array(),
                array('referenced_tweet_id' => $repost_data['referenced_tweet_id'])
            ));
        }

        return $data;
    }

    /**
     * Update existing repost data
     *
     * @param array $repost_data New repost data.
     * @param int $repost_id Existing repost ID.
     * @return bool Success status.
     */
    private function update_repost_data($repost_data, $repost_id) {
        try {
            $update_data = array(
                'engagement_metrics' => json_encode($repost_data['engagement_metrics'] ?? array()),
                'updated_at' => current_time('mysql')
            );

            // Update analysis data if new entities are available
            if (isset($repost_data['entities']) || isset($repost_data['context_annotations'])) {
                $existing = $this->database->get_row('reposts', array('id' => $repost_id));
                $existing_analysis = json_decode($existing['analysis_data'], true) ?: array();
                
                $new_analysis = array_merge($existing_analysis, array(
                    'entities' => $repost_data['entities'] ?? array(),
                    'context_annotations' => $repost_data['context_annotations'] ?? array()
                ));
                
                $update_data['analysis_data'] = json_encode($new_analysis);
            }

            $result = $this->database->update_repost($update_data, array('id' => $repost_id));
            
            if ($result) {
                $this->log('info', "Updated repost data: {$repost_id}");
                return true;
            } else {
                $this->log('error', "Failed to update repost data: {$repost_id}");
                return false;
            }

        } catch (Exception $e) {
            $this->log('error', "Exception updating repost data: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Format date for MySQL storage
     *
     * @param string $date_string Date string from API.
     * @return string Formatted date for MySQL.
     */
    private function format_date_for_mysql($date_string) {
        try {
            $date = new DateTime($date_string);
            return $date->format('Y-m-d H:i:s');
        } catch (Exception $e) {
            return current_time('mysql');
        }
    }

    /**
     * Store multiple reposts in batch
     *
     * @param array $reposts_array Array of repost data arrays.
     * @return array Results with success/error counts.
     */
    public function store_reposts_batch($reposts_array) {
        if (empty($reposts_array)) {
            return array('success' => 0, 'errors' => 0, 'results' => array());
        }

        $results = array(
            'success' => 0,
            'errors' => 0,
            'results' => array()
        );

        // Start transaction for batch operation
        $this->database->start_transaction();

        try {
            foreach ($reposts_array as $index => $repost_data) {
                $result = $this->store_repost_data($repost_data);
                
                if (is_wp_error($result)) {
                    $results['errors']++;
                    $results['results'][$index] = array(
                        'success' => false,
                        'error' => $result->get_error_message(),
                        'tweet_id' => $repost_data['original_tweet_id'] ?? 'unknown'
                    );
                } else {
                    $results['success']++;
                    $results['results'][$index] = array(
                        'success' => true,
                        'tweet_id' => $repost_data['original_tweet_id'] ?? 'unknown'
                    );
                }
            }

            // Commit transaction
            $this->database->commit_transaction();
            
            $this->log('info', "Batch storage completed: {$results['success']} successful, {$results['errors']} errors");

        } catch (Exception $e) {
            // Rollback transaction on error
            $this->database->rollback_transaction();
            
            $this->log('error', "Batch storage failed: " . $e->getMessage());
            
            $results['errors'] = count($reposts_array);
            $results['success'] = 0;
            $results['batch_error'] = $e->getMessage();
        }

        return $results;
    }

    /**
     * Check for duplicate reposts
     *
     * @param array $reposts_array Array of repost data arrays.
     * @return array Array with duplicates filtered out.
     */
    public function filter_duplicates($reposts_array) {
        if (empty($reposts_array)) {
            return array();
        }

        $filtered_reposts = array();
        $duplicate_count = 0;

        foreach ($reposts_array as $repost_data) {
            $tweet_id = $repost_data['original_tweet_id'] ?? '';
            $source_handle = $repost_data['source_handle'] ?? '';
            
            if (empty($tweet_id) || empty($source_handle)) {
                continue;
            }

            // Check if repost already exists
            $existing = $this->database->get_repost_by_tweet($tweet_id, $source_handle);
            
            if (!$existing) {
                $filtered_reposts[] = $repost_data;
            } else {
                $duplicate_count++;
            }
        }

        if ($duplicate_count > 0) {
            $this->log('info', "Filtered out {$duplicate_count} duplicate reposts");
        }

        return $filtered_reposts;
    }



    /**
     * Clean up old repost data
     *
     * @param int $days_old Number of days old to consider for cleanup.
     * @return int Number of records cleaned up.
     */
    public function cleanup_old_reposts($days_old = 365) {
        try {
            $cutoff_date = date('Y-m-d H:i:s', strtotime("-{$days_old} days"));
            
            $deleted_count = $this->database->delete('reposts', array(
                'created_at <' => $cutoff_date
            ));

            if ($deleted_count > 0) {
                $this->log('info', "Cleaned up {$deleted_count} old repost records (older than {$days_old} days)");
            }

            return $deleted_count;

        } catch (Exception $e) {
            $this->log('error', "Error cleaning up old reposts: " . $e->getMessage());
            return 0;
        }
    }

    /**
     * Export repost data
     *
     * @param array $filters Filters to apply.
     * @param string $format Export format (csv, json).
     * @return string|WP_Error Exported data or error.
     */
    public function export_repost_data($filters = array(), $format = 'csv') {
        try {
            $reposts = $this->database->get_reposts($filters, array('created_at' => 'DESC'));

            if (empty($reposts)) {
                return new WP_Error('no_data', 'No repost data found for export');
            }

            switch ($format) {
                case 'json':
                    return $this->export_json($reposts);
                case 'csv':
                default:
                    return $this->export_csv($reposts);
            }

        } catch (Exception $e) {
            $this->log('error', "Error exporting repost data: " . $e->getMessage());
            return new WP_Error('export_error', $e->getMessage());
        }
    }

    /**
     * Store tweet data in database
     *
     * @param array $tweet Tweet data.
     * @param array $user_info User information.
     * @return bool Success status.
     */
    private function store_tweet_data($tweet, $user_info) {
        try {
            // Check if tweet already exists
            $existing = $this->database->get_repost_by_tweet_id($tweet['id']);
            if ($existing) {
                // Update existing record
                return $this->update_tweet_data($tweet, $user_info, $existing['id']);
            }

            // Prepare data for storage
            $repost_data = array(
                'source_handle' => $user_info['username'],
                'source_user_id' => $user_info['id'],
                'original_tweet_id' => $tweet['id'],
                'original_text' => $tweet['text'],
                'created_at' => $tweet['created_at'],
                'engagement_metrics' => json_encode($tweet['public_metrics']),
                'entities' => json_encode($tweet['entities']),
                'context_annotations' => json_encode($tweet['context_annotations']),
                'conversation_id' => $tweet['conversation_id'],
                'referenced_tweets' => json_encode($tweet['referenced_tweets']),
                'analysis_data' => json_encode($this->analyze_tweet_content($tweet)),
                'processed_at' => current_time('mysql'),
            );

            // Insert into database
            $inserted = $this->database->insert_repost($repost_data);
            
            if ($inserted) {
                $this->log('debug', "Stored tweet {$tweet['id']} for user {$user_info['username']}");
                return true;
            } else {
                $this->log('error', "Failed to store tweet {$tweet['id']}");
                return false;
            }

        } catch (Exception $e) {
            $this->log('error', "Exception storing tweet {$tweet['id']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Update existing tweet data
     *
     * @param array $tweet Tweet data.
     * @param array $user_info User information.
     * @param int   $repost_id Repost record ID.
     * @return bool Success status.
     */
    private function update_tweet_data($tweet, $user_info, $repost_id) {
        try {
            $update_data = array(
                'engagement_metrics' => json_encode($tweet['public_metrics']),
                'analysis_data' => json_encode($this->analyze_tweet_content($tweet)),
                'updated_at' => current_time('mysql'),
            );

            $updated = $this->database->update_repost($repost_id, $update_data);
            
            if ($updated) {
                $this->log('debug', "Updated tweet {$tweet['id']}");
                return true;
            } else {
                $this->log('error', "Failed to update tweet {$tweet['id']}");
                return false;
            }

        } catch (Exception $e) {
            $this->log('error', "Exception updating tweet {$tweet['id']}: " . $e->getMessage());
            return false;
        }
    }

    /**
     * Analyze tweet content for patterns and insights
     *
     * @param array $tweet Tweet data.
     * @return array Analysis results.
     */
    private function analyze_tweet_content($tweet) {
        $analysis = array(
            'hashtags' => array(),
            'mentions' => array(),
            'urls' => array(),
            'media' => array(),
            'length' => strlen($tweet['text']),
            'word_count' => str_word_count($tweet['text']),
            'sentiment_score' => 0,
            'engagement_rate' => 0,
            'content_type' => 'text',
            'has_question' => false,
            'has_call_to_action' => false,
        );

        // Extract hashtags
        if (isset($tweet['entities']['hashtags'])) {
            foreach ($tweet['entities']['hashtags'] as $hashtag) {
                $analysis['hashtags'][] = $hashtag['tag'];
            }
        }

        // Extract mentions
        if (isset($tweet['entities']['mentions'])) {
            foreach ($tweet['entities']['mentions'] as $mention) {
                $analysis['mentions'][] = $mention['username'];
            }
        }

        // Extract URLs
        if (isset($tweet['entities']['urls'])) {
            foreach ($tweet['entities']['urls'] as $url) {
                $analysis['urls'][] = $url['url'];
            }
        }

        // Check for media
        if (isset($tweet['entities']['media'])) {
            foreach ($tweet['entities']['media'] as $media) {
                $analysis['media'][] = array(
                    'type' => $media['type'],
                    'url' => $media['url'],
                );
            }
            $analysis['content_type'] = 'media';
        }

        // Basic sentiment analysis
        $analysis['sentiment_score'] = $this->calculate_sentiment_score($tweet['text']);

        // Calculate engagement rate
        if (isset($tweet['public_metrics'])) {
            $metrics = $tweet['public_metrics'];
            $total_engagement = $metrics['retweet_count'] + $metrics['like_count'] + $metrics['reply_count'] + $metrics['quote_count'];
            $analysis['engagement_rate'] = $total_engagement;
        }

        // Check for questions
        $analysis['has_question'] = $this->contains_question($tweet['text']);

        // Check for call to action
        $analysis['has_call_to_action'] = $this->contains_call_to_action($tweet['text']);

        return $analysis;
    }

    /**
     * Calculate basic sentiment score
     *
     * @param string $text Text to analyze.
     * @return int Sentiment score (-100 to 100).
     */
    private function calculate_sentiment_score($text) {
        $positive_words = array('great', 'awesome', 'amazing', 'love', 'excellent', 'fantastic', 'wonderful', 'perfect', 'best', 'good');
        $negative_words = array('bad', 'terrible', 'awful', 'hate', 'worst', 'horrible', 'disappointing', 'frustrated', 'angry', 'sad');

        $text_lower = strtolower($text);
        $words = str_word_count($text_lower, 1);

        $positive_count = 0;
        $negative_count = 0;

        foreach ($words as $word) {
            if (in_array($word, $positive_words)) {
                $positive_count++;
            }
            if (in_array($word, $negative_words)) {
                $negative_count++;
            }
        }

        $total_words = count($words);
        if ($total_words === 0) {
            return 0;
        }

        $score = (($positive_count - $negative_count) / $total_words) * 100;
        return max(-100, min(100, $score));
    }

    /**
     * Check if text contains a question
     *
     * @param string $text Text to check.
     * @return bool True if contains question.
     */
    private function contains_question($text) {
        $question_patterns = array(
            '/\?/',
            '/^(what|who|where|when|why|how|which|whose|whom)\b/i',
            '/\b(do|does|did|is|are|was|were|can|could|will|would|should|may|might)\s+\w+\s*\?/i',
        );

        foreach ($question_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if text contains call to action
     *
     * @param string $text Text to check.
     * @return bool True if contains CTA.
     */
    private function contains_call_to_action($text) {
        $cta_patterns = array(
            '/\b(check|visit|read|watch|listen|download|sign|join|follow|like|share|retweet|comment|reply)\b/i',
            '/\b(click|tap|swipe|scroll|subscribe|buy|purchase|order|book|schedule|register|enroll)\b/i',
            '/\b(learn|discover|explore|find|get|grab|claim|try|test|demo|sample|free|offer|deal)\b/i',
        );

        foreach ($cta_patterns as $pattern) {
            if (preg_match($pattern, $text)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Analyze stored data for patterns and insights
     */
    public function analyze_stored_data() {
        $this->log('info', 'Starting stored data analysis');

        // Get all reposts
        $reposts = $this->database->get_all_reposts();
        
        if (empty($reposts)) {
            $this->log('info', 'No reposts found for analysis');
            return;
        }

        $analysis = array(
            'total_posts' => count($reposts),
            'total_engagement' => 0,
            'avg_engagement' => 0,
            'top_hashtags' => array(),
            'top_mentions' => array(),
            'content_types' => array(),
            'sentiment_distribution' => array(),
            'best_performing_posts' => array(),
            'engagement_trends' => array(),
        );

        $hashtag_counts = array();
        $mention_counts = array();
        $content_type_counts = array();
        $sentiment_scores = array();
        $engagement_scores = array();

        foreach ($reposts as $repost) {
            $analysis_data = json_decode($repost['analysis_data'], true);
            $engagement_metrics = json_decode($repost['engagement_metrics'], true);

            // Aggregate engagement
            $total_engagement = $engagement_metrics['retweet_count'] + $engagement_metrics['like_count'] + 
                               $engagement_metrics['reply_count'] + $engagement_metrics['quote_count'];
            $analysis['total_engagement'] += $total_engagement;
            $engagement_scores[] = $total_engagement;

            // Count hashtags
            if (isset($analysis_data['hashtags'])) {
                foreach ($analysis_data['hashtags'] as $hashtag) {
                    $hashtag_counts[$hashtag] = isset($hashtag_counts[$hashtag]) ? $hashtag_counts[$hashtag] + 1 : 1;
                }
            }

            // Count mentions
            if (isset($analysis_data['mentions'])) {
                foreach ($analysis_data['mentions'] as $mention) {
                    $mention_counts[$mention] = isset($mention_counts[$mention]) ? $mention_counts[$mention] + 1 : 1;
                }
            }

            // Count content types
            $content_type = $analysis_data['content_type'] ?? 'text';
            $content_type_counts[$content_type] = isset($content_type_counts[$content_type]) ? $content_type_counts[$content_type] + 1 : 1;

            // Collect sentiment scores
            $sentiment_scores[] = $analysis_data['sentiment_score'] ?? 0;
        }

        // Calculate averages
        $analysis['avg_engagement'] = $analysis['total_engagement'] / $analysis['total_posts'];

        // Get top hashtags
        arsort($hashtag_counts);
        $analysis['top_hashtags'] = array_slice($hashtag_counts, 0, 10, true);

        // Get top mentions
        arsort($mention_counts);
        $analysis['top_mentions'] = array_slice($mention_counts, 0, 10, true);

        // Content type distribution
        $analysis['content_types'] = $content_type_counts;

        // Sentiment distribution
        $analysis['sentiment_distribution'] = array(
            'positive' => count(array_filter($sentiment_scores, function($score) { return $score > 20; })),
            'neutral' => count(array_filter($sentiment_scores, function($score) { return $score >= -20 && $score <= 20; })),
            'negative' => count(array_filter($sentiment_scores, function($score) { return $score < -20; })),
        );

        // Get best performing posts
        arsort($engagement_scores);
        $top_engagement_scores = array_slice($engagement_scores, 0, 10, true);
        $analysis['best_performing_posts'] = array_keys($top_engagement_scores);

        // Store analysis results
        update_option('xelite_repost_analysis', $analysis);
        
        $this->log('info', 'Data analysis completed: ' . json_encode($analysis));
    }

    /**
     * Get target accounts from settings
     *
     * @return array Array of account handles.
     */
    private function get_target_accounts() {
        $accounts = get_option('xelite_repost_engine_target_accounts', array());
        return array_filter($accounts, function($account) {
            return !empty($account['handle']) && !empty($account['active']);
        });
    }

    /**
     * Add admin menu
     */
    public function add_admin_menu() {
        add_submenu_page(
            'xelite-repost-engine',
            'X Data Management',
            'X Data',
            'manage_options',
            'xelite-repost-engine-x-data',
            array($this, 'render_admin_page')
        );
    }

    /**
     * Render admin page
     */
    public function render_admin_page() {
        $reposts = $this->database->get_all_reposts();
        $analysis = get_option('xelite_repost_analysis', array());
        
        include plugin_dir_path(XELITE_REPOST_ENGINE_PLUGIN_BASENAME) . 'admin/partials/x-data-management.php';
    }

    /**
     * AJAX handler for data export
     */
    public function ajax_export_data() {
        check_ajax_referer('xelite_export_data', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $format = sanitize_text_field($_POST['format'] ?? 'csv');
        $reposts = $this->database->get_all_reposts();

        if ($format === 'json') {
            $this->export_json($reposts);
        } else {
            $this->export_csv($reposts);
        }
    }

    /**
     * Export data as CSV
     *
     * @param array $reposts Repost data.
     */
    private function export_csv($reposts) {
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="repost-data-' . date('Y-m-d') . '.csv"');

        $output = fopen('php://output', 'w');

        // CSV headers
        fputcsv($output, array(
            'ID', 'Source Handle', 'Tweet ID', 'Text', 'Created At', 'Retweets', 'Likes', 'Replies', 'Quotes',
            'Hashtags', 'Mentions', 'Content Type', 'Sentiment Score', 'Has Question', 'Has CTA'
        ));

        foreach ($reposts as $repost) {
            $analysis_data = json_decode($repost['analysis_data'], true);
            $engagement_metrics = json_decode($repost['engagement_metrics'], true);

            fputcsv($output, array(
                $repost['id'],
                $repost['source_handle'],
                $repost['original_tweet_id'],
                $repost['original_text'],
                $repost['created_at'],
                $engagement_metrics['retweet_count'] ?? 0,
                $engagement_metrics['like_count'] ?? 0,
                $engagement_metrics['reply_count'] ?? 0,
                $engagement_metrics['quote_count'] ?? 0,
                implode(', ', $analysis_data['hashtags'] ?? array()),
                implode(', ', $analysis_data['mentions'] ?? array()),
                $analysis_data['content_type'] ?? 'text',
                $analysis_data['sentiment_score'] ?? 0,
                $analysis_data['has_question'] ? 'Yes' : 'No',
                $analysis_data['has_call_to_action'] ? 'Yes' : 'No',
            ));
        }

        fclose($output);
        exit;
    }

    /**
     * Export data as JSON
     *
     * @param array $reposts Repost data.
     */
    private function export_json($reposts) {
        header('Content-Type: application/json');
        header('Content-Disposition: attachment; filename="repost-data-' . date('Y-m-d') . '.json"');

        $export_data = array(
            'export_date' => current_time('mysql'),
            'total_records' => count($reposts),
            'data' => $reposts,
        );

        echo json_encode($export_data, JSON_PRETTY_PRINT);
        exit;
    }

    /**
     * AJAX handler for clearing data
     */
    public function ajax_clear_data() {
        check_ajax_referer('xelite_clear_data', 'nonce');

        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        $cleared = $this->database->clear_all_reposts();
        
        if ($cleared) {
            delete_option('xelite_repost_analysis');
            wp_send_json_success('All repost data cleared successfully');
        } else {
            wp_send_json_error('Failed to clear repost data');
        }
    }

    /**
     * Get repost statistics
     *
     * @return array Statistics.
     */
    public function get_repost_statistics() {
        $total_reposts = $this->database->get_repost_count();
        $total_engagement = $this->database->get_total_engagement();
        $avg_engagement = $total_reposts > 0 ? $total_engagement / $total_reposts : 0;

        return array(
            'total_reposts' => $total_reposts,
            'total_engagement' => $total_engagement,
            'avg_engagement' => round($avg_engagement, 2),
            'last_updated' => $this->database->get_last_updated(),
        );
    }

    /**
     * Log message
     *
     * @param string $level Log level.
     * @param string $message Log message.
     */
    private function log($level, $message) {
        if ($this->logger) {
            $this->logger->log($level, $message);
        } else {
            error_log("XeliteRepostEngine X Processor [{$level}]: {$message}");
        }
    }
} 