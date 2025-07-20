<?php
/**
 * Test Pattern Visualizer
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Test Pattern Visualizer Class
 */
class TestXeliteRepostEngine_Pattern_Visualizer extends TestCase {

    /**
     * Pattern visualizer instance
     *
     * @var XeliteRepostEngine_Pattern_Visualizer
     */
    private $visualizer;

    /**
     * Mock pattern analyzer
     *
     * @var PHPUnit\Framework\MockObject\MockObject
     */
    private $mock_pattern_analyzer;

    /**
     * Mock database
     *
     * @var PHPUnit\Framework\MockObject\MockObject
     */
    private $mock_database;

    /**
     * Mock logger
     *
     * @var PHPUnit\Framework\MockObject\MockObject
     */
    private $mock_logger;

    /**
     * Sample analysis data
     *
     * @var array
     */
    private $sample_analysis;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void {
        parent::setUp();

        // Create mocks
        $this->mock_pattern_analyzer = $this->createMock('XeliteRepostEngine_Pattern_Analyzer');
        $this->mock_database = $this->createMock('XeliteRepostEngine_Database');
        $this->mock_logger = $this->createMock('XeliteRepostEngine_Logger');

        // Create visualizer instance
        $this->visualizer = new XeliteRepostEngine_Pattern_Visualizer(
            $this->mock_pattern_analyzer,
            $this->mock_database,
            $this->mock_logger
        );

        // Sample analysis data
        $this->sample_analysis = array(
            'summary' => array(
                'total_reposts' => 150,
                'avg_length' => 120,
                'avg_engagement_per_repost' => 45.5
            ),
            'length_patterns' => array(
                'category_distribution' => array(
                    'short' => 60,
                    'medium' => 70,
                    'long' => 20
                ),
                'category_avg_engagement' => array(
                    'short' => 42.3,
                    'medium' => 48.7,
                    'long' => 35.2
                ),
                'optimal_length_range' => array(
                    'min' => 100,
                    'max' => 150
                )
            ),
            'tone_patterns' => array(
                'tone_distribution' => array(
                    'informative' => 45,
                    'conversational' => 35,
                    'inspirational' => 20
                ),
                'tone_effectiveness' => array(
                    'informative' => 8.5,
                    'conversational' => 7.2,
                    'inspirational' => 6.8
                ),
                'top_effective_tones' => array(
                    array('key' => 'informative', 'value' => 8.5),
                    array('key' => 'conversational', 'value' => 7.2),
                    array('key' => 'inspirational', 'value' => 6.8)
                )
            ),
            'time_patterns' => array(
                'hourly_avg_engagement' => array(
                    9 => 52.3, 10 => 48.7, 11 => 45.2, 12 => 42.1,
                    13 => 38.9, 14 => 41.2, 15 => 44.5, 16 => 47.8,
                    17 => 50.1, 18 => 53.4, 19 => 49.8, 20 => 46.2
                ),
                'best_hours' => array(
                    array('key' => 18, 'value' => 53.4),
                    array('key' => 9, 'value' => 52.3),
                    array('key' => 17, 'value' => 50.1)
                )
            ),
            'format_patterns' => array(
                'hashtags' => array(
                    'optimal_count' => 2,
                    'avg_engagement' => 8.5
                ),
                'emojis' => array(
                    'optimal_count' => 1,
                    'avg_engagement' => 7.2
                ),
                'urls' => array(
                    'optimal_count' => 1,
                    'avg_engagement' => 6.8
                ),
                'mentions' => array(
                    'optimal_count' => 0,
                    'avg_engagement' => 5.4
                )
            ),
            'engagement_correlation' => array(
                'correlations' => array(
                    'length' => 0.45,
                    'hashtags' => 0.32,
                    'emojis' => 0.28,
                    'mentions' => -0.15,
                    'urls' => 0.18
                )
            ),
            'content_patterns' => array(
                'top_words' => array(
                    array('key' => 'growth', 'value' => 25),
                    array('key' => 'success', 'value' => 22),
                    array('key' => 'business', 'value' => 20),
                    array('key' => 'tips', 'value' => 18),
                    array('key' => 'strategy', 'value' => 16)
                ),
                'top_phrases' => array(
                    array('key' => 'business growth', 'value' => 12),
                    array('key' => 'success tips', 'value' => 10),
                    array('key' => 'growth strategy', 'value' => 8)
                )
            ),
            'recommendations' => array(
                'Focus on medium-length tweets (100-150 characters)',
                'Use informative tone for better engagement',
                'Post during peak hours (9 AM, 5-6 PM)',
                'Include 2 hashtags and 1 emoji for optimal results'
            )
        );
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf('XeliteRepostEngine_Pattern_Visualizer', $this->visualizer);
    }

