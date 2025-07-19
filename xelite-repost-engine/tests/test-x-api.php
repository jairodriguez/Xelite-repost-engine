<?php
/**
 * Test suite for X API Service
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Test class for X API Service
 */
class Test_XeliteRepostEngine_X_API extends WP_UnitTestCase {

    /**
     * X API service instance
     *
     * @var XeliteRepostEngine_X_API
     */
    private $x_api;

    /**
     * X Auth service mock
     *
     * @var XeliteRepostEngine_X_Auth
     */
    private $auth_service;

    /**
     * Set up test environment
     */
    public function setUp(): void {
        parent::setUp();
        
        // Create mock auth service
        $this->auth_service = $this->createMock('XeliteRepostEngine_X_Auth');
        
        // Create X API service with mock auth
        $this->x_api = new XeliteRepostEngine_X_API($this->auth_service);
    }

    /**
     * Test constructor
     */
    public function test_constructor() {
        $this->assertInstanceOf('XeliteRepostEngine_X_API', $this->x_api);
    }

    /**
     * Test get_user_timeline with valid credentials
     */
    public function test_get_user_timeline_success() {
        // Mock credentials
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API response
        $mock_response = array(
            'data' => array(
                array(
                    'id' => '123456789',
                    'text' => 'Test tweet',
                    'created_at' => '2023-01-01T12:00:00.000Z',
                    'author_id' => '987654321',
                    'public_metrics' => array(
                        'retweet_count' => 10,
                        'like_count' => 50,
                    ),
                ),
            ),
            'includes' => array(
                'users' => array(
                    array(
                        'id' => '987654321',
                        'username' => 'testuser',
                        'name' => 'Test User',
                    ),
                ),
            ),
            'meta' => array(
                'result_count' => 1,
            ),
        );
        
        // Mock wp_remote_get
        add_filter('pre_http_request', function($preempt, $args, $url) use ($mock_response) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode($mock_response),
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321');
        
        $this->assertNotWPError($result);
        $this->assertArrayHasKey('tweets', $result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(1, $result['tweets']);
        $this->assertEquals('123456789', $result['tweets'][0]['id']);
    }

    /**
     * Test get_user_timeline with authentication failure
     */
    public function test_get_user_timeline_auth_failure() {
        $this->auth_service->method('get_credentials')
            ->willReturn(new WP_Error('auth_failed', 'Authentication failed'));
        
        $result = $this->x_api->get_user_timeline('987654321');
        
        $this->assertWPError($result);
        $this->assertEquals('auth_failed', $result->get_error_code());
    }

    /**
     * Test get_user_timeline with rate limit
     */
    public function test_get_user_timeline_rate_limit() {
        // Set rate limit to exceeded
        set_transient('xelite_api_rate_limit', array(
            'count' => 300,
            'window_start' => time(),
        ), 900);
        
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        $result = $this->x_api->get_user_timeline('987654321');
        
        $this->assertWPError($result);
        $this->assertEquals('rate_limit_exceeded', $result->get_error_code());
        
        // Clean up
        delete_transient('xelite_api_rate_limit');
    }

    /**
     * Test get_user_timeline with API error
     */
    public function test_get_user_timeline_api_error() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API error response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 500),
                'body' => 'Internal Server Error',
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321');
        
        $this->assertWPError($result);
        $this->assertEquals('api_error', $result->get_error_code());
    }

    /**
     * Test get_user_info with user ID
     */
    public function test_get_user_info_by_id() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API response
        $mock_response = array(
            'data' => array(
                'id' => '987654321',
                'username' => 'testuser',
                'name' => 'Test User',
                'description' => 'Test description',
                'profile_image_url' => 'https://example.com/image.jpg',
                'verified' => true,
                'public_metrics' => array(
                    'followers_count' => 1000,
                    'following_count' => 500,
                ),
            ),
        );
        
        add_filter('pre_http_request', function($preempt, $args, $url) use ($mock_response) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode($mock_response),
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_info('987654321', false);
        
        $this->assertNotWPError($result);
        $this->assertEquals('987654321', $result['id']);
        $this->assertEquals('testuser', $result['username']);
        $this->assertEquals('Test User', $result['name']);
    }

