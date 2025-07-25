<?php
/**
 * Context-Aware Prompt Builder for Repost Intelligence
 *
 * Creates effective prompts that incorporate user context data and repost patterns
 * for optimal AI-generated content.
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Prompt Builder Class
 */
class XeliteRepostEngine_Prompt_Builder extends XeliteRepostEngine_Abstract_Base {

    /**
     * User meta service
     *
     * @var XeliteRepostEngine_User_Meta
     */
    private $user_meta;

    /**
     * Pattern analyzer service
     *
     * @var XeliteRepostEngine_Pattern_Analyzer
     */
    private $pattern_analyzer;

    /**
     * Database service
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
     * Few-shot collector service
     *
     * @var XeliteRepostEngine_Few_Shot_Collector
     */
    private $few_shot_collector;

    /**
     * Prompt templates
     *
     * @var array
     */
    private $prompt_templates;

    /**
     * Constructor
     *
     * @param XeliteRepostEngine_User_Meta $user_meta User meta service.
     * @param XeliteRepostEngine_Pattern_Analyzer $pattern_analyzer Pattern analyzer service.
     * @param XeliteRepostEngine_Database $database Database service.
     * @param XeliteRepostEngine_Logger $logger Logger service.
     * @param XeliteRepostEngine_Few_Shot_Collector $few_shot_collector Few-shot collector service.
     */
    public function __construct($user_meta, $pattern_analyzer, $database, $logger = null, $few_shot_collector = null) {
        parent::__construct();
        
        $this->user_meta = $user_meta;
        $this->pattern_analyzer = $pattern_analyzer;
        $this->database = $database;
        $this->logger = $logger;
        $this->few_shot_collector = $few_shot_collector;
        
        $this->init_prompt_templates();
    }

    /**
     * Initialize the class
     */
    protected function init() {
        $this->init_hooks();
    }

    /**
     * Initialize prompt templates
     */
    private function init_prompt_templates() {
        $this->prompt_templates = array(
            'content_generation' => array(
                'version' => '2.0',
                'template' => $this->get_content_generation_template(),
                'max_tokens' => 3000,
                'temperature' => 0.7,
                'few_shot_enabled' => true,
                'max_examples' => 5,
                'example_selection_strategy' => 'engagement_score'
            ),
            'content_optimization' => array(
                'version' => '2.0',
                'template' => $this->get_content_optimization_template(),
                'max_tokens' => 2000,
                'temperature' => 0.6,
                'few_shot_enabled' => true,
                'max_examples' => 3,
                'example_selection_strategy' => 'similar_content'
            ),
            'hashtag_suggestion' => array(
                'version' => '1.0',
                'template' => $this->get_hashtag_suggestion_template(),
                'max_tokens' => 500,
                'temperature' => 0.5,
                'few_shot_enabled' => false
            ),
            'engagement_prediction' => array(
                'version' => '1.0',
                'template' => $this->get_engagement_prediction_template(),
                'max_tokens' => 1000,
                'temperature' => 0.3,
                'few_shot_enabled' => false
            ),
            'few_shot_enhanced_generation' => array(
                'version' => '1.0',
                'template' => $this->get_few_shot_enhanced_template(),
                'max_tokens' => 3500,
                'temperature' => 0.8,
                'few_shot_enabled' => true,
                'max_examples' => 7,
                'example_selection_strategy' => 'category_match'
            )
        );
    }

    /**
     * Initialize WordPress hooks
     */
    private function init_hooks() {
        add_action('wp_ajax_xelite_test_prompt', array($this, 'ajax_test_prompt'));
        add_action('wp_ajax_xelite_ab_test_prompts', array($this, 'ajax_ab_test_prompts'));
        add_action('wp_ajax_xelite_get_prompt_analytics', array($this, 'ajax_get_prompt_analytics'));
        add_action('wp_ajax_xelite_get_available_templates', array($this, 'ajax_get_available_templates'));
        add_action('wp_ajax_xelite_build_enhanced_prompt', array($this, 'ajax_build_enhanced_prompt'));
    }

    /**
     * Build context-aware prompt for content generation
     *
     * @param int $user_id User ID.
     * @param array $options Generation options.
     * @return array Built prompt with metadata.
     */
    public function build_content_generation_prompt($user_id, $options = array()) {
        $template_type = $options['template_type'] ?? 'content_generation';
        
        // Use few-shot enhanced template if available and requested
        if (isset($options['use_enhanced_template']) && $options['use_enhanced_template'] && 
            isset($this->prompt_templates['few_shot_enhanced_generation'])) {
            $template_type = 'few_shot_enhanced_generation';
        }
        
        return $this->build_prompt_with_template($user_id, $template_type, $options);
    }

