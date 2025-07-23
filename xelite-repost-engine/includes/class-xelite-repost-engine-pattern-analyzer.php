<?php
/**
 * Pattern Analyzer for Repost Intelligence
 *
 * Analyzes patterns in successful reposts to identify what makes content
 * more likely to be reposted by target accounts.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pattern Analyzer Class
 */
class XeliteRepostEngine_Pattern_Analyzer extends XeliteRepostEngine_Abstract_Base {

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
     * Cache for analysis results
     *
     * @var array
     */
    private $cache = array();

    /**
     * Analysis configuration
     *
     * @var array
     */
    private $config;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Database $database Database service.
     * @param XeliteRepostEngine_Logger $logger Logger service.
     */
    public function __construct($database, $logger = null) {
        parent::__construct();
        
        $this->database = $database;
        $this->logger = $logger;
        
        $this->init_config();
    }

    /**
     * Initialize the class
     */
    protected function init() {
        $this->init_hooks();
    }

    /**
     * Initialize configuration
     */
    private function init_config() {
        $this->config = array(
            'length_categories' => array(
                'short' => array('min' => 0, 'max' => 100),
                'medium' => array('min' => 101, 'max' => 200),
                'long' => array('min' => 201, 'max' => 280)
            ),
            'tone_patterns' => array(
                'question' => array(
                    'patterns' => array('?', 'what', 'how', 'why', 'when', 'where', 'who'),
                    'weight' => 1.2
                ),
                'statement' => array(
                    'patterns' => array('.', '!', 'fact', 'truth', 'reality'),
                    'weight' => 1.0
                ),
                'call_to_action' => array(
                    'patterns' => array('check', 'read', 'follow', 'share', 'retweet', 'like', 'comment'),
                    'weight' => 1.3
                ),
                'story' => array(
                    'patterns' => array('story', 'experience', 'happened', 'when i', 'i was'),
                    'weight' => 1.1
                ),
                'tip' => array(
                    'patterns' => array('tip', 'hack', 'trick', 'secret', 'pro tip', 'advice'),
                    'weight' => 1.4
                )
            ),
            'format_patterns' => array(
                'hashtags' => array(
                    'max_count' => 3,
                    'weight' => 1.1
                ),
                'emojis' => array(
                    'max_count' => 2,
                    'weight' => 1.05
                ),
                'urls' => array(
                    'max_count' => 1,
                    'weight' => 1.2
                ),
                'mentions' => array(
                    'max_count' => 2,
                    'weight' => 1.15
                )
            ),
            'engagement_metrics' => array(
                'repost_count' => array('weight' => 1.0),
                'like_count' => array('weight' => 0.8),
                'reply_count' => array('weight' => 0.9),
                'quote_count' => array('weight' => 1.1)
            )
        );
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_xelite_analyze_patterns', array($this, 'ajax_analyze_patterns'));
        add_action('wp_ajax_xelite_get_pattern_insights', array($this, 'ajax_get_pattern_insights'));
        add_action('xelite_after_repost_scraped', array($this, 'analyze_new_repost'));
    }

    /**
     * Analyze patterns for a specific account or all accounts
     *
     * @param string|null $source_handle Source handle to analyze (null for all).
     * @param int $limit Number of reposts to analyze.
     * @return array Analysis results.
     */
    public function analyze_patterns($source_handle = null, $limit = 1000) {
        $cache_key = 'pattern_analysis_' . ($source_handle ?: 'all') . '_' . $limit;
        
        if (isset($this->cache[$cache_key])) {
            return $this->cache[$cache_key];
        }

        $this->log('info', "Starting pattern analysis for " . ($source_handle ?: 'all accounts') . " (limit: {$limit})");

        // Get repost data
        $reposts = $this->get_repost_data($source_handle, $limit);
        
        if (empty($reposts)) {
            $this->log('warning', 'No repost data found for analysis');
            return array();
        }

        $analysis = array(
            'summary' => $this->analyze_summary($reposts),
            'length_patterns' => $this->analyze_length_patterns($reposts),
            'tone_patterns' => $this->analyze_tone_patterns($reposts),
            'format_patterns' => $this->analyze_format_patterns($reposts),
            'engagement_correlation' => $this->analyze_engagement_correlation($reposts),
            'time_patterns' => $this->analyze_time_patterns($reposts),
            'content_patterns' => $this->analyze_content_patterns($reposts),
            'recommendations' => array()
        );

        // Generate recommendations
        $analysis['recommendations'] = $this->generate_recommendations($analysis);

        // Cache results
        $this->cache[$cache_key] = $analysis;

        // Store analysis results
        $this->store_analysis_results($source_handle, $analysis);

        $this->log('info', "Pattern analysis completed. Found " . count($reposts) . " reposts to analyze");

        return $analysis;
    }

