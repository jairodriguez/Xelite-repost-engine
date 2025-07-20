<?php
/**
 * Test Prompt Builder
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Test Prompt Builder Class
 */
class TestXeliteRepostEngine_Prompt_Builder extends TestCase {

    /**
     * Prompt builder instance
     *
     * @var XeliteRepostEngine_Prompt_Builder
     */
    private $prompt_builder;

    /**
     * Mock user meta service
     *
     * @var PHPUnit\Framework\MockObject\MockObject
     */
    private $mock_user_meta;

    /**
     * Mock pattern analyzer service
     *
     * @var PHPUnit\Framework\MockObject\MockObject
     */
    private $mock_pattern_analyzer;

    /**
     * Mock database service
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
     * Sample user context
     *
     * @var array
     */
    private $sample_user_context;

    /**
     * Sample repost patterns
     *
     * @var array
     */
    private $sample_repost_patterns;

    /**
     * Sample few-shot examples
     *
     * @var array
     */
    private $sample_few_shot_examples;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void {
        parent::setUp();

        // Create mock services
        $this->mock_user_meta = $this->createMock('XeliteRepostEngine_User_Meta');
        $this->mock_pattern_analyzer = $this->createMock('XeliteRepostEngine_Pattern_Analyzer');
        $this->mock_database = $this->createMock('XeliteRepostEngine_Database');
        $this->mock_logger = $this->createMock('XeliteRepostEngine_Logger');

        // Create prompt builder instance
        $this->prompt_builder = new XeliteRepostEngine_Prompt_Builder(
            $this->mock_user_meta,
            $this->mock_pattern_analyzer,
            $this->mock_database,
            $this->mock_logger
        );

        // Sample user context
        $this->sample_user_context = array(
            'writing_style' => 'Conversational and engaging',
            'offer' => 'Digital marketing consulting services',
            'audience' => 'Small business owners and entrepreneurs',
            'pain_points' => 'Struggling with social media marketing',
            'topic' => 'Social media growth strategies',
            'ikigai' => 'Helping businesses grow through digital marketing',
            'target_accounts' => array('@garyvee', '@naval')
        );

        // Sample repost patterns
        $this->sample_repost_patterns = array(
            'length_patterns' => array(
                'optimal_length_range' => array(
                    'min' => 100,
                    'max' => 150
                )
            ),
            'tone_patterns' => array(
                'top_effective_tones' => array(
                    array('key' => 'informative', 'value' => 8.5),
                    array('key' => 'conversational', 'value' => 7.8)
                )
            ),
            'format_patterns' => array(
                'hashtags' => array('optimal_count' => 2),
                'emojis' => array('optimal_count' => 1)
            ),
            'content_patterns' => array(
                'top_words' => array(
                    array('key' => 'growth', 'value' => 25),
                    array('key' => 'success', 'value' => 22),
                    array('key' => 'strategy', 'value' => 18)
                )
            )
        );

        // Sample few-shot examples
        $this->sample_few_shot_examples = array(
            array(
                'original_text' => 'Want to grow your business? Start with these 3 simple strategies that actually work.',
                'engagement_score' => 8.5,
                'source_handle' => '@garyvee'
            ),
            array(
                'original_text' => 'The best marketing strategy is building something worth talking about.',
                'engagement_score' => 9.2,
                'source_handle' => '@naval'
            )
        );
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf('XeliteRepostEngine_Prompt_Builder', $this->prompt_builder);
    }

