<?php
/**
 * Pattern Visualizer for Repost Intelligence
 *
 * Transforms pattern analysis data into visual representations
 * for dashboard charts and graphs.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Pattern Visualizer Class
 */
class XeliteRepostEngine_Pattern_Visualizer extends XeliteRepostEngine_Abstract_Base {

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
     * Chart color schemes
     *
     * @var array
     */
    private $color_schemes;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_Pattern_Analyzer $pattern_analyzer Pattern analyzer service.
     * @param XeliteRepostEngine_Database $database Database service.
     * @param XeliteRepostEngine_Logger $logger Logger service.
     */
    public function __construct($pattern_analyzer, $database, $logger = null) {
        parent::__construct();
        
        $this->pattern_analyzer = $pattern_analyzer;
        $this->database = $database;
        $this->logger = $logger;
        
        $this->init_color_schemes();
    }

    /**
     * Initialize the class
     */
    protected function init() {
        $this->init_hooks();
    }

    /**
     * Initialize color schemes for charts
     */
    private function init_color_schemes() {
        $this->color_schemes = array(
            'primary' => array(
                '#3B82F6', '#1D4ED8', '#1E40AF', '#1E3A8A', '#172554',
                '#06B6D4', '#0891B2', '#0E7490', '#155E75', '#164E63'
            ),
            'success' => array(
                '#10B981', '#059669', '#047857', '#065F46', '#064E3B',
                '#84CC16', '#65A30D', '#4D7C0F', '#3F6212', '#365314'
            ),
            'warning' => array(
                '#F59E0B', '#D97706', '#B45309', '#92400E', '#78350F',
                '#F97316', '#EA580C', '#C2410C', '#9A3412', '#7C2D12'
            ),
            'danger' => array(
                '#EF4444', '#DC2626', '#B91C1C', '#991B1B', '#7F1D1D',
                '#EC4899', '#DB2777', '#BE185D', '#9D174D', '#831843'
            ),
            'neutral' => array(
                '#6B7280', '#4B5563', '#374151', '#1F2937', '#111827',
                '#9CA3AF', '#6B7280', '#4B5563', '#374151', '#1F2937'
            )
        );
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_xelite_get_chart_data', array($this, 'ajax_get_chart_data'));
        add_action('wp_ajax_xelite_get_pattern_comparison', array($this, 'ajax_get_pattern_comparison'));
        add_action('wp_ajax_xelite_get_top_patterns', array($this, 'ajax_get_top_patterns'));
        add_action('wp_ajax_xelite_get_pattern_score', array($this, 'ajax_get_pattern_score'));
    }

    /**
     * Generate chart data for length patterns
     *
     * @param array $analysis Pattern analysis data.
     * @return array Chart data.
     */
    public function generate_length_chart_data($analysis) {
        if (!isset($analysis['length_patterns'])) {
            return array();
        }

        $length_data = $analysis['length_patterns'];
        $categories = array('short', 'medium', 'long');
        $labels = array('Short (0-100)', 'Medium (101-200)', 'Long (201-280)');

        $datasets = array(
            array(
                'label' => 'Number of Reposts',
                'data' => array(),
                'backgroundColor' => array_slice($this->color_schemes['primary'], 0, 3),
                'borderColor' => array_slice($this->color_schemes['primary'], 0, 3),
                'borderWidth' => 2
            ),
            array(
                'label' => 'Average Engagement',
                'data' => array(),
                'backgroundColor' => array_slice($this->color_schemes['success'], 0, 3),
                'borderColor' => array_slice($this->color_schemes['success'], 0, 3),
                'borderWidth' => 2,
                'yAxisID' => 'y1'
            )
        );

        foreach ($categories as $category) {
            $datasets[0]['data'][] = $length_data['category_distribution'][$category] ?? 0;
            $datasets[1]['data'][] = round($length_data['category_avg_engagement'][$category] ?? 0, 2);
        }

        return array(
            'type' => 'bar',
            'data' => array(
                'labels' => $labels,
                'datasets' => $datasets
            ),
            'options' => array(
                'responsive' => true,
                'scales' => array(
                    'y' => array(
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'left',
                        'title' => array('display' => true, 'text' => 'Number of Reposts')
                    ),
                    'y1' => array(
                        'type' => 'linear',
                        'display' => true,
                        'position' => 'right',
                        'title' => array('display' => true, 'text' => 'Average Engagement'),
                        'grid' => array('drawOnChartArea' => false)
                    )
                ),
                'plugins' => array(
                    'title' => array(
                        'display' => true,
                        'text' => 'Tweet Length vs Engagement'
                    ),
                    'legend' => array('display' => true)
                )
            )
        );
    }