    /**
     * Get repost data from database
     *
     * @param string|null $source_handle Source handle filter.
     * @param int $limit Number of reposts to retrieve.
     * @return array Repost data.
     */
    private function get_repost_data($source_handle = null, $limit = 1000) {
        $where_clause = "WHERE 1=1";
        $params = array();

        if ($source_handle) {
            $where_clause .= " AND source_handle = %s";
            $params[] = $source_handle;
        }

        $query = "SELECT * FROM {$this->database->get_table_name('reposts')} 
                  {$where_clause} 
                  ORDER BY timestamp DESC 
                  LIMIT %d";
        $params[] = $limit;

        return $this->database->get_results($query, $params);
    }

    /**
     * Analyze summary statistics
     *
     * @param array $reposts Repost data.
     * @return array Summary statistics.
     */
    private function analyze_summary($reposts) {
        $total_reposts = count($reposts);
        $total_engagement = 0;
        $avg_length = 0;
        $source_counts = array();

        foreach ($reposts as $repost) {
            $total_engagement += intval($repost->repost_count);
            $avg_length += strlen($repost->original_text);
            
            $source = $repost->source_handle;
            $source_counts[$source] = isset($source_counts[$source]) ? $source_counts[$source] + 1 : 1;
        }

        return array(
            'total_reposts' => $total_reposts,
            'total_engagement' => $total_engagement,
            'avg_engagement_per_repost' => $total_reposts > 0 ? $total_engagement / $total_reposts : 0,
            'avg_length' => $total_reposts > 0 ? $avg_length / $total_reposts : 0,
            'top_sources' => $this->get_top_sources($source_counts, 5),
            'date_range' => array(
                'earliest' => $reposts[count($reposts) - 1]->timestamp ?? null,
                'latest' => $reposts[0]->timestamp ?? null
            )
        );
    }

    /**
     * Analyze length patterns
     *
     * @param array $reposts Repost data.
     * @return array Length analysis.
     */
    private function analyze_length_patterns($reposts) {
        $length_categories = $this->config['length_categories'];
        $category_counts = array_fill_keys(array_keys($length_categories), 0);
        $category_engagement = array_fill_keys(array_keys($length_categories), 0);
        $length_engagement_correlation = array();

        foreach ($reposts as $repost) {
            $length = strlen($repost->original_text);
            $engagement = intval($repost->repost_count);

            // Categorize by length
            foreach ($length_categories as $category => $range) {
                if ($length >= $range['min'] && $length <= $range['max']) {
                    $category_counts[$category]++;
                    $category_engagement[$category] += $engagement;
                    break;
                }
            }

            // Store for correlation analysis
            $length_engagement_correlation[] = array(
                'length' => $length,
                'engagement' => $engagement
            );
        }

        // Calculate averages
        $category_avg_engagement = array();
        foreach ($category_counts as $category => $count) {
            $category_avg_engagement[$category] = $count > 0 ? $category_engagement[$category] / $count : 0;
        }

        return array(
            'category_distribution' => $category_counts,
            'category_avg_engagement' => $category_avg_engagement,
            'correlation' => $this->calculate_correlation($length_engagement_correlation, 'length', 'engagement'),
            'optimal_length_range' => $this->find_optimal_length_range($length_engagement_correlation)
        );
    }

