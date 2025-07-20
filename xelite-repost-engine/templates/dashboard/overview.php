<?php
/**
 * Dashboard Overview Tab Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="xelite-overview">
    <!-- Welcome Section -->
    <div class="xelite-welcome-section">
        <div class="welcome-content">
            <h2><?php _e('Welcome to Repost Intelligence', 'xelite-repost-engine'); ?></h2>
            <p><?php _e('Generate AI-powered content that\'s more likely to be reposted by analyzing patterns from your target accounts.', 'xelite-repost-engine'); ?></p>
        </div>
        
        <div class="welcome-actions">
            <button type="button" class="button button-primary" id="get-started">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Generate Your First Content', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="view-tutorial">
                <span class="dashicons dashicons-video-alt3"></span>
                <?php _e('View Tutorial', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>

    <!-- Key Metrics -->
    <div class="xelite-metrics-section">
        <h3><?php _e('Your Content Generation Stats', 'xelite-repost-engine'); ?></h3>
        
        <div class="xelite-analytics-grid">
            <div class="xelite-analytics-card">
                <span class="xelite-analytics-number" id="total-generated">
                    <?php echo esc_html($dashboard_data['generation_stats']['total_generated']); ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Total Generated', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card secondary">
                <span class="xelite-analytics-number" id="total-saved">
                    <?php echo esc_html($dashboard_data['generation_stats']['total_saved']); ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Saved Content', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card warning">
                <span class="xelite-analytics-number" id="total-posted">
                    <?php echo esc_html($dashboard_data['generation_stats']['total_posted']); ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Posted Content', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card danger">
                <span class="xelite-analytics-number" id="target-accounts">
                    <?php echo esc_html(count($dashboard_data['account_stats']['target_accounts'] ?? array())); ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Target Accounts', 'xelite-repost-engine'); ?></span>
            </div>
        </div>
    </div>

    <!-- Quick Actions -->
    <div class="xelite-quick-actions">
        <h3><?php _e('Quick Actions', 'xelite-repost-engine'); ?></h3>
        
        <div class="xelite-dashboard-grid">
            <div class="xelite-dashboard-card">
                <h3>
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('Generate Content', 'xelite-repost-engine'); ?>
                </h3>
                <p><?php _e('Create AI-powered content based on your target account patterns.', 'xelite-repost-engine'); ?></p>
                <button type="button" class="button button-primary" data-action="generate-content">
                    <?php _e('Start Generating', 'xelite-repost-engine'); ?>
                </button>
            </div>
            
            <div class="xelite-dashboard-card">
                <h3>
                    <span class="dashicons dashicons-chart-line"></span>
                    <?php _e('View Patterns', 'xelite-repost-engine'); ?>
                </h3>
                <p><?php _e('Analyze repost patterns from your target accounts.', 'xelite-repost-engine'); ?></p>
                <button type="button" class="button button-secondary" data-action="view-patterns">
                    <?php _e('View Patterns', 'xelite-repost-engine'); ?>
                </button>
            </div>
            
            <div class="xelite-dashboard-card">
                <h3>
                    <span class="dashicons dashicons-admin-settings"></span>
                    <?php _e('Update Settings', 'xelite-repost-engine'); ?>
                </h3>
                <p><?php _e('Configure your personal settings and preferences.', 'xelite-repost-engine'); ?></p>
                <button type="button" class="button button-secondary" data-action="update-settings">
                    <?php _e('Go to Settings', 'xelite-repost-engine'); ?>
                </button>
            </div>
            
            <div class="xelite-dashboard-card">
                <h3>
                    <span class="dashicons dashicons-chart-bar"></span>
                    <?php _e('View Analytics', 'xelite-repost-engine'); ?>
                </h3>
                <p><?php _e('See detailed analytics and insights about your content performance.', 'xelite-repost-engine'); ?></p>
                <button type="button" class="button button-secondary" data-action="view-analytics">
                    <?php _e('View Analytics', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Recent Patterns -->
    <div class="xelite-recent-patterns">
        <h3><?php _e('Recent Repost Patterns', 'xelite-repost-engine'); ?></h3>
        
        <?php if (!empty($dashboard_data['recent_patterns'])): ?>
            <div class="xelite-patterns-list">
                <?php foreach (array_slice($dashboard_data['recent_patterns'], 0, 3) as $pattern): ?>
                    <div class="xelite-pattern-item">
                        <div class="pattern-header">
                            <span class="pattern-source">@<?php echo esc_html($pattern['source_handle']); ?></span>
                            <span class="pattern-count"><?php echo esc_html($pattern['repost_count']); ?> reposts</span>
                        </div>
                        <div class="pattern-text">
                            <?php echo esc_html(wp_trim_words($pattern['text'], 20, '...')); ?>
                        </div>
                        <div class="pattern-meta">
                            <span class="pattern-length"><?php echo esc_html(strlen($pattern['text'])); ?> chars</span>
                            <span class="pattern-engagement"><?php echo esc_html($pattern['avg_engagement']); ?> avg engagement</span>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <div class="patterns-actions">
                <button type="button" class="button button-secondary" data-action="view-all-patterns">
                    <?php _e('View All Patterns', 'xelite-repost-engine'); ?>
                </button>
            </div>
        <?php else: ?>
            <div class="xelite-empty-state">
                <div class="empty-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <h4><?php _e('No Patterns Yet', 'xelite-repost-engine'); ?></h4>
                <p><?php _e('Start by adding target accounts to monitor for repost patterns.', 'xelite-repost-engine'); ?></p>
                <button type="button" class="button button-primary" data-action="add-target-accounts">
                    <?php _e('Add Target Accounts', 'xelite-repost-engine'); ?>
                </button>
            </div>
        <?php endif; ?>
    </div>

    <!-- User Context Summary -->
    <?php if (!empty($dashboard_data['user_context'])): ?>
        <div class="xelite-user-context">
            <h3><?php _e('Your Content Profile', 'xelite-repost-engine'); ?></h3>
            
            <div class="xelite-dashboard-grid">
                <?php if (!empty($dashboard_data['user_context']['writing_style'])): ?>
                    <div class="xelite-dashboard-card">
                        <h4><?php _e('Writing Style', 'xelite-repost-engine'); ?></h4>
                        <p><?php echo esc_html($dashboard_data['user_context']['writing_style']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($dashboard_data['user_context']['audience'])): ?>
                    <div class="xelite-dashboard-card">
                        <h4><?php _e('Target Audience', 'xelite-repost-engine'); ?></h4>
                        <p><?php echo esc_html($dashboard_data['user_context']['audience']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($dashboard_data['user_context']['topic'])): ?>
                    <div class="xelite-dashboard-card">
                        <h4><?php _e('Main Topic', 'xelite-repost-engine'); ?></h4>
                        <p><?php echo esc_html($dashboard_data['user_context']['topic']); ?></p>
                    </div>
                <?php endif; ?>
                
                <?php if (!empty($dashboard_data['user_context']['offer'])): ?>
                    <div class="xelite-dashboard-card">
                        <h4><?php _e('Your Offer', 'xelite-repost-engine'); ?></h4>
                        <p><?php echo esc_html($dashboard_data['user_context']['offer']); ?></p>
                    </div>
                <?php endif; ?>
            </div>
            
            <div class="context-actions">
                <button type="button" class="button button-secondary" data-action="update-context">
                    <?php _e('Update Profile', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
    <?php else: ?>
        <div class="xelite-setup-required">
            <h3><?php _e('Complete Your Profile', 'xelite-repost-engine'); ?></h3>
            <p><?php _e('To generate personalized content, please complete your content profile.', 'xelite-repost-engine'); ?></p>
            <button type="button" class="button button-primary" data-action="setup-profile">
                <?php _e('Complete Profile', 'xelite-repost-engine'); ?>
            </button>
        </div>
    <?php endif; ?>

    <!-- Last Generated Content -->
    <?php if (!empty($dashboard_data['generation_stats']['last_generated'])): ?>
        <div class="xelite-last-generated">
            <h3><?php _e('Last Generated Content', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-dashboard-card">
                <div class="content-preview">
                    <p class="content-text"><?php echo esc_html($dashboard_data['generation_stats']['last_generated']); ?></p>
                    <div class="content-actions">
                        <button type="button" class="xelite-content-btn copy" data-content="<?php echo esc_attr($dashboard_data['generation_stats']['last_generated']); ?>">
                            <span class="dashicons dashicons-clipboard"></span>
                            <?php _e('Copy', 'xelite-repost-engine'); ?>
                        </button>
                        <button type="button" class="xelite-content-btn save" data-content="<?php echo esc_attr($dashboard_data['generation_stats']['last_generated']); ?>">
                            <span class="dashicons dashicons-saved"></span>
                            <?php _e('Save', 'xelite-repost-engine'); ?>
                        </button>
                    </div>
                </div>
            </div>
        </div>
    <?php endif; ?>
</div> 