    /**
     * Test building content generation prompt
     */
    public function test_build_content_generation_prompt() {
        $user_id = 1;
        $options = array('max_examples' => 2);

        // Mock user meta responses
        $this->mock_user_meta->method('get_user_meta')
            ->willReturnMap(array(
                array($user_id, 'writing-style', 'Conversational and engaging'),
                array($user_id, 'irresistible-offer', 'Digital marketing consulting services'),
                array($user_id, 'dream-client', 'Small business owners and entrepreneurs'),
                array($user_id, 'dream-client-pain-points', 'Struggling with social media marketing'),
                array($user_id, 'topic', 'Social media growth strategies'),
                array($user_id, 'ikigai', 'Helping businesses grow through digital marketing'),
                array($user_id, 'target_accounts', array('@garyvee', '@naval'))
            ));

        // Mock pattern analyzer response
        $this->mock_pattern_analyzer->method('analyze_account_patterns')
            ->willReturn($this->sample_repost_patterns);

        // Mock database response
        $this->mock_database->method('get_top_reposts_by_account')
            ->willReturn($this->sample_few_shot_examples);

        $result = $this->prompt_builder->build_content_generation_prompt($user_id, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('prompt', $result);
        $this->assertArrayHasKey('template_version', $result);
        $this->assertArrayHasKey('user_context', $result);
        $this->assertArrayHasKey('repost_patterns', $result);
        $this->assertArrayHasKey('few_shot_examples_count', $result);
        $this->assertArrayHasKey('estimated_tokens', $result);
        $this->assertArrayHasKey('max_tokens', $result);
        $this->assertArrayHasKey('temperature', $result);

        $this->assertIsString($result['prompt']);
        $this->assertNotEmpty($result['prompt']);
        $this->assertGreaterThan(0, $result['estimated_tokens']);
        $this->assertEquals(2, $result['few_shot_examples_count']);
    }

    /**
     * Test building content generation prompt with empty user context
     */
    public function test_build_content_generation_prompt_empty_context() {
        $user_id = 1;

        // Mock empty user meta responses
        $this->mock_user_meta->method('get_user_meta')
            ->willReturn('');

        // Mock pattern analyzer response
        $this->mock_pattern_analyzer->method('analyze_account_patterns')
            ->willReturn(array());

        // Mock database response
        $this->mock_database->method('get_top_reposts_by_account')
            ->willReturn(array());

        $result = $this->prompt_builder->build_content_generation_prompt($user_id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('prompt', $result);
        $this->assertStringContainsString('No specific user context available', $result['prompt']);
        $this->assertStringContainsString('No repost patterns available', $result['prompt']);
        $this->assertStringContainsString('No examples available', $result['prompt']);
    }

    /**
     * Test building optimization prompt
     */
    public function test_build_optimization_prompt() {
        $original_content = 'Want to grow your business? Here are some tips.';
        $user_id = 1;
        $options = array('optimize_for_engagement' => true);

        // Mock user meta responses
        $this->mock_user_meta->method('get_user_meta')
            ->willReturnMap(array(
                array($user_id, 'writing-style', 'Conversational and engaging'),
                array($user_id, 'irresistible-offer', 'Digital marketing consulting services'),
                array($user_id, 'dream-client', 'Small business owners and entrepreneurs'),
                array($user_id, 'dream-client-pain-points', 'Struggling with social media marketing'),
                array($user_id, 'topic', 'Social media growth strategies'),
                array($user_id, 'ikigai', 'Helping businesses grow through digital marketing'),
                array($user_id, 'target_accounts', array('@garyvee', '@naval'))
            ));

        // Mock pattern analyzer response
        $this->mock_pattern_analyzer->method('analyze_account_patterns')
            ->willReturn($this->sample_repost_patterns);

        $result = $this->prompt_builder->build_optimization_prompt($original_content, $user_id, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('prompt', $result);
        $this->assertArrayHasKey('template_version', $result);
        $this->assertArrayHasKey('original_content_length', $result);
        $this->assertArrayHasKey('estimated_tokens', $result);
        $this->assertArrayHasKey('max_tokens', $result);
        $this->assertArrayHasKey('temperature', $result);

        $this->assertStringContainsString($original_content, $result['prompt']);
        $this->assertEquals(strlen($original_content), $result['original_content_length']);
    }

    /**
     * Test A/B test creation
     */
    public function test_create_ab_test() {
        $user_id = 1;
        $variations = array(
            'Variation 1: Focus on pain points',
            'Variation 2: Focus on benefits',
            'Variation 3: Focus on social proof'
        );
        $options = array('test_duration' => '7 days');

        $result = $this->prompt_builder->create_ab_test($user_id, $variations, $options);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('test_id', $result);
        $this->assertArrayHasKey('user_id', $result);
        $this->assertArrayHasKey('variations', $result);
        $this->assertArrayHasKey('options', $result);
        $this->assertArrayHasKey('created_at', $result);
        $this->assertArrayHasKey('status', $result);

        $this->assertEquals($user_id, $result['user_id']);
        $this->assertEquals($variations, $result['variations']);
        $this->assertEquals($options, $result['options']);
        $this->assertEquals('active', $result['status']);
        $this->assertStringStartsWith('prompt_ab_', $result['test_id']);
    }

    /**
     * Test getting A/B test variation
     */
    public function test_get_ab_test_variation() {
        $test_id = 'prompt_ab_test123';
        $user_id = 1;
        $variations = array(
            'Variation 1: Focus on pain points',
            'Variation 2: Focus on benefits'
        );

        // Create test configuration
        $test_config = array(
            'test_id' => $test_id,
            'user_id' => $user_id,
            'variations' => $variations,
            'options' => array(),
            'created_at' => current_time('mysql'),
            'status' => 'active'
        );

        update_option("xelite_prompt_ab_test_{$test_id}", $test_config);

        $result = $this->prompt_builder->get_ab_test_variation($test_id, $user_id);

        $this->assertIsString($result);
        $this->assertContains($result, $variations);

        // Clean up
        delete_option("xelite_prompt_ab_test_{$test_id}");
        delete_option("xelite_prompt_ab_tracking_{$test_id}");
    }

    /**
     * Test getting A/B test variation with inactive test
     */
    public function test_get_ab_test_variation_inactive() {
        $test_id = 'prompt_ab_test123';
        $user_id = 1;

        // Create inactive test configuration
        $test_config = array(
            'test_id' => $test_id,
            'user_id' => $user_id,
            'variations' => array('Variation 1', 'Variation 2'),
            'options' => array(),
            'created_at' => current_time('mysql'),
            'status' => 'inactive'
        );

        update_option("xelite_prompt_ab_test_{$test_id}", $test_config);

        $result = $this->prompt_builder->get_ab_test_variation($test_id, $user_id);

        $this->assertNull($result);

        // Clean up
        delete_option("xelite_prompt_ab_test_{$test_id}");
    }

    /**
     * Test recording A/B test result
     */
    public function test_record_ab_test_result() {
        $test_id = 'prompt_ab_test123';
        $variation_index = 0;
        $result = array(
            'engagement_score' => 8.5,
            'was_reposted' => true,
            'generated_content' => 'Test content'
        );

        $this->prompt_builder->record_ab_test_result($test_id, $variation_index, $result);

        $stored_results = get_option("xelite_prompt_ab_results_{$test_id}");
        
        $this->assertIsArray($stored_results);
        $this->assertArrayHasKey($variation_index, $stored_results);
        $this->assertCount(1, $stored_results[$variation_index]);
        $this->assertEquals($result['engagement_score'], $stored_results[$variation_index][0]['engagement_score']);
        $this->assertEquals($result['was_reposted'], $stored_results[$variation_index][0]['was_reposted']);

        // Clean up
        delete_option("xelite_prompt_ab_results_{$test_id}");
    }

    /**
     * Test getting A/B test analytics
     */
    public function test_get_ab_test_analytics() {
        $test_id = 'prompt_ab_test123';
        $user_id = 1;
        $variations = array(
            'Variation 1: Focus on pain points',
            'Variation 2: Focus on benefits'
        );

        // Create test configuration
        $test_config = array(
            'test_id' => $test_id,
            'user_id' => $user_id,
            'variations' => $variations,
            'options' => array(),
            'created_at' => current_time('mysql'),
            'status' => 'active'
        );

        update_option("xelite_prompt_ab_test_{$test_id}", $test_config);

        // Create tracking data
        $tracking = array(
            0 => array('selections' => 10, 'users' => array(1, 2, 3)),
            1 => array('selections' => 8, 'users' => array(4, 5))
        );
        update_option("xelite_prompt_ab_tracking_{$test_id}", $tracking);

        // Create results data
        $results = array(
            0 => array(
                array('engagement_score' => 8.5, 'was_reposted' => true),
                array('engagement_score' => 7.2, 'was_reposted' => false)
            ),
            1 => array(
                array('engagement_score' => 9.1, 'was_reposted' => true),
                array('engagement_score' => 8.8, 'was_reposted' => true)
            )
        );
        update_option("xelite_prompt_ab_results_{$test_id}", $results);

        $analytics = $this->prompt_builder->get_ab_test_analytics($test_id);

        $this->assertIsArray($analytics);
        $this->assertArrayHasKey('test_id', $analytics);
        $this->assertArrayHasKey('status', $analytics);
        $this->assertArrayHasKey('variations', $analytics);

        $this->assertEquals($test_id, $analytics['test_id']);
        $this->assertEquals('active', $analytics['status']);
        $this->assertCount(2, $analytics['variations']);

        // Check variation 0 analytics
        $this->assertEquals(10, $analytics['variations'][0]['selections']);
        $this->assertEquals(3, $analytics['variations'][0]['unique_users']);
        $this->assertEquals(2, $analytics['variations'][0]['results_count']);
        $this->assertEquals(7.85, $analytics['variations'][0]['average_engagement'], '', 0.01);
        $this->assertEquals(0.5, $analytics['variations'][0]['repost_rate'], '', 0.01);

        // Check variation 1 analytics
        $this->assertEquals(8, $analytics['variations'][1]['selections']);
        $this->assertEquals(2, $analytics['variations'][1]['unique_users']);
        $this->assertEquals(2, $analytics['variations'][1]['results_count']);
        $this->assertEquals(8.95, $analytics['variations'][1]['average_engagement'], '', 0.01);
        $this->assertEquals(1.0, $analytics['variations'][1]['repost_rate'], '', 0.01);

        // Clean up
        delete_option("xelite_prompt_ab_test_{$test_id}");
        delete_option("xelite_prompt_ab_tracking_{$test_id}");
        delete_option("xelite_prompt_ab_results_{$test_id}");
    }

    /**
     * Test getting A/B test analytics with non-existent test
     */
    public function test_get_ab_test_analytics_not_found() {
        $test_id = 'non_existent_test';

        $analytics = $this->prompt_builder->get_ab_test_analytics($test_id);

        $this->assertNull($analytics);
    }

    /**
     * Test token count estimation
     */
    public function test_estimate_token_count() {
        $text = 'This is a test prompt with some content to estimate token count.';
        
        // Use reflection to test private method
        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('estimate_token_count');
        $method->setAccessible(true);

        $estimated_tokens = $method->invoke($this->prompt_builder, $text);

        $this->assertIsInt($estimated_tokens);
        $this->assertGreaterThan(0, $estimated_tokens);
        
        // Rough estimation: 1 token ≈ 4 characters
        $expected_tokens = ceil(strlen($text) / 4);
        $this->assertEquals($expected_tokens, $estimated_tokens);
    }

    /**
     * Test AJAX handlers exist
     */
    public function test_ajax_handlers_exist() {
        $reflection = new ReflectionClass($this->prompt_builder);
        
        $this->assertTrue($reflection->hasMethod('ajax_test_prompt'));
        $this->assertTrue($reflection->hasMethod('ajax_ab_test_prompts'));
        $this->assertTrue($reflection->hasMethod('ajax_get_prompt_analytics'));
    }

    /**
     * Test logging functionality
     */
    public function test_logging() {
        $this->mock_logger->expects($this->atLeastOnce())
            ->method('log')
            ->with(
                $this->equalTo('debug'),
                $this->stringContains('[Prompt Builder]'),
                $this->isType('array')
            );

        // Trigger a method that logs
        $user_id = 1;
        $this->mock_user_meta->method('get_user_meta')->willReturn('');
        $this->mock_pattern_analyzer->method('analyze_account_patterns')->willReturn(array());
        $this->mock_database->method('get_top_reposts_by_account')->willReturn(array());

        $this->prompt_builder->build_content_generation_prompt($user_id);
    }

    /**
     * Test edge cases
     */
    public function test_edge_cases() {
        // Test with very long content
        $long_content = str_repeat('This is a very long content string. ', 100);
        $user_id = 1;

        $this->mock_user_meta->method('get_user_meta')->willReturn('');
        $this->mock_pattern_analyzer->method('analyze_account_patterns')->willReturn(array());

        $result = $this->prompt_builder->build_optimization_prompt($long_content, $user_id);

        $this->assertIsArray($result);
        $this->assertArrayHasKey('prompt', $result);
        $this->assertStringContainsString($long_content, $result['prompt']);

        // Test with special characters
        $special_content = 'Content with special chars: @#$%^&*()_+-=[]{}|;:,.<>?';
        $result = $this->prompt_builder->build_optimization_prompt($special_content, $user_id);

        $this->assertIsArray($result);
        $this->assertStringContainsString($special_content, $result['prompt']);
    }

    /**
     * Test prompt template system
     */
    public function test_prompt_templates() {
        $reflection = new ReflectionClass($this->prompt_builder);
        $property = $reflection->getProperty('prompt_templates');
        $property->setAccessible(true);

        $templates = $property->getValue($this->prompt_builder);

        $this->assertIsArray($templates);
        $this->assertArrayHasKey('content_generation', $templates);
        $this->assertArrayHasKey('content_optimization', $templates);
        $this->assertArrayHasKey('hashtag_suggestion', $templates);
        $this->assertArrayHasKey('engagement_prediction', $templates);

        foreach ($templates as $template_type => $template_data) {
            $this->assertArrayHasKey('version', $template_data);
            $this->assertArrayHasKey('template', $template_data);
            $this->assertArrayHasKey('max_tokens', $template_data);
            $this->assertArrayHasKey('temperature', $template_data);

            $this->assertIsString($template_data['version']);
            $this->assertIsString($template_data['template']);
            $this->assertIsInt($template_data['max_tokens']);
            $this->assertIsFloat($template_data['temperature']);
        }
    }

    /**
     * Test user context extraction
     */
    public function test_extract_user_context() {
        $user_id = 1;

        // Mock user meta responses
        $this->mock_user_meta->method('get_user_meta')
            ->willReturnMap(array(
                array($user_id, 'writing-style', 'Conversational'),
                array($user_id, 'irresistible-offer', 'Digital marketing'),
                array($user_id, 'dream-client', 'Small business owners'),
                array($user_id, 'dream-client-pain-points', 'Marketing struggles'),
                array($user_id, 'topic', 'Social media'),
                array($user_id, 'ikigai', 'Helping businesses'),
                array($user_id, 'target_accounts', array('@garyvee', '@naval')),
                array($user_id, 'content_preferences', 'Educational content')
            ));

        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('extract_user_context');
        $method->setAccessible(true);

        $context = $method->invoke($this->prompt_builder, $user_id);

        $this->assertIsArray($context);
        $this->assertArrayHasKey('writing_style', $context);
        $this->assertArrayHasKey('offer', $context);
        $this->assertArrayHasKey('audience', $context);
        $this->assertArrayHasKey('pain_points', $context);
        $this->assertArrayHasKey('topic', $context);
        $this->assertArrayHasKey('ikigai', $context);
        $this->assertArrayHasKey('target_accounts', $context);
        $this->assertArrayHasKey('content_preferences', $context);

        $this->assertEquals('Conversational', $context['writing_style']);
        $this->assertEquals('Digital marketing', $context['offer']);
        $this->assertEquals(array('@garyvee', '@naval'), $context['target_accounts']);
    }

    /**
     * Test repost patterns analysis
     */
    public function test_analyze_repost_patterns() {
        $user_id = 1;
        $options = array('timeframe' => '30 days');

        // Mock user meta responses
        $this->mock_user_meta->method('get_user_meta')
            ->willReturn(array('@garyvee', '@naval'));

        // Mock pattern analyzer responses
        $this->mock_pattern_analyzer->method('analyze_account_patterns')
            ->willReturnMap(array(
                array('@garyvee', $options, $this->sample_repost_patterns),
                array('@naval', $options, array(
                    'length_patterns' => array(
                        'optimal_length_range' => array('min' => 80, 'max' => 120)
                    ),
                    'tone_patterns' => array(
                        'top_effective_tones' => array(
                            array('key' => 'philosophical', 'value' => 9.0)
                        )
                    )
                ))
            ));

        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('analyze_repost_patterns');
        $method->setAccessible(true);

        $patterns = $method->invoke($this->prompt_builder, $user_id, $options);

        $this->assertIsArray($patterns);
        $this->assertArrayHasKey('length_patterns', $patterns);
        $this->assertArrayHasKey('tone_patterns', $patterns);
        $this->assertArrayHasKey('format_patterns', $patterns);
        $this->assertArrayHasKey('content_patterns', $patterns);
    }

    /**
     * Test few-shot examples retrieval
     */
    public function test_get_few_shot_examples() {
        $user_id = 1;
        $options = array('max_examples' => 3);

        // Mock user meta responses
        $this->mock_user_meta->method('get_user_meta')
            ->willReturn(array('@garyvee', '@naval'));

        // Mock database responses
        $this->mock_database->method('get_top_reposts_by_account')
            ->willReturnMap(array(
                array('@garyvee', 3, $this->sample_few_shot_examples),
                array('@naval', 3, array(
                    array(
                        'original_text' => 'The best marketing is building something worth talking about.',
                        'engagement_score' => 9.5,
                        'source_handle' => '@naval'
                    )
                ))
            ));

        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('get_few_shot_examples');
        $method->setAccessible(true);

        $examples = $method->invoke($this->prompt_builder, $user_id, $options);

        $this->assertIsArray($examples);
        $this->assertLessThanOrEqual(3, count($examples));
        
        // Should be sorted by engagement score
        if (count($examples) > 1) {
            $this->assertGreaterThanOrEqual(
                $examples[1]['engagement_score'],
                $examples[0]['engagement_score']
            );
        }
    }

    /**
     * Test template population
     */
    public function test_populate_template() {
        $template = 'User: {{user_context}}\nPatterns: {{repost_patterns}}\nExamples: {{few_shot_examples}}';
        $data = array(
            'user_context' => array('writing_style' => 'Conversational'),
            'repost_patterns' => array('length_patterns' => array()),
            'few_shot_examples' => array(array('text' => 'Example'))
        );

        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('populate_template');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, $template, $data);

        $this->assertIsString($result);
        $this->assertStringNotContainsString('{{user_context}}', $result);
        $this->assertStringNotContainsString('{{repost_patterns}}', $result);
        $this->assertStringNotContainsString('{{few_shot_examples}}', $result);
        $this->assertStringContainsString('Writing Style: Conversational', $result);
    }

    /**
     * Test format user context
     */
    public function test_format_user_context() {
        $context = array(
            'writing_style' => 'Conversational',
            'offer' => 'Digital marketing',
            'audience' => 'Small business owners',
            'pain_points' => 'Marketing struggles',
            'topic' => 'Social media',
            'ikigai' => 'Helping businesses'
        );

        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('format_user_context');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, $context);

        $this->assertIsString($result);
        $this->assertStringContainsString('Writing Style: Conversational', $result);
        $this->assertStringContainsString('Offer: Digital marketing', $result);
        $this->assertStringContainsString('Target Audience: Small business owners', $result);
        $this->assertStringContainsString('Pain Points: Marketing struggles', $result);
        $this->assertStringContainsString('Topic: Social media', $result);
        $this->assertStringContainsString('Ikigai: Helping businesses', $result);
    }

    /**
     * Test format user context with empty data
     */
    public function test_format_user_context_empty() {
        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('format_user_context');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, array());

        $this->assertIsString($result);
        $this->assertStringContainsString('No specific user context available', $result);
    }