    /**
     * Test length chart data generation
     */
    public function test_generate_length_chart_data() {
        $chart_data = $this->visualizer->generate_length_chart_data($this->sample_analysis);

        $this->assertIsArray($chart_data);
        $this->assertEquals('bar', $chart_data['type']);
        $this->assertArrayHasKey('data', $chart_data);
        $this->assertArrayHasKey('options', $chart_data);
        $this->assertArrayHasKey('labels', $chart_data['data']);
        $this->assertArrayHasKey('datasets', $chart_data['data']);

        // Check labels
        $expected_labels = array('Short (0-100)', 'Medium (101-200)', 'Long (201-280)');
        $this->assertEquals($expected_labels, $chart_data['data']['labels']);

        // Check datasets
        $this->assertCount(2, $chart_data['data']['datasets']);
        $this->assertEquals('Number of Reposts', $chart_data['data']['datasets'][0]['label']);
        $this->assertEquals('Average Engagement', $chart_data['data']['datasets'][1]['label']);

        // Check data values
        $this->assertEquals(array(60, 70, 20), $chart_data['data']['datasets'][0]['data']);
        $this->assertEquals(array(42.3, 48.7, 35.2), $chart_data['data']['datasets'][1]['data']);
    }

    /**
     * Test tone chart data generation
     */
    public function test_generate_tone_chart_data() {
        $chart_data = $this->visualizer->generate_tone_chart_data($this->sample_analysis);

        $this->assertIsArray($chart_data);
        $this->assertEquals('doughnut', $chart_data['type']);
        $this->assertArrayHasKey('data', $chart_data);
        $this->assertArrayHasKey('options', $chart_data);

        // Check labels
        $expected_labels = array('Informative', 'Conversational', 'Inspirational');
        $this->assertEquals($expected_labels, $chart_data['data']['labels']);

        // Check data values
        $this->assertEquals(array(45, 35, 20), $chart_data['data']['datasets'][0]['data']);
    }

    /**
     * Test time chart data generation
     */
    public function test_generate_time_chart_data() {
        $chart_data = $this->visualizer->generate_time_chart_data($this->sample_analysis);

        $this->assertIsArray($chart_data);
        $this->assertEquals('line', $chart_data['type']);
        $this->assertArrayHasKey('data', $chart_data);
        $this->assertArrayHasKey('options', $chart_data);

        // Check that we have 24 hours of data
        $this->assertCount(24, $chart_data['data']['labels']);
        $this->assertCount(24, $chart_data['data']['datasets'][0]['data']);

        // Check some specific values
        $this->assertEquals('9 AM', $chart_data['data']['labels'][9]);
        $this->assertEquals('6 PM', $chart_data['data']['labels'][18]);
        $this->assertEquals(52.3, $chart_data['data']['datasets'][0]['data'][9]);
        $this->assertEquals(53.4, $chart_data['data']['datasets'][0]['data'][18]);
    }

    /**
     * Test format chart data generation
     */
    public function test_generate_format_chart_data() {
        $chart_data = $this->visualizer->generate_format_chart_data($this->sample_analysis);

        $this->assertIsArray($chart_data);
        $this->assertEquals('bar', $chart_data['type']);
        $this->assertArrayHasKey('data', $chart_data);
        $this->assertArrayHasKey('options', $chart_data);

        // Check labels
        $expected_labels = array('Hashtags', 'Emojis', 'URLs', 'Mentions');
        $this->assertEquals($expected_labels, $chart_data['data']['labels']);

        // Check datasets
        $this->assertCount(2, $chart_data['data']['datasets']);
        $this->assertEquals('Optimal Count', $chart_data['data']['datasets'][0]['label']);
        $this->assertEquals('Average Engagement', $chart_data['data']['datasets'][1]['label']);

        // Check data values
        $this->assertEquals(array(2, 1, 1, 0), $chart_data['data']['datasets'][0]['data']);
        $this->assertEquals(array(8.5, 7.2, 6.8, 5.4), $chart_data['data']['datasets'][1]['data']);
    }

