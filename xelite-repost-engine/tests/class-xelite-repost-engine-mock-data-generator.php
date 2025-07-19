<?php
/**
 * Mock Data Generator for Xelite Repost Engine Tests
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Mock data generator class for testing
 */
class XeliteRepostEngine_Mock_Data_Generator {
    
    /**
     * Sample source handles for testing
     *
     * @var array
     */
    private $sample_handles = array(
        'productivity_guru', 'marketing_expert', 'business_coach', 'tech_leader',
        'startup_founder', 'content_creator', 'social_media_pro', 'growth_hacker',
        'entrepreneur_life', 'digital_nomad', 'side_hustle_king', 'passive_income_builder',
        'mindset_master', 'fitness_coach', 'nutrition_expert', 'wellness_warrior',
        'finance_freedom', 'investing_pro', 'real_estate_guru', 'stock_market_wizard'
    );
    
    /**
     * Sample tweet content templates
     *
     * @var array
     */
    private $tweet_templates = array(
        'productivity' => array(
            'The #1 productivity hack that changed my life: {tip}',
            'Stop doing {bad_habit}. Start doing {good_habit} instead.',
            'I used to struggle with {problem}. Here\'s how I fixed it: {solution}',
            'Want to {goal}? Try this simple 3-step process: {steps}',
            'The biggest mistake people make with {topic}: {mistake}',
            'My morning routine in 3 words: {routine}',
            'If you want to {desire}, you need to {action}',
            'The secret to {success} isn\'t what you think: {secret}'
        ),
        'marketing' => array(
            'The marketing strategy that 10x\'d my business: {strategy}',
            'Stop wasting money on {bad_approach}. Try {good_approach} instead.',
            'Want more {metric}? Here\'s the framework I use: {framework}',
            'The #1 reason your {campaign} isn\'t working: {reason}',
            'I spent {amount} on {tactic} and got {result}',
            'The marketing funnel that converts {percentage}: {funnel}',
            'If you\'re not doing {technique}, you\'re leaving money on the table',
            'The psychology behind {behavior}: {psychology}'
        ),
        'business' => array(
            'The business model that made me {income}: {model}',
            'Want to scale your business? Stop doing {limiting_activity}',
            'The biggest lesson I learned from {experience}: {lesson}',
            'If you want to {business_goal}, focus on {key_factor}',
            'The framework I use to {business_process}: {framework}',
            'Stop {bad_business_practice}. Start {good_business_practice}',
            'The secret to {business_success}: {secret}',
            'I turned {small_thing} into {big_result} by {method}'
        ),
        'mindset' => array(
            'The mindset shift that changed everything: {shift}',
            'Stop thinking {limiting_belief}. Start thinking {empowering_belief}',
            'Want to {personal_goal}? Change your {mindset_aspect}',
            'The psychology of {behavior}: {psychology}',
            'If you struggle with {challenge}, try this: {solution}',
            'The mental framework for {success}: {framework}',
            'Stop {negative_thought}. Start {positive_thought}',
            'The mindset difference between {successful_people} and {unsuccessful_people}: {difference}'
        )
    );
    
    /**
     * Sample tips, habits, and solutions
     *
     * @var array
     */
    private $content_fillers = array(
        'tip' => array(
            'time blocking', 'the 2-minute rule', 'batching similar tasks',
            'the 80/20 principle', 'single-tasking', 'energy management',
            'the 5-minute rule', 'habit stacking', 'environmental design'
        ),
        'bad_habit' => array(
            'checking email first thing', 'multitasking', 'perfectionism',
            'procrastination', 'overthinking', 'people-pleasing',
            'comparison', 'negative self-talk', 'over-commitment'
        ),
        'good_habit' => array(
            'planning your day the night before', 'single-tasking', 'done is better than perfect',
            'the 2-minute rule', 'positive self-talk', 'setting boundaries',
            'focusing on your own journey', 'saying no to things that don\'t align'
        ),
        'problem' => array(
            'time management', 'procrastination', 'overwhelm', 'lack of focus',
            'burnout', 'imposter syndrome', 'decision fatigue', 'analysis paralysis'
        ),
        'solution' => array(
            'time blocking', 'the 2-minute rule', 'batching', 'energy management',
            'boundary setting', 'self-compassion', 'decision frameworks', 'action over perfection'
        ),
        'goal' => array(
            'be more productive', 'build better habits', 'achieve your goals',
            'reduce stress', 'increase focus', 'improve relationships',
            'grow your business', 'find work-life balance'
        ),
        'steps' => array(
            'plan, execute, review', 'start small, build momentum, scale',
            'identify, implement, iterate', 'assess, strategize, execute',
            'clarity, action, consistency', 'vision, strategy, execution'
        ),
        'topic' => array(
            'productivity', 'time management', 'goal setting', 'habit building',
            'focus', 'energy management', 'decision making', 'stress management'
        ),
        'mistake' => array(
            'trying to do everything', 'perfectionism', 'multitasking',
            'not planning ahead', 'ignoring energy levels', 'over-commitment',
            'comparison', 'lack of boundaries'
        ),
        'routine' => array(
            'plan, execute, reflect', 'move, meditate, create',
            'clarity, action, gratitude', 'energy, focus, flow',
            'intention, action, review', 'mindset, movement, momentum'
        ),
        'desire' => array(
            'success', 'happiness', 'freedom', 'impact', 'growth',
            'balance', 'fulfillment', 'achievement'
        ),
        'action' => array(
            'start small', 'be consistent', 'focus on progress', 'embrace failure',
            'learn continuously', 'take action', 'persist', 'adapt'
        ),
        'success' => array(
            'productivity', 'happiness', 'business growth', 'personal development',
            'relationships', 'health', 'wealth', 'impact'
        ),
        'secret' => array(
            'consistency over intensity', 'systems over goals', 'progress over perfection',
            'action over planning', 'learning over knowing', 'adaptation over rigidity'
        )
    );
    