    /**
     * Generate chart data for tone patterns
     *
     * @param array $analysis Pattern analysis data.
     * @return array Chart data.
     */
    public function generate_tone_chart_data($analysis) {
        if (!isset($analysis['tone_patterns'])) {
            return array();
        }

        $tone_data = $analysis['tone_patterns'];
        $tones = array_keys($tone_data['tone_distribution']);
        $labels = array_map('ucfirst', $tones);

        $datasets = array(
            array(
                'label' => 'Tone Distribution',
                'data' => array_values($tone_data['tone_distribution']),
                'backgroundColor' => array_slice($this->color_schemes['primary'], 0, count($tones)),
                'borderColor' => array_slice($this->color_schemes['primary'], 0, count($tones)),
                'borderWidth' => 2
            )
        );

        return array(
            'type' => 'doughnut',
            'data' => array(
                'labels' => $labels,
                'datasets' => $datasets
            ),
            'options' => array(
                'responsive' => true,
                'plugins' => array(
                    'title' => array(
                        'display' => true,
                        'text' => 'Tone Distribution'
                    ),
                    'legend' => array('display' => true),
                    'tooltip' => array(
                        'callbacks' => array(
                            'label' => 'function(context) { return context.label + ": " + context.parsed + " reposts"; }'
                        )
                    )
                )
            )
        );
    }

    /**
     * Generate chart data for time patterns
     *
     * @param array $analysis Pattern analysis data.
     * @return array Chart data.
     */
    public function generate_time_chart_data($analysis) {
        if (!isset($analysis['time_patterns'])) {
            return array();
        }

        $time_data = $analysis['time_patterns'];
        $hours = range(0, 23);
        $hour_labels = array_map(array($this, 'get_hour_label'), $hours);

        $datasets = array(
            array(
                'label' => 'Hourly Engagement',
                'data' => array_values($time_data['hourly_avg_engagement']),
                'backgroundColor' => 'rgba(59, 130, 246, 0.2)',
                'borderColor' => '#3B82F6',
                'borderWidth' => 2,
                'fill' => true,
                'tension' => 0.4
            )
        );

        return array(
            'type' => 'line',
            'data' => array(
                'labels' => $hour_labels,
                'datasets' => $datasets
            ),
            'options' => array(
                'responsive' => true,
                'scales' => array(
                    'y' => array(
                        'beginAtZero' => true,
                        'title' => array('display' => true, 'text' => 'Average Engagement')
                    ),
                    'x' => array(
                        'title' => array('display' => true, 'text' => 'Hour of Day')
                    )
                ),
                'plugins' => array(
                    'title' => array(
                        'display' => true,
                        'text' => 'Hourly Engagement Pattern'
                    ),
                    'legend' => array('display' => true)
                )
            )
        );
    }