    /**
     * Test correlation chart data generation
     */
    public function test_generate_correlation_chart_data() {
        $chart_data = $this->visualizer->generate_correlation_chart_data($this->sample_analysis);

        $this->assertIsArray($chart_data);
        $this->assertEquals('bar', $chart_data['type']);
        $this->assertArrayHasKey('data', $chart_data);
        $this->assertArrayHasKey('options', $chart_data);

        // Check labels
        $expected_labels = array('Length', 'Hashtags', 'Emojis', 'Mentions', 'Urls');
        $this->assertEquals($expected_labels, $chart_data['data']['labels']);

        // Check data values
        $this->assertEquals(array(0.45, 0.32, 0.28, -0.15, 0.18), $chart_data['data']['datasets'][0]['data']);

        // Check color coding (positive vs negative correlations)
        $colors = $chart_data['data']['datasets'][0]['backgroundColor'];
        $this->assertEquals('#10B981', $colors[0]); // positive
        $this->assertEquals('#10B981', $colors[1]); // positive
        $this->assertEquals('#10B981', $colors[2]); // positive
        $this->assertEquals('#EF4444', $colors[3]); // negative
        $this->assertEquals('#10B981', $colors[4]); // positive
    }

    /**
     * Test top patterns data generation
     */
    public function test_generate_top_patterns_data() {
        $top_patterns = $this->visualizer->generate_top_patterns_data($this->sample_analysis);

        $this->assertIsArray($top_patterns);
        $this->assertArrayHasKey('tones', $top_patterns);
        $this->assertArrayHasKey('words', $top_patterns);
        $this->assertArrayHasKey('phrases', $top_patterns);
        $this->assertArrayHasKey('times', $top_patterns);

        // Check tones
        $this->assertCount(3, $top_patterns['tones']);
        $this->assertEquals('informative', $top_patterns['tones'][0]['key']);
        $this->assertEquals(8.5, $top_patterns['tones'][0]['value']);

        // Check words
        $this->assertCount(5, $top_patterns['words']);
        $this->assertEquals('growth', $top_patterns['words'][0]['key']);
        $this->assertEquals(25, $top_patterns['words'][0]['value']);

        // Check phrases
        $this->assertCount(3, $top_patterns['phrases']);
        $this->assertEquals('business growth', $top_patterns['phrases'][0]['key']);
        $this->assertEquals(12, $top_patterns['phrases'][0]['value']);

        // Check times
        $this->assertCount(3, $top_patterns['times']);
        $this->assertEquals('6 PM', $top_patterns['times'][0]['time']);
        $this->assertEquals(53.4, $top_patterns['times'][0]['engagement']);
    }

    /**
     * Test pattern comparison generation
     */
    public function test_generate_pattern_comparison() {
        $analysis2 = $this->sample_analysis;
        $analysis2['tone_patterns']['tone_effectiveness']['informative'] = 7.8;
        $analysis2['length_patterns']['category_avg_engagement']['short'] = 38.5;

        $comparison = $this->visualizer->generate_pattern_comparison(
            $this->sample_analysis,
            $analysis2,
            'Account 1',
            'Account 2'
        );

        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('tones', $comparison);
        $this->assertArrayHasKey('lengths', $comparison);

        // Check tone comparison
        $this->assertCount(3, $comparison['tones']);
        $this->assertEquals('Informative', $comparison['tones'][0]['tone']);
        $this->assertEquals(8.5, $comparison['tones'][0]['Account 1']);
        $this->assertEquals(7.8, $comparison['tones'][0]['Account 2']);

        // Check length comparison
        $this->assertCount(3, $comparison['lengths']);
        $this->assertEquals('Short', $comparison['lengths'][0]['category']);
        $this->assertEquals(42.3, $comparison['lengths'][0]['Account 1']);
        $this->assertEquals(38.5, $comparison['lengths'][0]['Account 2']);
    }