    /**
     * Test get_user_info with username
     */
    public function test_get_user_info_by_username() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API response
        $mock_response = array(
            'data' => array(
                'id' => '987654321',
                'username' => 'testuser',
                'name' => 'Test User',
            ),
        );
        
        add_filter('pre_http_request', function($preempt, $args, $url) use ($mock_response) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode($mock_response),
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_info('testuser', true);
        
        $this->assertNotWPError($result);
        $this->assertEquals('987654321', $result['id']);
        $this->assertEquals('testuser', $result['username']);
    }

    /**
     * Test get_tweet_details
     */
    public function test_get_tweet_details() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API response
        $mock_response = array(
            'data' => array(
                'id' => '123456789',
                'text' => 'Test tweet content',
                'created_at' => '2023-01-01T12:00:00.000Z',
                'author_id' => '987654321',
                'public_metrics' => array(
                    'retweet_count' => 10,
                    'like_count' => 50,
                ),
                'conversation_id' => '123456789',
            ),
            'includes' => array(
                'users' => array(
                    array(
                        'id' => '987654321',
                        'username' => 'testuser',
                        'name' => 'Test User',
                    ),
                ),
            ),
        );
        
        add_filter('pre_http_request', function($preempt, $args, $url) use ($mock_response) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode($mock_response),
            );
        }, 10, 3);
        
        $result = $this->x_api->get_tweet_details('123456789');
        
        $this->assertNotWPError($result);
        $this->assertArrayHasKey('tweet', $result);
        $this->assertArrayHasKey('users', $result);
        $this->assertEquals('123456789', $result['tweet']['id']);
        $this->assertEquals('Test tweet content', $result['tweet']['text']);
    }

    /**
     * Test search_tweets
     */
    public function test_search_tweets() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API response
        $mock_response = array(
            'data' => array(
                array(
                    'id' => '123456789',
                    'text' => 'Search result tweet',
                    'created_at' => '2023-01-01T12:00:00.000Z',
                    'author_id' => '987654321',
                ),
            ),
            'includes' => array(
                'users' => array(
                    array(
                        'id' => '987654321',
                        'username' => 'testuser',
                        'name' => 'Test User',
                    ),
                ),
            ),
            'meta' => array(
                'result_count' => 1,
            ),
        );
        
        add_filter('pre_http_request', function($preempt, $args, $url) use ($mock_response) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode($mock_response),
            );
        }, 10, 3);
        
        $result = $this->x_api->search_tweets('test query');
        
        $this->assertNotWPError($result);
        $this->assertArrayHasKey('tweets', $result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertCount(1, $result['tweets']);
    }

    /**
     * Test caching functionality
     */
    public function test_caching_functionality() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API response
        $mock_response = array(
            'data' => array(
                array(
                    'id' => '123456789',
                    'text' => 'Cached tweet',
                    'created_at' => '2023-01-01T12:00:00.000Z',
                    'author_id' => '987654321',
                ),
            ),
        );
        
        add_filter('pre_http_request', function($preempt, $args, $url) use ($mock_response) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode($mock_response),
            );
        }, 10, 3);
        
        // First call should hit the API
        $result1 = $this->x_api->get_user_timeline('987654321');
        $this->assertNotWPError($result1);
        
        // Second call should use cache
        $result2 = $this->x_api->get_user_timeline('987654321');
        $this->assertNotWPError($result2);
        $this->assertEquals($result1, $result2);
        
        // Verify cache exists
        $cache_key = 'xelite_timeline_987654321_100';
        $cached_data = get_transient($cache_key);
        $this->assertNotFalse($cached_data);
    }

    /**
     * Test clear_user_cache
     */
    public function test_clear_user_cache() {
        // Set up some test cache
        set_transient('xelite_timeline_987654321_100', array('test' => 'data'), 900);
        set_transient('xelite_user_info_id_987654321', array('test' => 'data'), 900);
        
        $this->x_api->clear_user_cache('987654321');
        
        // Verify cache is cleared
        $this->assertFalse(get_transient('xelite_timeline_987654321_100'));
        $this->assertFalse(get_transient('xelite_user_info_id_987654321'));
    }

    /**
     * Test clear_all_cache
     */
    public function test_clear_all_cache() {
        // Set up some test cache
        set_transient('xelite_timeline_987654321_100', array('test' => 'data'), 900);
        set_transient('xelite_user_info_id_987654321', array('test' => 'data'), 900);
        set_transient('xelite_tweet_123456789', array('test' => 'data'), 900);
        
        $this->x_api->clear_all_cache();
        
        // Verify all cache is cleared
        $this->assertFalse(get_transient('xelite_timeline_987654321_100'));
        $this->assertFalse(get_transient('xelite_user_info_id_987654321'));
        $this->assertFalse(get_transient('xelite_tweet_123456789'));
    }

    /**
     * Test get_rate_limit_status
     */
    public function test_get_rate_limit_status() {
        // Test with no rate limit data
        $status = $this->x_api->get_rate_limit_status();
        $this->assertArrayHasKey('remaining', $status);
        $this->assertArrayHasKey('reset_time', $status);
        $this->assertArrayHasKey('window_start', $status);
        $this->assertEquals(300, $status['remaining']);
        
        // Test with existing rate limit data
        set_transient('xelite_api_rate_limit', array(
            'count' => 100,
            'window_start' => time(),
        ), 900);
        
        $status = $this->x_api->get_rate_limit_status();
        $this->assertEquals(200, $status['remaining']);
        
        // Clean up
        delete_transient('xelite_api_rate_limit');
    }

    /**
     * Test data normalization
     */
    public function test_data_normalization() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API response with various data types
        $mock_response = array(
            'data' => array(
                array(
                    'id' => '123456789',
                    'text' => 'Test tweet with entities',
                    'created_at' => '2023-01-01T12:00:00.000Z',
                    'author_id' => '987654321',
                    'public_metrics' => array(
                        'retweet_count' => 10,
                        'like_count' => 50,
                        'reply_count' => 5,
                        'quote_count' => 2,
                    ),
                    'entities' => array(
                        'hashtags' => array(
                            array('tag' => 'test'),
                        ),
                        'mentions' => array(
                            array('username' => 'testuser'),
                        ),
                    ),
                    'context_annotations' => array(
                        array('domain' => array('id' => '1', 'name' => 'test')),
                    ),
                    'conversation_id' => '123456789',
                    'referenced_tweets' => array(
                        array('type' => 'replied_to', 'id' => '123456788'),
                    ),
                ),
            ),
            'includes' => array(
                'users' => array(
                    array(
                        'id' => '987654321',
                        'username' => 'testuser',
                        'name' => 'Test User',
                        'description' => 'Test description',
                        'profile_image_url' => 'https://example.com/image.jpg',
                        'verified' => true,
                        'public_metrics' => array(
                            'followers_count' => 1000,
                            'following_count' => 500,
                            'tweet_count' => 100,
                            'listed_count' => 50,
                        ),
                        'created_at' => '2020-01-01T00:00:00.000Z',
                    ),
                ),
            ),
            'meta' => array(
                'result_count' => 1,
                'next_token' => 'next_page_token',
            ),
        );
        
        add_filter('pre_http_request', function($preempt, $args, $url) use ($mock_response) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode($mock_response),
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321');
        
        $this->assertNotWPError($result);
        $this->assertArrayHasKey('tweets', $result);
        $this->assertArrayHasKey('users', $result);
        $this->assertArrayHasKey('meta', $result);
        
        // Test tweet normalization
        $tweet = $result['tweets'][0];
        $this->assertEquals('123456789', $tweet['id']);
        $this->assertEquals('Test tweet with entities', $tweet['text']);
        $this->assertEquals('2023-01-01T12:00:00.000Z', $tweet['created_at']);
        $this->assertEquals('987654321', $tweet['author_id']);
        $this->assertArrayHasKey('public_metrics', $tweet);
        $this->assertArrayHasKey('entities', $tweet);
        $this->assertArrayHasKey('context_annotations', $tweet);
        $this->assertEquals('123456789', $tweet['conversation_id']);
        $this->assertArrayHasKey('referenced_tweets', $tweet);
        
        // Test user normalization
        $user = $result['users']['987654321'];
        $this->assertEquals('987654321', $user['id']);
        $this->assertEquals('testuser', $user['username']);
        $this->assertEquals('Test User', $user['name']);
        $this->assertEquals('Test description', $user['description']);
        $this->assertEquals('https://example.com/image.jpg', $user['profile_image_url']);
        $this->assertTrue($user['verified']);
        $this->assertArrayHasKey('public_metrics', $user);
        $this->assertEquals('2020-01-01T00:00:00.000Z', $user['created_at']);
    }

    /**
     * Test pagination support
     */
    public function test_pagination_support() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock API response with pagination
        $mock_response = array(
            'data' => array(
                array(
                    'id' => '123456789',
                    'text' => 'Page 2 tweet',
                    'created_at' => '2023-01-01T12:00:00.000Z',
                    'author_id' => '987654321',
                ),
            ),
            'meta' => array(
                'result_count' => 1,
                'next_token' => 'next_page_token',
                'previous_token' => 'prev_page_token',
            ),
        );
        
        add_filter('pre_http_request', function($preempt, $args, $url) use ($mock_response) {
            return array(
                'response' => array('code' => 200),
                'body' => json_encode($mock_response),
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321', 100, 'page_token');
        
        $this->assertNotWPError($result);
        $this->assertArrayHasKey('meta', $result);
        $this->assertArrayHasKey('next_token', $result['meta']);
        $this->assertArrayHasKey('previous_token', $result['meta']);
    }

    /**
     * Test error handling for JSON decode failure
     */
    public function test_json_decode_error() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock invalid JSON response
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 200),
                'body' => 'Invalid JSON response',
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321');
        
        $this->assertWPError($result);
        $this->assertEquals('json_decode_error', $result->get_error_code());
    }

    /**
     * Test network error handling
     */
    public function test_network_error() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Mock network error
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return new WP_Error('http_request_failed', 'Network error');
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321');
        
        $this->assertWPError($result);
        $this->assertEquals('http_request_failed', $result->get_error_code());
    }

    /**
     * Test OAuth signature generation
     */
    public function test_oauth_signature_generation() {
        // This test would require reflection to test private methods
        // For now, we'll test that the API request includes OAuth headers
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        $oauth_header_captured = '';
        
        add_filter('pre_http_request', function($preempt, $args, $url) use (&$oauth_header_captured) {
            if (isset($args['headers']['Authorization'])) {
                $oauth_header_captured = $args['headers']['Authorization'];
            }
            return array(
                'response' => array('code' => 200),
                'body' => json_encode(array('data' => array())),
            );
        }, 10, 3);
        
        $this->x_api->get_user_timeline('987654321');
        
        // Verify OAuth header is present and properly formatted
        $this->assertStringStartsWith('OAuth ', $oauth_header_captured);
        $this->assertStringContainsString('oauth_consumer_key', $oauth_header_captured);
        $this->assertStringContainsString('oauth_token', $oauth_header_captured);
        $this->assertStringContainsString('oauth_signature', $oauth_header_captured);
    }

    /**
     * Test different HTTP status codes
     */
    public function test_http_status_codes() {
        $credentials = array(
            'consumer_key' => 'test_consumer_key',
            'consumer_secret' => 'test_consumer_secret',
            'access_token' => 'test_access_token',
            'access_token_secret' => 'test_access_token_secret',
        );
        
        $this->auth_service->method('get_credentials')
            ->willReturn($credentials);
        
        // Test 401 Unauthorized
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 401),
                'body' => 'Unauthorized',
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321');
        $this->assertWPError($result);
        $this->assertEquals('authentication_failed', $result->get_error_code());
        
        // Test 403 Forbidden
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 403),
                'body' => 'Forbidden',
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321');
        $this->assertWPError($result);
        $this->assertEquals('access_forbidden', $result->get_error_code());
        
        // Test 429 Rate Limited
        add_filter('pre_http_request', function($preempt, $args, $url) {
            return array(
                'response' => array('code' => 429),
                'body' => 'Rate Limited',
            );
        }, 10, 3);
        
        $result = $this->x_api->get_user_timeline('987654321');
        $this->assertWPError($result);
        $this->assertEquals('rate_limit_exceeded', $result->get_error_code());
    }

    /**
     * Clean up after tests
     */
    public function tearDown(): void {
        // Clear any test cache
        delete_transient('xelite_api_rate_limit');
        delete_transient('xelite_timeline_987654321_100');
        delete_transient('xelite_user_info_id_987654321');
        delete_transient('xelite_tweet_123456789');
        
        parent::tearDown();
    }
} 