    /**
     * Generate chart data for format patterns
     *
     * @param array $analysis Pattern analysis data.
     * @return array Chart data.
     */
    public function generate_format_chart_data($analysis) {
        if (!isset($analysis['format_patterns'])) {
            return array();
        }

        $format_data = $analysis['format_patterns'];
        $formats = array('hashtags', 'emojis', 'urls', 'mentions');
        $labels = array('Hashtags', 'Emojis', 'URLs', 'Mentions');

        $datasets = array(
            array(
                'label' => 'Optimal Count',
                'data' => array(),
                'backgroundColor' => array_slice($this->color_schemes['success'], 0, count($formats)),
                'borderColor' => array_slice($this->color_schemes['success'], 0, count($formats)),
                'borderWidth' => 2
            ),
            array(
                'label' => 'Average Engagement',
                'data' => array(),
                'backgroundColor' => array_slice($this->color_schemes['primary'], 0, count($formats)),
                'borderColor' => array_slice($this->color_schemes['primary'], 0, count($formats)),
                'borderWidth' => 2
            )
        );

        foreach ($formats as $format) {
            $datasets[0]['data'][] = $format_data[$format]['optimal_count'] ?? 0;
            $datasets[1]['data'][] = round($format_data[$format]['avg_engagement'] ?? 0, 2);
        }

        return array(
            'type' => 'bar',
            'data' => array(
                'labels' => $labels,
                'datasets' => $datasets
            ),
            'options' => array(
                'responsive' => true,
                'scales' => array(
                    'y' => array(
                        'beginAtZero' => true,
                        'title' => array('display' => true, 'text' => 'Count / Engagement')
                    )
                ),
                'plugins' => array(
                    'title' => array(
                        'display' => true,
                        'text' => 'Format Element Analysis'
                    ),
                    'legend' => array('display' => true)
                )
            )
        );
    }

    /**
     * Generate chart data for engagement correlation
     *
     * @param array $analysis Pattern analysis data.
     * @return array Chart data.
     */
    public function generate_correlation_chart_data($analysis) {
        if (!isset($analysis['engagement_correlation'])) {
            return array();
        }

        $correlation_data = $analysis['engagement_correlation'];
        $metrics = array_keys($correlation_data['correlations']);
        $labels = array_map('ucfirst', $metrics);

        $datasets = array(
            array(
                'label' => 'Correlation Coefficient',
                'data' => array_values($correlation_data['correlations']),
                'backgroundColor' => array_map(function($value) {
                    return $value > 0 ? '#10B981' : '#EF4444';
                }, array_values($correlation_data['correlations'])),
                'borderColor' => array_map(function($value) {
                    return $value > 0 ? '#059669' : '#DC2626';
                }, array_values($correlation_data['correlations'])),
                'borderWidth' => 2
            )
        );

        return array(
            'type' => 'bar',
            'data' => array(
                'labels' => $labels,
                'datasets' => $datasets
            ),
            'options' => array(
                'responsive' => true,
                'scales' => array(
                    'y' => array(
                        'min' => -1,
                        'max' => 1,
                        'title' => array('display' => true, 'text' => 'Correlation Coefficient')
                    )
                ),
                'plugins' => array(
                    'title' => array(
                        'display' => true,
                        'text' => 'Engagement Correlation Analysis'
                    ),
                    'legend' => array('display' => true)
                )
            )
        );
    }

    /**
     * Generate top performing patterns data
     *
     * @param array $analysis Pattern analysis data.
     * @return array Top patterns data.
     */
    public function generate_top_patterns_data($analysis) {
        $top_patterns = array();

        // Top tones
        if (isset($analysis['tone_patterns']['top_effective_tones'])) {
            $top_patterns['tones'] = array_slice($analysis['tone_patterns']['top_effective_tones'], 0, 5);
        }

        // Top words
        if (isset($analysis['content_patterns']['top_words'])) {
            $top_patterns['words'] = array_slice($analysis['content_patterns']['top_words'], 0, 10);
        }

        // Top phrases
        if (isset($analysis['content_patterns']['top_phrases'])) {
            $top_patterns['phrases'] = array_slice($analysis['content_patterns']['top_phrases'], 0, 5);
        }

        // Best times
        if (isset($analysis['time_patterns']['best_hours'])) {
            $top_patterns['times'] = array_map(function($hour) {
                return array(
                    'time' => $this->get_hour_label($hour['key']),
                    'engagement' => round($hour['value'], 2)
                );
            }, array_slice($analysis['time_patterns']['best_hours'], 0, 3));
        }

        return $top_patterns;
    }

