<?php
/**
 * Test OpenAI Integration
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

use PHPUnit\Framework\TestCase;

/**
 * Test OpenAI Integration Class
 */
class TestXeliteRepostEngine_OpenAI extends TestCase {

    /**
     * OpenAI service instance
     *
     * @var XeliteRepostEngine_OpenAI
     */
    private $openai;

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
     * Sample patterns
     *
     * @var array
     */
    private $sample_patterns;

    /**
     * Set up test fixtures
     */
    protected function setUp(): void {
        parent::setUp();

        // Create mock logger
        $this->mock_logger = $this->createMock('XeliteRepostEngine_Logger');

        // Create OpenAI service instance
        $this->openai = new XeliteRepostEngine_OpenAI($this->mock_logger);

        // Sample user context
        $this->sample_user_context = array(
            'writing_style' => 'Conversational and engaging',
            'offer' => 'Digital marketing consulting services',
            'audience' => 'Small business owners and entrepreneurs',
            'pain_points' => 'Struggling with social media marketing',
            'topic' => 'Social media growth strategies'
        );

        // Sample patterns
        $this->sample_patterns = array(
            'length_patterns' => array(
                'optimal_length_range' => array(
                    'min' => 100,
                    'max' => 150
                )
            ),
            'tone_patterns' => array(
                'top_effective_tones' => array(
                    array('key' => 'informative', 'value' => 8.5)
                )
            ),
            'format_patterns' => array(
                'hashtags' => array('optimal_count' => 2),
                'emojis' => array('optimal_count' => 1)
            ),
            'content_patterns' => array(
                'top_words' => array(
                    array('key' => 'growth', 'value' => 25),
                    array('key' => 'success', 'value' => 22)
                )
            )
        );
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf('XeliteRepostEngine_OpenAI', $this->openai);
    }

    /**
     * Test API key validation with empty key
     */
    public function test_validate_api_key_empty() {
        $result = $this->openai->validate_api_key('');
        
        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertEquals('API key is empty', $result['error']);
    }