    /**
     * Analyze tone patterns
     *
     * @param array $reposts Repost data.
     * @return array Tone analysis.
     */
    private function analyze_tone_patterns($reposts) {
        $tone_patterns = $this->config['tone_patterns'];
        $tone_counts = array_fill_keys(array_keys($tone_patterns), 0);
        $tone_engagement = array_fill_keys(array_keys($tone_patterns), 0);
        $tone_texts = array_fill_keys(array_keys($tone_patterns), array());

        foreach ($reposts as $repost) {
            $text = strtolower($repost->original_text);
            $engagement = intval($repost->repost_count);
            $detected_tones = array();

            foreach ($tone_patterns as $tone => $config) {
                foreach ($config['patterns'] as $pattern) {
                    if (strpos($text, $pattern) !== false) {
                        $detected_tones[] = $tone;
                        $tone_counts[$tone]++;
                        $tone_engagement[$tone] += $engagement;
                        $tone_texts[$tone][] = $repost->original_text;
                        break; // Only count once per tone
                    }
                }
            }

            // Handle mixed tones
            if (count($detected_tones) > 1) {
                $tone_counts['mixed'] = isset($tone_counts['mixed']) ? $tone_counts['mixed'] + 1 : 1;
                $tone_engagement['mixed'] = isset($tone_engagement['mixed']) ? $tone_engagement['mixed'] + $engagement : $engagement;
            }
        }

        // Calculate averages and effectiveness
        $tone_effectiveness = array();
        foreach ($tone_counts as $tone => $count) {
            if ($count > 0) {
                $avg_engagement = $tone_engagement[$tone] / $count;
                $weight = isset($tone_patterns[$tone]['weight']) ? $tone_patterns[$tone]['weight'] : 1.0;
                $tone_effectiveness[$tone] = $avg_engagement * $weight;
            } else {
                $tone_effectiveness[$tone] = 0;
            }
        }

        return array(
            'tone_distribution' => $tone_counts,
            'tone_avg_engagement' => array_map(function($count, $engagement) {
                return $count > 0 ? $engagement / $count : 0;
            }, $tone_counts, $tone_engagement),
            'tone_effectiveness' => $tone_effectiveness,
            'top_effective_tones' => $this->get_top_items($tone_effectiveness, 3),
            'example_texts' => array_map(function($texts) {
                return array_slice($texts, 0, 5); // Return top 5 examples
            }, $tone_texts)
        );
    }

    /**
     * Analyze format patterns
     *
     * @param array $reposts Repost data.
     * @return array Format analysis.
     */
    private function analyze_format_patterns($reposts) {
        $format_patterns = $this->config['format_patterns'];
        $format_stats = array();

        foreach ($format_patterns as $format => $config) {
            $format_stats[$format] = array(
                'counts' => array(),
                'engagement' => array(),
                'avg_engagement' => 0
            );
        }

        foreach ($reposts as $repost) {
            $text = $repost->original_text;
            $engagement = intval($repost->repost_count);

            // Analyze hashtags
            $hashtag_count = preg_match_all('/#\w+/', $text, $matches);
            $this->update_format_stats($format_stats['hashtags'], $hashtag_count, $engagement);

            // Analyze emojis
            $emoji_count = preg_match_all('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', $text, $matches);
            $this->update_format_stats($format_stats['emojis'], $emoji_count, $engagement);

            // Analyze URLs
            $url_count = preg_match_all('/https?:\/\/[^\s]+/', $text, $matches);
            $this->update_format_stats($format_stats['urls'], $url_count, $engagement);

            // Analyze mentions
            $mention_count = preg_match_all('/@\w+/', $text, $matches);
            $this->update_format_stats($format_stats['mentions'], $mention_count, $engagement);
        }

        // Calculate averages
        foreach ($format_stats as $format => &$stats) {
            $total_count = array_sum($stats['counts']);
            $total_engagement = array_sum($stats['engagement']);
            $stats['avg_engagement'] = $total_count > 0 ? $total_engagement / $total_count : 0;
            $stats['optimal_count'] = $this->find_optimal_count($stats['counts'], $stats['engagement']);
        }

        return $format_stats;
    }

