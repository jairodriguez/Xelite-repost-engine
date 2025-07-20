<?php
/**
 * Dashboard Settings Tab Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="xelite-settings">
    <!-- Settings Header -->
    <div class="settings-header">
        <h2><?php _e('Personal Settings', 'xelite-repost-engine'); ?></h2>
        <p><?php _e('Configure your personal preferences and content generation settings.', 'xelite-repost-engine'); ?></p>
    </div>

    <!-- Settings Form -->
    <form id="user-settings-form" class="xelite-settings-form">
        <!-- Content Generation Settings -->
        <div class="settings-section">
            <h3><?php _e('Content Generation Settings', 'xelite-repost-engine'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="default-tone"><?php _e('Default Tone:', 'xelite-repost-engine'); ?></label>
                    <select id="default-tone" name="default_tone">
                        <option value="conversational" <?php selected($user_settings['default_tone'] ?? '', 'conversational'); ?>>
                            <?php _e('Conversational', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="professional" <?php selected($user_settings['default_tone'] ?? '', 'professional'); ?>>
                            <?php _e('Professional', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="casual" <?php selected($user_settings['default_tone'] ?? '', 'casual'); ?>>
                            <?php _e('Casual', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="enthusiastic" <?php selected($user_settings['default_tone'] ?? '', 'enthusiastic'); ?>>
                            <?php _e('Enthusiastic', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="informative" <?php selected($user_settings['default_tone'] ?? '', 'informative'); ?>>
                            <?php _e('Informative', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="humorous" <?php selected($user_settings['default_tone'] ?? '', 'humorous'); ?>>
                            <?php _e('Humorous', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="inspirational" <?php selected($user_settings['default_tone'] ?? '', 'inspirational'); ?>>
                            <?php _e('Inspirational', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('The default tone for generated content.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="max-tokens"><?php _e('Max Tokens:', 'xelite-repost-engine'); ?></label>
                    <input type="number" id="max-tokens" name="max_tokens" 
                           value="<?php echo esc_attr($user_settings['max_tokens'] ?? 280); ?>" 
                           min="50" max="500" step="10">
                    <p class="description"><?php _e('Maximum number of tokens for generated content.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="temperature"><?php _e('Creativity Level:', 'xelite-repost-engine'); ?></label>
                    <input type="range" id="temperature" name="temperature" 
                           value="<?php echo esc_attr($user_settings['temperature'] ?? 0.7); ?>" 
                           min="0.1" max="1.0" step="0.1">
                    <div class="range-labels">
                        <span><?php _e('Conservative', 'xelite-repost-engine'); ?></span>
                        <span><?php _e('Balanced', 'xelite-repost-engine'); ?></span>
                        <span><?php _e('Creative', 'xelite-repost-engine'); ?></span>
                    </div>
                    <p class="description"><?php _e('How creative should the AI be when generating content?', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="default-variations"><?php _e('Default Variations:', 'xelite-repost-engine'); ?></label>
                    <select id="default-variations" name="default_variations">
                        <option value="1" <?php selected($user_settings['default_variations'] ?? 3, 1); ?>>
                            <?php _e('1 variation', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="3" <?php selected($user_settings['default_variations'] ?? 3, 3); ?>>
                            <?php _e('3 variations', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="5" <?php selected($user_settings['default_variations'] ?? 3, 5); ?>>
                            <?php _e('5 variations', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('Default number of content variations to generate.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
        </div>

        <!-- Content Preferences -->
        <div class="settings-section">
            <h3><?php _e('Content Preferences', 'xelite-repost-engine'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="include-hashtags-default"><?php _e('Default Hashtag Setting:', 'xelite-repost-engine'); ?></label>
                    <select id="include-hashtags-default" name="include_hashtags_default">
                        <option value="auto" <?php selected($user_settings['include_hashtags_default'] ?? 'auto', 'auto'); ?>>
                            <?php _e('Auto-generate relevant hashtags', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="manual" <?php selected($user_settings['include_hashtags_default'] ?? 'auto', 'manual'); ?>>
                            <?php _e('Let me add hashtags manually', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="none" <?php selected($user_settings['include_hashtags_default'] ?? 'auto', 'none'); ?>>
                            <?php _e('No hashtags', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('Default setting for hashtag inclusion in generated content.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="include-cta-default"><?php _e('Default CTA Setting:', 'xelite-repost-engine'); ?></label>
                    <select id="include-cta-default" name="include_cta_default">
                        <option value="auto" <?php selected($user_settings['include_cta_default'] ?? 'auto', 'auto'); ?>>
                            <?php _e('Auto-generate CTA', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="manual" <?php selected($user_settings['include_cta_default'] ?? 'auto', 'manual'); ?>>
                            <?php _e('Let me add CTA manually', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="none" <?php selected($user_settings['include_cta_default'] ?? 'auto', 'none'); ?>>
                            <?php _e('No CTA', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('Default setting for call-to-action inclusion in generated content.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="preferred-length"><?php _e('Preferred Content Length:', 'xelite-repost-engine'); ?></label>
                    <select id="preferred-length" name="preferred_length">
                        <option value="short" <?php selected($user_settings['preferred_length'] ?? 'medium', 'short'); ?>>
                            <?php _e('Short (100-150 chars)', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="medium" <?php selected($user_settings['preferred_length'] ?? 'medium', 'medium'); ?>>
                            <?php _e('Medium (150-250 chars)', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="long" <?php selected($user_settings['preferred_length'] ?? 'medium', 'long'); ?>>
                            <?php _e('Long (250+ chars)', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('Your preferred content length for generated posts.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="pattern-influence-default"><?php _e('Default Pattern Influence:', 'xelite-repost-engine'); ?></label>
                    <select id="pattern-influence-default" name="pattern_influence_default">
                        <option value="low" <?php selected($user_settings['pattern_influence_default'] ?? 'medium', 'low'); ?>>
                            <?php _e('Low (Minimal pattern influence)', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="medium" <?php selected($user_settings['pattern_influence_default'] ?? 'medium', 'medium'); ?>>
                            <?php _e('Medium (Balanced pattern influence)', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="high" <?php selected($user_settings['pattern_influence_default'] ?? 'medium', 'high'); ?>>
                            <?php _e('High (Strong pattern influence)', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('How much should repost patterns influence generated content by default?', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
        </div>

        <!-- Automation Settings -->
        <div class="settings-section">
            <h3><?php _e('Automation Settings', 'xelite-repost-engine'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="auto-save" name="auto_save" 
                               value="1" <?php checked($user_settings['auto_save'] ?? true); ?>>
                        <span class="checkmark"></span>
                        <?php _e('Auto-save generated content', 'xelite-repost-engine'); ?>
                    </label>
                    <p class="description"><?php _e('Automatically save generated content to your library.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="notifications" name="notifications" 
                               value="1" <?php checked($user_settings['notifications'] ?? true); ?>>
                        <span class="checkmark"></span>
                        <?php _e('Enable notifications', 'xelite-repost-engine'); ?>
                    </label>
                    <p class="description"><?php _e('Receive notifications about new patterns and recommendations.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="auto-optimize" name="auto_optimize" 
                               value="1" <?php checked($user_settings['auto_optimize'] ?? false); ?>>
                        <span class="checkmark"></span>
                        <?php _e('Auto-optimize content', 'xelite-repost-engine'); ?>
                    </label>
                    <p class="description"><?php _e('Automatically optimize generated content for better repost potential.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="pattern-updates" name="pattern_updates" 
                               value="1" <?php checked($user_settings['pattern_updates'] ?? true); ?>>
                        <span class="checkmark"></span>
                        <?php _e('Pattern update notifications', 'xelite-repost-engine'); ?>
                    </label>
                    <p class="description"><?php _e('Get notified when new repost patterns are discovered.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
        </div>

        <!-- Content Library Settings -->
        <div class="settings-section">
            <h3><?php _e('Content Library Settings', 'xelite-repost-engine'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="library-organization"><?php _e('Library Organization:', 'xelite-repost-engine'); ?></label>
                    <select id="library-organization" name="library_organization">
                        <option value="date" <?php selected($user_settings['library_organization'] ?? 'date', 'date'); ?>>
                            <?php _e('By Date', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="topic" <?php selected($user_settings['library_organization'] ?? 'date', 'topic'); ?>>
                            <?php _e('By Topic', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="tone" <?php selected($user_settings['library_organization'] ?? 'date', 'tone'); ?>>
                            <?php _e('By Tone', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="performance" <?php selected($user_settings['library_organization'] ?? 'date', 'performance'); ?>>
                            <?php _e('By Performance', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('How to organize your saved content library.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label for="auto-cleanup"><?php _e('Auto Cleanup:', 'xelite-repost-engine'); ?></label>
                    <select id="auto-cleanup" name="auto_cleanup">
                        <option value="never" <?php selected($user_settings['auto_cleanup'] ?? 'never', 'never'); ?>>
                            <?php _e('Never', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="30" <?php selected($user_settings['auto_cleanup'] ?? 'never', '30'); ?>>
                            <?php _e('After 30 days', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="60" <?php selected($user_settings['auto_cleanup'] ?? 'never', '60'); ?>>
                            <?php _e('After 60 days', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="90" <?php selected($user_settings['auto_cleanup'] ?? 'never', '90'); ?>>
                            <?php _e('After 90 days', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('Automatically remove old unused content from your library.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
        </div>

        <!-- Export/Import Settings -->
        <div class="settings-section">
            <h3><?php _e('Data Management', 'xelite-repost-engine'); ?></h3>
            
            <div class="form-row">
                <div class="form-group">
                    <label for="data-retention"><?php _e('Data Retention:', 'xelite-repost-engine'); ?></label>
                    <select id="data-retention" name="data_retention">
                        <option value="30" <?php selected($user_settings['data_retention'] ?? '90', '30'); ?>>
                            <?php _e('30 days', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="60" <?php selected($user_settings['data_retention'] ?? '90', '60'); ?>>
                            <?php _e('60 days', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="90" <?php selected($user_settings['data_retention'] ?? '90', '90'); ?>>
                            <?php _e('90 days', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="180" <?php selected($user_settings['data_retention'] ?? '90', '180'); ?>>
                            <?php _e('180 days', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="365" <?php selected($user_settings['data_retention'] ?? '90', '365'); ?>>
                            <?php _e('1 year', 'xelite-repost-engine'); ?>
                        </option>
                        <option value="forever" <?php selected($user_settings['data_retention'] ?? '90', 'forever'); ?>>
                            <?php _e('Forever', 'xelite-repost-engine'); ?>
                        </option>
                    </select>
                    <p class="description"><?php _e('How long to keep your analytics and pattern data.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <label class="checkbox-label">
                        <input type="checkbox" id="anonymize-data" name="anonymize_data" 
                               value="1" <?php checked($user_settings['anonymize_data'] ?? false); ?>>
                        <span class="checkmark"></span>
                        <?php _e('Anonymize data for analytics', 'xelite-repost-engine'); ?>
                    </label>
                    <p class="description"><?php _e('Remove personal identifiers from analytics data.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
            
            <div class="form-row">
                <div class="form-group">
                    <button type="button" class="button button-secondary" id="export-settings">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export Settings', 'xelite-repost-engine'); ?>
                    </button>
                    <p class="description"><?php _e('Download your current settings as a backup.', 'xelite-repost-engine'); ?></p>
                </div>
                
                <div class="form-group">
                    <button type="button" class="button button-secondary" id="import-settings">
                        <span class="dashicons dashicons-upload"></span>
                        <?php _e('Import Settings', 'xelite-repost-engine'); ?>
                    </button>
                    <p class="description"><?php _e('Import settings from a backup file.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
        </div>

        <!-- Form Actions -->
        <div class="form-actions">
            <button type="submit" class="button button-primary button-large" id="save-settings">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save Settings', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="reset-settings">
                <span class="dashicons dashicons-admin-tools"></span>
                <?php _e('Reset to Defaults', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </form>

    <!-- Settings Import Modal -->
    <div id="settings-import-modal" class="xelite-modal" style="display: none;">
        <div class="xelite-modal-content">
            <div class="xelite-modal-header">
                <h3><?php _e('Import Settings', 'xelite-repost-engine'); ?></h3>
                <button type="button" class="xelite-modal-close">&times;</button>
            </div>
            
            <div class="xelite-modal-body">
                <div class="import-content">
                    <p><?php _e('Select a settings backup file to import:', 'xelite-repost-engine'); ?></p>
                    <input type="file" id="settings-file" accept=".json" />
                </div>
            </div>
            
            <div class="xelite-modal-footer">
                <button type="button" class="button button-secondary xelite-modal-close">
                    <?php _e('Cancel', 'xelite-repost-engine'); ?>
                </button>
                <button type="button" class="button button-primary" id="confirm-import">
                    <?php _e('Import Settings', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div class="xelite-loading-state" id="settings-loading" style="display: none;">
        <div class="loading-content">
            <div class="xelite-loading"></div>
            <h3><?php _e('Saving Settings...', 'xelite-repost-engine'); ?></h3>
            <p><?php _e('Please wait while we save your settings.', 'xelite-repost-engine'); ?></p>
        </div>
    </div>
</div> 