    /**
     * Test pattern score calculation
     */
    public function test_calculate_pattern_score() {
        $score = $this->visualizer->calculate_pattern_score($this->sample_analysis);

        $this->assertIsArray($score);
        $this->assertArrayHasKey('total_score', $score);
        $this->assertArrayHasKey('max_score', $score);
        $this->assertArrayHasKey('percentage', $score);
        $this->assertArrayHasKey('grade', $score);
        $this->assertArrayHasKey('factors', $score);
        $this->assertArrayHasKey('recommendations', $score);

        // Check score values
        $this->assertEquals(100, $score['max_score']);
        $this->assertGreaterThan(0, $score['total_score']);
        $this->assertLessThanOrEqual(100, $score['total_score']);
        $this->assertGreaterThan(0, $score['percentage']);
        $this->assertLessThanOrEqual(100, $score['percentage']);

        // Check grade
        $this->assertIsString($score['grade']);
        $this->assertMatchesRegularExpression('/^[A-F][+-]?$/', $score['grade']);

        // Check factors
        $this->assertIsArray($score['factors']);
        $this->assertArrayHasKey('length', $score['factors']);
        $this->assertArrayHasKey('tone', $score['factors']);
        $this->assertArrayHasKey('format', $score['factors']);
        $this->assertArrayHasKey('engagement', $score['factors']);

        // Check recommendations
        $this->assertIsArray($score['recommendations']);
        $this->assertNotEmpty($score['recommendations']);
    }

    /**
     * Test data normalization
     */
    public function test_normalize_data() {
        $data = array('a' => 10, 'b' => 20, 'c' => 30, 'd' => 40);

        // Test min_max normalization
        $normalized = $this->visualizer->normalize_data($data, 'min_max');
        $this->assertIsArray($normalized);
        $this->assertEquals(0, $normalized['a']);
        $this->assertEquals(0.33, round($normalized['b'], 2));
        $this->assertEquals(0.67, round($normalized['c'], 2));
        $this->assertEquals(1, $normalized['d']);

        // Test decimal normalization
        $normalized = $this->visualizer->normalize_data($data, 'decimal');
        $this->assertIsArray($normalized);
        $this->assertEquals(0.25, $normalized['a']);
        $this->assertEquals(0.5, $normalized['b']);
        $this->assertEquals(0.75, $normalized['c']);
        $this->assertEquals(1, $normalized['d']);

        // Test empty data
        $normalized = $this->visualizer->normalize_data(array(), 'min_max');
        $this->assertIsArray($normalized);
        $this->assertEmpty($normalized);
    }

    /**
     * Test dashboard data generation
     */
    public function test_generate_dashboard_data() {
        $this->mock_pattern_analyzer->method('analyze_patterns')
            ->willReturn($this->sample_analysis);

        $dashboard_data = $this->visualizer->generate_dashboard_data('test_handle');

        $this->assertIsArray($dashboard_data);
        $this->assertArrayHasKey('charts', $dashboard_data);
        $this->assertArrayHasKey('top_patterns', $dashboard_data);
        $this->assertArrayHasKey('score', $dashboard_data);
        $this->assertArrayHasKey('summary', $dashboard_data);
        $this->assertArrayHasKey('recommendations', $dashboard_data);

        // Check charts
        $this->assertArrayHasKey('length', $dashboard_data['charts']);
        $this->assertArrayHasKey('tone', $dashboard_data['charts']);
        $this->assertArrayHasKey('time', $dashboard_data['charts']);
        $this->assertArrayHasKey('format', $dashboard_data['charts']);
        $this->assertArrayHasKey('correlation', $dashboard_data['charts']);

        // Check summary
        $this->assertEquals(150, $dashboard_data['summary']['total_reposts']);
        $this->assertEquals(120, $dashboard_data['summary']['avg_length']);
        $this->assertEquals(45.5, $dashboard_data['summary']['avg_engagement_per_repost']);

        // Check recommendations
        $this->assertIsArray($dashboard_data['recommendations']);
        $this->assertNotEmpty($dashboard_data['recommendations']);
    }