    /**
     * Update format statistics
     *
     * @param array $stats Stats array to update.
     * @param int $count Count of the format element.
     * @param int $engagement Engagement value.
     */
    private function update_format_stats(&$stats, $count, $engagement) {
        $count_key = $count > 5 ? '5+' : (string)$count;
        
        if (!isset($stats['counts'][$count_key])) {
            $stats['counts'][$count_key] = 0;
            $stats['engagement'][$count_key] = 0;
        }
        
        $stats['counts'][$count_key]++;
        $stats['engagement'][$count_key] += $engagement;
    }

    /**
     * Analyze engagement correlation
     *
     * @param array $reposts Repost data.
     * @return array Engagement correlation analysis.
     */
    private function analyze_engagement_correlation($reposts) {
        $correlations = array();
        $engagement_metrics = $this->config['engagement_metrics'];

        foreach ($engagement_metrics as $metric => $config) {
            $data = array();
            
            foreach ($reposts as $repost) {
                $value = intval($repost->{$metric . '_count'} ?? 0);
                $repost_count = intval($repost->repost_count);
                
                if ($value > 0) {
                    $data[] = array(
                        'metric_value' => $value,
                        'repost_count' => $repost_count
                    );
                }
            }

            if (!empty($data)) {
                $correlations[$metric] = $this->calculate_correlation($data, 'metric_value', 'repost_count');
            } else {
                $correlations[$metric] = 0;
            }
        }

        return array(
            'correlations' => $correlations,
            'strongest_correlation' => $this->get_top_items($correlations, 1),
            'engagement_predictors' => $this->identify_engagement_predictors($reposts)
        );
    }

    /**
     * Analyze time patterns
     *
     * @param array $reposts Repost data.
     * @return array Time pattern analysis.
     */
    private function analyze_time_patterns($reposts) {
        $hourly_distribution = array_fill(0, 24, 0);
        $daily_distribution = array_fill(0, 7, 0);
        $hourly_engagement = array_fill(0, 24, 0);
        $daily_engagement = array_fill(0, 7, 0);

        foreach ($reposts as $repost) {
            $timestamp = strtotime($repost->timestamp);
            $hour = (int)date('G', $timestamp);
            $day = (int)date('w', $timestamp);
            $engagement = intval($repost->repost_count);

            $hourly_distribution[$hour]++;
            $daily_distribution[$day]++;
            $hourly_engagement[$hour] += $engagement;
            $daily_engagement[$day] += $engagement;
        }

        // Calculate averages
        $hourly_avg_engagement = array();
        $daily_avg_engagement = array();

        for ($i = 0; $i < 24; $i++) {
            $hourly_avg_engagement[$i] = $hourly_distribution[$i] > 0 ? $hourly_engagement[$i] / $hourly_distribution[$i] : 0;
        }

        for ($i = 0; $i < 7; $i++) {
            $daily_avg_engagement[$i] = $daily_distribution[$i] > 0 ? $daily_engagement[$i] / $daily_distribution[$i] : 0;
        }

        return array(
            'hourly_distribution' => $hourly_distribution,
            'daily_distribution' => $daily_distribution,
            'hourly_avg_engagement' => $hourly_avg_engagement,
            'daily_avg_engagement' => $daily_avg_engagement,
            'best_hours' => $this->get_top_hours($hourly_avg_engagement, 3),
            'best_days' => $this->get_top_days($daily_avg_engagement, 3)
        );
    }

