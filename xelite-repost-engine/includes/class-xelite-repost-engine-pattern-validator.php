<?php
/**
 * Pattern Validator for Repost Intelligence
 *
 * Tests identified patterns against new content and validates their effectiveness.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pattern Validator Class
 */
class XeliteRepostEngine_Pattern_Validator extends XeliteRepostEngine_Abstract_Base {

    /**
     * Pattern analyzer instance
     *
     * @var XeliteRepostEngine_Pattern_Analyzer
     */
    private $pattern_analyzer;

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
     * Cache expiration time (in seconds)
     *
     * @var int
     */
    private $cache_expiration;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Pattern_Analyzer $pattern_analyzer Pattern analyzer service.
     * @param XeliteRepostEngine_Database $database Database service.
     * @param XeliteRepostEngine_Logger $logger Logger service.
     */
    public function __construct($pattern_analyzer, $database, $logger = null) {
        $this->pattern_analyzer = $pattern_analyzer;
        $this->database = $database;
        $this->logger = $logger;
        $this->cache_expiration = 3600; // 1 hour default
        
        $this->init_hooks();
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_xelite_validate_pattern', array($this, 'ajax_validate_pattern'));
        add_action('wp_ajax_xelite_ab_test_pattern', array($this, 'ajax_ab_test_pattern'));
        add_action('wp_ajax_xelite_get_pattern_confidence', array($this, 'ajax_get_pattern_confidence'));
        add_action('wp_ajax_xelite_track_pattern_performance', array($this, 'ajax_track_pattern_performance'));
        add_action('wp_ajax_xelite_detect_pattern_decay', array($this, 'ajax_detect_pattern_decay'));
    }

    /**
     * Apply identified patterns to new content
     *
     * @param string $content Original content.
     * @param array $patterns Patterns to apply.
     * @param string $source_handle Source handle for pattern context.
     * @return array Modified content and applied patterns.
     */
    public function apply_patterns_to_content($content, $patterns, $source_handle = null) {
        $original_content = $content;
        $applied_patterns = array();
        $modifications = array();

        // Get pattern analysis for context
        $pattern_analysis = $this->pattern_analyzer->analyze_patterns($source_handle, 1000);
        
        if (empty($pattern_analysis)) {
            return array(
                'content' => $content,
                'applied_patterns' => array(),
                'modifications' => array(),
                'confidence' => 0
            );
        }

        // Apply length pattern
        if (isset($patterns['length']) && $patterns['length']) {
            $length_result = $this->apply_length_pattern($content, $pattern_analysis);
            if ($length_result['modified']) {
                $content = $length_result['content'];
                $applied_patterns['length'] = $length_result['pattern'];
                $modifications['length'] = $length_result['changes'];
            }
        }

        // Apply tone pattern
        if (isset($patterns['tone']) && $patterns['tone']) {
            $tone_result = $this->apply_tone_pattern($content, $pattern_analysis);
            if ($tone_result['modified']) {
                $content = $tone_result['content'];
                $applied_patterns['tone'] = $tone_result['pattern'];
                $modifications['tone'] = $tone_result['changes'];
            }
        }

        // Apply format patterns
        if (isset($patterns['format']) && $patterns['format']) {
            $format_result = $this->apply_format_patterns($content, $pattern_analysis);
            if ($format_result['modified']) {
                $content = $format_result['content'];
                $applied_patterns['format'] = $format_result['patterns'];
                $modifications['format'] = $format_result['changes'];
            }
        }

        // Apply content patterns
        if (isset($patterns['content']) && $patterns['content']) {
            $content_result = $this->apply_content_patterns($content, $pattern_analysis);
            if ($content_result['modified']) {
                $content = $content_result['content'];
                $applied_patterns['content'] = $content_result['patterns'];
                $modifications['content'] = $content_result['changes'];
            }
        }

        // Calculate confidence score
        $confidence = $this->calculate_pattern_confidence($applied_patterns, $pattern_analysis);

        return array(
            'content' => $content,
            'original_content' => $original_content,
            'applied_patterns' => $applied_patterns,
            'modifications' => $modifications,
            'confidence' => $confidence,
            'pattern_analysis' => $pattern_analysis
        );
    }