    /**
     * Build prompt with specific template type
     *
     * @param int $user_id User ID.
     * @param string $template_type Template type.
     * @param array $options Generation options.
     * @return array Built prompt with metadata.
     */
    public function build_prompt_with_template($user_id, $template_type, $options = array()) {
        if (!isset($this->prompt_templates[$template_type])) {
            $this->log('error', 'Template type not found', array('template_type' => $template_type));
            return false;
        }

        $template_config = $this->prompt_templates[$template_type];
        $user_context = $this->extract_user_context($user_id);
        $repost_patterns = $this->analyze_repost_patterns($user_id, $options);
        
        // Get few-shot examples if enabled for this template
        $few_shot_examples = array();
        if ($template_config['few_shot_enabled']) {
            $example_options = array_merge($options, array(
                'max_examples' => $template_config['max_examples'] ?? 3,
                'selection_strategy' => $template_config['example_selection_strategy'] ?? 'engagement_score'
            ));
            $few_shot_examples = $this->get_few_shot_examples($user_id, $example_options);
        }
        
        $template = $template_config['template'];
        $prompt = $this->populate_template($template, array(
            'user_context' => $user_context,
            'repost_patterns' => $repost_patterns,
            'few_shot_examples' => $few_shot_examples,
            'options' => $options
        ));

        return array(
            'prompt' => $prompt,
            'template_type' => $template_type,
            'template_version' => $template_config['version'],
            'user_context' => $user_context,
            'repost_patterns' => $repost_patterns,
            'few_shot_examples_count' => count($few_shot_examples),
            'few_shot_enabled' => $template_config['few_shot_enabled'],
            'estimated_tokens' => $this->estimate_token_count($prompt),
            'max_tokens' => $template_config['max_tokens'],
            'temperature' => $template_config['temperature'],
            'example_selection_strategy' => $template_config['example_selection_strategy'] ?? null
        );
    }

    /**
     * Build prompt for content optimization
     *
     * @param string $original_content Original content to optimize.
     * @param int $user_id User ID.
     * @param array $options Optimization options.
     * @return array Built prompt with metadata.
     */
    public function build_optimization_prompt($original_content, $user_id, $options = array()) {
        $user_context = $this->extract_user_context($user_id);
        $repost_patterns = $this->analyze_repost_patterns($user_id, $options);
        
        $template = $this->prompt_templates['content_optimization']['template'];
        $prompt = $this->populate_template($template, array(
            'original_content' => $original_content,
            'user_context' => $user_context,
            'repost_patterns' => $repost_patterns,
            'options' => $options
        ));

        return array(
            'prompt' => $prompt,
            'template_version' => $this->prompt_templates['content_optimization']['version'],
            'original_content_length' => strlen($original_content),
            'estimated_tokens' => $this->estimate_token_count($prompt),
            'max_tokens' => $this->prompt_templates['content_optimization']['max_tokens'],
            'temperature' => $this->prompt_templates['content_optimization']['temperature']
        );
    }

    /**
     * Extract user context from user meta
     *
     * @param int $user_id User ID.
     * @return array User context data.
     */
    private function extract_user_context($user_id) {
        $context = array();
        
        // Get user meta fields
        $meta_fields = array(
            'writing_style' => 'writing-style',
            'offer' => 'irresistible-offer',
            'audience' => 'dream-client',
            'pain_points' => 'dream-client-pain-points',
            'topic' => 'topic',
            'ikigai' => 'ikigai'
        );

        foreach ($meta_fields as $key => $meta_key) {
            $value = $this->user_meta->get_user_meta($user_id, $meta_key);
            if (!empty($value)) {
                $context[$key] = $value;
            }
        }

        // Get user's target accounts
        $target_accounts = $this->user_meta->get_user_meta($user_id, 'target_accounts');
        if (!empty($target_accounts)) {
            $context['target_accounts'] = is_array($target_accounts) ? $target_accounts : array($target_accounts);
        }

        // Get user's content preferences
        $content_preferences = $this->user_meta->get_user_meta($user_id, 'content_preferences');
        if (!empty($content_preferences)) {
            $context['content_preferences'] = $content_preferences;
        }

        $this->log('debug', 'Extracted user context', array('user_id' => $user_id, 'context' => $context));
        
        return $context;
    }