    /**
     * Analyze content patterns
     *
     * @param array $reposts Repost data.
     * @return array Content pattern analysis.
     */
    private function analyze_content_patterns($reposts) {
        $word_frequency = array();
        $phrase_patterns = array();
        $content_types = array();

        foreach ($reposts as $repost) {
            $text = strtolower($repost->original_text);
            $engagement = intval($repost->repost_count);

            // Word frequency analysis
            $words = preg_split('/\s+/', $text);
            foreach ($words as $word) {
                $word = trim($word, '.,!?@#$%^&*()_+-=[]{}|;:"\'<>?/\\');
                if (strlen($word) > 2 && !in_array($word, array('the', 'and', 'for', 'are', 'but', 'not', 'you', 'all', 'can', 'had', 'her', 'was', 'one', 'our', 'out', 'day', 'get', 'has', 'him', 'his', 'how', 'man', 'new', 'now', 'old', 'see', 'two', 'way', 'who', 'boy', 'did', 'its', 'let', 'put', 'say', 'she', 'too', 'use'))) {
                    if (!isset($word_frequency[$word])) {
                        $word_frequency[$word] = array('count' => 0, 'engagement' => 0);
                    }
                    $word_frequency[$word]['count']++;
                    $word_frequency[$word]['engagement'] += $engagement;
                }
            }

            // Phrase pattern analysis
            $phrases = $this->extract_phrases($text);
            foreach ($phrases as $phrase) {
                if (!isset($phrase_patterns[$phrase])) {
                    $phrase_patterns[$phrase] = array('count' => 0, 'engagement' => 0);
                }
                $phrase_patterns[$phrase]['count']++;
                $phrase_patterns[$phrase]['engagement'] += $engagement;
            }

            // Content type classification
            $content_type = $this->classify_content_type($text);
            if (!isset($content_types[$content_type])) {
                $content_types[$content_type] = array('count' => 0, 'engagement' => 0);
            }
            $content_types[$content_type]['count']++;
            $content_types[$content_type]['engagement'] += $engagement;
        }

        return array(
            'top_words' => $this->get_top_words($word_frequency, 20),
            'top_phrases' => $this->get_top_phrases($phrase_patterns, 10),
            'content_type_distribution' => $content_types,
            'content_type_effectiveness' => array_map(function($data) {
                return $data['count'] > 0 ? $data['engagement'] / $data['count'] : 0;
            }, $content_types)
        );
    }

    /**
     * Extract phrases from text
     *
     * @param string $text Text to analyze.
     * @return array Extracted phrases.
     */
    private function extract_phrases($text) {
        $phrases = array();
        $words = preg_split('/\s+/', $text);
        
        // Extract 2-4 word phrases
        for ($i = 0; $i < count($words) - 1; $i++) {
            for ($j = 2; $j <= 4 && $i + $j <= count($words); $j++) {
                $phrase = implode(' ', array_slice($words, $i, $j));
                $phrase = trim($phrase, '.,!?@#$%^&*()_+-=[]{}|;:"\'<>?/\\');
                if (strlen($phrase) > 5) {
                    $phrases[] = $phrase;
                }
            }
        }
        
        return $phrases;
    }

    /**
     * Classify content type
     *
     * @param string $text Text to classify.
     * @return string Content type.
     */
    private function classify_content_type($text) {
        $text = strtolower($text);
        
        if (preg_match('/\?/', $text)) {
            return 'question';
        } elseif (preg_match('/(tip|hack|secret|advice|pro tip)/', $text)) {
            return 'tip';
        } elseif (preg_match('/(story|experience|happened|when i|i was)/', $text)) {
            return 'story';
        } elseif (preg_match('/(check|read|follow|share|retweet|like|comment)/', $text)) {
            return 'call_to_action';
        } elseif (preg_match('/(fact|truth|reality|actually|really)/', $text)) {
            return 'fact';
        } else {
            return 'general';
        }
    }