    /**
     * Apply length pattern to content
     *
     * @param string $content Content to modify.
     * @param array $pattern_analysis Pattern analysis data.
     * @return array Modification result.
     */
    private function apply_length_pattern($content, $pattern_analysis) {
        if (!isset($pattern_analysis['length_patterns']['optimal_length_range'])) {
            return array('modified' => false, 'content' => $content);
        }

        $optimal_range = $pattern_analysis['length_patterns']['optimal_length_range'];
        $current_length = strlen($content);
        $target_length = ($optimal_range['min'] + $optimal_range['max']) / 2;

        if ($current_length >= $optimal_range['min'] && $current_length <= $optimal_range['max']) {
            return array('modified' => false, 'content' => $content);
        }

        $modified = false;
        $changes = array();

        if ($current_length > $optimal_range['max']) {
            // Truncate content
            $content = substr($content, 0, $optimal_range['max']);
            $modified = true;
            $changes[] = 'Content truncated to optimal length';
        } elseif ($current_length < $optimal_range['min']) {
            // Add filler content (placeholder)
            $filler = ' ' . str_repeat('.', $optimal_range['min'] - $current_length);
            $content .= $filler;
            $modified = true;
            $changes[] = 'Content extended to optimal length';
        }

        return array(
            'modified' => $modified,
            'content' => $content,
            'pattern' => array(
                'optimal_range' => $optimal_range,
                'original_length' => $current_length,
                'new_length' => strlen($content)
            ),
            'changes' => $changes
        );
    }

    /**
     * Apply tone pattern to content
     *
     * @param string $content Content to modify.
     * @param array $pattern_analysis Pattern analysis data.
     * @return array Modification result.
     */
    private function apply_tone_pattern($content, $pattern_analysis) {
        if (!isset($pattern_analysis['tone_patterns']['top_effective_tones'])) {
            return array('modified' => false, 'content' => $content);
        }

        $top_tone = $pattern_analysis['tone_patterns']['top_effective_tones'][0] ?? null;
        if (!$top_tone) {
            return array('modified' => false, 'content' => $content);
        }

        $current_tone = $this->detect_content_tone($content);
        $target_tone = $top_tone['key'];

        if ($current_tone === $target_tone) {
            return array('modified' => false, 'content' => $content);
        }

        // Apply tone transformation
        $modified_content = $this->transform_content_tone($content, $current_tone, $target_tone);
        
        return array(
            'modified' => true,
            'content' => $modified_content,
            'pattern' => array(
                'target_tone' => $target_tone,
                'original_tone' => $current_tone,
                'effectiveness_score' => $top_tone['value']
            ),
            'changes' => array("Tone transformed from {$current_tone} to {$target_tone}")
        );
    }

    /**
     * Apply format patterns to content
     *
     * @param string $content Content to modify.
     * @param array $pattern_analysis Pattern analysis data.
     * @return array Modification result.
     */
    private function apply_format_patterns($content, $pattern_analysis) {
        if (!isset($pattern_analysis['format_patterns'])) {
            return array('modified' => false, 'content' => $content);
        }

        $format_patterns = $pattern_analysis['format_patterns'];
        $modified = false;
        $changes = array();
        $applied_patterns = array();

        // Apply hashtag pattern
        if (isset($format_patterns['hashtags'])) {
            $hashtag_result = $this->apply_hashtag_pattern($content, $format_patterns['hashtags']);
            if ($hashtag_result['modified']) {
                $content = $hashtag_result['content'];
                $modified = true;
                $changes[] = $hashtag_result['change'];
                $applied_patterns['hashtags'] = $hashtag_result['pattern'];
            }
        }

        // Apply emoji pattern
        if (isset($format_patterns['emojis'])) {
            $emoji_result = $this->apply_emoji_pattern($content, $format_patterns['emojis']);
            if ($emoji_result['modified']) {
                $content = $emoji_result['content'];
                $modified = true;
                $changes[] = $emoji_result['change'];
                $applied_patterns['emojis'] = $emoji_result['pattern'];
            }
        }

        return array(
            'modified' => $modified,
            'content' => $content,
            'patterns' => $applied_patterns,
            'changes' => $changes
        );
    }

