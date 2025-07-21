<?php
/**
 * Dashboard Patterns Tab Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="xelite-patterns">
    <!-- Patterns Header -->
    <div class="patterns-header">
        <h2><?php _e('Repost Patterns Analysis', 'xelite-repost-engine'); ?></h2>
        <p><?php _e('Analyze patterns from your target accounts to understand what content gets reposted.', 'xelite-repost-engine'); ?></p>
    </div>

    <!-- Pattern Filters -->
    <div class="pattern-filters">
        <div class="filter-section">
            <h3><?php _e('Filter Patterns', 'xelite-repost-engine'); ?></h3>
            
            <div class="filter-controls">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="pattern-source" class="tooltip-trigger" data-tooltip="<?php _e('Filter by specific source account', 'xelite-repost-engine'); ?>">
                            <?php _e('Source Account:', 'xelite-repost-engine'); ?>
                        </label>
                        <select id="pattern-source">
                            <option value=""><?php _e('All Accounts', 'xelite-repost-engine'); ?></option>
                            <?php if (!empty($patterns)): ?>
                                <?php foreach (array_unique(array_column($patterns, 'source_handle')) as $handle): ?>
                                    <option value="<?php echo esc_attr($handle); ?>">@<?php echo esc_html($handle); ?></option>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="pattern-min-reposts" class="tooltip-trigger" data-tooltip="<?php _e('Minimum number of reposts required', 'xelite-repost-engine'); ?>">
                            <?php _e('Min Reposts:', 'xelite-repost-engine'); ?>
                        </label>
                        <select id="pattern-min-reposts">
                            <option value="1"><?php _e('1+ reposts', 'xelite-repost-engine'); ?></option>
                            <option value="5" selected><?php _e('5+ reposts', 'xelite-repost-engine'); ?></option>
                            <option value="10"><?php _e('10+ reposts', 'xelite-repost-engine'); ?></option>
                            <option value="20"><?php _e('20+ reposts', 'xelite-repost-engine'); ?></option>
                            <option value="50"><?php _e('50+ reposts', 'xelite-repost-engine'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="pattern-min-engagement" class="tooltip-trigger" data-tooltip="<?php _e('Minimum average engagement rate', 'xelite-repost-engine'); ?>">
                            <?php _e('Min Engagement:', 'xelite-repost-engine'); ?>
                        </label>
                        <input type="number" id="pattern-min-engagement" min="0" step="0.1" placeholder="0">
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="pattern-date-from" class="tooltip-trigger" data-tooltip="<?php _e('Start date for pattern analysis', 'xelite-repost-engine'); ?>">
                            <?php _e('Date From:', 'xelite-repost-engine'); ?>
                        </label>
                        <input type="date" id="pattern-date-from">
                    </div>
                    
                    <div class="filter-group">
                        <label for="pattern-date-to" class="tooltip-trigger" data-tooltip="<?php _e('End date for pattern analysis', 'xelite-repost-engine'); ?>">
                            <?php _e('Date To:', 'xelite-repost-engine'); ?>
                        </label>
                        <input type="date" id="pattern-date-to">
                    </div>
                    
                    <div class="filter-group">
                        <label for="pattern-content-type" class="tooltip-trigger" data-tooltip="<?php _e('Filter by content format type', 'xelite-repost-engine'); ?>">
                            <?php _e('Content Type:', 'xelite-repost-engine'); ?>
                        </label>
                        <select id="pattern-content-type">
                            <option value=""><?php _e('All Types', 'xelite-repost-engine'); ?></option>
                            <option value="text"><?php _e('Text Only', 'xelite-repost-engine'); ?></option>
                            <option value="image"><?php _e('With Image', 'xelite-repost-engine'); ?></option>
                            <option value="video"><?php _e('With Video', 'xelite-repost-engine'); ?></option>
                            <option value="poll"><?php _e('Poll', 'xelite-repost-engine'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="pattern-tone" class="tooltip-trigger" data-tooltip="<?php _e('Filter by content tone', 'xelite-repost-engine'); ?>">
                            <?php _e('Tone:', 'xelite-repost-engine'); ?>
                        </label>
                        <select id="pattern-tone">
                            <option value=""><?php _e('All Tones', 'xelite-repost-engine'); ?></option>
                            <option value="conversational"><?php _e('Conversational', 'xelite-repost-engine'); ?></option>
                            <option value="professional"><?php _e('Professional', 'xelite-repost-engine'); ?></option>
                            <option value="casual"><?php _e('Casual', 'xelite-repost-engine'); ?></option>
                            <option value="humorous"><?php _e('Humorous', 'xelite-repost-engine'); ?></option>
                            <option value="inspirational"><?php _e('Inspirational', 'xelite-repost-engine'); ?></option>
                        </select>
                    </div>
                </div>
                
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="pattern-sort" class="tooltip-trigger" data-tooltip="<?php _e('Sort patterns by this criteria', 'xelite-repost-engine'); ?>">
                            <?php _e('Sort By:', 'xelite-repost-engine'); ?>
                        </label>
                        <select id="pattern-sort">
                            <option value="repost_count" selected><?php _e('Repost Count', 'xelite-repost-engine'); ?></option>
                            <option value="engagement"><?php _e('Engagement', 'xelite-repost-engine'); ?></option>
                            <option value="date"><?php _e('Date', 'xelite-repost-engine'); ?></option>
                            <option value="length"><?php _e('Length', 'xelite-repost-engine'); ?></option>
                            <option value="hashtag_count"><?php _e('Hashtag Count', 'xelite-repost-engine'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group">
                        <label for="pattern-sort-order" class="tooltip-trigger" data-tooltip="<?php _e('Sort order (ascending or descending)', 'xelite-repost-engine'); ?>">
                            <?php _e('Sort Order:', 'xelite-repost-engine'); ?>
                        </label>
                        <select id="pattern-sort-order">
                            <option value="desc" selected><?php _e('Descending', 'xelite-repost-engine'); ?></option>
                            <option value="asc"><?php _e('Ascending', 'xelite-repost-engine'); ?></option>
                        </select>
                    </div>
                    
                    <div class="filter-group search-group">
                        <label for="pattern-search" class="tooltip-trigger" data-tooltip="<?php _e('Search within pattern text', 'xelite-repost-engine'); ?>">
                            <?php _e('Search:', 'xelite-repost-engine'); ?>
                        </label>
                        <input type="text" id="pattern-search" placeholder="<?php _e('Search patterns...', 'xelite-repost-engine'); ?>">
                    </div>
                </div>
            </div>
            
            <div class="filter-actions">
                <button type="button" class="button button-primary" id="apply-filters">
                    <span class="dashicons dashicons-filter"></span>
                    <?php _e('Apply Filters', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="button button-secondary" id="reset-filters">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Reset', 'xelite-repost-engine'); ?>
                </button>
                
                <div class="export-dropdown">
                    <button type="button" class="button button-secondary" id="export-patterns">
                        <span class="dashicons dashicons-download"></span>
                        <?php _e('Export', 'xelite-repost-engine'); ?>
                    </button>
                    <div class="export-options">
                        <button type="button" class="export-option" data-format="csv"><?php _e('Export as CSV', 'xelite-repost-engine'); ?></button>
                        <button type="button" class="export-option" data-format="json"><?php _e('Export as JSON', 'xelite-repost-engine'); ?></button>
                        <button type="button" class="export-option" data-format="pdf"><?php _e('Export as PDF', 'xelite-repost-engine'); ?></button>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Pattern Statistics -->
    <div class="pattern-statistics">
        <div class="xelite-analytics-grid">
            <div class="xelite-analytics-card">
                <span class="xelite-analytics-number" id="total-patterns">
                    <?php echo esc_html(count($patterns)); ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Total Patterns', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card secondary">
                <span class="xelite-analytics-number" id="avg-reposts">
                    <?php 
                    if (!empty($patterns)) {
                        $avg_reposts = array_sum(array_column($patterns, 'repost_count')) / count($patterns);
                        echo esc_html(round($avg_reposts, 1));
                    } else {
                        echo '0';
                    }
                    ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Avg Reposts', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card warning">
                <span class="xelite-analytics-number" id="avg-engagement">
                    <?php 
                    if (!empty($patterns)) {
                        $avg_engagement = array_sum(array_column($patterns, 'avg_engagement')) / count($patterns);
                        echo esc_html(round($avg_engagement, 1));
                    } else {
                        echo '0';
                    }
                    ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Avg Engagement', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card danger">
                <span class="xelite-analytics-number" id="unique-sources">
                    <?php 
                    if (!empty($patterns)) {
                        echo esc_html(count(array_unique(array_column($patterns, 'source_handle'))));
                    } else {
                        echo '0';
                    }
                    ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Source Accounts', 'xelite-repost-engine'); ?></span>
            </div>
        </div>
    </div>

    <!-- Pattern Charts -->
    <div class="pattern-charts">
        <div class="charts-header">
            <h3><?php _e('Pattern Analytics', 'xelite-repost-engine'); ?></h3>
            <div class="chart-controls">
                <select id="chart-type" class="chart-selector">
                    <option value="repost_trends"><?php _e('Repost Trends', 'xelite-repost-engine'); ?></option>
                    <option value="content_types"><?php _e('Content Types', 'xelite-repost-engine'); ?></option>
                    <option value="tone_analysis"><?php _e('Tone Analysis', 'xelite-repost-engine'); ?></option>
                    <option value="length_distribution"><?php _e('Length Distribution', 'xelite-repost-engine'); ?></option>
                    <option value="engagement_correlation"><?php _e('Engagement Correlation', 'xelite-repost-engine'); ?></option>
                </select>
                <button type="button" class="button button-secondary" id="refresh-chart">
                    <span class="dashicons dashicons-update"></span>
                    <?php _e('Refresh', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
        
        <div class="charts-container">
            <div class="chart-wrapper">
                <canvas id="pattern-chart" width="400" height="200"></canvas>
                <div class="chart-loading" id="chart-loading" style="display: none;">
                    <div class="xelite-loading"></div>
                    <p><?php _e('Loading chart data...', 'xelite-repost-engine'); ?></p>
                </div>
                <div class="chart-error" id="chart-error" style="display: none;">
                    <p><?php _e('Error loading chart data. Please try again.', 'xelite-repost-engine'); ?></p>
                </div>
            </div>
            
            <div class="chart-insights" id="chart-insights">
                <h4><?php _e('Key Insights', 'xelite-repost-engine'); ?></h4>
                <div class="insights-content" id="insights-content">
                    <!-- Insights will be loaded here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Patterns List -->
    <div class="patterns-content">
        <?php if (!empty($patterns)): ?>
            <div class="patterns-list" id="patterns-list">
                <?php foreach ($patterns as $index => $pattern): ?>
                    <div class="xelite-pattern-item" data-pattern-id="<?php echo esc_attr($pattern['id'] ?? $index); ?>">
                        <div class="pattern-header">
                            <div class="pattern-source">
                                <span class="source-handle">@<?php echo esc_html($pattern['source_handle']); ?></span>
                                <span class="repost-count"><?php echo esc_html($pattern['repost_count']); ?> reposts</span>
                            </div>
                            
                            <div class="pattern-meta">
                                <span class="pattern-date"><?php echo esc_html(date('M j, Y', strtotime($pattern['created_at'] ?? 'now'))); ?></span>
                                <span class="pattern-engagement"><?php echo esc_html($pattern['avg_engagement']); ?> avg engagement</span>
                            </div>
                        </div>
                        
                        <div class="pattern-content">
                            <div class="pattern-text">
                                <?php echo esc_html($pattern['text']); ?>
                            </div>
                            
                            <div class="pattern-details">
                                <div class="detail-item">
                                    <span class="detail-label"><?php _e('Length:', 'xelite-repost-engine'); ?></span>
                                    <span class="detail-value"><?php echo esc_html(strlen($pattern['text'])); ?> chars</span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label"><?php _e('Tone:', 'xelite-repost-engine'); ?></span>
                                    <span class="detail-value"><?php echo esc_html($pattern['tone'] ?? 'N/A'); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label"><?php _e('Format:', 'xelite-repost-engine'); ?></span>
                                    <span class="detail-value"><?php echo esc_html($pattern['format'] ?? 'Text'); ?></span>
                                </div>
                                
                                <div class="detail-item">
                                    <span class="detail-label"><?php _e('Hashtags:', 'xelite-repost-engine'); ?></span>
                                    <span class="detail-value"><?php echo esc_html($pattern['hashtag_count'] ?? 0); ?></span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="pattern-actions">
                            <button type="button" class="xelite-content-btn copy" data-content="<?php echo esc_attr($pattern['text']); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                                <?php _e('Copy', 'xelite-repost-engine'); ?>
                            </button>
                            
                            <button type="button" class="xelite-content-btn analyze" data-pattern-id="<?php echo esc_attr($pattern['id'] ?? $index); ?>">
                                <span class="dashicons dashicons-chart-line"></span>
                                <?php _e('Analyze', 'xelite-repost-engine'); ?>
                            </button>
                            
                            <button type="button" class="xelite-content-btn generate-similar" data-pattern-id="<?php echo esc_attr($pattern['id'] ?? $index); ?>">
                                <span class="dashicons dashicons-admin-generic"></span>
                                <?php _e('Generate Similar', 'xelite-repost-engine'); ?>
                            </button>
                            
                            <button type="button" class="xelite-content-btn bookmark" data-pattern-id="<?php echo esc_attr($pattern['id'] ?? $index); ?>">
                                <span class="dashicons dashicons-bookmark"></span>
                                <?php _e('Bookmark', 'xelite-repost-engine'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <!-- Pagination -->
            <div class="patterns-pagination">
                <div class="pagination-info">
                    <span class="showing-info"><?php _e('Showing', 'xelite-repost-engine'); ?> <span id="showing-start">1</span>-<span id="showing-end"><?php echo esc_html(min(20, count($patterns))); ?></span> <?php _e('of', 'xelite-repost-engine'); ?> <span id="total-count"><?php echo esc_html(count($patterns)); ?></span> <?php _e('patterns', 'xelite-repost-engine'); ?></span>
                </div>
                
                <div class="pagination-controls">
                    <button type="button" class="button button-secondary" id="prev-page" disabled>
                        <?php _e('Previous', 'xelite-repost-engine'); ?>
                    </button>
                    
                    <span class="page-numbers">
                        <span class="current-page">1</span> / <span class="total-pages"><?php echo esc_html(ceil(count($patterns) / 20)); ?></span>
                    </span>
                    
                    <button type="button" class="button button-secondary" id="next-page" <?php echo count($patterns) <= 20 ? 'disabled' : ''; ?>>
                        <?php _e('Next', 'xelite-repost-engine'); ?>
                    </button>
                </div>
            </div>
        <?php else: ?>
            <div class="xelite-empty-state">
                <div class="empty-icon">
                    <span class="dashicons dashicons-chart-line"></span>
                </div>
                <h4><?php _e('No Patterns Available', 'xelite-repost-engine'); ?></h4>
                <p><?php _e('No repost patterns have been analyzed yet. This could be because:', 'xelite-repost-engine'); ?></p>
                <ul>
                    <li><?php _e('No target accounts have been added', 'xelite-repost-engine'); ?></li>
                    <li><?php _e('Pattern analysis hasn\'t been run yet', 'xelite-repost-engine'); ?></li>
                    <li><?php _e('No repost data has been collected', 'xelite-repost-engine'); ?></li>
                </ul>
                
                <div class="empty-actions">
                    <button type="button" class="button button-primary" data-action="add-target-accounts">
                        <?php _e('Add Target Accounts', 'xelite-repost-engine'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" data-action="run-analysis">
                        <?php _e('Run Pattern Analysis', 'xelite-repost-engine'); ?>
                    </button>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <!-- Pattern Analysis Modal -->
    <div id="pattern-analysis-modal" class="xelite-modal" style="display: none;">
        <div class="xelite-modal-content">
            <div class="xelite-modal-header">
                <h3><?php _e('Pattern Analysis', 'xelite-repost-engine'); ?></h3>
                <button type="button" class="xelite-modal-close">&times;</button>
            </div>
            
            <div class="xelite-modal-body">
                <div class="analysis-content" id="analysis-content">
                    <!-- Analysis content will be loaded here -->
                </div>
            </div>
            
            <div class="xelite-modal-footer">
                <button type="button" class="button button-secondary xelite-modal-close">
                    <?php _e('Close', 'xelite-repost-engine'); ?>
                </button>
                
                <button type="button" class="button button-primary" id="generate-from-analysis">
                    <span class="dashicons dashicons-admin-generic"></span>
                    <?php _e('Generate Similar Content', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div class="xelite-loading-state" id="patterns-loading" style="display: none;">
        <div class="loading-content">
            <div class="xelite-loading"></div>
            <h3><?php _e('Loading Patterns...', 'xelite-repost-engine'); ?></h3>
            <p><?php _e('Analyzing repost patterns from your target accounts.', 'xelite-repost-engine'); ?></p>
        </div>
    </div>
</div> 