    /**
     * Analyze repost patterns for the user
     *
     * @param int $user_id User ID.
     * @param array $options Analysis options.
     * @return array Repost patterns.
     */
    private function analyze_repost_patterns($user_id, $options = array()) {
        $target_accounts = $this->user_meta->get_user_meta($user_id, 'target_accounts');
        
        if (empty($target_accounts)) {
            return array();
        }

        $accounts = is_array($target_accounts) ? $target_accounts : array($target_accounts);
        $patterns = array();

        foreach ($accounts as $account) {
            $account_patterns = $this->pattern_analyzer->analyze_account_patterns($account, $options);
            if (!empty($account_patterns)) {
                $patterns[$account] = $account_patterns;
            }
        }

        // Aggregate patterns across accounts
        $aggregated_patterns = $this->aggregate_patterns($patterns);

        $this->log('debug', 'Analyzed repost patterns', array(
            'user_id' => $user_id,
            'accounts' => $accounts,
            'patterns_count' => count($aggregated_patterns)
        ));

        return $aggregated_patterns;
    }

    /**
     * Aggregate patterns across multiple accounts
     *
     * @param array $patterns Patterns by account.
     * @return array Aggregated patterns.
     */
    private function aggregate_patterns($patterns) {
        if (empty($patterns)) {
            return array();
        }

        $aggregated = array(
            'length_patterns' => array(),
            'tone_patterns' => array(),
            'format_patterns' => array(),
            'content_patterns' => array(),
            'engagement_patterns' => array()
        );

        foreach ($patterns as $account => $account_patterns) {
            foreach ($aggregated as $pattern_type => &$aggregated_data) {
                if (isset($account_patterns[$pattern_type])) {
                    $this->merge_pattern_data($aggregated_data, $account_patterns[$pattern_type]);
                }
            }
        }

        // Normalize aggregated data
        foreach ($aggregated as $pattern_type => &$data) {
            if (!empty($data)) {
                $aggregated[$pattern_type] = $this->normalize_pattern_data($data);
            }
        }

        return $aggregated;
    }

    /**
     * Merge pattern data from different accounts
     *
     * @param array $aggregated Aggregated data.
     * @param array $new_data New data to merge.
     */
    private function merge_pattern_data(&$aggregated, $new_data) {
        if (empty($aggregated)) {
            $aggregated = $new_data;
            return;
        }

        foreach ($new_data as $key => $value) {
            if (is_array($value)) {
                if (!isset($aggregated[$key])) {
                    $aggregated[$key] = array();
                }
                $this->merge_pattern_data($aggregated[$key], $value);
            } else {
                if (isset($aggregated[$key])) {
                    $aggregated[$key] = ($aggregated[$key] + $value) / 2;
                } else {
                    $aggregated[$key] = $value;
                }
            }
        }
    }

    /**
     * Normalize pattern data
     *
     * @param array $data Pattern data.
     * @return array Normalized data.
     */
    private function normalize_pattern_data($data) {
        // Sort arrays by value if they contain key-value pairs
        foreach ($data as $key => $value) {
            if (is_array($value) && isset($value[0]['key']) && isset($value[0]['value'])) {
                usort($data[$key], function($a, $b) {
                    return $b['value'] <=> $a['value'];
                });
            }
        }

        return $data;
    }

    /**
     * Get few-shot learning examples
     *
     * @param int $user_id User ID.
     * @param array $options Example options.
     * @return array Few-shot examples.
     */
    private function get_few_shot_examples($user_id, $options = array()) {
        // Use the few-shot collector service if available
        if ($this->few_shot_collector) {
            $max_examples = $options['max_examples'] ?? 3;
            $content_type = $options['content_type'] ?? null;
            $category_id = $options['category_id'] ?? null;
            $min_engagement = $options['min_engagement'] ?? 0;
            
            $filters = array(
                'is_active' => 1,
                'limit' => $max_examples
            );
            
            if ($content_type) {
                $filters['content_type'] = $content_type;
            }
            
            if ($category_id) {
                $filters['category_id'] = $category_id;
            }
            
            if ($min_engagement > 0) {
                $filters['min_engagement'] = $min_engagement;
            }
            
            $examples = $this->few_shot_collector->get_few_shot_examples($filters);
            
            $this->log('debug', 'Retrieved few-shot examples from collector', array(
                'user_id' => $user_id,
                'examples_count' => count($examples),
                'filters' => $filters
            ));
            
            return $examples;
        }
        
        // Fallback to database method if few-shot collector not available
        $target_accounts = $this->user_meta->get_user_meta($user_id, 'target_accounts');
        
        if (empty($target_accounts)) {
            return array();
        }

        $accounts = is_array($target_accounts) ? $target_accounts : array($target_accounts);
        $examples = array();
        $max_examples = $options['max_examples'] ?? 3;

        foreach ($accounts as $account) {
            $account_examples = $this->database->get_top_reposts_by_account($account, $max_examples);
            if (!empty($account_examples)) {
                $examples = array_merge($examples, $account_examples);
            }
        }

        // Sort by engagement and limit
        usort($examples, function($a, $b) {
            return ($b['engagement_score'] ?? 0) <=> ($a['engagement_score'] ?? 0);
        });

        $examples = array_slice($examples, 0, $max_examples);

        $this->log('debug', 'Retrieved few-shot examples from database', array(
            'user_id' => $user_id,
            'examples_count' => count($examples)
        ));

        return $examples;
    }