    /**
     * Apply content patterns to content
     *
     * @param string $content Content to modify.
     * @param array $pattern_analysis Pattern analysis data.
     * @return array Modification result.
     */
    private function apply_content_patterns($content, $pattern_analysis) {
        if (!isset($pattern_analysis['content_patterns']['top_words'])) {
            return array('modified' => false, 'content' => $content);
        }

        $top_words = $pattern_analysis['content_patterns']['top_words'];
        $top_phrases = $pattern_analysis['content_patterns']['top_phrases'] ?? array();
        
        $modified = false;
        $changes = array();
        $applied_patterns = array();

        // Suggest word replacements
        $word_suggestions = $this->suggest_word_replacements($content, $top_words);
        if (!empty($word_suggestions)) {
            $modified = true;
            $changes[] = 'Word suggestions provided for better engagement';
            $applied_patterns['word_suggestions'] = $word_suggestions;
        }

        // Suggest phrase additions
        $phrase_suggestions = $this->suggest_phrase_additions($content, $top_phrases);
        if (!empty($phrase_suggestions)) {
            $modified = true;
            $changes[] = 'Phrase suggestions provided for better engagement';
            $applied_patterns['phrase_suggestions'] = $phrase_suggestions;
        }

        return array(
            'modified' => $modified,
            'content' => $content, // Content not directly modified, only suggestions
            'patterns' => $applied_patterns,
            'changes' => $changes
        );
    }

    /**
     * A/B testing framework to compare pattern effectiveness
     *
     * @param string $original_content Original content.
     * @param array $patterns Patterns to test.
     * @param string $source_handle Source handle.
     * @param int $test_duration Test duration in days.
     * @return array A/B test setup.
     */
    public function setup_ab_test($original_content, $patterns, $source_handle = null, $test_duration = 7) {
        $test_id = uniqid('ab_test_');
        $variants = array();

        // Create control variant (original content)
        $variants['control'] = array(
            'content' => $original_content,
            'patterns_applied' => array(),
            'weight' => 0.5 // 50% traffic
        );

        // Create test variant (with patterns applied)
        $pattern_result = $this->apply_patterns_to_content($original_content, $patterns, $source_handle);
        $variants['test'] = array(
            'content' => $pattern_result['content'],
            'patterns_applied' => $pattern_result['applied_patterns'],
            'weight' => 0.5 // 50% traffic
        );

        $test_data = array(
            'test_id' => $test_id,
            'source_handle' => $source_handle,
            'original_content' => $original_content,
            'patterns' => $patterns,
            'variants' => $variants,
            'test_duration' => $test_duration,
            'start_date' => current_time('mysql'),
            'end_date' => date('Y-m-d H:i:s', strtotime("+{$test_duration} days")),
            'status' => 'active',
            'metrics' => array(
                'control' => array('impressions' => 0, 'reposts' => 0, 'engagement' => 0),
                'test' => array('impressions' => 0, 'reposts' => 0, 'engagement' => 0)
            )
        );

        // Store test data
        $this->store_ab_test_data($test_data);

        return array(
            'test_id' => $test_id,
            'variants' => $variants,
            'test_data' => $test_data
        );
    }