    /**
     * Test API key validation with invalid key
     */
    public function test_validate_api_key_invalid() {
        // Mock wp_remote_request to return error
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return new WP_Error('http_request_failed', 'Invalid API key');
        }, 10, 3);

        $result = $this->openai->validate_api_key('invalid_key');
        
        $this->assertIsArray($result);
        $this->assertFalse($result['valid']);
        $this->assertStringContainsString('Invalid API key', $result['error']);

        remove_all_filters('pre_http_request');
    }

    /**
     * Test API key validation with valid key
     */
    public function test_validate_api_key_valid() {
        // Mock wp_remote_request to return success
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'data' => array(
                        array('id' => 'gpt-4'),
                        array('id' => 'gpt-3.5-turbo')
                    )
                ))
            );
        }, 10, 3);

        $result = $this->openai->validate_api_key('valid_key');
        
        $this->assertIsArray($result);
        $this->assertTrue($result['valid']);
        $this->assertArrayHasKey('models', $result);

        remove_all_filters('pre_http_request');
    }

    /**
     * Test connection test
     */
    public function test_test_connection() {
        // Mock validate_api_key to return valid
        $openai = $this->getMockBuilder('XeliteRepostEngine_OpenAI')
            ->setConstructorArgs(array($this->mock_logger))
            ->onlyMethods(array('validate_api_key', 'generate_completion'))
            ->getMock();

        $openai->method('validate_api_key')
            ->willReturn(array('valid' => true, 'models' => array()));

        $openai->method('generate_completion')
            ->willReturn(array(
                'choices' => array(array('text' => 'Test response')),
                'model' => 'gpt-4'
            ));

        $result = $openai->test_connection();
        
        $this->assertIsArray($result);
        $this->assertTrue($result['connected']);
        $this->assertArrayHasKey('response', $result);
    }

    /**
     * Test content generation
     */
    public function test_generate_content() {
        // Mock validate_api_key and generate_completion
        $openai = $this->getMockBuilder('XeliteRepostEngine_OpenAI')
            ->setConstructorArgs(array($this->mock_logger))
            ->onlyMethods(array('validate_api_key', 'generate_completion'))
            ->getMock();

        $openai->method('validate_api_key')
            ->willReturn(array('valid' => true));

        $openai->method('generate_completion')
            ->willReturn(array(
                'choices' => array(array('text' => 'Generated content here')),
                'model' => 'gpt-4',
                'usage' => array('total_tokens' => 150)
            ));

        $result = $openai->generate_content($this->sample_user_context, $this->sample_patterns);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
        $this->assertArrayHasKey('prompt', $result);
        $this->assertArrayHasKey('usage', $result);
        $this->assertArrayHasKey('model', $result);
    }

    /**
     * Test content generation with invalid API key
     */
    public function test_generate_content_invalid_api_key() {
        $openai = $this->getMockBuilder('XeliteRepostEngine_OpenAI')
            ->setConstructorArgs(array($this->mock_logger))
            ->onlyMethods(array('validate_api_key'))
            ->getMock();

        $openai->method('validate_api_key')
            ->willReturn(array('valid' => false, 'error' => 'Invalid API key'));

        $result = $openai->generate_content($this->sample_user_context, $this->sample_patterns);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('invalid_api_key', $result->get_error_code());
    }

    /**
     * Test completion generation
     */
    public function test_generate_completion() {
        // Mock wp_remote_request to return success
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'choices' => array(array('text' => 'Generated completion')),
                    'model' => 'gpt-4',
                    'usage' => array('total_tokens' => 100)
                ))
            );
        }, 10, 3);

        $result = $this->openai->generate_completion('Test prompt', 100);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('choices', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('usage', $result);

        remove_all_filters('pre_http_request');
    }

    /**
     * Test completion generation with API error
     */
    public function test_generate_completion_api_error() {
        // Mock wp_remote_request to return error
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 400),
                'body' => json_encode(array(
                    'error' => array('message' => 'Invalid request')
                ))
            );
        }, 10, 3);

        $result = $this->openai->generate_completion('Test prompt', 100);
        
        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('openai_api_error', $result->get_error_code());

        remove_all_filters('pre_http_request');
    }

    /**
     * Test chat completion generation
     */
    public function test_generate_chat_completion() {
        // Mock wp_remote_request to return success
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'choices' => array(array('message' => array('content' => 'Chat response'))),
                    'model' => 'gpt-4',
                    'usage' => array('total_tokens' => 120)
                ))
            );
        }, 10, 3);

        $messages = array(
            array('role' => 'user', 'content' => 'Hello')
        );

        $result = $this->openai->generate_chat_completion($messages, 100);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('choices', $result);
        $this->assertArrayHasKey('model', $result);

        remove_all_filters('pre_http_request');
    }

    /**
     * Test prompt building
     */
    public function test_build_content_prompt() {
        $reflection = new ReflectionClass($this->openai);
        $method = $reflection->getMethod('build_content_prompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($this->openai, $this->sample_user_context, $this->sample_patterns);

        $this->assertIsString($prompt);
        $this->assertStringContainsString('expert social media content creator', $prompt);
        $this->assertStringContainsString('User Context:', $prompt);
        $this->assertStringContainsString('Pattern Analysis', $prompt);
        $this->assertStringContainsString('Requirements:', $prompt);
        $this->assertStringContainsString('Conversational and engaging', $prompt);
        $this->assertStringContainsString('100-150 characters', $prompt);
        $this->assertStringContainsString('informative', $prompt);
    }

    /**
     * Test content formatting
     */
    public function test_format_generated_content() {
        $reflection = new ReflectionClass($this->openai);
        $method = $reflection->getMethod('format_generated_content');
        $method->setAccessible(true);

        $completion = array(
            'choices' => array(array('text' => "1. First variation\n\n2. Second variation\n\n3. Third variation")),
            'model' => 'gpt-4',
            'usage' => array('total_tokens' => 150)
        );

        $result = $method->invoke($this->openai, $completion, array());

        $this->assertIsArray($result);
        $this->assertArrayHasKey('variations', $result);
        $this->assertArrayHasKey('raw_text', $result);
        $this->assertArrayHasKey('model', $result);
        $this->assertArrayHasKey('usage', $result);

        $this->assertCount(3, $result['variations']);
        $this->assertEquals('gpt-4', $result['model']);
    }

    /**
     * Test API request with no API key
     */
    public function test_make_api_request_no_key() {
        $reflection = new ReflectionClass($this->openai);
        $method = $reflection->getMethod('make_api_request');
        $method->setAccessible(true);

        // Set API key to empty
        $reflection_property = $reflection->getProperty('api_key');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->openai, '');

        $result = $method->invoke($this->openai, 'test', 'GET');

        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('no_api_key', $result->get_error_code());
    }

    /**
     * Test API request with network error
     */
    public function test_make_api_request_network_error() {
        // Mock wp_remote_request to return error
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return new WP_Error('http_request_failed', 'Network error');
        }, 10, 3);

        $reflection = new ReflectionClass($this->openai);
        $method = $reflection->getMethod('make_api_request');
        $method->setAccessible(true);

        // Set API key
        $reflection_property = $reflection->getProperty('api_key');
        $reflection_property->setAccessible(true);
        $reflection_property->setValue($this->openai, 'test_key');

        $result = $method->invoke($this->openai, 'test', 'GET');

        $this->assertInstanceOf('WP_Error', $result);
        $this->assertEquals('http_request_failed', $result->get_error_code());

        remove_all_filters('pre_http_request');
    }

    /**
     * Test rate limiting
     */
    public function test_rate_limiting() {
        $reflection = new ReflectionClass($this->openai);
        $check_method = $reflection->getMethod('check_rate_limits');
        $update_method = $reflection->getMethod('update_rate_limits');
        $check_method->setAccessible(true);
        $update_method->setAccessible(true);

        // Test initial state
        $this->assertTrue($check_method->invoke($this->openai));

        // Update with usage
        $update_method->invoke($this->openai, array('total_tokens' => 1000));

        // Should still be within limits
        $this->assertTrue($check_method->invoke($this->openai));
    }

    /**
     * Test rate limit exceeded
     */
    public function test_rate_limit_exceeded() {
        $reflection = new ReflectionClass($this->openai);
        $check_method = $reflection->getMethod('check_rate_limits');
        $update_method = $reflection->getMethod('update_rate_limits');
        $check_method->setAccessible(true);
        $update_method->setAccessible(true);

        // Simulate many requests
        for ($i = 0; $i < 70; $i++) {
            $update_method->invoke($this->openai, array('total_tokens' => 1000));
        }

        // Should exceed rate limit
        $this->assertFalse($check_method->invoke($this->openai));
    }

    /**
     * Test caching functionality
     */
    public function test_caching() {
        $reflection = new ReflectionClass($this->openai);
        $cache_method = $reflection->getMethod('cache_generated_content');
        $get_cache_method = $reflection->getMethod('get_cached_content');
        $clear_cache_method = $reflection->getMethod('clear_cached_content');
        
        $cache_method->setAccessible(true);
        $get_cache_method->setAccessible(true);
        $clear_cache_method->setAccessible(true);

        $content = array('variations' => array('Test content'));

        // Test caching
        $cache_method->invoke($this->openai, $this->sample_user_context, $this->sample_patterns, $content);

        // Test retrieving cached content
        $cached = $get_cache_method->invoke($this->openai, $this->sample_user_context, $this->sample_patterns);
        $this->assertEquals($content, $cached);

        // Test clearing cache
        $result = $clear_cache_method->invoke($this->openai, $this->sample_user_context, $this->sample_patterns);
        $this->assertTrue($result);

        // Cache should be cleared
        $cached = $get_cache_method->invoke($this->openai, $this->sample_user_context, $this->sample_patterns);
        $this->assertFalse($cached);
    }

    /**
     * Test getting models
     */
    public function test_get_models() {
        // Mock wp_remote_request to return models
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'data' => array(
                        array('id' => 'gpt-4'),
                        array('id' => 'gpt-3.5-turbo')
                    )
                ))
            );
        }, 10, 3);

        $result = $this->openai->get_models();
        
        $this->assertIsArray($result);
        $this->assertCount(2, $result);
        $this->assertEquals('gpt-4', $result[0]['id']);

        remove_all_filters('pre_http_request');
    }

    /**
     * Test getting usage statistics
     */
    public function test_get_usage() {
        // Mock wp_remote_request to return usage data
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode(array(
                    'total_usage' => 1000,
                    'daily_costs' => array()
                ))
            );
        }, 10, 3);

        $result = $this->openai->get_usage('2024-01-01');
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('total_usage', $result);

        remove_all_filters('pre_http_request');
    }

    /**
     * Test AJAX handlers exist
     */
    public function test_ajax_handlers_exist() {
        $reflection = new ReflectionClass($this->openai);
        
        $this->assertTrue($reflection->hasMethod('ajax_test_connection'));
        $this->assertTrue($reflection->hasMethod('ajax_generate_content'));
        $this->assertTrue($reflection->hasMethod('ajax_validate_api_key'));
    }

    /**
     * Test logging functionality
     */
    public function test_logging() {
        $this->mock_logger->expects($this->once())
            ->method('log')
            ->with('info', $this->stringContains('[OpenAI Service]'), $this->anything());

        $reflection = new ReflectionClass($this->openai);
        $log_method = $reflection->getMethod('log');
        $log_method->setAccessible(true);
        
        $log_method->invoke($this->openai, 'info', 'Test message', array('test' => 'data'));
    }

    /**
     * Test edge cases
     */
    public function test_edge_cases() {
        // Test with empty user context and patterns
        $result = $this->openai->generate_content(array(), array());
        $this->assertInstanceOf('WP_Error', $result);

        // Test with very long prompt
        $long_prompt = str_repeat('This is a very long prompt. ', 1000);
        $result = $this->openai->generate_completion($long_prompt, 100);
        $this->assertInstanceOf('WP_Error', $result);

        // Test with invalid max_tokens
        $result = $this->openai->generate_completion('Test', 10000);
        $this->assertInstanceOf('WP_Error', $result);
    }

    /**
     * Test content generation with different options
     */
    public function test_generate_content_with_options() {
        $openai = $this->getMockBuilder('XeliteRepostEngine_OpenAI')
            ->setConstructorArgs(array($this->mock_logger))
            ->onlyMethods(array('validate_api_key', 'generate_completion'))
            ->getMock();

        $openai->method('validate_api_key')
            ->willReturn(array('valid' => true));

        $openai->method('generate_completion')
            ->willReturn(array(
                'choices' => array(array('text' => 'Generated content')),
                'model' => 'gpt-4',
                'usage' => array('total_tokens' => 100)
            ));

        $options = array(
            'max_tokens' => 200,
            'temperature' => 0.8,
            'instructions' => 'Make it more engaging',
            'max_variations' => 2
        );

        $result = $openai->generate_content($this->sample_user_context, $this->sample_patterns, $options);
        
        $this->assertIsArray($result);
        $this->assertArrayHasKey('content', $result);
    }

    /**
     * Test prompt building with minimal data
     */
    public function test_build_content_prompt_minimal() {
        $reflection = new ReflectionClass($this->openai);
        $method = $reflection->getMethod('build_content_prompt');
        $method->setAccessible(true);

        $minimal_context = array('topic' => 'Test topic');
        $minimal_patterns = array();

        $prompt = $method->invoke($this->openai, $minimal_context, $minimal_patterns);

        $this->assertIsString($prompt);
        $this->assertStringContainsString('Test topic', $prompt);
        $this->assertStringContainsString('Requirements:', $prompt);
    }

    /**
     * Test content formatting with single variation
     */
    public function test_format_generated_content_single() {
        $reflection = new ReflectionClass($this->openai);
        $method = $reflection->getMethod('format_generated_content');
        $method->setAccessible(true);

        $completion = array(
            'choices' => array(array('text' => 'Single content variation')),
            'model' => 'gpt-4',
            'usage' => array('total_tokens' => 50)
        );

        $result = $method->invoke($this->openai, $completion, array());

        $this->assertIsArray($result);
        $this->assertCount(1, $result['variations']);
        $this->assertEquals('Single content variation', $result['variations'][0]);
    }

    /**
     * Test content formatting with empty response
     */
    public function test_format_generated_content_empty() {
        $reflection = new ReflectionClass($this->openai);
        $method = $reflection->getMethod('format_generated_content');
        $method->setAccessible(true);

        $completion = array(
            'choices' => array(array('text' => '')),
            'model' => 'gpt-4',
            'usage' => array('total_tokens' => 10)
        );

        $result = $method->invoke($this->openai, $completion, array());

        $this->assertIsArray($result);
        $this->assertEmpty($result['variations']);
    }
} 