    /**
     * Test format repost patterns
     */
    public function test_format_repost_patterns() {
        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('format_repost_patterns');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, $this->sample_repost_patterns);

        $this->assertIsString($result);
        $this->assertStringContainsString('Optimal Length: 100-150 characters', $result);
        $this->assertStringContainsString('Most Effective Tones: Informative (8.5)', $result);
        $this->assertStringContainsString('Format Recommendations: hashtags: 2, emojis: 1', $result);
        $this->assertStringContainsString('High-Engagement Words: growth (25)', $result);
    }

    /**
     * Test format repost patterns with empty data
     */
    public function test_format_repost_patterns_empty() {
        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('format_repost_patterns');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, array());

        $this->assertIsString($result);
        $this->assertStringContainsString('No repost patterns available', $result);
    }

    /**
     * Test format few-shot examples
     */
    public function test_format_few_shot_examples() {
        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('format_few_shot_examples');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, $this->sample_few_shot_examples);

        $this->assertIsString($result);
        $this->assertStringContainsString('Example 1:', $result);
        $this->assertStringContainsString('Example 2:', $result);
        $this->assertStringContainsString('Want to grow your business?', $result);
        $this->assertStringContainsString('The best marketing strategy', $result);
        $this->assertStringContainsString('Engagement Score: 8.5', $result);
        $this->assertStringContainsString('Engagement Score: 9.2', $result);
    }

    /**
     * Test format few-shot examples with empty data
     */
    public function test_format_few_shot_examples_empty() {
        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('format_few_shot_examples');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, array());

        $this->assertIsString($result);
        $this->assertStringContainsString('No examples available', $result);
    }

    /**
     * Test format options
     */
    public function test_format_options() {
        $options = array(
            'max_tokens' => 280,
            'temperature' => 0.7,
            'optimize_for_engagement' => true
        );

        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('format_options');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, $options);

        $this->assertIsString($result);
        $this->assertStringContainsString('Max tokens: 280', $result);
        $this->assertStringContainsString('Temperature: 0.7', $result);
        $this->assertStringContainsString('Optimize for engagement: 1', $result);
    }

    /**
     * Test format options with empty data
     */
    public function test_format_options_empty() {
        $reflection = new ReflectionClass($this->prompt_builder);
        $method = $reflection->getMethod('format_options');
        $method->setAccessible(true);

        $result = $method->invoke($this->prompt_builder, array());

        $this->assertIsString($result);
        $this->assertStringContainsString('No specific options provided', $result);
    }
} 