    /**
     * Track A/B test performance
     *
     * @param string $test_id Test ID.
     * @param string $variant Variant name (control or test).
     * @param array $metrics Performance metrics.
     * @return bool Success status.
     */
    public function track_ab_test_performance($test_id, $variant, $metrics) {
        $test_data = $this->get_ab_test_data($test_id);
        
        if (!$test_data) {
            return false;
        }

        // Update metrics
        if (isset($test_data['metrics'][$variant])) {
            $test_data['metrics'][$variant]['impressions'] += $metrics['impressions'] ?? 0;
            $test_data['metrics'][$variant]['reposts'] += $metrics['reposts'] ?? 0;
            $test_data['metrics'][$variant]['engagement'] += $metrics['engagement'] ?? 0;
        }

        // Calculate conversion rates
        $test_data['metrics'][$variant]['repost_rate'] = 
            $test_data['metrics'][$variant]['impressions'] > 0 
            ? ($test_data['metrics'][$variant]['reposts'] / $test_data['metrics'][$variant]['impressions']) * 100 
            : 0;

        $test_data['metrics'][$variant]['engagement_rate'] = 
            $test_data['metrics'][$variant]['impressions'] > 0 
            ? ($test_data['metrics'][$variant]['engagement'] / $test_data['metrics'][$variant]['impressions']) * 100 
            : 0;

        // Store updated data
        $this->store_ab_test_data($test_data);

        return true;
    }

    /**
     * Analyze A/B test results
     *
     * @param string $test_id Test ID.
     * @return array Analysis results.
     */
    public function analyze_ab_test_results($test_id) {
        $test_data = $this->get_ab_test_data($test_id);
        
        if (!$test_data) {
            return array();
        }

        $control = $test_data['metrics']['control'];
        $test = $test_data['metrics']['test'];

        // Calculate statistical significance
        $repost_significance = $this->calculate_statistical_significance(
            $control['reposts'], $control['impressions'],
            $test['reposts'], $test['impressions']
        );

        $engagement_significance = $this->calculate_statistical_significance(
            $control['engagement'], $control['impressions'],
            $test['engagement'], $test['impressions']
        );

        // Determine winner
        $winner = 'none';
        $confidence = 0;

        if ($repost_significance['significant'] && $test['repost_rate'] > $control['repost_rate']) {
            $winner = 'test';
            $confidence = $repost_significance['confidence'];
        } elseif ($repost_significance['significant'] && $control['repost_rate'] > $test['repost_rate']) {
            $winner = 'control';
            $confidence = $repost_significance['confidence'];
        }

        return array(
            'test_id' => $test_id,
            'winner' => $winner,
            'confidence' => $confidence,
            'metrics' => array(
                'control' => $control,
                'test' => $test
            ),
            'significance' => array(
                'repost' => $repost_significance,
                'engagement' => $engagement_significance
            ),
            'improvement' => array(
                'repost_rate' => $test['repost_rate'] - $control['repost_rate'],
                'engagement_rate' => $test['engagement_rate'] - $control['engagement_rate']
            )
        );
    }

    /**
     * Calculate confidence score for pattern reliability
     *
     * @param array $applied_patterns Applied patterns.
     * @param array $pattern_analysis Pattern analysis data.
     * @return float Confidence score (0-100).
     */
    public function calculate_pattern_confidence($applied_patterns, $pattern_analysis) {
        if (empty($applied_patterns)) {
            return 0;
        }

        $confidence_scores = array();
        $total_weight = 0;

        // Length pattern confidence
        if (isset($applied_patterns['length'])) {
            $length_confidence = $this->calculate_length_confidence($applied_patterns['length'], $pattern_analysis);
            $confidence_scores['length'] = $length_confidence;
            $total_weight += 25; // 25% weight
        }

        // Tone pattern confidence
        if (isset($applied_patterns['tone'])) {
            $tone_confidence = $this->calculate_tone_confidence($applied_patterns['tone'], $pattern_analysis);
            $confidence_scores['tone'] = $tone_confidence;
            $total_weight += 25; // 25% weight
        }

        // Format pattern confidence
        if (isset($applied_patterns['format'])) {
            $format_confidence = $this->calculate_format_confidence($applied_patterns['format'], $pattern_analysis);
            $confidence_scores['format'] = $format_confidence;
            $total_weight += 25; // 25% weight
        }

        // Content pattern confidence
        if (isset($applied_patterns['content'])) {
            $content_confidence = $this->calculate_content_confidence($applied_patterns['content'], $pattern_analysis);
            $confidence_scores['content'] = $content_confidence;
            $total_weight += 25; // 25% weight
        }

        // Calculate weighted average
        $total_confidence = 0;
        foreach ($confidence_scores as $type => $score) {
            $weight = 25; // Equal weight for each pattern type
            $total_confidence += ($score * $weight);
        }

        return $total_weight > 0 ? ($total_confidence / $total_weight) : 0;
    }