    /**
     * Generate pattern comparison data
     *
     * @param array $analysis1 First analysis data.
     * @param array $analysis2 Second analysis data.
     * @param string $label1 First label.
     * @param string $label2 Second label.
     * @return array Comparison data.
     */
    public function generate_pattern_comparison($analysis1, $analysis2, $label1 = 'Account 1', $label2 = 'Account 2') {
        $comparison = array();

        // Compare tone effectiveness
        if (isset($analysis1['tone_patterns']['tone_effectiveness']) && isset($analysis2['tone_patterns']['tone_effectiveness'])) {
            $tones = array_unique(array_merge(
                array_keys($analysis1['tone_patterns']['tone_effectiveness']),
                array_keys($analysis2['tone_patterns']['tone_effectiveness'])
            ));

            $comparison['tones'] = array();
            foreach ($tones as $tone) {
                $comparison['tones'][] = array(
                    'tone' => ucfirst($tone),
                    $label1 => round($analysis1['tone_patterns']['tone_effectiveness'][$tone] ?? 0, 2),
                    $label2 => round($analysis2['tone_patterns']['tone_effectiveness'][$tone] ?? 0, 2)
                );
            }
        }

        // Compare length patterns
        if (isset($analysis1['length_patterns']['category_avg_engagement']) && isset($analysis2['length_patterns']['category_avg_engagement'])) {
            $categories = array('short', 'medium', 'long');
            $comparison['lengths'] = array();
            foreach ($categories as $category) {
                $comparison['lengths'][] = array(
                    'category' => ucfirst($category),
                    $label1 => round($analysis1['length_patterns']['category_avg_engagement'][$category] ?? 0, 2),
                    $label2 => round($analysis2['length_patterns']['category_avg_engagement'][$category] ?? 0, 2)
                );
            }
        }

        return $comparison;
    }

    /**
     * Calculate pattern effectiveness score
     *
     * @param array $analysis Pattern analysis data.
     * @return array Score data.
     */
    public function calculate_pattern_score($analysis) {
        $score = 0;
        $max_score = 100;
        $factors = array();

        // Length score (25 points)
        if (isset($analysis['length_patterns']['optimal_length_range'])) {
            $optimal_range = $analysis['length_patterns']['optimal_length_range'];
            $avg_length = $analysis['summary']['avg_length'] ?? 0;
            
            if ($avg_length >= $optimal_range['min'] && $avg_length <= $optimal_range['max']) {
                $length_score = 25;
            } else {
                $length_score = max(0, 25 - abs($avg_length - ($optimal_range['min'] + $optimal_range['max']) / 2) / 10);
            }
            $score += $length_score;
            $factors['length'] = round($length_score, 2);
        }

        // Tone score (25 points)
        if (isset($analysis['tone_patterns']['top_effective_tones'])) {
            $top_tone = $analysis['tone_patterns']['top_effective_tones'][0] ?? null;
            if ($top_tone) {
                $tone_score = min(25, $top_tone['value'] * 10);
                $score += $tone_score;
                $factors['tone'] = round($tone_score, 2);
            }
        }

        // Format score (25 points)
        if (isset($analysis['format_patterns'])) {
            $format_score = 0;
            $format_count = 0;
            foreach ($analysis['format_patterns'] as $format => $data) {
                if (isset($data['avg_engagement']) && $data['avg_engagement'] > 0) {
                    $format_score += min(8.33, $data['avg_engagement'] * 2);
                    $format_count++;
                }
            }
            if ($format_count > 0) {
                $format_score = $format_score / $format_count * 3;
            }
            $score += $format_score;
            $factors['format'] = round($format_score, 2);
        }

        // Engagement score (25 points)
        if (isset($analysis['summary']['avg_engagement_per_repost'])) {
            $engagement = $analysis['summary']['avg_engagement_per_repost'] ?? 0;
            $engagement_score = min(25, $engagement * 2);
            $score += $engagement_score;
            $factors['engagement'] = round($engagement_score, 2);
        }

        $score = min($max_score, max(0, $score));

        return array(
            'total_score' => round($score, 2),
            'max_score' => $max_score,
            'percentage' => round(($score / $max_score) * 100, 1),
            'grade' => $this->get_score_grade($score),
            'factors' => $factors,
            'recommendations' => $this->get_score_recommendations($factors, $score)
        );
    }

