<?php
/**
 * Test Pattern Validator
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Test Pattern Validator Class
 */
class TestXeliteRepostEngine_Pattern_Validator extends TestCase {

    /**
     * Pattern validator instance
     *
     * @var XeliteRepostEngine_Pattern_Validator
     */
    private $validator;

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

        // Create validator instance
        $this->validator = new XeliteRepostEngine_Pattern_Validator(
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
                'optimal_length_range' => array(
                    'min' => 100,
                    'max' => 150
                ),
                'category_avg_engagement' => array(
                    'short' => 42.3,
                    'medium' => 48.7,
                    'long' => 35.2
                )
            ),
            'tone_patterns' => array(
                'top_effective_tones' => array(
                    array('key' => 'informative', 'value' => 8.5),
                    array('key' => 'conversational', 'value' => 7.2),
                    array('key' => 'inspirational', 'value' => 6.8)
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
                )
            ),
            'content_patterns' => array(
                'top_words' => array(
                    array('key' => 'growth', 'value' => 25),
                    array('key' => 'success', 'value' => 22),
                    array('key' => 'business', 'value' => 20)
                ),
                'top_phrases' => array(
                    array('key' => 'business growth', 'value' => 12),
                    array('key' => 'success tips', 'value' => 10)
                )
            )
        );
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf('XeliteRepostEngine_Pattern_Validator', $this->validator);
    }

    /**
     * Test applying patterns to content
     */
    public function test_apply_patterns_to_content() {
        $this->mock_pattern_analyzer->method('analyze_patterns')
            ->willReturn($this->sample_analysis);

        $content = "This is a test tweet that needs optimization.";
        $patterns = array(
            'length' => true,
            'tone' => true,
            'format' => true,
            'content' => true
        );

        $result = $this->validator->apply_patterns_to_content($content, $patterns, 'test_handle');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('original_content', $result);
        $this->assertArrayHasKey('applied_patterns', $result);
        $this->assertArrayHasKey('modifications', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('pattern_analysis', $result);

        // Check that content was modified
        $this->assertNotEquals($content, $result['content']);
        $this->assertEquals($content, $result['original_content']);

        // Check that patterns were applied
        $this->assertNotEmpty($result['applied_patterns']);
        $this->assertNotEmpty($result['modifications']);
        $this->assertGreaterThan(0, $result['confidence']);
    }

    /**
     * Test applying patterns with empty analysis
     */
    public function test_apply_patterns_to_content_empty_analysis() {
        $this->mock_pattern_analyzer->method('analyze_patterns')
            ->willReturn(array());

        $content = "This is a test tweet.";
        $patterns = array('length' => true);

        $result = $this->validator->apply_patterns_to_content($content, $patterns, 'test_handle');

        $this->assertIsArray($result);
        $this->assertEquals($content, $result['content']);
        $this->assertEmpty($result['applied_patterns']);
        $this->assertEquals(0, $result['confidence']);
    }

    /**
     * Test length pattern application
     */
    public function test_apply_length_pattern() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('apply_length_pattern');
        $method->setAccessible(true);

        // Test content that's too long
        $long_content = str_repeat("This is a very long tweet that exceeds the optimal length range. ", 10);
        $result = $method->invoke($this->validator, $long_content, $this->sample_analysis);

        $this->assertTrue($result['modified']);
        $this->assertLessThanOrEqual(150, strlen($result['content']));
        $this->assertArrayHasKey('optimal_range', $result['pattern']);

        // Test content that's too short
        $short_content = "Short";
        $result = $method->invoke($this->validator, $short_content, $this->sample_analysis);

        $this->assertTrue($result['modified']);
        $this->assertGreaterThanOrEqual(100, strlen($result['content']));

        // Test content that's already optimal
        $optimal_content = str_repeat("Optimal length content. ", 5);
        $result = $method->invoke($this->validator, $optimal_content, $this->sample_analysis);

        $this->assertFalse($result['modified']);
        $this->assertEquals($optimal_content, $result['content']);
    }

    /**
     * Test tone pattern application
     */
    public function test_apply_tone_pattern() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('apply_tone_pattern');
        $method->setAccessible(true);

        // Test with conversational content
        $conversational_content = "I think this is a great idea and I believe it will work.";
        $result = $method->invoke($this->validator, $conversational_content, $this->sample_analysis);

        $this->assertTrue($result['modified']);
        $this->assertArrayHasKey('target_tone', $result['pattern']);
        $this->assertEquals('informative', $result['pattern']['target_tone']);

        // Test with already optimal tone
        $informative_content = "Here are some tips for business growth and success.";
        $result = $method->invoke($this->validator, $informative_content, $this->sample_analysis);

        $this->assertFalse($result['modified']);
    }

    /**
     * Test format pattern application
     */
    public function test_apply_format_patterns() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('apply_format_patterns');
        $method->setAccessible(true);

        $content = "This is a test tweet without hashtags or emojis.";
        $result = $method->invoke($this->validator, $content, $this->sample_analysis);

        $this->assertTrue($result['modified']);
        $this->assertArrayHasKey('hashtags', $result['patterns']);
        $this->assertArrayHasKey('emojis', $result['patterns']);

        // Check that hashtags were added
        $this->assertStringContainsString('#', $result['content']);
        $this->assertStringContainsString('🚀', $result['content']);
    }

    /**
     * Test content pattern application
     */
    public function test_apply_content_patterns() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('apply_content_patterns');
        $method->setAccessible(true);

        $content = "This is a test tweet about something else.";
        $result = $method->invoke($this->validator, $content, $this->sample_analysis);

        $this->assertTrue($result['modified']);
        $this->assertArrayHasKey('word_suggestions', $result['patterns']);
        $this->assertArrayHasKey('phrase_suggestions', $result['patterns']);

        // Check suggestions
        $this->assertNotEmpty($result['patterns']['word_suggestions']);
        $this->assertNotEmpty($result['patterns']['phrase_suggestions']);
    }

    /**
     * Test A/B testing setup
     */
    public function test_setup_ab_test() {
        $content = "This is a test tweet for A/B testing.";
        $patterns = array('length' => true, 'tone' => true);
        $source_handle = 'test_handle';

        $result = $this->validator->setup_ab_test($content, $patterns, $source_handle, 7);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_id', $result);
        $this->assertArrayHasKey('variants', $result);
        $this->assertArrayHasKey('test_data', $result);

        // Check variants
        $this->assertArrayHasKey('control', $result['variants']);
        $this->assertArrayHasKey('test', $result['variants']);

        // Check test data
        $this->assertEquals('active', $result['test_data']['status']);
        $this->assertEquals(7, $result['test_data']['test_duration']);
        $this->assertArrayHasKey('metrics', $result['test_data']);
    }

    /**
     * Test A/B test performance tracking
     */
    public function test_track_ab_test_performance() {
        // Setup test data
        $test_id = 'ab_test_' . uniqid();
        $test_data = array(
            'test_id' => $test_id,
            'metrics' => array(
                'control' => array('impressions' => 0, 'reposts' => 0, 'engagement' => 0),
                'test' => array('impressions' => 0, 'reposts' => 0, 'engagement' => 0)
            )
        );

        // Mock get_ab_test_data to return our test data
        $reflection = new ReflectionClass($this->validator);
        $get_method = $reflection->getMethod('get_ab_test_data');
        $get_method->setAccessible(true);
        $store_method = $reflection->getMethod('store_ab_test_data');
        $store_method->setAccessible(true);

        $store_method->invoke($this->validator, $test_data);

        $metrics = array(
            'impressions' => 100,
            'reposts' => 5,
            'engagement' => 25
        );

        $result = $this->validator->track_ab_test_performance($test_id, 'test', $metrics);

        $this->assertTrue($result);
    }

    /**
     * Test A/B test results analysis
     */
    public function test_analyze_ab_test_results() {
        // Setup test data with significant difference
        $test_id = 'ab_test_' . uniqid();
        $test_data = array(
            'test_id' => $test_id,
            'metrics' => array(
                'control' => array(
                    'impressions' => 1000,
                    'reposts' => 20,
                    'engagement' => 100,
                    'repost_rate' => 2.0,
                    'engagement_rate' => 10.0
                ),
                'test' => array(
                    'impressions' => 1000,
                    'reposts' => 35,
                    'engagement' => 150,
                    'repost_rate' => 3.5,
                    'engagement_rate' => 15.0
                )
            )
        );

        $reflection = new ReflectionClass($this->validator);
        $store_method = $reflection->getMethod('store_ab_test_data');
        $store_method->setAccessible(true);
        $store_method->invoke($this->validator, $test_data);

        $result = $this->validator->analyze_ab_test_results($test_id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('winner', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('metrics', $result);
        $this->assertArrayHasKey('significance', $result);
        $this->assertArrayHasKey('improvement', $result);

        // Check improvement calculations
        $this->assertEquals(1.5, $result['improvement']['repost_rate']);
        $this->assertEquals(5.0, $result['improvement']['engagement_rate']);
    }

    /**
     * Test pattern confidence calculation
     */
    public function test_calculate_pattern_confidence() {
        $applied_patterns = array(
            'length' => array(
                'optimal_range' => array('min' => 100, 'max' => 150),
                'original_length' => 80,
                'new_length' => 120
            ),
            'tone' => array(
                'target_tone' => 'informative',
                'original_tone' => 'conversational',
                'effectiveness_score' => 8.5
            ),
            'format' => array(
                'hashtags' => array('optimal_count' => 2, 'added' => 2),
                'emojis' => array('optimal_count' => 1, 'added' => 1)
            )
        );

        $confidence = $this->validator->calculate_pattern_confidence($applied_patterns, $this->sample_analysis);

        $this->assertIsFloat($confidence);
        $this->assertGreaterThan(0, $confidence);
        $this->assertLessThanOrEqual(100, $confidence);
    }

    /**
     * Test pattern performance tracking
     */
    public function test_track_pattern_performance() {
        $pattern_type = 'length';
        $pattern_data = array('optimal_range' => array('min' => 100, 'max' => 150));
        $performance = array('repost_rate' => 3.5, 'engagement_rate' => 12.0);

        $result = $this->validator->track_pattern_performance($pattern_type, $pattern_data, $performance);

        $this->assertTrue($result);
    }

    /**
     * Test pattern decay detection
     */
    public function test_detect_pattern_decay() {
        // Mock performance history with declining trend
        $pattern_type = 'length';
        $pattern_data = array('optimal_range' => array('min' => 100, 'max' => 150));

        // Mock get_pattern_performance_history to return declining data
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('get_pattern_performance_history');
        $method->setAccessible(true);

        $declining_data = array();
        for ($i = 0; $i < 15; $i++) {
            $declining_data[] = array(
                'performance' => array('repost_rate' => 5.0 - ($i * 0.2)),
                'timestamp' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                'date' => date('Y-m-d', strtotime("-{$i} days"))
            );
        }

        // Mock the database method
        $this->mock_database->method('get_results')
            ->willReturn($declining_data);

        $result = $this->validator->detect_pattern_decay($pattern_type, $pattern_data, 30);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('decay_detected', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('trend', $result);
        $this->assertArrayHasKey('recommendation', $result);

        // Should detect decay in declining data
        $this->assertTrue($result['decay_detected']);
        $this->assertGreaterThan(0.7, $result['confidence']);
    }

    /**
     * Test tone detection
     */
    public function test_detect_content_tone() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('detect_content_tone');
        $method->setAccessible(true);

        // Test informative tone
        $informative_content = "Here are some tips for business growth and success.";
        $tone = $method->invoke($this->validator, $informative_content);
        $this->assertEquals('informative', $tone);

        // Test conversational tone
        $conversational_content = "I think this is a great idea and I believe it will work.";
        $tone = $method->invoke($this->validator, $conversational_content);
        $this->assertEquals('conversational', $tone);

        // Test inspirational tone
        $inspirational_content = "Dream big and achieve your goals with these success strategies.";
        $tone = $method->invoke($this->validator, $inspirational_content);
        $this->assertEquals('inspirational', $tone);
    }

    /**
     * Test tone transformation
     */
    public function test_transform_content_tone() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('transform_content_tone');
        $method->setAccessible(true);

        $content = "This is a test tweet.";
        $from_tone = 'conversational';
        $to_tone = 'informative';

        $result = $method->invoke($this->validator, $content, $from_tone, $to_tone);

        $this->assertStringContainsString('Here\'s how:', $result);
        $this->assertStringContainsString($content, $result);
    }

    /**
     * Test hashtag pattern application
     */
    public function test_apply_hashtag_pattern() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('apply_hashtag_pattern');
        $method->setAccessible(true);

        $content = "This is a test tweet without hashtags.";
        $hashtag_pattern = array('optimal_count' => 2, 'avg_engagement' => 8.5);

        $result = $method->invoke($this->validator, $content, $hashtag_pattern);

        $this->assertTrue($result['modified']);
        $this->assertStringContainsString('#growth', $result['content']);
        $this->assertStringContainsString('#success', $result['content']);
        $this->assertEquals(2, $result['pattern']['added']);
    }

    /**
     * Test emoji pattern application
     */
    public function test_apply_emoji_pattern() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('apply_emoji_pattern');
        $method->setAccessible(true);

        $content = "This is a test tweet without emojis.";
        $emoji_pattern = array('optimal_count' => 1, 'avg_engagement' => 7.2);

        $result = $method->invoke($this->validator, $content, $emoji_pattern);

        $this->assertTrue($result['modified']);
        $this->assertStringContainsString('🚀', $result['content']);
        $this->assertEquals(1, $result['pattern']['added']);
    }

    /**
     * Test word replacement suggestions
     */
    public function test_suggest_word_replacements() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('suggest_word_replacements');
        $method->setAccessible(true);

        $content = "This is a test tweet about something else.";
        $top_words = array(
            array('key' => 'growth', 'value' => 25),
            array('key' => 'success', 'value' => 22),
            array('key' => 'business', 'value' => 20)
        );

        $suggestions = $method->invoke($this->validator, $content, $top_words);

        $this->assertIsArray($suggestions);
        $this->assertLessThanOrEqual(3, count($suggestions));

        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('word', $suggestion);
            $this->assertArrayHasKey('frequency', $suggestion);
            $this->assertArrayHasKey('suggestion', $suggestion);
        }
    }

    /**
     * Test phrase addition suggestions
     */
    public function test_suggest_phrase_additions() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('suggest_phrase_additions');
        $method->setAccessible(true);

        $content = "This is a test tweet about something else.";
        $top_phrases = array(
            array('key' => 'business growth', 'value' => 12),
            array('key' => 'success tips', 'value' => 10)
        );

        $suggestions = $method->invoke($this->validator, $content, $top_phrases);

        $this->assertIsArray($suggestions);
        $this->assertLessThanOrEqual(2, count($suggestions));

        foreach ($suggestions as $suggestion) {
            $this->assertArrayHasKey('phrase', $suggestion);
            $this->assertArrayHasKey('frequency', $suggestion);
            $this->assertArrayHasKey('suggestion', $suggestion);
        }
    }

    /**
     * Test statistical significance calculation
     */
    public function test_calculate_statistical_significance() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('calculate_statistical_significance');
        $method->setAccessible(true);

        // Test with significant difference
        $result = $method->invoke($this->validator, 20, 1000, 35, 1000);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('significant', $result);
        $this->assertArrayHasKey('confidence', $result);
        $this->assertArrayHasKey('z_score', $result);
        $this->assertArrayHasKey('control_rate', $result);
        $this->assertArrayHasKey('test_rate', $result);

        $this->assertTrue($result['significant']);
        $this->assertGreaterThan(0, $result['confidence']);

        // Test with no difference
        $result = $method->invoke($this->validator, 20, 1000, 20, 1000);
        $this->assertFalse($result['significant']);
    }

    /**
     * Test length confidence calculation
     */
    public function test_calculate_length_confidence() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('calculate_length_confidence');
        $method->setAccessible(true);

        $pattern = array(
            'optimal_range' => array('min' => 100, 'max' => 150),
            'new_length' => 125
        );

        $confidence = $method->invoke($this->validator, $pattern, $this->sample_analysis);

        $this->assertEquals(100, $confidence); // Perfect length

        // Test suboptimal length
        $pattern['new_length'] = 80;
        $confidence = $method->invoke($this->validator, $pattern, $this->sample_analysis);

        $this->assertLessThan(100, $confidence);
        $this->assertGreaterThan(0, $confidence);
    }

    /**
     * Test tone confidence calculation
     */
    public function test_calculate_tone_confidence() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('calculate_tone_confidence');
        $method->setAccessible(true);

        $pattern = array(
            'target_tone' => 'informative',
            'effectiveness_score' => 8.5
        );

        $confidence = $method->invoke($this->validator, $pattern, $this->sample_analysis);

        $this->assertEquals(85, $confidence); // 8.5 * 10
    }

    /**
     * Test format confidence calculation
     */
    public function test_calculate_format_confidence() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('calculate_format_confidence');
        $method->setAccessible(true);

        $patterns = array(
            'hashtags' => array('optimal_count' => 2, 'added' => 2),
            'emojis' => array('optimal_count' => 1, 'added' => 1)
        );

        $confidence = $method->invoke($this->validator, $patterns, $this->sample_analysis);

        $this->assertIsFloat($confidence);
        $this->assertGreaterThan(0, $confidence);
        $this->assertLessThanOrEqual(100, $confidence);
    }

    /**
     * Test content confidence calculation
     */
    public function test_calculate_content_confidence() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('calculate_content_confidence');
        $method->setAccessible(true);

        $patterns = array(
            'word_suggestions' => array(
                array('frequency' => 25),
                array('frequency' => 22)
            ),
            'phrase_suggestions' => array(
                array('frequency' => 12)
            )
        );

        $confidence = $method->invoke($this->validator, $patterns, $this->sample_analysis);

        $this->assertIsFloat($confidence);
        $this->assertGreaterThan(0, $confidence);
        $this->assertLessThanOrEqual(100, $confidence);
    }

    /**
     * Test performance trend calculation
     */
    public function test_calculate_performance_trend() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('calculate_performance_trend');
        $method->setAccessible(true);

        $performance_data = array();
        for ($i = 0; $i < 10; $i++) {
            $performance_data[] = array(
                'performance' => array('repost_rate' => 5.0 + ($i * 0.1)),
                'timestamp' => date('Y-m-d H:i:s', strtotime("-{$i} days")),
                'date' => date('Y-m-d', strtotime("-{$i} days"))
            );
        }

        $trend = $method->invoke($this->validator, $performance_data);

        $this->assertIsArray($trend);
        $this->assertArrayHasKey('slope', $trend);
        $this->assertArrayHasKey('direction', $trend);

        $this->assertGreaterThan(0, $trend['slope']);
        $this->assertEquals('improving', $trend['direction']);
    }

    /**
     * Test decay score calculation
     */
    public function test_calculate_decay_score() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('calculate_decay_score');
        $method->setAccessible(true);

        $trend = array('slope' => -0.2, 'direction' => 'declining');
        $performance_data = array_fill(0, 10, array('performance' => array('repost_rate' => 5.0)));

        $decay_score = $method->invoke($this->validator, $trend, $performance_data);

        $this->assertIsFloat($decay_score);
        $this->assertGreaterThan(0, $decay_score);
        $this->assertLessThanOrEqual(1, $decay_score);
    }

    /**
     * Test trend consistency calculation
     */
    public function test_calculate_trend_consistency() {
        $reflection = new ReflectionClass($this->validator);
        $method = $reflection->getMethod('calculate_trend_consistency');
        $method->setAccessible(true);

        $performance_data = array();
        for ($i = 0; $i < 10; $i++) {
            $performance_data[] = array(
                'performance' => array('repost_rate' => 5.0 - ($i * 0.1))
            );
        }

        $consistency = $method->invoke($this->validator, $performance_data);

        $this->assertIsFloat($consistency);
        $this->assertGreaterThan(0, $consistency);
        $this->assertLessThanOrEqual(1, $consistency);
    }

    /**
     * Test AJAX handlers exist
     */
    public function test_ajax_handlers_exist() {
        $reflection = new ReflectionClass($this->validator);
        
        $this->assertTrue($reflection->hasMethod('ajax_validate_pattern'));
        $this->assertTrue($reflection->hasMethod('ajax_ab_test_pattern'));
        $this->assertTrue($reflection->hasMethod('ajax_get_pattern_confidence'));
        $this->assertTrue($reflection->hasMethod('ajax_track_pattern_performance'));
        $this->assertTrue($reflection->hasMethod('ajax_detect_pattern_decay'));
    }

    /**
     * Test logging functionality
     */
    public function test_logging() {
        $this->mock_logger->expects($this->once())
            ->method('log')
            ->with('info', $this->stringContains('[Pattern Validator]'), $this->anything());

        $reflection = new ReflectionClass($this->validator);
        $log_method = $reflection->getMethod('log');
        $log_method->setAccessible(true);
        
        $log_method->invoke($this->validator, 'info', 'Test message', array('test' => 'data'));
    }

    /**
     * Test edge cases
     */
    public function test_edge_cases() {
        // Test with empty patterns
        $result = $this->validator->apply_patterns_to_content("Test content", array(), 'test_handle');
        $this->assertEquals("Test content", $result['content']);
        $this->assertEmpty($result['applied_patterns']);

        // Test with invalid pattern types
        $result = $this->validator->apply_patterns_to_content("Test content", array('invalid' => true), 'test_handle');
        $this->assertEquals("Test content", $result['content']);

        // Test confidence calculation with empty patterns
        $confidence = $this->validator->calculate_pattern_confidence(array(), $this->sample_analysis);
        $this->assertEquals(0, $confidence);
    }

    /**
     * Test cache functionality
     */
    public function test_cache_functionality() {
        $reflection = new ReflectionClass($this->validator);
        $store_method = $reflection->getMethod('store_ab_test_data');
        $store_method->setAccessible(true);
        $get_method = $reflection->getMethod('get_ab_test_data');
        $get_method->setAccessible(true);

        $test_data = array('test_id' => 'test123', 'data' => 'test');
        $store_method->invoke($this->validator, $test_data);

        $retrieved = $get_method->invoke($this->validator, 'test123');
        $this->assertEquals($test_data, $retrieved);
    }
} 