    /**
     * Track historical performance for patterns over time
     *
     * @param string $pattern_type Pattern type.
     * @param array $pattern_data Pattern data.
     * @param array $performance Performance metrics.
     * @return bool Success status.
     */
    public function track_pattern_performance($pattern_type, $pattern_data, $performance) {
        $tracking_data = array(
            'pattern_type' => $pattern_type,
            'pattern_data' => $pattern_data,
            'performance' => $performance,
            'timestamp' => current_time('mysql'),
            'date' => current_time('Y-m-d')
        );

        $cache_key = "pattern_performance_{$pattern_type}_" . md5(serialize($pattern_data));
        $existing_data = get_transient($cache_key);

        if ($existing_data) {
            $existing_data[] = $tracking_data;
        } else {
            $existing_data = array($tracking_data);
        }

        // Store for 30 days
        set_transient($cache_key, $existing_data, 30 * DAY_IN_SECONDS);

        // Also store in database for long-term analysis
        $this->store_pattern_performance($tracking_data);

        return true;
    }

    /**
     * Detect pattern decay to identify when patterns become less effective
     *
     * @param string $pattern_type Pattern type.
     * @param array $pattern_data Pattern data.
     * @param int $time_window Time window in days.
     * @return array Decay analysis.
     */
    public function detect_pattern_decay($pattern_type, $pattern_data, $time_window = 30) {
        $performance_data = $this->get_pattern_performance_history($pattern_type, $pattern_data, $time_window);
        
        if (count($performance_data) < 10) {
            return array(
                'decay_detected' => false,
                'confidence' => 0,
                'reason' => 'Insufficient data for analysis'
            );
        }

        // Calculate trend
        $trend = $this->calculate_performance_trend($performance_data);
        
        // Calculate decay score
        $decay_score = $this->calculate_decay_score($trend, $performance_data);

        $decay_detected = $decay_score > 0.7; // 70% threshold

        return array(
            'decay_detected' => $decay_detected,
            'confidence' => $decay_score,
            'trend' => $trend,
            'performance_data' => $performance_data,
            'recommendation' => $decay_detected ? 'Consider updating or replacing this pattern' : 'Pattern remains effective'
        );
    }

    /**
     * Helper methods
     */

    private function detect_content_tone($content) {
        // Simple tone detection based on keywords
        $tone_keywords = array(
            'informative' => array('tips', 'guide', 'how to', 'learn', 'understand', 'explain'),
            'conversational' => array('think', 'feel', 'believe', 'wonder', 'imagine', 'suppose'),
            'inspirational' => array('dream', 'achieve', 'success', 'inspire', 'motivate', 'believe')
        );

        $content_lower = strtolower($content);
        $tone_scores = array();

        foreach ($tone_keywords as $tone => $keywords) {
            $score = 0;
            foreach ($keywords as $keyword) {
                $score += substr_count($content_lower, $keyword);
            }
            $tone_scores[$tone] = $score;
        }

        return array_keys($tone_scores, max($tone_scores))[0] ?? 'neutral';
    }

    private function transform_content_tone($content, $from_tone, $to_tone) {
        // Simple tone transformation (placeholder implementation)
        $transformations = array(
            'informative' => array(
                'conversational' => 'I think ',
                'inspirational' => 'You can '
            ),
            'conversational' => array(
                'informative' => 'Here\'s how: ',
                'inspirational' => 'Imagine '
            ),
            'inspirational' => array(
                'informative' => 'Learn this: ',
                'conversational' => 'I believe '
            )
        );

        if (isset($transformations[$from_tone][$to_tone])) {
            return $transformations[$from_tone][$to_tone] . $content;
        }

        return $content;
    }

