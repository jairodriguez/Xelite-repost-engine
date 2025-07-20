<?php
/**
 * Dashboard Content Generator Tab Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="xelite-content-generator">
    <!-- Generator Header -->
    <div class="generator-header">
        <h2><?php _e('AI Content Generator', 'xelite-repost-engine'); ?></h2>
        <p><?php _e('Generate personalized content based on your profile and target account patterns.', 'xelite-repost-engine'); ?></p>
    </div>

    <!-- Generator Form -->
    <div class="xelite-generator-form">
        <div class="form-section">
            <h3><?php _e('Content Parameters', 'xelite-repost-engine'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="content-topic"><?php _e('Topic or Theme:', 'xelite-repost-engine'); ?></label>
                    <input type="text" id="content-topic" class="regular-text" 
                           placeholder="<?php _e('Enter a topic or theme for your content', 'xelite-repost-engine'); ?>"
                           value="<?php echo esc_attr($user_context['topic'] ?? ''); ?>">
                    <p class="description"><?php _e('What would you like to create content about?', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="content-tone"><?php _e('Tone:', 'xelite-repost-engine'); ?></label>
                    <select id="content-tone">
                        <option value="conversational"><?php _e('Conversational', 'xelite-repost-engine'); ?></option>
                        <option value="professional"><?php _e('Professional', 'xelite-repost-engine'); ?></option>
                        <option value="casual"><?php _e('Casual', 'xelite-repost-engine'); ?></option>
                        <option value="enthusiastic"><?php _e('Enthusiastic', 'xelite-repost-engine'); ?></option>
                        <option value="informative"><?php _e('Informative', 'xelite-repost-engine'); ?></option>
                        <option value="humorous"><?php _e('Humorous', 'xelite-repost-engine'); ?></option>
                        <option value="inspirational"><?php _e('Inspirational', 'xelite-repost-engine'); ?></option>
                    </select>
                    <p class="description"><?php _e('Choose the tone that best fits your brand and audience.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="content-length"><?php _e('Length:', 'xelite-repost-engine'); ?></label>
                    <select id="content-length">
                        <option value="short"><?php _e('Short (100-150 chars)', 'xelite-repost-engine'); ?></option>
                        <option value="medium" selected><?php _e('Medium (150-250 chars)', 'xelite-repost-engine'); ?></option>
                        <option value="long"><?php _e('Long (250+ chars)', 'xelite-repost-engine'); ?></option>
                    </select>
                    <p class="description"><?php _e('Select the desired length for your content.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="content-creativity"><?php _e('Creativity Level:', 'xelite-repost-engine'); ?></label>
                    <select id="content-creativity">
                        <option value="conservative"><?php _e('Conservative (More predictable)', 'xelite-repost-engine'); ?></option>
                        <option value="balanced" selected><?php _e('Balanced (Recommended)', 'xelite-repost-engine'); ?></option>
                        <option value="creative"><?php _e('Creative (More unique)', 'xelite-repost-engine'); ?></option>
                    </select>
                    <p class="description"><?php _e('How creative should the AI be when generating content?', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="content-count"><?php _e('Number of Variations:', 'xelite-repost-engine'); ?></label>
                    <select id="content-count">
                        <option value="1"><?php _e('1 variation', 'xelite-repost-engine'); ?></option>
                        <option value="3" selected><?php _e('3 variations', 'xelite-repost-engine'); ?></option>
                        <option value="5"><?php _e('5 variations', 'xelite-repost-engine'); ?></option>
                    </select>
                    <p class="description"><?php _e('How many different content variations would you like?', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
        </div>

        <!-- Pattern Selection -->
        <div class="form-section">
            <h3><?php _e('Pattern Influence', 'xelite-repost-engine'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="pattern-influence"><?php _e('Pattern Influence Level:', 'xelite-repost-engine'); ?></label>
                    <select id="pattern-influence">
                        <option value="low"><?php _e('Low (Minimal pattern influence)', 'xelite-repost-engine'); ?></option>
                        <option value="medium" selected><?php _e('Medium (Balanced pattern influence)', 'xelite-repost-engine'); ?></option>
                        <option value="high"><?php _e('High (Strong pattern influence)', 'xelite-repost-engine'); ?></option>
                    </select>
                    <p class="description"><?php _e('How much should repost patterns influence the generated content?', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label><?php _e('Target Accounts to Consider:', 'xelite-repost-engine'); ?></label>
                    <div class="pattern-accounts">
                        <?php if (!empty($patterns)): ?>
                            <?php foreach (array_slice($patterns, 0, 5) as $pattern): ?>
                                <label class="pattern-account-checkbox">
                                    <input type="checkbox" name="pattern_accounts[]" 
                                           value="<?php echo esc_attr($pattern['source_handle']); ?>" 
                                           checked>
                                    <span class="account-name">@<?php echo esc_html($pattern['source_handle']); ?></span>
                                    <span class="account-stats">
                                        (<?php echo esc_html($pattern['repost_count']); ?> reposts)
                                    </span>
                                </label>
                            <?php endforeach; ?>
                            
                            <?php if (count($patterns) > 5): ?>
                                <div class="more-accounts">
                                    <button type="button" class="button button-secondary" id="show-all-accounts">
                                        <?php _e('Show All Accounts', 'xelite-repost-engine'); ?>
                                    </button>
                                </div>
                            <?php endif; ?>
                        <?php else: ?>
                            <p class="no-patterns"><?php _e('No patterns available. Add target accounts first.', 'xelite-repost-engine'); ?></p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <!-- Advanced Options -->
        <div class="form-section">
            <h3><?php _e('Advanced Options', 'xelite-repost-engine'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="include-hashtags"><?php _e('Include Hashtags:', 'xelite-repost-engine'); ?></label>
                    <select id="include-hashtags">
                        <option value="auto" selected><?php _e('Auto-generate relevant hashtags', 'xelite-repost-engine'); ?></option>
                        <option value="manual"><?php _e('Let me add hashtags manually', 'xelite-repost-engine'); ?></option>
                        <option value="none"><?php _e('No hashtags', 'xelite-repost-engine'); ?></option>
                    </select>
                </div>
                
                <div class="form-group">
                    <label for="include-cta"><?php _e('Include Call-to-Action:', 'xelite-repost-engine'); ?></label>
                    <select id="include-cta">
                        <option value="auto" selected><?php _e('Auto-generate CTA', 'xelite-repost-engine'); ?></option>
                        <option value="manual"><?php _e('Let me add CTA manually', 'xelite-repost-engine'); ?></option>
                        <option value="none"><?php _e('No CTA', 'xelite-repost-engine'); ?></option>
                    </select>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="custom-instructions"><?php _e('Custom Instructions (Optional):', 'xelite-repost-engine'); ?></label>
                    <textarea id="custom-instructions" rows="3" 
                              placeholder="<?php _e('Add any specific instructions or requirements for the content...', 'xelite-repost-engine'); ?>"></textarea>
                    <p class="description"><?php _e('Any additional instructions for the AI content generator.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
        </div>

        <!-- Generate Button -->
        <div class="form-actions">
            <button type="button" class="button button-primary button-large" id="generate-content">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Generate Content', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="save-preset">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save as Preset', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="load-preset">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Load Preset', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>

    <!-- Generated Content Results -->
    <div class="xelite-generated-content" id="generated-content-results" style="display: none;">
        <h3><?php _e('Generated Content', 'xelite-repost-engine'); ?></h3>
        
        <div class="content-variations" id="content-variations">
            <!-- Generated content will be inserted here -->
        </div>
        
        <div class="content-actions">
            <button type="button" class="button button-secondary" id="regenerate-all">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Regenerate All', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="button button-primary" id="save-all-content">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save All Content', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>

    <!-- Content Templates -->
    <div class="content-templates" id="content-templates" style="display: none;">
        <h3><?php _e('Content Template', 'xelite-repost-engine'); ?></h3>
        
        <div class="template-item">
            <div class="template-content">
                <div class="content-text" id="template-text"></div>
                <div class="content-meta">
                    <span class="content-length" id="template-length"></span>
                    <span class="content-tokens" id="template-tokens"></span>
                    <span class="content-score" id="template-score"></span>
                </div>
            </div>
            
            <div class="template-actions">
                <button type="button" class="xelite-content-btn copy" data-target="template-text">
                    <span class="dashicons dashicons-clipboard"></span>
                    <?php _e('Copy', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="xelite-content-btn save" data-target="template-text">
                    <span class="dashicons dashicons-saved"></span>
                    <?php _e('Save', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="xelite-content-btn edit" data-target="template-text">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Edit', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="xelite-content-btn optimize" data-target="template-text">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Optimize', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div class="xelite-loading-state" id="generation-loading" style="display: none;">
        <div class="loading-content">
            <div class="xelite-loading"></div>
            <h3><?php _e('Generating Content...', 'xelite-repost-engine'); ?></h3>
            <p><?php _e('This may take a few moments. We\'re analyzing patterns and creating personalized content for you.', 'xelite-repost-engine'); ?></p>
        </div>
    </div>

    <!-- Error State -->
    <div class="xelite-error-state" id="generation-error" style="display: none;">
        <div class="error-content">
            <span class="dashicons dashicons-warning"></span>
            <h3><?php _e('Generation Failed', 'xelite-repost-engine'); ?></h3>
            <p id="error-message"><?php _e('An error occurred while generating content. Please try again.', 'xelite-repost-engine'); ?></p>
            <button type="button" class="button button-primary" id="retry-generation">
                <?php _e('Try Again', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Content Variation Template -->
<script type="text/template" id="content-variation-template">
    <div class="content-variation" data-variation-id="{{variation_id}}">
        <div class="variation-header">
            <h4><?php _e('Variation', 'xelite-repost-engine'); ?> #{{variation_number}}</h4>
            <div class="variation-score">
                <span class="score-label"><?php _e('Repost Score:', 'xelite-repost-engine'); ?></span>
                <span class="score-value">{{repost_score}}%</span>
            </div>
        </div>
        
        <div class="variation-content">
            <div class="content-text">{{content_text}}</div>
            <div class="content-meta">
                <span class="content-length">{{content_length}} chars</span>
                <span class="content-tokens">{{token_count}} tokens</span>
                <span class="content-tone">{{tone}}</span>
            </div>
        </div>
        
        <div class="variation-actions">
            <button type="button" class="xelite-content-btn copy" data-content="{{content_text}}">
                <span class="dashicons dashicons-clipboard"></span>
                <?php _e('Copy', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="xelite-content-btn save" data-content="{{content_text}}">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="xelite-content-btn edit" data-variation-id="{{variation_id}}">
                <span class="dashicons dashicons-edit"></span>
                <?php _e('Edit', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="xelite-content-btn optimize" data-variation-id="{{variation_id}}">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Optimize', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="xelite-content-btn regenerate" data-variation-id="{{variation_id}}">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Regenerate', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>
</script> 