    /**
     * Populate template with data
     *
     * @param string $template Template string.
     * @param array $data Data to populate.
     * @return string Populated template.
     */
    private function populate_template($template, $data) {
        $placeholders = array(
            '{{user_context}}' => $this->format_user_context($data['user_context'] ?? array()),
            '{{repost_patterns}}' => $this->format_repost_patterns($data['repost_patterns'] ?? array()),
            '{{few_shot_examples}}' => $this->format_few_shot_examples($data['few_shot_examples'] ?? array()),
            '{{original_content}}' => $data['original_content'] ?? '',
            '{{options}}' => $this->format_options($data['options'] ?? array())
        );

        return str_replace(array_keys($placeholders), array_values($placeholders), $template);
    }

    /**
     * Format user context for prompt
     *
     * @param array $context User context.
     * @return string Formatted context.
     */
    private function format_user_context($context) {
        if (empty($context)) {
            return "No specific user context available.";
        }

        $formatted = array();
        
        if (!empty($context['writing_style'])) {
            $formatted[] = "Writing Style: " . $context['writing_style'];
        }
        
        if (!empty($context['offer'])) {
            $formatted[] = "Offer: " . $context['offer'];
        }
        
        if (!empty($context['audience'])) {
            $formatted[] = "Target Audience: " . $context['audience'];
        }
        
        if (!empty($context['pain_points'])) {
            $formatted[] = "Pain Points: " . $context['pain_points'];
        }
        
        if (!empty($context['topic'])) {
            $formatted[] = "Topic: " . $context['topic'];
        }
        
        if (!empty($context['ikigai'])) {
            $formatted[] = "Ikigai: " . $context['ikigai'];
        }

        return implode("\n", $formatted);
    }

    /**
     * Format repost patterns for prompt
     *
     * @param array $patterns Repost patterns.
     * @return string Formatted patterns.
     */
    private function format_repost_patterns($patterns) {
        if (empty($patterns)) {
            return "No repost patterns available.";
        }

        $formatted = array();

        // Length patterns
        if (!empty($patterns['length_patterns']['optimal_length_range'])) {
            $optimal = $patterns['length_patterns']['optimal_length_range'];
            $formatted[] = "Optimal Length: {$optimal['min']}-{$optimal['max']} characters";
        }

        // Tone patterns
        if (!empty($patterns['tone_patterns']['top_effective_tones'])) {
            $tones = array_slice($patterns['tone_patterns']['top_effective_tones'], 0, 3);
            $tone_names = array_map(function($tone) {
                return ucfirst($tone['key']) . " ({$tone['value']})";
            }, $tones);
            $formatted[] = "Most Effective Tones: " . implode(', ', $tone_names);
        }

        // Format patterns
        if (!empty($patterns['format_patterns'])) {
            $format_items = array();
            foreach ($patterns['format_patterns'] as $type => $data) {
                if (isset($data['optimal_count'])) {
                    $format_items[] = "{$type}: {$data['optimal_count']}";
                }
            }
            if (!empty($format_items)) {
                $formatted[] = "Format Recommendations: " . implode(', ', $format_items);
            }
        }

        // Content patterns
        if (!empty($patterns['content_patterns']['top_words'])) {
            $words = array_slice($patterns['content_patterns']['top_words'], 0, 5);
            $word_list = array_map(function($word) {
                return $word['key'] . " ({$word['value']})";
            }, $words);
            $formatted[] = "High-Engagement Words: " . implode(', ', $word_list);
        }

        return implode("\n", $formatted);
    }