    /**
     * Calculate correlation coefficient
     *
     * @param array $data Data array.
     * @param string $x_key X-axis key.
     * @param string $y_key Y-axis key.
     * @return float Correlation coefficient.
     */
    private function calculate_correlation($data, $x_key, $y_key) {
        if (count($data) < 2) {
            return 0;
        }

        $n = count($data);
        $sum_x = 0;
        $sum_y = 0;
        $sum_xy = 0;
        $sum_x2 = 0;
        $sum_y2 = 0;

        foreach ($data as $point) {
            $x = $point[$x_key];
            $y = $point[$y_key];
            
            $sum_x += $x;
            $sum_y += $y;
            $sum_xy += $x * $y;
            $sum_x2 += $x * $x;
            $sum_y2 += $y * $y;
        }

        $numerator = $n * $sum_xy - $sum_x * $sum_y;
        $denominator = sqrt(($n * $sum_x2 - $sum_x * $sum_x) * ($n * $sum_y2 - $sum_y * $sum_y));

        return $denominator != 0 ? $numerator / $denominator : 0;
    }

    /**
     * Find optimal length range
     *
     * @param array $data Length-engagement data.
     * @return array Optimal range.
     */
    private function find_optimal_length_range($data) {
        if (empty($data)) {
            return array('min' => 0, 'max' => 0);
        }

        // Group by length ranges and find best performing range
        $ranges = array(
            'short' => array('min' => 0, 'max' => 100, 'avg_engagement' => 0, 'count' => 0),
            'medium' => array('min' => 101, 'max' => 200, 'avg_engagement' => 0, 'count' => 0),
            'long' => array('min' => 201, 'max' => 280, 'avg_engagement' => 0, 'count' => 0)
        );

        foreach ($data as $point) {
            foreach ($ranges as $range_name => &$range) {
                if ($point['length'] >= $range['min'] && $point['length'] <= $range['max']) {
                    $range['avg_engagement'] += $point['engagement'];
                    $range['count']++;
                    break;
                }
            }
        }

        // Calculate averages
        foreach ($ranges as &$range) {
            if ($range['count'] > 0) {
                $range['avg_engagement'] /= $range['count'];
            }
        }

        // Find best performing range
        $best_range = null;
        $best_engagement = 0;

        foreach ($ranges as $range_name => $range) {
            if ($range['avg_engagement'] > $best_engagement) {
                $best_engagement = $range['avg_engagement'];
                $best_range = $range;
            }
        }

        return $best_range ? array('min' => $best_range['min'], 'max' => $best_range['max']) : array('min' => 0, 'max' => 0);
    }

    /**
     * Find optimal count for format elements
     *
     * @param array $counts Count distribution.
     * @param array $engagement Engagement distribution.
     * @return int Optimal count.
     */
    private function find_optimal_count($counts, $engagement) {
        $optimal_count = 0;
        $best_avg_engagement = 0;

        foreach ($counts as $count => $count_value) {
            if ($count_value > 0 && isset($engagement[$count])) {
                $avg_engagement = $engagement[$count] / $count_value;
                if ($avg_engagement > $best_avg_engagement) {
                    $best_avg_engagement = $avg_engagement;
                    $optimal_count = $count === '5+' ? 5 : intval($count);
                }
            }
        }

        return $optimal_count;
    }

    /**
     * Generate recommendations based on analysis
     *
     * @param array $analysis Analysis results.
     * @return array Recommendations.
     */
    private function generate_recommendations($analysis) {
        $recommendations = array();

        // Length recommendations
        if (isset($analysis['length_patterns']['optimal_length_range'])) {
            $optimal = $analysis['length_patterns']['optimal_length_range'];
            $recommendations[] = array(
                'type' => 'length',
                'title' => 'Optimal Tweet Length',
                'description' => "Aim for tweets between {$optimal['min']} and {$optimal['max']} characters for best engagement.",
                'priority' => 'high'
            );
        }

        // Tone recommendations
        if (isset($analysis['tone_patterns']['top_effective_tones'])) {
            $top_tone = $analysis['tone_patterns']['top_effective_tones'][0] ?? null;
            if ($top_tone) {
                $recommendations[] = array(
                    'type' => 'tone',
                    'title' => 'Most Effective Tone',
                    'description' => "Use {$top_tone['key']} tone more frequently as it shows the highest engagement.",
                    'priority' => 'high'
                );
            }
        }

        // Format recommendations
        foreach ($analysis['format_patterns'] as $format => $stats) {
            if (isset($stats['optimal_count']) && $stats['optimal_count'] > 0) {
                $recommendations[] = array(
                    'type' => 'format',
                    'title' => "Optimal {$format} Usage",
                    'description' => "Use {$stats['optimal_count']} {$format} per tweet for best results.",
                    'priority' => 'medium'
                );
            }
        }

        // Time recommendations
        if (isset($analysis['time_patterns']['best_hours'])) {
            $best_hour = $analysis['time_patterns']['best_hours'][0] ?? null;
            if ($best_hour) {
                $hour_name = $this->get_hour_name($best_hour['key']);
                $recommendations[] = array(
                    'type' => 'timing',
                    'title' => 'Best Posting Time',
                    'description' => "Posts at {$hour_name} show the highest engagement rates.",
                    'priority' => 'medium'
                );
            }
        }

        return $recommendations;
    }