    /**
     * Generate a single mock repost
     *
     * @param array $options Options for generating the repost
     * @return array
     */
    public function generate_repost($options = array()) {
        $defaults = array(
            'user_id' => rand(1, 10),
            'platform' => 'x',
            'repost_date' => null,
            'engagement_level' => 'medium', // low, medium, high
            'is_analyzed' => rand(0, 1),
            'include_analysis' => false
        );
        
        $options = wp_parse_args($options, $defaults);
        
        // Generate tweet content
        $content = $this->generate_tweet_content();
        
        // Generate engagement metrics based on level
        $engagement_metrics = $this->generate_engagement_metrics($options['engagement_level']);
        
        // Generate repost data
        $repost = array(
            'source_handle' => $this->get_random_handle(),
            'original_tweet_id' => $this->generate_tweet_id(),
            'original_text' => $content,
            'platform' => $options['platform'],
            'repost_date' => $options['repost_date'] ?: $this->generate_random_date(),
            'engagement_metrics' => $engagement_metrics,
            'user_id' => $options['user_id'],
            'repost_count' => rand(0, 5),
            'is_analyzed' => $options['is_analyzed']
        );
        
        // Add analysis data if requested
        if ($options['include_analysis'] && $options['is_analyzed']) {
            $repost['analysis_data'] = $this->generate_analysis_data($content);
        }
        
        return $repost;
    }
    
    /**
     * Generate multiple mock reposts
     *
     * @param int $count Number of reposts to generate
     * @param array $options Options for generating reposts
     * @return array
     */
    public function generate_reposts($count, $options = array()) {
        $reposts = array();
        
        for ($i = 0; $i < $count; $i++) {
            $reposts[] = $this->generate_repost($options);
        }
        
        return $reposts;
    }
    
    /**
     * Generate realistic tweet content
     *
     * @return string
     */
    private function generate_tweet_content() {
        $categories = array_keys($this->tweet_templates);
        $category = $categories[array_rand($categories)];
        $templates = $this->tweet_templates[$category];
        $template = $templates[array_rand($templates)];
        
        // Replace placeholders with random content
        $content = $template;
        foreach ($this->content_fillers as $placeholder => $fillers) {
            if (strpos($content, '{' . $placeholder . '}') !== false) {
                $replacement = $fillers[array_rand($fillers)];
                $content = str_replace('{' . $placeholder . '}', $replacement, $content);
            }
        }
        
        // Add some hashtags
        $hashtags = $this->generate_hashtags($category);
        $content .= ' ' . $hashtags;
        
        return $content;
    }
    
    /**
     * Generate engagement metrics
     *
     * @param string $level Engagement level (low, medium, high)
     * @return array
     */
    private function generate_engagement_metrics($level) {
        switch ($level) {
            case 'low':
                $likes = rand(5, 50);
                $retweets = rand(1, 10);
                $replies = rand(1, 5);
                break;
            case 'high':
                $likes = rand(500, 5000);
                $retweets = rand(100, 1000);
                $replies = rand(50, 200);
                break;
            case 'medium':
            default:
                $likes = rand(50, 500);
                $retweets = rand(10, 100);
                $replies = rand(5, 50);
                break;
        }
        
        return array(
            'likes' => $likes,
            'retweets' => $retweets,
            'replies' => $replies
        );
    }
    
    /**
     * Generate analysis data for a tweet
     *
     * @param string $content Tweet content
     * @return array
     */
    private function generate_analysis_data($content) {
        $patterns = array('question', 'statement', 'story', 'tip', 'quote', 'list');
        $tones = array('professional', 'casual', 'enthusiastic', 'authoritative', 'friendly', 'motivational');
        $lengths = array('short', 'medium', 'long');
        
        return array(
            'pattern' => $patterns[array_rand($patterns)],
            'tone' => $tones[array_rand($tones)],
            'length' => $lengths[array_rand($lengths)],
            'sentiment' => rand(0, 100) / 100, // 0 to 1
            'readability_score' => rand(60, 90),
            'keyword_density' => array(
                'productivity' => rand(0, 5),
                'business' => rand(0, 5),
                'success' => rand(0, 5),
                'growth' => rand(0, 5)
            ),
            'estimated_reach' => rand(1000, 100000),
            'engagement_rate' => rand(1, 10) / 100 // 1% to 10%
        );
    }
    