    /**
     * Get score grade
     *
     * @param float $score Score value.
     * @return string Grade.
     */
    private function get_score_grade($score) {
        if ($score >= 90) return 'A+';
        if ($score >= 85) return 'A';
        if ($score >= 80) return 'A-';
        if ($score >= 75) return 'B+';
        if ($score >= 70) return 'B';
        if ($score >= 65) return 'B-';
        if ($score >= 60) return 'C+';
        if ($score >= 55) return 'C';
        if ($score >= 50) return 'C-';
        if ($score >= 45) return 'D+';
        if ($score >= 40) return 'D';
        return 'F';
    }

    /**
     * Get score recommendations
     *
     * @param array $factors Score factors.
     * @param float $total_score Total score.
     * @return array Recommendations.
     */
    private function get_score_recommendations($factors, $total_score) {
        $recommendations = array();

        if ($total_score < 60) {
            $recommendations[] = 'Your content strategy needs significant improvement. Focus on the areas with lowest scores.';
        } elseif ($total_score < 80) {
            $recommendations[] = 'Good progress! Continue optimizing the areas with room for improvement.';
        } else {
            $recommendations[] = 'Excellent content strategy! Maintain these high-performing patterns.';
        }

        // Specific recommendations based on factors
        if (isset($factors['length']) && $factors['length'] < 15) {
            $recommendations[] = 'Optimize tweet length for better engagement.';
        }

        if (isset($factors['tone']) && $factors['tone'] < 15) {
            $recommendations[] = 'Experiment with different tone types to improve engagement.';
        }

        if (isset($factors['format']) && $factors['format'] < 15) {
            $recommendations[] = 'Use format elements (hashtags, emojis) more effectively.';
        }

        if (isset($factors['engagement']) && $factors['engagement'] < 15) {
            $recommendations[] = 'Focus on creating more engaging content that encourages reposts.';
        }

        return $recommendations;
    }

    /**
     * Normalize data for charts
     *
     * @param array $data Raw data.
     * @param string $method Normalization method.
     * @return array Normalized data.
     */
    public function normalize_data($data, $method = 'min_max') {
        if (empty($data)) {
            return $data;
        }

        $values = array_values($data);
        $min = min($values);
        $max = max($values);

        if ($max == $min) {
            return array_fill_keys(array_keys($data), 1);
        }

        $normalized = array();
        foreach ($data as $key => $value) {
            switch ($method) {
                case 'min_max':
                    $normalized[$key] = ($value - $min) / ($max - $min);
                    break;
                case 'z_score':
                    $mean = array_sum($values) / count($values);
                    $std = sqrt(array_sum(array_map(function($x) use ($mean) {
                        return pow($x - $mean, 2);
                    }, $values)) / count($values));
                    $normalized[$key] = ($value - $mean) / $std;
                    break;
                case 'decimal':
                    $normalized[$key] = $value / $max;
                    break;
                default:
                    $normalized[$key] = $value;
            }
        }

        return $normalized;
    }

