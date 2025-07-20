<?php
/**
 * Dashboard Analytics Tab Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="xelite-analytics">
    <!-- Analytics Header -->
    <div class="analytics-header">
        <h2><?php _e('Content Analytics & Insights', 'xelite-repost-engine'); ?></h2>
        <p><?php _e('Track your content performance and get insights to improve your repost success rate.', 'xelite-repost-engine'); ?></p>
    </div>

    <!-- Analytics Overview -->
    <div class="analytics-overview">
        <div class="xelite-analytics-grid">
            <div class="xelite-analytics-card">
                <span class="xelite-analytics-number" id="total-content">
                    <?php echo esc_html($analytics_data['summary']['total_reposts'] ?? 0); ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Total Content', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card secondary">
                <span class="xelite-analytics-number" id="avg-engagement">
                    <?php echo esc_html(round($analytics_data['summary']['avg_engagement_per_repost'] ?? 0, 1)); ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Avg Engagement', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card warning">
                <span class="xelite-analytics-number" id="repost-rate">
                    <?php 
                    $repost_rate = isset($analytics_data['summary']['repost_rate']) ? $analytics_data['summary']['repost_rate'] : 0;
                    echo esc_html(round($repost_rate * 100, 1)) . '%';
                    ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Repost Rate', 'xelite-repost-engine'); ?></span>
            </div>
            
            <div class="xelite-analytics-card danger">
                <span class="xelite-analytics-number" id="best-performing">
                    <?php echo esc_html($analytics_data['summary']['best_performing_length'] ?? 0); ?>
                </span>
                <span class="xelite-analytics-label"><?php _e('Best Length (chars)', 'xelite-repost-engine'); ?></span>
            </div>
        </div>
    </div>

    <!-- Analytics Charts -->
    <div class="analytics-charts">
        <div class="chart-section">
            <h3><?php _e('Content Performance Trends', 'xelite-repost-engine'); ?></h3>
            
            <div class="chart-container">
                <canvas id="performance-chart" width="400" height="200"></canvas>
            </div>
            
            <div class="chart-legend">
                <div class="legend-item">
                    <span class="legend-color" style="background: #0073aa;"></span>
                    <span class="legend-label"><?php _e('Engagement Rate', 'xelite-repost-engine'); ?></span>
                </div>
                <div class="legend-item">
                    <span class="legend-color" style="background: #00a32a;"></span>
                    <span class="legend-label"><?php _e('Repost Rate', 'xelite-repost-engine'); ?></span>
                </div>
            </div>
        </div>
        
        <div class="chart-section">
            <h3><?php _e('Content Length Analysis', 'xelite-repost-engine'); ?></h3>
            
            <div class="chart-container">
                <canvas id="length-chart" width="400" height="200"></canvas>
            </div>
            
            <div class="chart-insights">
                <div class="insight-item">
                    <span class="insight-icon">📊</span>
                    <div class="insight-content">
                        <h4><?php _e('Optimal Length', 'xelite-repost-engine'); ?></h4>
                        <p><?php _e('Content between 150-250 characters performs best for reposts.', 'xelite-repost-engine'); ?></p>
                    </div>
                </div>
            </div>
        </div>
        
        <div class="chart-section">
            <h3><?php _e('Tone Performance', 'xelite-repost-engine'); ?></h3>
            
            <div class="chart-container">
                <canvas id="tone-chart" width="400" height="200"></canvas>
            </div>
            
            <div class="tone-breakdown">
                <?php if (!empty($analytics_data['charts']['tone'] ?? [])): ?>
                    <?php foreach ($analytics_data['charts']['tone'] as $tone => $data): ?>
                        <div class="tone-item">
                            <span class="tone-name"><?php echo esc_html(ucfirst($tone)); ?></span>
                            <div class="tone-bar">
                                <div class="tone-fill" style="width: <?php echo esc_attr($data['percentage']); ?>%"></div>
                            </div>
                            <span class="tone-percentage"><?php echo esc_html($data['percentage']); ?>%</span>
                        </div>
                    <?php endforeach; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Top Performing Content -->
    <div class="top-performing-content">
        <h3><?php _e('Top Performing Content', 'xelite-repost-engine'); ?></h3>
        
        <?php if (!empty($analytics_data['top_patterns'] ?? [])): ?>
            <div class="content-list">
                <?php foreach (array_slice($analytics_data['top_patterns'], 0, 5) as $index => $content): ?>
                    <div class="content-item">
                        <div class="content-rank">
                            <span class="rank-number">#<?php echo esc_html($index + 1); ?></span>
                        </div>
                        
                        <div class="content-details">
                            <div class="content-text">
                                <?php echo esc_html($content['text']); ?>
                            </div>
                            
                            <div class="content-stats">
                                <span class="stat-item">
                                    <span class="stat-label"><?php _e('Reposts:', 'xelite-repost-engine'); ?></span>
                                    <span class="stat-value"><?php echo esc_html($content['repost_count']); ?></span>
                                </span>
                                
                                <span class="stat-item">
                                    <span class="stat-label"><?php _e('Engagement:', 'xelite-repost-engine'); ?></span>
                                    <span class="stat-value"><?php echo esc_html($content['avg_engagement']); ?></span>
                                </span>
                                
                                <span class="stat-item">
                                    <span class="stat-label"><?php _e('Length:', 'xelite-repost-engine'); ?></span>
                                    <span class="stat-value"><?php echo esc_html(strlen($content['text'])); ?> chars</span>
                                </span>
                                
                                <span class="stat-item">
                                    <span class="stat-label"><?php _e('Tone:', 'xelite-repost-engine'); ?></span>
                                    <span class="stat-value"><?php echo esc_html($content['tone'] ?? 'N/A'); ?></span>
                                </span>
                            </div>
                        </div>
                        
                        <div class="content-actions">
                            <button type="button" class="xelite-content-btn copy" data-content="<?php echo esc_attr($content['text']); ?>">
                                <span class="dashicons dashicons-clipboard"></span>
                                <?php _e('Copy', 'xelite-repost-engine'); ?>
                            </button>
                            
                            <button type="button" class="xelite-content-btn analyze" data-content-id="<?php echo esc_attr($content['id'] ?? $index); ?>">
                                <span class="dashicons dashicons-chart-line"></span>
                                <?php _e('Analyze', 'xelite-repost-engine'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="xelite-empty-state">
                <div class="empty-icon">
                    <span class="dashicons dashicons-chart-bar"></span>
                </div>
                <h4><?php _e('No Analytics Data', 'xelite-repost-engine'); ?></h4>
                <p><?php _e('Analytics data will appear here once you start generating and tracking content performance.', 'xelite-repost-engine'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Recommendations -->
    <div class="analytics-recommendations">
        <h3><?php _e('AI-Powered Recommendations', 'xelite-repost-engine'); ?></h3>
        
        <?php if (!empty($analytics_data['recommendations'] ?? [])): ?>
            <div class="recommendations-list">
                <?php foreach ($analytics_data['recommendations'] as $recommendation): ?>
                    <div class="recommendation-item">
                        <div class="recommendation-icon">
                            <span class="dashicons dashicons-lightbulb"></span>
                        </div>
                        
                        <div class="recommendation-content">
                            <h4><?php echo esc_html($recommendation['title']); ?></h4>
                            <p><?php echo esc_html($recommendation['description']); ?></p>
                            
                            <?php if (!empty($recommendation['action'])): ?>
                                <button type="button" class="button button-secondary" data-action="<?php echo esc_attr($recommendation['action']); ?>">
                                    <?php echo esc_html($recommendation['action_text'] ?? __('Apply', 'xelite-repost-engine')); ?>
                                </button>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php else: ?>
            <div class="recommendations-placeholder">
                <p><?php _e('AI recommendations will appear here based on your content performance analysis.', 'xelite-repost-engine'); ?></p>
            </div>
        <?php endif; ?>
    </div>

    <!-- Export Options -->
    <div class="analytics-export">
        <h3><?php _e('Export Analytics', 'xelite-repost-engine'); ?></h3>
        
        <div class="export-options">
            <div class="export-option">
                <h4><?php _e('Export Data', 'xelite-repost-engine'); ?></h4>
                <p><?php _e('Download your analytics data in various formats for further analysis.', 'xelite-repost-engine'); ?></p>
                
                <div class="export-buttons">
                    <button type="button" class="button button-secondary" id="export-csv">
                        <span class="dashicons dashicons-media-spreadsheet"></span>
                        <?php _e('Export CSV', 'xelite-repost-engine'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" id="export-json">
                        <span class="dashicons dashicons-media-code"></span>
                        <?php _e('Export JSON', 'xelite-repost-engine'); ?>
                    </button>
                    
                    <button type="button" class="button button-secondary" id="export-pdf">
                        <span class="dashicons dashicons-media-document"></span>
                        <?php _e('Export PDF Report', 'xelite-repost-engine'); ?>
                    </button>
                </div>
            </div>
            
            <div class="export-option">
                <h4><?php _e('Schedule Reports', 'xelite-repost-engine'); ?></h4>
                <p><?php _e('Set up automated reports to be sent to your email.', 'xelite-repost-engine'); ?></p>
                
                <button type="button" class="button button-primary" id="schedule-report">
                    <span class="dashicons dashicons-calendar-alt"></span>
                    <?php _e('Schedule Report', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
    </div>

    <!-- Loading State -->
    <div class="xelite-loading-state" id="analytics-loading" style="display: none;">
        <div class="loading-content">
            <div class="xelite-loading"></div>
            <h3><?php _e('Loading Analytics...', 'xelite-repost-engine'); ?></h3>
            <p><?php _e('Analyzing your content performance data.', 'xelite-repost-engine'); ?></p>
        </div>
    </div>
</div>

<!-- Analytics Chart Scripts -->
<script type="text/javascript">
jQuery(document).ready(function($) {
    // Initialize analytics charts when the page loads
    if (typeof XeliteAnalytics !== 'undefined') {
        XeliteAnalytics.initCharts();
    }
});
</script> 