    /**
     * Format few-shot examples for prompt
     *
     * @param array $examples Few-shot examples.
     * @return string Formatted examples.
     */
    private function format_few_shot_examples($examples) {
        if (empty($examples)) {
            return "No examples available.";
        }

        $formatted = array();
        $formatted[] = "Here are examples of highly successful content that achieved high engagement:";
        $formatted[] = "";
        
        foreach ($examples as $index => $example) {
            $formatted[] = "EXAMPLE " . ($index + 1) . ":";
            $formatted[] = "Content: " . $example['original_text'];
            
            // Add engagement metrics if available
            if (isset($example['engagement_score'])) {
                $formatted[] = "Engagement Score: " . number_format($example['engagement_score'], 2);
            }
            
            if (isset($example['repost_count']) && $example['repost_count'] > 0) {
                $formatted[] = "Reposts: " . $example['repost_count'];
            }
            
            if (isset($example['like_count']) && $example['like_count'] > 0) {
                $formatted[] = "Likes: " . $example['like_count'];
            }
            
            if (isset($example['retweet_count']) && $example['retweet_count'] > 0) {
                $formatted[] = "Retweets: " . $example['retweet_count'];
            }
            
            // Add content analysis if available
            if (isset($example['content_length'])) {
                $formatted[] = "Length: " . $example['content_length'] . " characters";
            }
            
            if (isset($example['content_type']) && $example['content_type'] !== 'text') {
                $formatted[] = "Type: " . ucfirst($example['content_type']);
            }
            
            if (isset($example['hashtags']) && !empty($example['hashtags'])) {
                $hashtags = is_array($example['hashtags']) ? $example['hashtags'] : json_decode($example['hashtags'], true);
                if ($hashtags) {
                    $formatted[] = "Hashtags: " . implode(', ', $hashtags);
                }
            }
            
            if (isset($example['mentions']) && !empty($example['mentions'])) {
                $mentions = is_array($example['mentions']) ? $example['mentions'] : json_decode($example['mentions'], true);
                if ($mentions) {
                    $formatted[] = "Mentions: " . implode(', ', $mentions);
                }
            }
            
            if (isset($example['selection_reason']) && !empty($example['selection_reason'])) {
                $formatted[] = "Why it worked: " . $example['selection_reason'];
            }
            
            $formatted[] = "";
        }

        return implode("\n", $formatted);
    }

    /**
     * Format options for prompt
     *
     * @param array $options Options.
     * @return string Formatted options.
     */
    private function format_options($options) {
        if (empty($options)) {
            return "No specific options provided.";
        }

        $formatted = array();
        
        foreach ($options as $key => $value) {
            if (is_string($value) || is_numeric($value)) {
                $formatted[] = ucfirst(str_replace('_', ' ', $key)) . ": " . $value;
            }
        }

        return implode("\n", $formatted);
    }

    /**
     * Estimate token count for a prompt
     *
     * @param string $prompt Prompt text.
     * @return int Estimated token count.
     */
    private function estimate_token_count($prompt) {
        // Rough estimation: 1 token ≈ 4 characters for English text
        return ceil(strlen($prompt) / 4);
    }

    /**
     * Get content generation template
     *
     * @return string Template.
     */
    private function get_content_generation_template() {
        return <<<EOT
You are an expert social media content creator specializing in creating engaging, repost-worthy content for X (Twitter). Your goal is to help users create content that has a high chance of being reposted by influential accounts.

User Context:
{{user_context}}

Repost Patterns Analysis:
{{repost_patterns}}

Few-Shot Learning Examples:
{{few_shot_examples}}

Additional Options:
{{options}}

Instructions:
1. Study the provided examples carefully - these are proven successful content pieces
2. Analyze what makes each example effective (tone, structure, length, hashtags, etc.)
3. Create 3 different content variations that incorporate the successful elements from the examples
4. Ensure each variation is within the optimal length range identified in patterns
5. Use the most effective tone and format elements from the patterns
6. Incorporate high-engagement words naturally
7. Make content authentic and valuable to the target audience
8. Ensure content aligns with the user's writing style and offer
9. Apply the same level of quality and engagement potential as the examples

Key Principles from Examples:
- Notice the content structure and flow
- Pay attention to how hashtags and mentions are used
- Observe the tone and voice consistency
- Consider the length and readability
- Identify what makes each piece "repost-worthy"

Generate the content variations below, ensuring they match the quality and effectiveness of the provided examples:

EOT;
    }

    /**
     * Get content optimization template
     *
     * @return string Template.
     */
    private function get_content_optimization_template() {
        return <<<EOT
You are an expert social media content optimizer. Your task is to improve the given content to make it more likely to be reposted by influential accounts.

Original Content:
{{original_content}}

User Context:
{{user_context}}

Repost Patterns Analysis:
{{repost_patterns}}

Additional Options:
{{options}}

Instructions:
1. Optimize the content to match the identified repost patterns
2. Maintain the original message and intent
3. Adjust length to fit optimal range if needed
4. Apply the most effective tone and format elements
5. Incorporate high-engagement words naturally
6. Ensure the content remains authentic to the user's style

Provide the optimized version below:

EOT;
    }