    /**
     * Test empty analysis handling
     */
    public function test_empty_analysis_handling() {
        $empty_analysis = array();

        // Test all chart methods with empty data
        $length_chart = $this->visualizer->generate_length_chart_data($empty_analysis);
        $this->assertEmpty($length_chart);

        $tone_chart = $this->visualizer->generate_tone_chart_data($empty_analysis);
        $this->assertEmpty($tone_chart);

        $time_chart = $this->visualizer->generate_time_chart_data($empty_analysis);
        $this->assertEmpty($time_chart);

        $format_chart = $this->visualizer->generate_format_chart_data($empty_analysis);
        $this->assertEmpty($format_chart);

        $correlation_chart = $this->visualizer->generate_correlation_chart_data($empty_analysis);
        $this->assertEmpty($correlation_chart);

        $top_patterns = $this->visualizer->generate_top_patterns_data($empty_analysis);
        $this->assertEmpty($top_patterns);
    }

    /**
     * Test color schemes
     */
    public function test_color_schemes() {
        $reflection = new ReflectionClass($this->visualizer);
        $color_schemes_property = $reflection->getProperty('color_schemes');
        $color_schemes_property->setAccessible(true);
        $color_schemes = $color_schemes_property->getValue($this->visualizer);

        $this->assertIsArray($color_schemes);
        $this->assertArrayHasKey('primary', $color_schemes);
        $this->assertArrayHasKey('success', $color_schemes);
        $this->assertArrayHasKey('warning', $color_schemes);
        $this->assertArrayHasKey('danger', $color_schemes);
        $this->assertArrayHasKey('neutral', $color_schemes);

        // Check that each scheme has colors
        foreach ($color_schemes as $scheme => $colors) {
            $this->assertIsArray($colors);
            $this->assertNotEmpty($colors);
            $this->assertGreaterThanOrEqual(5, count($colors));
        }
    }

    /**
     * Test score grade calculation
     */
    public function test_score_grade_calculation() {
        $reflection = new ReflectionClass($this->visualizer);
        $get_score_grade_method = $reflection->getMethod('get_score_grade');
        $get_score_grade_method->setAccessible(true);

        // Test various scores
        $this->assertEquals('A+', $get_score_grade_method->invoke($this->visualizer, 95));
        $this->assertEquals('A', $get_score_grade_method->invoke($this->visualizer, 87));
        $this->assertEquals('B+', $get_score_grade_method->invoke($this->visualizer, 78));
        $this->assertEquals('C', $get_score_grade_method->invoke($this->visualizer, 58));
        $this->assertEquals('D', $get_score_grade_method->invoke($this->visualizer, 42));
        $this->assertEquals('F', $get_score_grade_method->invoke($this->visualizer, 35));
    }

    /**
     * Test score recommendations
     */
    public function test_score_recommendations() {
        $reflection = new ReflectionClass($this->visualizer);
        $get_score_recommendations_method = $reflection->getMethod('get_score_recommendations');
        $get_score_recommendations_method->setAccessible(true);

        $factors = array(
            'length' => 10,
            'tone' => 8,
            'format' => 12,
            'engagement' => 15
        );

        // Test low score recommendations
        $recommendations = $get_score_recommendations_method->invoke($this->visualizer, $factors, 45);
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        $this->assertStringContainsString('significant improvement', $recommendations[0]);

        // Test high score recommendations
        $recommendations = $get_score_recommendations_method->invoke($this->visualizer, $factors, 85);
        $this->assertIsArray($recommendations);
        $this->assertNotEmpty($recommendations);
        $this->assertStringContainsString('Excellent', $recommendations[0]);
    }

    /**
     * Test hour label generation
     */
    public function test_hour_label_generation() {
        $reflection = new ReflectionClass($this->visualizer);
        $get_hour_label_method = $reflection->getMethod('get_hour_label');
        $get_hour_label_method->setAccessible(true);

        // Test various hours
        $this->assertEquals('12 AM', $get_hour_label_method->invoke($this->visualizer, 0));
        $this->assertEquals('1 AM', $get_hour_label_method->invoke($this->visualizer, 1));
        $this->assertEquals('12 PM', $get_hour_label_method->invoke($this->visualizer, 12));
        $this->assertEquals('6 PM', $get_hour_label_method->invoke($this->visualizer, 18));
        $this->assertEquals('11 PM', $get_hour_label_method->invoke($this->visualizer, 23));
        $this->assertEquals('Unknown', $get_hour_label_method->invoke($this->visualizer, 25));
    }