    /**
     * Generate hashtags for a category
     *
     * @param string $category Content category
     * @return string
     */
    private function generate_hashtags($category) {
        $hashtag_map = array(
            'productivity' => array('#productivity', '#timemanagement', '#habits', '#focus', '#goals'),
            'marketing' => array('#marketing', '#growth', '#business', '#strategy', '#sales'),
            'business' => array('#business', '#entrepreneur', '#startup', '#success', '#leadership'),
            'mindset' => array('#mindset', '#motivation', '#growth', '#success', '#mindfulness')
        );
        
        $hashtags = isset($hashtag_map[$category]) ? $hashtag_map[$category] : array('#test', '#content');
        $selected_hashtags = array_rand(array_flip($hashtags), rand(2, 4));
        
        return is_array($selected_hashtags) ? implode(' ', $selected_hashtags) : $selected_hashtags;
    }
    
    /**
     * Get a random handle from the sample list
     *
     * @return string
     */
    private function get_random_handle() {
        return $this->sample_handles[array_rand($this->sample_handles)];
    }
    
    /**
     * Generate a realistic tweet ID
     *
     * @return string
     */
    private function generate_tweet_id() {
        return (string) rand(1000000000000000000, 9999999999999999999);
    }
    
    /**
     * Generate a random date within the last 30 days
     *
     * @return string
     */
    private function generate_random_date() {
        $days_ago = rand(0, 30);
        $hours_ago = rand(0, 23);
        $minutes_ago = rand(0, 59);
        
        return date('Y-m-d H:i:s', strtotime("-$days_ago days -$hours_ago hours -$minutes_ago minutes"));
    }
    
    /**
     * Generate test users for testing
     *
     * @param int $count Number of users to generate
     * @return array
     */
    public function generate_test_users($count = 5) {
        $users = array();
        
        for ($i = 1; $i <= $count; $i++) {
            $users[] = array(
                'ID' => $i,
                'user_login' => 'testuser' . $i,
                'user_email' => 'testuser' . $i . '@example.com',
                'display_name' => 'Test User ' . $i,
                'user_meta' => array(
                    'personal-context' => 'Digital entrepreneur focused on productivity and business growth',
                    'dream-client' => 'Small business owners looking to scale',
                    'writing-style' => 'Professional yet approachable',
                    'irresistible-offer' => 'Free productivity framework',
                    'dream-client-pain-points' => 'Time management, overwhelm, lack of systems',
                    'ikigai' => 'Helping others achieve their goals through better systems',
                    'topic' => 'Productivity and business growth'
                )
            );
        }
        
        return $users;
    }
    
    /**
     * Generate performance test data
     *
     * @param int $count Number of records to generate
     * @return array
     */
    public function generate_performance_test_data($count = 1000) {
        $data = array();
        
        for ($i = 0; $i < $count; $i++) {
            $data[] = array(
                'source_handle' => 'perf_user_' . ($i % 100), // 100 unique users
                'original_tweet_id' => 'perf_tweet_' . $i,
                'original_text' => 'Performance test tweet ' . $i . ' with some content to make it longer and more realistic for testing purposes',
                'platform' => 'x',
                'repost_date' => date('Y-m-d H:i:s', strtotime("-" . rand(0, 365) . " days")),
                'engagement_metrics' => array(
                    'likes' => rand(10, 10000),
                    'retweets' => rand(5, 2000),
                    'replies' => rand(1, 500)
                ),
                'user_id' => rand(1, 50), // 50 unique users
                'repost_count' => rand(0, 10),
                'is_analyzed' => rand(0, 1),
                'analysis_data' => rand(0, 1) ? $this->generate_analysis_data('Test content') : null
            );
        }
        
        return $data;
    }
    
    /**
     * Generate date range test data
     *
     * @param string $start_date Start date (Y-m-d)
     * @param string $end_date End date (Y-m-d)
     * @param int $count_per_day Number of records per day
     * @return array
     */
    public function generate_date_range_test_data($start_date, $end_date, $count_per_day = 5) {
        $data = array();
        $current_date = strtotime($start_date);
        $end_timestamp = strtotime($end_date);
        
        while ($current_date <= $end_timestamp) {
            for ($i = 0; $i < $count_per_day; $i++) {
                $data[] = array(
                    'source_handle' => 'date_user_' . rand(1, 10),
                    'original_tweet_id' => 'date_tweet_' . date('Ymd', $current_date) . '_' . $i,
                    'original_text' => 'Date range test tweet for ' . date('Y-m-d', $current_date) . ' #' . $i,
                    'platform' => 'x',
                    'repost_date' => date('Y-m-d H:i:s', $current_date + rand(0, 86399)), // Random time during the day
                    'engagement_metrics' => array(
                        'likes' => rand(10, 1000),
                        'retweets' => rand(5, 200),
                        'replies' => rand(1, 50)
                    ),
                    'user_id' => rand(1, 5),
                    'repost_count' => rand(0, 5),
                    'is_analyzed' => rand(0, 1)
                );
            }
            
            $current_date = strtotime('+1 day', $current_date);
        }
        
        return $data;
    }
} 