    /**
     * Get hashtag suggestion template
     *
     * @return string Template.
     */
    private function get_hashtag_suggestion_template() {
        return <<<EOT
You are a hashtag optimization expert for X (Twitter). Analyze the given content and suggest relevant hashtags that would improve engagement and repost potential.

Content:
{{original_content}}

User Context:
{{user_context}}

Repost Patterns:
{{repost_patterns}}

Instructions:
1. Suggest 3-5 relevant hashtags
2. Consider trending hashtags in the user's niche
3. Include a mix of popular and niche hashtags
4. Ensure hashtags align with the content and user's audience
5. Avoid overused or spammy hashtags

Suggested hashtags:

EOT;
    }

    /**
     * Get engagement prediction template
     *
     * @return string Template.
     */
    private function get_engagement_prediction_template() {
        return <<<EOT
You are a social media engagement analyst. Predict the potential engagement and repost likelihood for the given content based on historical patterns.

Content:
{{original_content}}

User Context:
{{user_context}}

Repost Patterns:
{{repost_patterns}}

Instructions:
1. Rate the content on a scale of 1-10 for repost potential
2. Identify strengths and weaknesses
3. Suggest specific improvements
4. Predict likely engagement metrics
5. Compare against successful patterns

Analysis:

EOT;
    }

    /**
     * Get few-shot enhanced generation template
     *
     * @return string Template.
     */
    private function get_few_shot_enhanced_template() {
        return <<<EOT
You are an expert social media content creator with deep knowledge of what makes content go viral and get reposted. You have access to proven examples of highly successful content that achieved exceptional engagement.

User Context:
{{user_context}}

Repost Patterns Analysis:
{{repost_patterns}}

Proven Successful Examples:
{{few_shot_examples}}

Additional Options:
{{options}}

Instructions:
1. Carefully analyze each provided example to understand what makes it successful
2. Identify the key elements: tone, structure, length, hashtag usage, call-to-action, etc.
3. Create 3 different content variations that incorporate the most effective elements from the examples
4. Each variation should be as compelling and repost-worthy as the examples provided
5. Ensure content aligns with the user's context and target audience
6. Apply the optimal patterns identified in the analysis
7. Make each piece authentic, valuable, and highly shareable

Key Success Factors to Emulate:
- Emotional resonance and relatability
- Clear value proposition
- Engaging storytelling or insights
- Strategic use of hashtags and mentions
- Optimal length and readability
- Strong call-to-action or takeaway
- Authentic voice and personality

Generate 3 content variations below, each with the potential to achieve similar success as the provided examples:

EOT;
    }

    /**
     * Create A/B test for prompt variations
     *
     * @param int $user_id User ID.
     * @param array $variations Prompt variations.
     * @param array $options Test options.
     * @return array A/B test configuration.
     */
    public function create_ab_test($user_id, $variations, $options = array()) {
        $test_id = uniqid('prompt_ab_');
        $test_config = array(
            'test_id' => $test_id,
            'user_id' => $user_id,
            'variations' => $variations,
            'options' => $options,
            'created_at' => current_time('mysql'),
            'status' => 'active'
        );

        // Store test configuration
        update_option("xelite_prompt_ab_test_{$test_id}", $test_config);

        $this->log('info', 'Created A/B test for prompts', array(
            'test_id' => $test_id,
            'user_id' => $user_id,
            'variations_count' => count($variations)
        ));

        return $test_config;
    }

    /**
     * Get prompt version for A/B testing
     *
     * @param string $test_id Test ID.
     * @param int $user_id User ID.
     * @return array Prompt variation.
     */
    public function get_ab_test_variation($test_id, $user_id) {
        $test_config = get_option("xelite_prompt_ab_test_{$test_id}");
        
        if (!$test_config || $test_config['status'] !== 'active') {
            return null;
        }

        // Simple random selection (can be enhanced with more sophisticated algorithms)
        $variation_index = array_rand($test_config['variations']);
        $variation = $test_config['variations'][$variation_index];

        // Track selection
        $this->track_ab_test_selection($test_id, $user_id, $variation_index);

        return $variation;
    }

    /**
     * Track A/B test selection
     *
     * @param string $test_id Test ID.
     * @param int $user_id User ID.
     * @param int $variation_index Variation index.
     */
    private function track_ab_test_selection($test_id, $user_id, $variation_index) {
        $tracking_key = "xelite_prompt_ab_tracking_{$test_id}";
        $tracking = get_option($tracking_key, array());
        
        if (!isset($tracking[$variation_index])) {
            $tracking[$variation_index] = array('selections' => 0, 'users' => array());
        }
        
        $tracking[$variation_index]['selections']++;
        if (!in_array($user_id, $tracking[$variation_index]['users'])) {
            $tracking[$variation_index]['users'][] = $user_id;
        }
        
        update_option($tracking_key, $tracking);
    }