    private function apply_hashtag_pattern($content, $hashtag_pattern) {
        $current_hashtags = preg_match_all('/#\w+/', $content, $matches);
        $optimal_count = $hashtag_pattern['optimal_count'] ?? 2;

        if ($current_hashtags >= $optimal_count) {
            return array('modified' => false, 'content' => $content);
        }

        // Add placeholder hashtags
        $hashtags_to_add = $optimal_count - $current_hashtags;
        $placeholder_hashtags = array('#growth', '#success', '#business');
        
        for ($i = 0; $i < $hashtags_to_add; $i++) {
            $content .= ' ' . $placeholder_hashtags[$i % count($placeholder_hashtags)];
        }

        return array(
            'modified' => true,
            'content' => $content,
            'pattern' => array('optimal_count' => $optimal_count, 'added' => $hashtags_to_add),
            'change' => "Added {$hashtags_to_add} hashtags to reach optimal count"
        );
    }

    private function apply_emoji_pattern($content, $emoji_pattern) {
        $current_emojis = preg_match_all('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]/u', $content, $matches);
        $optimal_count = $emoji_pattern['optimal_count'] ?? 1;

        if ($current_emojis >= $optimal_count) {
            return array('modified' => false, 'content' => $content);
        }

        // Add placeholder emoji
        $content .= ' 🚀';

        return array(
            'modified' => true,
            'content' => $content,
            'pattern' => array('optimal_count' => $optimal_count, 'added' => 1),
            'change' => 'Added emoji to reach optimal count'
        );
    }

    private function suggest_word_replacements($content, $top_words) {
        $suggestions = array();
        $content_words = str_word_count(strtolower($content), 1);

        foreach ($top_words as $word_data) {
            $top_word = strtolower($word_data['key']);
            if (!in_array($top_word, $content_words)) {
                $suggestions[] = array(
                    'word' => $top_word,
                    'frequency' => $word_data['value'],
                    'suggestion' => "Consider using '{$top_word}' in your content"
                );
            }
        }

        return array_slice($suggestions, 0, 3); // Limit to top 3 suggestions
    }

    private function suggest_phrase_additions($content, $top_phrases) {
        $suggestions = array();

        foreach ($top_phrases as $phrase_data) {
            $phrase = strtolower($phrase_data['key']);
            if (strpos(strtolower($content), $phrase) === false) {
                $suggestions[] = array(
                    'phrase' => $phrase,
                    'frequency' => $phrase_data['value'],
                    'suggestion' => "Consider including '{$phrase}' in your content"
                );
            }
        }

        return array_slice($suggestions, 0, 2); // Limit to top 2 suggestions
    }

    private function calculate_statistical_significance($control_success, $control_total, $test_success, $test_total) {
        if ($control_total == 0 || $test_total == 0) {
            return array('significant' => false, 'confidence' => 0);
        }

        $control_rate = $control_success / $control_total;
        $test_rate = $test_success / $test_total;
        
        $pooled_rate = ($control_success + $test_success) / ($control_total + $test_total);
        $standard_error = sqrt($pooled_rate * (1 - $pooled_rate) * (1/$control_total + 1/$test_total));
        
        $z_score = abs($test_rate - $control_rate) / $standard_error;
        
        // 95% confidence level (z = 1.96)
        $significant = $z_score > 1.96;
        $confidence = min(100, $z_score * 50); // Convert to percentage

        return array(
            'significant' => $significant,
            'confidence' => $confidence,
            'z_score' => $z_score,
            'control_rate' => $control_rate,
            'test_rate' => $test_rate
        );
    }

    private function calculate_length_confidence($pattern, $analysis) {
        if (!isset($analysis['length_patterns']['optimal_length_range'])) {
            return 0;
        }

        $optimal_range = $analysis['length_patterns']['optimal_length_range'];
        $new_length = $pattern['new_length'] ?? 0;
        
        if ($new_length >= $optimal_range['min'] && $new_length <= $optimal_range['max']) {
            return 100;
        }

        $distance = min(abs($new_length - $optimal_range['min']), abs($new_length - $optimal_range['max']));
        $max_distance = $optimal_range['max'] - $optimal_range['min'];
        
        return max(0, 100 - ($distance / $max_distance) * 100);
    }