    /**
     * Store analysis results in database
     *
     * @param string|null $source_handle Source handle.
     * @param array $analysis Analysis results.
     */
    private function store_analysis_results($source_handle, $analysis) {
        $data = array(
            'source_handle' => $source_handle ?: 'all',
            'analysis_data' => json_encode($analysis),
            'created_at' => current_time('mysql')
        );

        $this->database->insert('pattern_analysis', $data);
    }

    /**
     * Analyze new repost when scraped
     *
     * @param object $repost Repost object.
     */
    public function analyze_new_repost($repost) {
        // Clear cache to ensure fresh analysis
        $this->cache = array();
        
        // Trigger analysis for the source account
        $this->analyze_patterns($repost->source_handle, 1000);
    }

    /**
     * AJAX handler for pattern analysis
     */
    public function ajax_analyze_patterns() {
        check_ajax_referer('xelite_pattern_analysis_nonce', 'nonce');
        
        if (!current_user_can('manage_options')) {
            wp_send_json_error('Insufficient permissions');
        }

        $source_handle = sanitize_text_field($_POST['source_handle'] ?? '');
        $limit = intval($_POST['limit'] ?? 1000);

        $analysis = $this->analyze_patterns($source_handle, $limit);
        
        wp_send_json_success($analysis);
    }

    /**
     * AJAX handler for pattern insights
     */
    public function ajax_get_pattern_insights() {
        check_ajax_referer('xelite_pattern_analysis_nonce', 'nonce');
        
        $source_handle = sanitize_text_field($_POST['source_handle'] ?? '');
        
        // Get latest analysis from database
        $query = "SELECT analysis_data FROM {$this->database->get_table_name('pattern_analysis')} 
                  WHERE source_handle = %s 
                  ORDER BY created_at DESC 
                  LIMIT 1";
        
        $result = $this->database->get_row($query, array($source_handle ?: 'all'));
        
        if ($result) {
            $analysis = json_decode($result->analysis_data, true);
            wp_send_json_success($analysis);
        } else {
            wp_send_json_error('No analysis data found');
        }
    }

    /**
     * Helper methods
     */

    private function get_top_sources($source_counts, $limit) {
        arsort($source_counts);
        return array_slice($source_counts, 0, $limit, true);
    }

    private function get_top_items($items, $limit) {
        arsort($items);
        $top_items = array();
        $count = 0;
        foreach ($items as $key => $value) {
            if ($count >= $limit) break;
            $top_items[] = array('key' => $key, 'value' => $value);
            $count++;
        }
        return $top_items;
    }