    /**
     * Record A/B test result
     *
     * @param string $test_id Test ID.
     * @param int $variation_index Variation index.
     * @param array $result Test result.
     */
    public function record_ab_test_result($test_id, $variation_index, $result) {
        $results_key = "xelite_prompt_ab_results_{$test_id}";
        $results = get_option($results_key, array());
        
        if (!isset($results[$variation_index])) {
            $results[$variation_index] = array();
        }
        
        $results[$variation_index][] = array_merge($result, array(
            'timestamp' => current_time('mysql')
        ));
        
        update_option($results_key, $results);

        $this->log('info', 'Recorded A/B test result', array(
            'test_id' => $test_id,
            'variation_index' => $variation_index,
            'result' => $result
        ));
    }

    /**
     * Get A/B test analytics
     *
     * @param string $test_id Test ID.
     * @return array Analytics data.
     */
    public function get_ab_test_analytics($test_id) {
        $test_config = get_option("xelite_prompt_ab_test_{$test_id}");
        $tracking = get_option("xelite_prompt_ab_tracking_{$test_id}", array());
        $results = get_option("xelite_prompt_ab_results_{$test_id}", array());

        if (!$test_config) {
            return null;
        }

        $analytics = array(
            'test_id' => $test_id,
            'status' => $test_config['status'],
            'created_at' => $test_config['created_at'],
            'variations' => array()
        );

        foreach ($test_config['variations'] as $index => $variation) {
            $tracking_data = $tracking[$index] ?? array('selections' => 0, 'users' => array());
            $result_data = $results[$index] ?? array();

            $analytics['variations'][$index] = array(
                'prompt' => $variation,
                'selections' => $tracking_data['selections'],
                'unique_users' => count($tracking_data['users']),
                'results_count' => count($result_data),
                'average_engagement' => $this->calculate_average_engagement($result_data),
                'repost_rate' => $this->calculate_repost_rate($result_data)
            );
        }

        return $analytics;
    }

    /**
     * Calculate average engagement from results
     *
     * @param array $results Results data.
     * @return float Average engagement.
     */
    private function calculate_average_engagement($results) {
        if (empty($results)) {
            return 0;
        }

        $total_engagement = 0;
        $count = 0;

        foreach ($results as $result) {
            if (isset($result['engagement_score'])) {
                $total_engagement += $result['engagement_score'];
                $count++;
            }
        }

        return $count > 0 ? $total_engagement / $count : 0;
    }

    /**
     * Calculate repost rate from results
     *
     * @param array $results Results data.
     * @return float Repost rate.
     */
    private function calculate_repost_rate($results) {
        if (empty($results)) {
            return 0;
        }

        $repost_count = 0;
        foreach ($results as $result) {
            if (isset($result['was_reposted']) && $result['was_reposted']) {
                $repost_count++;
            }
        }

        return $repost_count / count($results);
    }