    /**
     * Generate comprehensive dashboard data
     *
     * @param string|null $source_handle Source handle.
     * @return array Dashboard data.
     */
    public function generate_dashboard_data($source_handle = null) {
        $analysis = $this->pattern_analyzer->analyze_patterns($source_handle, 1000);

        if (empty($analysis)) {
            return array();
        }

        return array(
            'charts' => array(
                'length' => $this->generate_length_chart_data($analysis),
                'tone' => $this->generate_tone_chart_data($analysis),
                'time' => $this->generate_time_chart_data($analysis),
                'format' => $this->generate_format_chart_data($analysis),
                'correlation' => $this->generate_correlation_chart_data($analysis)
            ),
            'top_patterns' => $this->generate_top_patterns_data($analysis),
            'score' => $this->calculate_pattern_score($analysis),
            'summary' => $analysis['summary'] ?? array(),
            'recommendations' => $analysis['recommendations'] ?? array()
        );
    }

    /**
     * AJAX handler for chart data
     */
    public function ajax_get_chart_data() {
        check_ajax_referer('xelite_pattern_visualizer_nonce', 'nonce');
        
        $source_handle = sanitize_text_field($_POST['source_handle'] ?? '');
        $chart_type = sanitize_text_field($_POST['chart_type'] ?? '');
        
        $analysis = $this->pattern_analyzer->analyze_patterns($source_handle, 1000);
        
        if (empty($analysis)) {
            wp_send_json_error('No analysis data available');
        }

        $chart_data = array();
        switch ($chart_type) {
            case 'length':
                $chart_data = $this->generate_length_chart_data($analysis);
                break;
            case 'tone':
                $chart_data = $this->generate_tone_chart_data($analysis);
                break;
            case 'time':
                $chart_data = $this->generate_time_chart_data($analysis);
                break;
            case 'format':
                $chart_data = $this->generate_format_chart_data($analysis);
                break;
            case 'correlation':
                $chart_data = $this->generate_correlation_chart_data($analysis);
                break;
            default:
                wp_send_json_error('Invalid chart type');
        }

        wp_send_json_success($chart_data);
    }

    /**
     * AJAX handler for pattern comparison
     */
    public function ajax_get_pattern_comparison() {
        check_ajax_referer('xelite_pattern_visualizer_nonce', 'nonce');
        
        $source_handle1 = sanitize_text_field($_POST['source_handle1'] ?? '');
        $source_handle2 = sanitize_text_field($_POST['source_handle2'] ?? '');
        
        $analysis1 = $this->pattern_analyzer->analyze_patterns($source_handle1, 1000);
        $analysis2 = $this->pattern_analyzer->analyze_patterns($source_handle2, 1000);
        
        if (empty($analysis1) || empty($analysis2)) {
            wp_send_json_error('Insufficient data for comparison');
        }

        $comparison = $this->generate_pattern_comparison($analysis1, $analysis2, $source_handle1, $source_handle2);
        wp_send_json_success($comparison);
    }

    /**
     * AJAX handler for top patterns
     */
    public function ajax_get_top_patterns() {
        check_ajax_referer('xelite_pattern_visualizer_nonce', 'nonce');
        
        $source_handle = sanitize_text_field($_POST['source_handle'] ?? '');
        $analysis = $this->pattern_analyzer->analyze_patterns($source_handle, 1000);
        
        if (empty($analysis)) {
            wp_send_json_error('No analysis data available');
        }

        $top_patterns = $this->generate_top_patterns_data($analysis);
        wp_send_json_success($top_patterns);
    }

    /**
     * AJAX handler for pattern score
     */
    public function ajax_get_pattern_score() {
        check_ajax_referer('xelite_pattern_visualizer_nonce', 'nonce');
        
        $source_handle = sanitize_text_field($_POST['source_handle'] ?? '');
        $analysis = $this->pattern_analyzer->analyze_patterns($source_handle, 1000);
        
        if (empty($analysis)) {
            wp_send_json_error('No analysis data available');
        }

        $score = $this->calculate_pattern_score($analysis);
        wp_send_json_success($score);
    }

    /**
     * Helper methods
     */

    private function get_hour_label($hour) {
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
            $this->logger->log($level, "[Pattern Visualizer] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine Pattern Visualizer] {$message}");
        }
    }
} 