    private function get_top_hours($hourly_engagement, $limit) {
        $top_hours = array();
        for ($i = 0; $i < 24; $i++) {
            $top_hours[] = array('key' => $i, 'value' => $hourly_engagement[$i]);
        }
        usort($top_hours, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        return array_slice($top_hours, 0, $limit);
    }

    private function get_top_days($daily_engagement, $limit) {
        $day_names = array('Sunday', 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday', 'Saturday');
        $top_days = array();
        for ($i = 0; $i < 7; $i++) {
            $top_days[] = array('key' => $i, 'name' => $day_names[$i], 'value' => $daily_engagement[$i]);
        }
        usort($top_days, function($a, $b) {
            return $b['value'] <=> $a['value'];
        });
        return array_slice($top_days, 0, $limit);
    }

    private function get_top_words($word_frequency, $limit) {
        $words = array();
        foreach ($word_frequency as $word => $data) {
            $words[] = array(
                'word' => $word,
                'count' => $data['count'],
                'avg_engagement' => $data['count'] > 0 ? $data['engagement'] / $data['count'] : 0
            );
        }
        usort($words, function($a, $b) {
            return $b['avg_engagement'] <=> $a['avg_engagement'];
        });
        return array_slice($words, 0, $limit);
    }

    private function get_top_phrases($phrase_patterns, $limit) {
        $phrases = array();
        foreach ($phrase_patterns as $phrase => $data) {
            $phrases[] = array(
                'phrase' => $phrase,
                'count' => $data['count'],
                'avg_engagement' => $data['count'] > 0 ? $data['engagement'] / $data['count'] : 0
            );
        }
        usort($phrases, function($a, $b) {
            return $b['avg_engagement'] <=> $a['avg_engagement'];
        });
        return array_slice($phrases, 0, $limit);
    }

    private function identify_engagement_predictors($reposts) {
        $predictors = array();
        
        // Analyze various factors
        $factors = array(
            'has_hashtags' => function($text) { return preg_match('/#\w+/', $text) ? 1 : 0; },
            'has_emojis' => function($text) { return preg_match('/[\x{1F600}-\x{1F64F}]|[\x{1F300}-\x{1F5FF}]|[\x{1F680}-\x{1F6FF}]|[\x{1F1E0}-\x{1F1FF}]|[\x{2600}-\x{26FF}]|[\x{2700}-\x{27BF}]/u', $text) ? 1 : 0; },
            'has_urls' => function($text) { return preg_match('/https?:\/\/[^\s]+/', $text) ? 1 : 0; },
            'has_mentions' => function($text) { return preg_match('/@\w+/', $text) ? 1 : 0; },
            'is_question' => function($text) { return preg_match('/\?/', $text) ? 1 : 0; },
            'has_call_to_action' => function($text) { return preg_match('/(check|read|follow|share|retweet|like|comment)/', $text) ? 1 : 0; }
        );

        foreach ($factors as $factor_name => $factor_function) {
            $with_factor = array();
            $without_factor = array();

            foreach ($reposts as $repost) {
                $engagement = intval($repost->repost_count);
                if ($factor_function($repost->original_text)) {
                    $with_factor[] = $engagement;
                } else {
                    $without_factor[] = $engagement;
                }
            }

            if (!empty($with_factor) && !empty($without_factor)) {
                $avg_with = array_sum($with_factor) / count($with_factor);
                $avg_without = array_sum($without_factor) / count($without_factor);
                
                $predictors[$factor_name] = array(
                    'avg_engagement_with' => $avg_with,
                    'avg_engagement_without' => $avg_without,
                    'impact' => $avg_with - $avg_without,
                    'frequency' => count($with_factor) / count($reposts)
                );
            }
        }

        return $predictors;
    }

    private function get_hour_name($hour) {
        $hour_names = array(
            0 => '12 AM', 1 => '1 AM', 2 => '2 AM', 3 => '3 AM', 4 => '4 AM', 5 => '5 AM',
            6 => '6 AM', 7 => '7 AM', 8 => '8 AM', 9 => '9 AM', 10 => '10 AM', 11 => '11 AM',
            12 => '12 PM', 13 => '1 PM', 14 => '2 PM', 15 => '3 PM', 16 => '4 PM', 17 => '5 PM',
            18 => '6 PM', 19 => '7 PM', 20 => '8 PM', 21 => '9 PM', 22 => '10 PM', 23 => '11 PM'
        );
        return $hour_names[$hour] ?? 'Unknown';
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
            $this->logger->log($level, "[Pattern Analyzer] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine Pattern Analyzer] {$message}");
        }
    }
} 