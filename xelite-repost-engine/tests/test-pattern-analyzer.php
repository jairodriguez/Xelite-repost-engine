<?php
/**
 * Test suite for Pattern Analyzer
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for Pattern Analyzer
 */
class Test_XeliteRepostEngine_Pattern_Analyzer extends WP_UnitTestCase {

    /**
     * Pattern Analyzer instance
     *
     * @var XeliteRepostEngine_Pattern_Analyzer
     */
    private $pattern_analyzer;

    /**
     * Database mock
     *
     * @var XeliteRepostEngine_Database
     */
    private $database_mock;

    /**
     * Logger mock
     *
     * @var XeliteRepostEngine_Logger
     */
    private $logger_mock;

    /**
     * Sample repost data
     *
     * @var array
     */
    private $sample_reposts;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create mocks
        $this->database_mock = $this->createMock('XeliteRepostEngine_Database');
        $this->logger_mock = $this->createMock('XeliteRepostEngine_Logger');
        
        // Create Pattern Analyzer instance
        $this->pattern_analyzer = new XeliteRepostEngine_Pattern_Analyzer($this->database_mock, $this->logger_mock);
        
        // Create sample repost data
        $this->create_sample_reposts();
    }

    /**
     * Create sample repost data for testing
     */
    private function create_sample_reposts() {
        $this->sample_reposts = array(
            (object) array(
                'id' => 1,
                'source_handle' => 'test_account',
                'original_tweet_id' => '123456789',
                'original_text' => 'What\'s your biggest challenge with social media marketing? 🤔 #marketing #socialmedia',
                'repost_count' => 15,
                'like_count' => 45,
                'reply_count' => 8,
                'quote_count' => 3,
                'timestamp' => '2024-01-15 10:30:00'
            ),
            (object) array(
                'id' => 2,
                'source_handle' => 'test_account',
                'original_tweet_id' => '123456790',
                'original_text' => 'Pro tip: Always engage with your audience before asking them to buy. Build relationships first! 💡',
                'repost_count' => 25,
                'like_count' => 67,
                'reply_count' => 12,
                'quote_count' => 5,
                'timestamp' => '2024-01-15 14:20:00'
            ),
            (object) array(
                'id' => 3,
                'source_handle' => 'test_account',
                'original_tweet_id' => '123456791',
                'original_text' => 'Check out this amazing story about how we helped a client increase their engagement by 300% in just 30 days! 📈',
                'repost_count' => 8,
                'like_count' => 23,
                'reply_count' => 4,
                'quote_count' => 1,
                'timestamp' => '2024-01-15 16:45:00'
            ),
            (object) array(
                'id' => 4,
                'source_handle' => 'another_account',
                'original_tweet_id' => '123456792',
                'original_text' => 'The truth about social media algorithms: they favor engagement over everything else.',
                'repost_count' => 12,
                'like_count' => 34,
                'reply_count' => 6,
                'quote_count' => 2,
                'timestamp' => '2024-01-15 09:15:00'
            ),
            (object) array(
                'id' => 5,
                'source_handle' => 'another_account',
                'original_tweet_id' => '123456793',
                'original_text' => 'Follow @experts for daily tips on growing your business! 🚀 #business #growth',
                'repost_count' => 18,
                'like_count' => 52,
                'reply_count' => 9,
                'quote_count' => 4,
                'timestamp' => '2024-01-15 11:30:00'
            )
        );
    }

    /**
     * Test constructor initialization
     */
    public function test_constructor_initialization() {
        $this->assertInstanceOf('XeliteRepostEngine_Pattern_Analyzer', $this->pattern_analyzer);
    }

    /**
     * Test pattern analysis with sample data
     */
    public function test_analyze_patterns() {
        // Mock database response
        $this->database_mock->method('get_results')
            ->willReturn($this->sample_reposts);
        
        $this->database_mock->method('get_table_name')
            ->willReturn('wp_xelite_reposts');
        
        $this->database_mock->method('insert')
            ->willReturn(1);

        $analysis = $this->pattern_analyzer->analyze_patterns('test_account', 100);

        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('summary', $analysis);
        $this->assertArrayHasKey('length_patterns', $analysis);
        $this->assertArrayHasKey('tone_patterns', $analysis);
        $this->assertArrayHasKey('format_patterns', $analysis);
        $this->assertArrayHasKey('engagement_correlation', $analysis);
        $this->assertArrayHasKey('time_patterns', $analysis);
        $this->assertArrayHasKey('content_patterns', $analysis);
        $this->assertArrayHasKey('recommendations', $analysis);
    }

    /**
     * Test summary analysis
     */
    public function test_analyze_summary() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('analyze_summary');
        $method->setAccessible(true);

        $summary = $method->invoke($this->pattern_analyzer, $this->sample_reposts);

        $this->assertArrayHasKey('total_reposts', $summary);
        $this->assertArrayHasKey('total_engagement', $summary);
        $this->assertArrayHasKey('avg_engagement_per_repost', $summary);
        $this->assertArrayHasKey('avg_length', $summary);
        $this->assertArrayHasKey('top_sources', $summary);
        $this->assertArrayHasKey('date_range', $summary);

        $this->assertEquals(5, $summary['total_reposts']);
        $this->assertEquals(78, $summary['total_engagement']); // Sum of repost_count
        $this->assertEquals(15.6, $summary['avg_engagement_per_repost']); // 78/5
    }

    /**
     * Test length pattern analysis
     */
    public function test_analyze_length_patterns() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('analyze_length_patterns');
        $method->setAccessible(true);

        $length_analysis = $method->invoke($this->pattern_analyzer, $this->sample_reposts);

        $this->assertArrayHasKey('category_distribution', $length_analysis);
        $this->assertArrayHasKey('category_avg_engagement', $length_analysis);
        $this->assertArrayHasKey('correlation', $length_analysis);
        $this->assertArrayHasKey('optimal_length_range', $length_analysis);

        // Test category distribution
        $this->assertArrayHasKey('short', $length_analysis['category_distribution']);
        $this->assertArrayHasKey('medium', $length_analysis['category_distribution']);
        $this->assertArrayHasKey('long', $length_analysis['category_distribution']);
    }

    /**
     * Test tone pattern analysis
     */
    public function test_analyze_tone_patterns() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('analyze_tone_patterns');
        $method->setAccessible(true);

        $tone_analysis = $method->invoke($this->pattern_analyzer, $this->sample_reposts);

        $this->assertArrayHasKey('tone_distribution', $tone_analysis);
        $this->assertArrayHasKey('tone_avg_engagement', $tone_analysis);
        $this->assertArrayHasKey('tone_effectiveness', $tone_analysis);
        $this->assertArrayHasKey('top_effective_tones', $tone_analysis);
        $this->assertArrayHasKey('example_texts', $tone_analysis);

        // Test that question tone is detected
        $this->assertGreaterThan(0, $tone_analysis['tone_distribution']['question']);
        
        // Test that call_to_action tone is detected
        $this->assertGreaterThan(0, $tone_analysis['tone_distribution']['call_to_action']);
    }

    /**
     * Test format pattern analysis
     */
    public function test_analyze_format_patterns() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('analyze_format_patterns');
        $method->setAccessible(true);

        $format_analysis = $method->invoke($this->pattern_analyzer, $this->sample_reposts);

        $this->assertArrayHasKey('hashtags', $format_analysis);
        $this->assertArrayHasKey('emojis', $format_analysis);
        $this->assertArrayHasKey('urls', $format_analysis);
        $this->assertArrayHasKey('mentions', $format_analysis);

        // Test hashtag analysis
        $this->assertArrayHasKey('counts', $format_analysis['hashtags']);
        $this->assertArrayHasKey('engagement', $format_analysis['hashtags']);
        $this->assertArrayHasKey('avg_engagement', $format_analysis['hashtags']);
        $this->assertArrayHasKey('optimal_count', $format_analysis['hashtags']);

        // Test emoji analysis
        $this->assertArrayHasKey('counts', $format_analysis['emojis']);
        $this->assertArrayHasKey('engagement', $format_analysis['emojis']);
        $this->assertArrayHasKey('avg_engagement', $format_analysis['emojis']);
        $this->assertArrayHasKey('optimal_count', $format_analysis['emojis']);
    }

    /**
     * Test engagement correlation analysis
     */
    public function test_analyze_engagement_correlation() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('analyze_engagement_correlation');
        $method->setAccessible(true);

        $correlation_analysis = $method->invoke($this->pattern_analyzer, $this->sample_reposts);

        $this->assertArrayHasKey('correlations', $correlation_analysis);
        $this->assertArrayHasKey('strongest_correlation', $correlation_analysis);
        $this->assertArrayHasKey('engagement_predictors', $correlation_analysis);

        // Test correlations
        $this->assertArrayHasKey('repost_count', $correlation_analysis['correlations']);
        $this->assertArrayHasKey('like_count', $correlation_analysis['correlations']);
        $this->assertArrayHasKey('reply_count', $correlation_analysis['correlations']);
        $this->assertArrayHasKey('quote_count', $correlation_analysis['correlations']);
    }

    /**
     * Test time pattern analysis
     */
    public function test_analyze_time_patterns() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('analyze_time_patterns');
        $method->setAccessible(true);

        $time_analysis = $method->invoke($this->pattern_analyzer, $this->sample_reposts);

        $this->assertArrayHasKey('hourly_distribution', $time_analysis);
        $this->assertArrayHasKey('daily_distribution', $time_analysis);
        $this->assertArrayHasKey('hourly_avg_engagement', $time_analysis);
        $this->assertArrayHasKey('daily_avg_engagement', $time_analysis);
        $this->assertArrayHasKey('best_hours', $time_analysis);
        $this->assertArrayHasKey('best_days', $time_analysis);

        // Test hourly distribution (24 hours)
        $this->assertCount(24, $time_analysis['hourly_distribution']);
        $this->assertCount(24, $time_analysis['hourly_avg_engagement']);

        // Test daily distribution (7 days)
        $this->assertCount(7, $time_analysis['daily_distribution']);
        $this->assertCount(7, $time_analysis['daily_avg_engagement']);
    }

    /**
     * Test content pattern analysis
     */
    public function test_analyze_content_patterns() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('analyze_content_patterns');
        $method->setAccessible(true);

        $content_analysis = $method->invoke($this->pattern_analyzer, $this->sample_reposts);

        $this->assertArrayHasKey('top_words', $content_analysis);
        $this->assertArrayHasKey('top_phrases', $content_analysis);
        $this->assertArrayHasKey('content_type_distribution', $content_analysis);
        $this->assertArrayHasKey('content_type_effectiveness', $content_analysis);

        // Test word frequency analysis
        $this->assertIsArray($content_analysis['top_words']);
        $this->assertLessThanOrEqual(20, count($content_analysis['top_words']));

        // Test phrase analysis
        $this->assertIsArray($content_analysis['top_phrases']);
        $this->assertLessThanOrEqual(10, count($content_analysis['top_phrases']));
    }

    /**
     * Test correlation calculation
     */
    public function test_calculate_correlation() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('calculate_correlation');
        $method->setAccessible(true);

        // Test perfect positive correlation
        $perfect_positive = array(
            array('x' => 1, 'y' => 1),
            array('x' => 2, 'y' => 2),
            array('x' => 3, 'y' => 3)
        );
        $correlation = $method->invoke($this->pattern_analyzer, $perfect_positive, 'x', 'y');
        $this->assertEquals(1.0, $correlation, '', 0.01);

        // Test perfect negative correlation
        $perfect_negative = array(
            array('x' => 1, 'y' => 3),
            array('x' => 2, 'y' => 2),
            array('x' => 3, 'y' => 1)
        );
        $correlation = $method->invoke($this->pattern_analyzer, $perfect_negative, 'x', 'y');
        $this->assertEquals(-1.0, $correlation, '', 0.01);

        // Test no correlation
        $no_correlation = array(
            array('x' => 1, 'y' => 1),
            array('x' => 2, 'y' => 1),
            array('x' => 3, 'y' => 1)
        );
        $correlation = $method->invoke($this->pattern_analyzer, $no_correlation, 'x', 'y');
        $this->assertEquals(0.0, $correlation, '', 0.01);
    }

    /**
     * Test optimal length range finding
     */
    public function test_find_optimal_length_range() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('find_optimal_length_range');
        $method->setAccessible(true);

        $data = array(
            array('length' => 50, 'engagement' => 10),
            array('length' => 150, 'engagement' => 20),
            array('length' => 250, 'engagement' => 15)
        );

        $optimal_range = $method->invoke($this->pattern_analyzer, $data);

        $this->assertArrayHasKey('min', $optimal_range);
        $this->assertArrayHasKey('max', $optimal_range);
        $this->assertGreaterThanOrEqual(0, $optimal_range['min']);
        $this->assertLessThanOrEqual(280, $optimal_range['max']);
    }

    /**
     * Test optimal count finding
     */
    public function test_find_optimal_count() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('find_optimal_count');
        $method->setAccessible(true);

        $counts = array('0' => 5, '1' => 10, '2' => 8, '3' => 3);
        $engagement = array('0' => 25, '1' => 60, '2' => 40, '3' => 15);

        $optimal_count = $method->invoke($this->pattern_analyzer, $counts, $engagement);

        $this->assertIsInt($optimal_count);
        $this->assertGreaterThanOrEqual(0, $optimal_count);
    }

    /**
     * Test recommendation generation
     */
    public function test_generate_recommendations() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('generate_recommendations');
        $method->setAccessible(true);

        // Create mock analysis data
        $analysis = array(
            'length_patterns' => array(
                'optimal_length_range' => array('min' => 100, 'max' => 200)
            ),
            'tone_patterns' => array(
                'top_effective_tones' => array(
                    array('key' => 'question', 'value' => 1.5)
                )
            ),
            'format_patterns' => array(
                'hashtags' => array('optimal_count' => 2),
                'emojis' => array('optimal_count' => 1)
            ),
            'time_patterns' => array(
                'best_hours' => array(
                    array('key' => 10, 'value' => 25.5)
                )
            )
        );

        $recommendations = $method->invoke($this->pattern_analyzer, $analysis);

        $this->assertIsArray($recommendations);
        $this->assertGreaterThan(0, count($recommendations));

        // Test recommendation structure
        foreach ($recommendations as $recommendation) {
            $this->assertArrayHasKey('type', $recommendation);
            $this->assertArrayHasKey('title', $recommendation);
            $this->assertArrayHasKey('description', $recommendation);
            $this->assertArrayHasKey('priority', $recommendation);
        }
    }

    /**
     * Test phrase extraction
     */
    public function test_extract_phrases() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('extract_phrases');
        $method->setAccessible(true);

        $text = "This is a test tweet with some content for analysis";
        $phrases = $method->invoke($this->pattern_analyzer, $text);

        $this->assertIsArray($phrases);
        $this->assertGreaterThan(0, count($phrases));

        // Test that phrases are extracted correctly
        $this->assertContains('This is', $phrases);
        $this->assertContains('is a', $phrases);
        $this->assertContains('a test', $phrases);
    }

    /**
     * Test content type classification
     */
    public function test_classify_content_type() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('classify_content_type');
        $method->setAccessible(true);

        // Test question classification
        $question_text = "What do you think about this?";
        $type = $method->invoke($this->pattern_analyzer, $question_text);
        $this->assertEquals('question', $type);

        // Test tip classification
        $tip_text = "Pro tip: Always test your content";
        $type = $method->invoke($this->pattern_analyzer, $tip_text);
        $this->assertEquals('tip', $type);

        // Test story classification
        $story_text = "When I was starting my business";
        $type = $method->invoke($this->pattern_analyzer, $story_text);
        $this->assertEquals('story', $type);

        // Test call to action classification
        $cta_text = "Follow us for more tips";
        $type = $method->invoke($this->pattern_analyzer, $cta_text);
        $this->assertEquals('call_to_action', $type);

        // Test fact classification
        $fact_text = "The truth about social media";
        $type = $method->invoke($this->pattern_analyzer, $fact_text);
        $this->assertEquals('fact', $type);

        // Test general classification
        $general_text = "Just a regular tweet";
        $type = $method->invoke($this->pattern_analyzer, $general_text);
        $this->assertEquals('general', $type);
    }

    /**
     * Test AJAX handlers
     */
    public function test_ajax_handlers() {
        // Test analyze patterns AJAX
        $_POST['nonce'] = wp_create_nonce('xelite_pattern_analysis_nonce');
        $_POST['source_handle'] = 'test_account';
        $_POST['limit'] = 100;

        $this->database_mock->method('get_results')
            ->willReturn($this->sample_reposts);
        
        $this->database_mock->method('get_table_name')
            ->willReturn('wp_xelite_reposts');
        
        $this->database_mock->method('insert')
            ->willReturn(1);

        ob_start();
        $this->pattern_analyzer->ajax_analyze_patterns();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertTrue($response['success']);

        // Test get insights AJAX
        $this->database_mock->method('get_row')
            ->willReturn((object) array('analysis_data' => json_encode(array('test' => 'data'))));

        ob_start();
        $this->pattern_analyzer->ajax_get_pattern_insights();
        $output = ob_get_clean();

        $response = json_decode($output, true);
        $this->assertTrue($response['success']);
    }

    /**
     * Test new repost analysis trigger
     */
    public function test_analyze_new_repost() {
        $repost = (object) array(
            'source_handle' => 'test_account',
            'original_tweet_id' => '123456789',
            'original_text' => 'New test tweet'
        );

        // Mock database methods
        $this->database_mock->method('get_results')
            ->willReturn($this->sample_reposts);
        
        $this->database_mock->method('get_table_name')
            ->willReturn('wp_xelite_reposts');
        
        $this->database_mock->method('insert')
            ->willReturn(1);

        $this->pattern_analyzer->analyze_new_repost($repost);

        // Test that cache is cleared
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $cache_property = $reflection->getProperty('cache');
        $cache_property->setAccessible(true);
        
        $cache = $cache_property->getValue($this->pattern_analyzer);
        $this->assertEmpty($cache);
    }

    /**
     * Test helper methods
     */
    public function test_helper_methods() {
        $reflection = new ReflectionClass($this->pattern_analyzer);

        // Test get_top_items
        $get_top_items = $reflection->getMethod('get_top_items');
        $get_top_items->setAccessible(true);

        $items = array('a' => 10, 'b' => 20, 'c' => 15);
        $top_items = $get_top_items->invoke($this->pattern_analyzer, $items, 2);

        $this->assertCount(2, $top_items);
        $this->assertEquals('b', $top_items[0]['key']);
        $this->assertEquals(20, $top_items[0]['value']);

        // Test get_hour_name
        $get_hour_name = $reflection->getMethod('get_hour_name');
        $get_hour_name->setAccessible(true);

        $this->assertEquals('10 AM', $get_hour_name->invoke($this->pattern_analyzer, 10));
        $this->assertEquals('2 PM', $get_hour_name->invoke($this->pattern_analyzer, 14));
        $this->assertEquals('12 AM', $get_hour_name->invoke($this->pattern_analyzer, 0));
    }

    /**
     * Test engagement predictors identification
     */
    public function test_identify_engagement_predictors() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('identify_engagement_predictors');
        $method->setAccessible(true);

        $predictors = $method->invoke($this->pattern_analyzer, $this->sample_reposts);

        $this->assertIsArray($predictors);
        $this->assertGreaterThan(0, count($predictors));

        foreach ($predictors as $factor => $data) {
            $this->assertArrayHasKey('avg_engagement_with', $data);
            $this->assertArrayHasKey('avg_engagement_without', $data);
            $this->assertArrayHasKey('impact', $data);
            $this->assertArrayHasKey('frequency', $data);
        }
    }

    /**
     * Test data retrieval with filters
     */
    public function test_get_repost_data() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('get_repost_data');
        $method->setAccessible(true);

        // Mock database response
        $this->database_mock->method('get_results')
            ->willReturn($this->sample_reposts);

        // Test with source handle filter
        $reposts = $method->invoke($this->pattern_analyzer, 'test_account', 100);
        $this->assertIsArray($reposts);

        // Test without source handle filter
        $reposts = $method->invoke($this->pattern_analyzer, null, 100);
        $this->assertIsArray($reposts);
    }

    /**
     * Test analysis results storage
     */
    public function test_store_analysis_results() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $method = $reflection->getMethod('store_analysis_results');
        $method->setAccessible(true);

        $analysis = array('test' => 'data');
        $source_handle = 'test_account';

        $this->database_mock->method('insert')
            ->willReturn(1);

        $result = $method->invoke($this->pattern_analyzer, $source_handle, $analysis);
        
        // Method should not return anything (void)
        $this->assertNull($result);
    }

    /**
     * Test configuration initialization
     */
    public function test_config_initialization() {
        $reflection = new ReflectionClass($this->pattern_analyzer);
        $config_property = $reflection->getProperty('config');
        $config_property->setAccessible(true);
        
        $config = $config_property->getValue($this->pattern_analyzer);

        $this->assertArrayHasKey('length_categories', $config);
        $this->assertArrayHasKey('tone_patterns', $config);
        $this->assertArrayHasKey('format_patterns', $config);
        $this->assertArrayHasKey('engagement_metrics', $config);

        // Test length categories
        $this->assertArrayHasKey('short', $config['length_categories']);
        $this->assertArrayHasKey('medium', $config['length_categories']);
        $this->assertArrayHasKey('long', $config['length_categories']);

        // Test tone patterns
        $this->assertArrayHasKey('question', $config['tone_patterns']);
        $this->assertArrayHasKey('call_to_action', $config['tone_patterns']);
        $this->assertArrayHasKey('tip', $config['tone_patterns']);
    }

    /**
     * Test cache functionality
     */
    public function test_cache_functionality() {
        // Mock database response
        $this->database_mock->method('get_results')
            ->willReturn($this->sample_reposts);
        
        $this->database_mock->method('get_table_name')
            ->willReturn('wp_xelite_reposts');
        
        $this->database_mock->method('insert')
            ->willReturn(1);

        // First call should hit database
        $analysis1 = $this->pattern_analyzer->analyze_patterns('test_account', 100);

        // Second call should use cache
        $analysis2 = $this->pattern_analyzer->analyze_patterns('test_account', 100);

        $this->assertEquals($analysis1, $analysis2);
    }

    /**
     * Test error handling
     */
    public function test_error_handling() {
        // Test with empty repost data
        $this->database_mock->method('get_results')
            ->willReturn(array());

        $analysis = $this->pattern_analyzer->analyze_patterns('test_account', 100);
        $this->assertEmpty($analysis);
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clean up any test data
        parent::tearDown();
    }
} 