    private function calculate_tone_confidence($pattern, $analysis) {
        if (!isset($analysis['tone_patterns']['tone_effectiveness'])) {
            return 0;
        }

        $target_tone = $pattern['target_tone'] ?? '';
        $effectiveness = $analysis['tone_patterns']['tone_effectiveness'][$target_tone] ?? 0;
        
        return min(100, $effectiveness * 10); // Scale 0-10 to 0-100
    }

    private function calculate_format_confidence($patterns, $analysis) {
        if (!isset($analysis['format_patterns'])) {
            return 0;
        }

        $confidence_scores = array();
        foreach ($patterns as $type => $pattern) {
            if (isset($analysis['format_patterns'][$type]['avg_engagement'])) {
                $confidence_scores[] = min(100, $analysis['format_patterns'][$type]['avg_engagement'] * 10);
            }
        }

        return empty($confidence_scores) ? 0 : array_sum($confidence_scores) / count($confidence_scores);
    }

    private function calculate_content_confidence($patterns, $analysis) {
        // Content patterns are suggestions, so confidence is based on pattern strength
        $confidence = 0;
        $count = 0;

        if (isset($patterns['word_suggestions'])) {
            foreach ($patterns['word_suggestions'] as $suggestion) {
                $confidence += min(100, $suggestion['frequency'] * 2);
                $count++;
            }
        }

        if (isset($patterns['phrase_suggestions'])) {
            foreach ($patterns['phrase_suggestions'] as $suggestion) {
                $confidence += min(100, $suggestion['frequency'] * 3);
                $count++;
            }
        }

        return $count > 0 ? $confidence / $count : 0;
    }

    private function store_ab_test_data($test_data) {
        $cache_key = "ab_test_{$test_data['test_id']}";
        set_transient($cache_key, $test_data, $this->cache_expiration);
    }

    private function get_ab_test_data($test_id) {
        $cache_key = "ab_test_{$test_id}";
        return get_transient($cache_key);
    }

    private function store_pattern_performance($data) {
        // Store in database for long-term analysis
        $table_name = $this->database->get_table_name('pattern_performance');
        
        $this->database->insert($table_name, array(
            'pattern_type' => $data['pattern_type'],
            'pattern_data' => json_encode($data['pattern_data']),
            'performance_data' => json_encode($data['performance']),
            'timestamp' => $data['timestamp'],
            'date' => $data['date']
        ));
    }

    private function get_pattern_performance_history($pattern_type, $pattern_data, $time_window) {
        $table_name = $this->database->get_table_name('pattern_performance');
        $pattern_hash = md5(serialize($pattern_data));
        
        $results = $this->database->get_results($this->database->prepare(
            "SELECT * FROM {$table_name} 
             WHERE pattern_type = %s 
             AND pattern_data LIKE %s 
             AND date >= DATE_SUB(NOW(), INTERVAL %d DAY)
             ORDER BY timestamp ASC",
            $pattern_type,
            '%' . $pattern_hash . '%',
            $time_window
        ));

        return array_map(function($row) {
            return array(
                'performance' => json_decode($row->performance_data, true),
                'timestamp' => $row->timestamp,
                'date' => $row->date
            );
        }, $results);
    }

    private function calculate_performance_trend($performance_data) {
        if (count($performance_data) < 2) {
            return array('slope' => 0, 'direction' => 'stable');
        }

        $x_values = range(1, count($performance_data));
        $y_values = array_map(function($data) {
            return $data['performance']['repost_rate'] ?? 0;
        }, $performance_data);

        $n = count($x_values);
        $sum_x = array_sum($x_values);
        $sum_y = array_sum($y_values);
        $sum_xy = 0;
        $sum_x2 = 0;

        for ($i = 0; $i < $n; $i++) {
            $sum_xy += $x_values[$i] * $y_values[$i];
            $sum_x2 += $x_values[$i] * $x_values[$i];
        }

        $slope = ($n * $sum_xy - $sum_x * $sum_y) / ($n * $sum_x2 - $sum_x * $sum_x);
        
        return array(
            'slope' => $slope,
            'direction' => $slope > 0.01 ? 'improving' : ($slope < -0.01 ? 'declining' : 'stable')
        );
    }