    /**
     * AJAX handler for testing prompts
     */
    public function ajax_test_prompt() {
        check_ajax_referer('xelite_prompt_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_id = get_current_user_id();
        $options = json_decode(stripslashes($_POST['options'] ?? '{}'), true);

        $prompt_data = $this->build_content_generation_prompt($user_id, $options);
        
        wp_send_json_success($prompt_data);
    }

    /**
     * AJAX handler for A/B testing prompts
     */
    public function ajax_ab_test_prompts() {
        check_ajax_referer('xelite_prompt_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_id = get_current_user_id();
        $variations = json_decode(stripslashes($_POST['variations'] ?? '[]'), true);
        $options = json_decode(stripslashes($_POST['options'] ?? '{}'), true);

        if (empty($variations) || count($variations) < 2) {
            wp_send_json_error('At least 2 variations are required for A/B testing');
        }

        $test_config = $this->create_ab_test($user_id, $variations, $options);
        
        wp_send_json_success($test_config);
    }

    /**
     * AJAX handler for getting prompt analytics
     */
    public function ajax_get_prompt_analytics() {
        check_ajax_referer('xelite_prompt_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $test_id = sanitize_text_field($_POST['test_id'] ?? '');
        
        if (empty($test_id)) {
            wp_send_json_error('Test ID is required');
        }

        $analytics = $this->get_ab_test_analytics($test_id);
        
        if (!$analytics) {
            wp_send_json_error('Test not found');
        }
        
        wp_send_json_success($analytics);
    }

    /**
     * AJAX handler for getting available templates
     */
    public function ajax_get_available_templates() {
        check_ajax_referer('xelite_prompt_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $templates = $this->get_available_templates();
        $stats = $this->get_prompt_template_stats();
        
        wp_send_json_success(array(
            'templates' => $templates,
            'stats' => $stats
        ));
    }

    /**
     * AJAX handler for building enhanced prompts
     */
    public function ajax_build_enhanced_prompt() {
        check_ajax_referer('xelite_prompt_nonce', 'nonce');
        
        if (!current_user_can('edit_posts')) {
            wp_send_json_error('Insufficient permissions');
        }

        $user_id = get_current_user_id();
        $template_type = sanitize_text_field($_POST['template_type'] ?? 'content_generation');
        $options = json_decode(stripslashes($_POST['options'] ?? '{}'), true);

        $prompt_data = $this->build_prompt_with_template($user_id, $template_type, $options);
        
        if (!$prompt_data) {
            wp_send_json_error('Failed to build prompt');
        }
        
        wp_send_json_success($prompt_data);
    }

    /**
     * Track prompt performance and update few-shot example usage
     *
     * @param array $prompt_data Prompt data used.
     * @param array $generated_content Generated content.
     * @param array $performance_metrics Performance metrics.
     */
    public function track_prompt_performance($prompt_data, $generated_content, $performance_metrics = array()) {
        if (!$this->few_shot_collector) {
            return;
        }

        // Extract example IDs from prompt data
        $example_ids = array();
        if (isset($prompt_data['few_shot_examples_count']) && $prompt_data['few_shot_examples_count'] > 0) {
            // This would need to be enhanced to track specific example IDs
            // For now, we'll track that examples were used
            $this->log('info', 'Prompt used few-shot examples', array(
                'examples_count' => $prompt_data['few_shot_examples_count'],
                'performance_metrics' => $performance_metrics
            ));
        }

        // Track prompt template performance
        $template_version = $prompt_data['template_version'] ?? '1.0';
        $this->track_template_performance($template_version, $performance_metrics);
    }

    /**
     * Track template performance
     *
     * @param string $template_version Template version.
     * @param array $performance_metrics Performance metrics.
     */
    private function track_template_performance($template_version, $performance_metrics) {
        $template_stats = get_option('xelite_prompt_template_stats', array());
        
        if (!isset($template_stats[$template_version])) {
            $template_stats[$template_version] = array(
                'usage_count' => 0,
                'total_engagement' => 0,
                'successful_generations' => 0,
                'average_engagement' => 0
            );
        }

        $template_stats[$template_version]['usage_count']++;
        
        if (isset($performance_metrics['engagement_score'])) {
            $template_stats[$template_version]['total_engagement'] += $performance_metrics['engagement_score'];
            $template_stats[$template_version]['successful_generations']++;
            $template_stats[$template_version]['average_engagement'] = 
                $template_stats[$template_version]['total_engagement'] / $template_stats[$template_version]['successful_generations'];
        }

        update_option('xelite_prompt_template_stats', $template_stats);
    }

    /**
     * Get prompt template statistics
     *
     * @return array Template statistics.
     */
    public function get_prompt_template_stats() {
        return get_option('xelite_prompt_template_stats', array());
    }

    /**
     * Get available template types and their configurations
     *
     * @return array Template configurations.
     */
    public function get_available_templates() {
        $templates = array();
        
        foreach ($this->prompt_templates as $type => $config) {
            $templates[$type] = array(
                'type' => $type,
                'version' => $config['version'],
                'max_tokens' => $config['max_tokens'],
                'temperature' => $config['temperature'],
                'few_shot_enabled' => $config['few_shot_enabled'] ?? false,
                'max_examples' => $config['max_examples'] ?? 0,
                'example_selection_strategy' => $config['example_selection_strategy'] ?? null,
                'description' => $this->get_template_description($type)
            );
        }
        
        return $templates;
    }

    /**
     * Get template description
     *
     * @param string $template_type Template type.
     * @return string Description.
     */
    private function get_template_description($template_type) {
        $descriptions = array(
            'content_generation' => 'Standard content generation with basic few-shot learning support',
            'content_optimization' => 'Optimize existing content using few-shot examples',
            'hashtag_suggestion' => 'Generate hashtag suggestions for content',
            'engagement_prediction' => 'Predict engagement and repost potential',
            'few_shot_enhanced_generation' => 'Advanced content generation with comprehensive few-shot learning'
        );
        
        return $descriptions[$template_type] ?? 'No description available';
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
            $this->logger->log($level, "[Prompt Builder] {$message}", $context);
        } else {
            error_log("[XeliteRepostEngine Prompt Builder] {$message}");
        }
    }
} 