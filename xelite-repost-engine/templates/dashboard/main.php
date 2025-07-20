<?php
/**
 * Main Dashboard Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap xelite-dashboard">
    <!-- Dashboard Header -->
    <div class="xelite-dashboard-header">
        <div class="header-content">
            <h1>
                <span class="dashicons dashicons-share"></span>
                <?php _e('Repost Intelligence Dashboard', 'xelite-repost-engine'); ?>
            </h1>
            <p class="description">
                <?php _e('Generate AI-powered content based on repost patterns from your target accounts.', 'xelite-repost-engine'); ?>
            </p>
        </div>
        
        <div class="header-actions">
            <button type="button" class="button button-secondary" id="refresh-dashboard">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Refresh', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="button button-primary" id="quick-generate">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Quick Generate', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>

    <!-- Dashboard Navigation -->
    <div class="xelite-dashboard-nav">
        <ul class="xelite-nav-tabs">
            <?php foreach ($tabs as $tab_slug => $tab): ?>
                <li>
                    <a href="<?php echo esc_url(add_query_arg('tab', $tab_slug, remove_query_arg('tab'))); ?>" 
                       class="<?php echo $current_tab === $tab_slug ? 'active' : ''; ?>"
                       data-tab="<?php echo esc_attr($tab_slug); ?>">
                        <span class="dashicons <?php echo esc_attr($tab['icon']); ?>"></span>
                        <?php echo esc_html($tab['title']); ?>
                    </a>
                </li>
            <?php endforeach; ?>
        </ul>
    </div>

    <!-- Dashboard Content -->
    <div class="xelite-dashboard-content">
        <?php if (isset($tabs[$current_tab]['callback']) && is_callable($tabs[$current_tab]['callback'])): ?>
            <?php call_user_func($tabs[$current_tab]['callback']); ?>
        <?php else: ?>
            <div class="xelite-error">
                <p><?php _e('Tab content not found.', 'xelite-repost-engine'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Loading Overlay -->
    <div class="xelite-loading-overlay" style="display: none;">
        <div class="xelite-loading-spinner">
            <div class="xelite-loading"></div>
            <p><?php _e('Loading...', 'xelite-repost-engine'); ?></p>
        </div>
    </div>

    <!-- Messages Container -->
    <div id="xelite-messages"></div>
</div>

<!-- Quick Generate Modal -->
<div id="quick-generate-modal" class="xelite-modal" style="display: none;">
    <div class="xelite-modal-content">
        <div class="xelite-modal-header">
            <h3><?php _e('Quick Content Generation', 'xelite-repost-engine'); ?></h3>
            <button type="button" class="xelite-modal-close">&times;</button>
        </div>
        
        <div class="xelite-modal-body">
            <div class="form-group">
                <label for="quick-topic"><?php _e('Topic or Theme:', 'xelite-repost-engine'); ?></label>
                <input type="text" id="quick-topic" class="regular-text" 
                       placeholder="<?php _e('Enter a topic or theme for your content', 'xelite-repost-engine'); ?>">
            </div>
            
            <div class="form-group">
                <label for="quick-tone"><?php _e('Tone:', 'xelite-repost-engine'); ?></label>
                <select id="quick-tone">
                    <option value="conversational"><?php _e('Conversational', 'xelite-repost-engine'); ?></option>
                    <option value="professional"><?php _e('Professional', 'xelite-repost-engine'); ?></option>
                    <option value="casual"><?php _e('Casual', 'xelite-repost-engine'); ?></option>
                    <option value="enthusiastic"><?php _e('Enthusiastic', 'xelite-repost-engine'); ?></option>
                    <option value="informative"><?php _e('Informative', 'xelite-repost-engine'); ?></option>
                </select>
            </div>
            
            <div class="form-group">
                <label for="quick-length"><?php _e('Length:', 'xelite-repost-engine'); ?></label>
                <select id="quick-length">
                    <option value="short"><?php _e('Short (100-150 chars)', 'xelite-repost-engine'); ?></option>
                    <option value="medium" selected><?php _e('Medium (150-250 chars)', 'xelite-repost-engine'); ?></option>
                    <option value="long"><?php _e('Long (250+ chars)', 'xelite-repost-engine'); ?></option>
                </select>
            </div>
        </div>
        
        <div class="xelite-modal-footer">
            <button type="button" class="button button-secondary xelite-modal-close">
                <?php _e('Cancel', 'xelite-repost-engine'); ?>
            </button>
            <button type="button" class="button button-primary" id="generate-quick-content">
                <span class="dashicons dashicons-admin-generic"></span>
                <?php _e('Generate Content', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>
</div>

<!-- Content Preview Modal -->
<div id="content-preview-modal" class="xelite-modal" style="display: none;">
    <div class="xelite-modal-content">
        <div class="xelite-modal-header">
            <h3><?php _e('Generated Content Preview', 'xelite-repost-engine'); ?></h3>
            <button type="button" class="xelite-modal-close">&times;</button>
        </div>
        
        <div class="xelite-modal-body">
            <div class="content-preview">
                <div class="content-text" id="preview-content-text"></div>
                <div class="content-meta">
                    <span class="content-length" id="preview-content-length"></span>
                    <span class="content-tokens" id="preview-content-tokens"></span>
                </div>
            </div>
        </div>
        
        <div class="xelite-modal-footer">
            <button type="button" class="button button-secondary xelite-modal-close">
                <?php _e('Close', 'xelite-repost-engine'); ?>
            </button>
            <button type="button" class="button button-secondary" id="regenerate-content">
                <span class="dashicons dashicons-update"></span>
                <?php _e('Regenerate', 'xelite-repost-engine'); ?>
            </button>
            <button type="button" class="button button-primary" id="save-content">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save Content', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize dashboard functionality
    if (typeof XeliteDashboard !== 'undefined') {
        XeliteDashboard.init();
    }
});
</script> 