    private function calculate_decay_score($trend, $performance_data) {
        if ($trend['direction'] !== 'declining') {
            return 0;
        }

        // Calculate decay score based on slope and consistency
        $slope_magnitude = abs($trend['slope']);
        $consistency = $this->calculate_trend_consistency($performance_data);
        
        return min(1, ($slope_magnitude * 10 + $consistency) / 2);
    }

    private function calculate_trend_consistency($performance_data) {
        if (count($performance_data) < 3) {
            return 0;
        }

        $rates = array_map(function($data) {
            return $data['performance']['repost_rate'] ?? 0;
        }, $performance_data);

        $declining_count = 0;
        for ($i = 1; $i < count($rates); $i++) {
            if ($rates[$i] < $rates[$i-1]) {
                $declining_count++;
            }
        }

        return $declining_count / (count($rates) - 1);
    }

    /**
     * AJAX handlers
     */

    public function ajax_validate_pattern() {
        check_ajax_referer('xelite_pattern_validator_nonce', 'nonce');
        
        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $patterns = json_decode(stripslashes($_POST['patterns'] ?? '{}'), true);
        $source_handle = sanitize_text_field($_POST['source_handle'] ?? '');
        
        if (empty($content)) {
            wp_send_json_error('Content is required');
        }

        $result = $this->apply_patterns_to_content($content, $patterns, $source_handle);
        wp_send_json_success($result);
    }

    public function ajax_ab_test_pattern() {
        check_ajax_referer('xelite_pattern_validator_nonce', 'nonce');
        
        $content = sanitize_textarea_field($_POST['content'] ?? '');
        $patterns = json_decode(stripslashes($_POST['patterns'] ?? '{}'), true);
        $source_handle = sanitize_text_field($_POST['source_handle'] ?? '');
        $test_duration = intval($_POST['test_duration'] ?? 7);
        
        if (empty($content)) {
            wp_send_json_error('Content is required');
        }

        $result = $this->setup_ab_test($content, $patterns, $source_handle, $test_duration);
        wp_send_json_success($result);
    }

    public function ajax_get_pattern_confidence() {
        check_ajax_referer('xelite_pattern_validator_nonce', 'nonce');
        
        $applied_patterns = json_decode(stripslashes($_POST['applied_patterns'] ?? '{}'), true);
        $source_handle = sanitize_text_field($_POST['source_handle'] ?? '');
        
        $pattern_analysis = $this->pattern_analyzer->analyze_patterns($source_handle, 1000);
        $confidence = $this->calculate_pattern_confidence($applied_patterns, $pattern_analysis);
        
        wp_send_json_success(array('confidence' => $confidence));
    }

    public function ajax_track_pattern_performance() {
        check_ajax_referer('xelite_pattern_validator_nonce', 'nonce');
        
        $pattern_type = sanitize_text_field($_POST['pattern_type'] ?? '');
        $pattern_data = json_decode(stripslashes($_POST['pattern_data'] ?? '{}'), true);
        $performance = json_decode(stripslashes($_POST['performance'] ?? '{}'), true);
        
        if (empty($pattern_type)) {
            wp_send_json_error('Pattern type is required');
        }

        $result = $this->track_pattern_performance($pattern_type, $pattern_data, $performance);
        wp_send_json_success(array('tracked' => $result));
    }

    public function ajax_detect_pattern_decay() {
        check_ajax_referer('xelite_pattern_validator_nonce', 'nonce');
        
        $pattern_type = sanitize_text_field($_POST['pattern_type'] ?? '');
        $pattern_data = json_decode(stripslashes($_POST['pattern_data'] ?? '{}'), true);
        $time_window = intval($_POST['time_window'] ?? 30);
        
        if (empty($pattern_type)) {
            wp_send_json_error('Pattern type is required');
        }

        $result = $this->detect_pattern_decay($pattern_type, $pattern_data, $time_window);
        wp_send_json_success($result);
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
            $this->logger->log($level, "[Pattern Validator] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine Pattern Validator] {$message}");
        }
    }
} 