    /**
     * Test AJAX handlers (basic structure)
     */
    public function test_ajax_handlers_exist() {
        $reflection = new ReflectionClass($this->visualizer);
        
        $this->assertTrue($reflection->hasMethod('ajax_get_chart_data'));
        $this->assertTrue($reflection->hasMethod('ajax_get_pattern_comparison'));
        $this->assertTrue($reflection->hasMethod('ajax_get_top_patterns'));
        $this->assertTrue($reflection->hasMethod('ajax_get_pattern_score'));
    }

    /**
     * Test logging functionality
     */
    public function test_logging() {
        $this->mock_logger->expects($this->once())
            ->method('log')
            ->with('info', $this->stringContains('[Pattern Visualizer]'), $this->anything());

        $reflection = new ReflectionClass($this->visualizer);
        $log_method = $reflection->getMethod('log');
        $log_method->setAccessible(true);
        
        $log_method->invoke($this->visualizer, 'info', 'Test message', array('test' => 'data'));
    }

    /**
     * Test hooks initialization
     */
    public function test_hooks_initialization() {
        // This test verifies that the hooks are properly set up
        // We can't easily test WordPress hooks in isolation, but we can verify the method exists
        $reflection = new ReflectionClass($this->visualizer);
        $this->assertTrue($reflection->hasMethod('init_hooks'));
    }

    /**
     * Test color schemes initialization
     */
    public function test_color_schemes_initialization() {
        $reflection = new ReflectionClass($this->visualizer);
        $this->assertTrue($reflection->hasMethod('init_color_schemes'));
    }

    /**
     * Test edge cases for normalization
     */
    public function test_normalization_edge_cases() {
        // Test with all same values
        $same_data = array('a' => 10, 'b' => 10, 'c' => 10);
        $normalized = $this->visualizer->normalize_data($same_data, 'min_max');
        $this->assertEquals(array('a' => 1, 'b' => 1, 'c' => 1), $normalized);

        // Test with single value
        $single_data = array('a' => 5);
        $normalized = $this->visualizer->normalize_data($single_data, 'min_max');
        $this->assertEquals(array('a' => 1), $normalized);

        // Test with zero values
        $zero_data = array('a' => 0, 'b' => 0, 'c' => 0);
        $normalized = $this->visualizer->normalize_data($zero_data, 'min_max');
        $this->assertEquals(array('a' => 1, 'b' => 1, 'c' => 1), $normalized);
    }

    /**
     * Test invalid chart type handling
     */
    public function test_invalid_chart_type_handling() {
        // Test with missing data keys
        $incomplete_analysis = array(
            'summary' => array('total_reposts' => 10)
        );

        $length_chart = $this->visualizer->generate_length_chart_data($incomplete_analysis);
        $this->assertEmpty($length_chart);

        $tone_chart = $this->visualizer->generate_tone_chart_data($incomplete_analysis);
        $this->assertEmpty($tone_chart);
    }

    /**
     * Test pattern comparison with missing data
     */
    public function test_pattern_comparison_missing_data() {
        $analysis1 = array('tone_patterns' => array('tone_effectiveness' => array('happy' => 8.0)));
        $analysis2 = array('tone_patterns' => array('tone_effectiveness' => array('sad' => 6.0)));

        $comparison = $this->visualizer->generate_pattern_comparison($analysis1, $analysis2);
        
        $this->assertIsArray($comparison);
        $this->assertArrayHasKey('tones', $comparison);
        $this->assertCount(2, $comparison['tones']); // Both tones should be included
    }

    /**
     * Test dashboard data with no analysis
     */
    public function test_dashboard_data_no_analysis() {
        $this->mock_pattern_analyzer->method('analyze_patterns')
            ->willReturn(array());

        $dashboard_data = $this->visualizer->generate_dashboard_data('test_handle');
        $this->assertEmpty($dashboard_data);
    }
} 