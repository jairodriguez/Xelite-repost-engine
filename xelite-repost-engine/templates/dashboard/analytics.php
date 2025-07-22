<?php
/**
 * Analytics Dashboard Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get current user ID
$user_id = get_current_user_id();

// Get analytics data
$analytics_collector = $this->container->get('analytics_collector');
$analytics_data = $analytics_collector->get_dashboard_data($user_id);

// Get date range from request or default to last 30 days
$date_range = isset($_GET['date_range']) ? sanitize_text_field($_GET['date_range']) : '30';
$start_date = isset($_GET['start_date']) ? sanitize_text_field($_GET['start_date']) : date('Y-m-d', strtotime("-{$date_range} days"));
$end_date = isset($_GET['end_date']) ? sanitize_text_field($_GET['end_date']) : date('Y-m-d');

// Get filter options
$content_types = $analytics_collector->get_content_types();
$engagement_ranges = $analytics_collector->get_engagement_ranges();
?>

<div class="wrap xelite-analytics-dashboard">
    <h1><?php _e('Analytics Dashboard', 'xelite-repost-engine'); ?></h1>
    
    <!-- Date Range Filter -->
    <div class="xelite-analytics-filters">
        <div class="xelite-filter-group">
            <label for="date_range"><?php _e('Date Range:', 'xelite-repost-engine'); ?></label>
            <select id="date_range" name="date_range">
                <option value="7" <?php selected($date_range, '7'); ?>><?php _e('Last 7 days', 'xelite-repost-engine'); ?></option>
                <option value="30" <?php selected($date_range, '30'); ?>><?php _e('Last 30 days', 'xelite-repost-engine'); ?></option>
                <option value="90" <?php selected($date_range, '90'); ?>><?php _e('Last 90 days', 'xelite-repost-engine'); ?></option>
                <option value="365" <?php selected($date_range, '365'); ?>><?php _e('Last year', 'xelite-repost-engine'); ?></option>
                <option value="custom" <?php selected($date_range, 'custom'); ?>><?php _e('Custom range', 'xelite-repost-engine'); ?></option>
            </select>
        </div>
        
        <div class="xelite-filter-group custom-date-range" style="display: <?php echo $date_range === 'custom' ? 'block' : 'none'; ?>;">
            <label for="start_date"><?php _e('Start Date:', 'xelite-repost-engine'); ?></label>
            <input type="date" id="start_date" name="start_date" value="<?php echo esc_attr($start_date); ?>">
            
            <label for="end_date"><?php _e('End Date:', 'xelite-repost-engine'); ?></label>
            <input type="date" id="end_date" name="end_date" value="<?php echo esc_attr($end_date); ?>">
        </div>
        
        <div class="xelite-filter-group">
            <label for="content_type_filter"><?php _e('Content Type:', 'xelite-repost-engine'); ?></label>
            <select id="content_type_filter" name="content_type">
                <option value=""><?php _e('All types', 'xelite-repost-engine'); ?></option>
                <?php foreach ($content_types as $type => $label): ?>
                    <option value="<?php echo esc_attr($type); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <div class="xelite-filter-group">
            <label for="engagement_filter"><?php _e('Engagement Range:', 'xelite-repost-engine'); ?></label>
            <select id="engagement_filter" name="engagement_range">
                <option value=""><?php _e('All ranges', 'xelite-repost-engine'); ?></option>
                <?php foreach ($engagement_ranges as $range => $label): ?>
                    <option value="<?php echo esc_attr($range); ?>"><?php echo esc_html($label); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        
        <button type="button" id="apply_filters" class="button button-primary">
            <?php _e('Apply Filters', 'xelite-repost-engine'); ?>
        </button>
        
        <button type="button" id="reset_filters" class="button">
            <?php _e('Reset', 'xelite-repost-engine'); ?>
        </button>
    </div>
    
    <!-- Key Metrics Overview -->
    <div class="xelite-metrics-overview">
        <div class="xelite-metric-card">
            <h3><?php _e('Total Reposts', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-metric-value" id="total_reposts">
                <?php echo number_format($analytics_data['total_reposts']); ?>
            </div>
            <div class="xelite-metric-change <?php echo $analytics_data['reposts_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $analytics_data['reposts_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics_data['reposts_change'], 1); ?>%
            </div>
        </div>
        
        <div class="xelite-metric-card">
            <h3><?php _e('Avg. Engagement Rate', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-metric-value" id="avg_engagement">
                <?php echo number_format($analytics_data['avg_engagement_rate'], 2); ?>%
            </div>
            <div class="xelite-metric-change <?php echo $analytics_data['engagement_change'] >= 0 ? 'positive' : 'negative'; ?>">
                <?php echo $analytics_data['engagement_change'] >= 0 ? '+' : ''; ?><?php echo number_format($analytics_data['engagement_change'], 1); ?>%
            </div>
        </div>
        
        <div class="xelite-metric-card">
            <h3><?php _e('Best Performing Time', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-metric-value" id="best_time">
                <?php echo esc_html($analytics_data['best_posting_time']); ?>
            </div>
            <div class="xelite-metric-subtitle">
                <?php _e('Avg. engagement', 'xelite-repost-engine'); ?>
            </div>
        </div>
        
        <div class="xelite-metric-card">
            <h3><?php _e('Top Content Type', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-metric-value" id="top_content_type">
                <?php echo esc_html($analytics_data['top_content_type']); ?>
            </div>
            <div class="xelite-metric-subtitle">
                <?php echo number_format($analytics_data['top_content_engagement'], 2); ?>% <?php _e('engagement', 'xelite-repost-engine'); ?>
            </div>
        </div>
    </div>
    
    <!-- Charts Section -->
    <div class="xelite-charts-section">
        <div class="xelite-chart-container">
            <h3><?php _e('Engagement Trends', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-chart-wrapper">
                <canvas id="engagement_trends_chart"></canvas>
            </div>
        </div>
        
        <div class="xelite-chart-container">
            <h3><?php _e('Content Type Performance', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-chart-wrapper">
                <canvas id="content_type_chart"></canvas>
            </div>
        </div>
        
        <div class="xelite-chart-container">
            <h3><?php _e('Posting Time Analysis', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-chart-wrapper">
                <canvas id="posting_time_chart"></canvas>
            </div>
        </div>
        
        <div class="xelite-chart-container">
            <h3><?php _e('Hashtag Performance', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-chart-wrapper">
                <canvas id="hashtag_chart"></canvas>
            </div>
        </div>
    </div>
    
    <!-- Top Performing Content -->
    <div class="xelite-top-content">
        <h3><?php _e('Top Performing Content', 'xelite-repost-engine'); ?></h3>
        <div class="xelite-content-list" id="top_content_list">
            <?php if (!empty($analytics_data['top_content'])): ?>
                <?php foreach ($analytics_data['top_content'] as $content): ?>
                    <div class="xelite-content-item">
                        <div class="xelite-content-meta">
                            <span class="xelite-content-type"><?php echo esc_html($content['content_type']); ?></span>
                            <span class="xelite-content-date"><?php echo esc_html($content['posted_date']); ?></span>
                        </div>
                        <div class="xelite-content-text">
                            <?php echo esc_html(wp_trim_words($content['content'], 20)); ?>
                        </div>
                        <div class="xelite-content-stats">
                            <span class="xelite-stat">
                                <i class="dashicons dashicons-heart"></i>
                                <?php echo number_format($content['likes']); ?>
                            </span>
                            <span class="xelite-stat">
                                <i class="dashicons dashicons-update"></i>
                                <?php echo number_format($content['retweets']); ?>
                            </span>
                            <span class="xelite-stat">
                                <i class="dashicons dashicons-admin-comments"></i>
                                <?php echo number_format($content['replies']); ?>
                            </span>
                            <span class="xelite-stat engagement-rate">
                                <?php echo number_format($content['engagement_rate'], 2); ?>% <?php _e('engagement', 'xelite-repost-engine'); ?>
                            </span>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="xelite-no-data"><?php _e('No content data available for the selected period.', 'xelite-repost-engine'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Insights Panel -->
    <div class="xelite-insights-panel">
        <h3><?php _e('AI-Powered Insights', 'xelite-repost-engine'); ?></h3>
        <div class="xelite-insights-content" id="insights_content">
            <?php if (!empty($analytics_data['insights'])): ?>
                <?php foreach ($analytics_data['insights'] as $insight): ?>
                    <div class="xelite-insight-item">
                        <div class="xelite-insight-icon">
                            <i class="dashicons <?php echo esc_attr($insight['icon']); ?>"></i>
                        </div>
                        <div class="xelite-insight-text">
                            <h4><?php echo esc_html($insight['title']); ?></h4>
                            <p><?php echo esc_html($insight['description']); ?></p>
                            <?php if (!empty($insight['recommendation'])): ?>
                                <div class="xelite-insight-recommendation">
                                    <strong><?php _e('Recommendation:', 'xelite-repost-engine'); ?></strong>
                                    <?php echo esc_html($insight['recommendation']); ?>
                                </div>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="xelite-no-data"><?php _e('No insights available. Continue posting to generate personalized recommendations.', 'xelite-repost-engine'); ?></p>
            <?php endif; ?>
        </div>
    </div>
    
    <!-- Export Options -->
    <div class="xelite-export-section">
        <h3><?php _e('Export Analytics Data', 'xelite-repost-engine'); ?></h3>
        <div class="xelite-export-options">
            <button type="button" id="export_csv" class="button">
                <i class="dashicons dashicons-media-spreadsheet"></i>
                <?php _e('Export as CSV', 'xelite-repost-engine'); ?>
            </button>
            <button type="button" id="export_json" class="button">
                <i class="dashicons dashicons-media-code"></i>
                <?php _e('Export as JSON', 'xelite-repost-engine'); ?>
            </button>
            <button type="button" id="export_pdf" class="button">
                <i class="dashicons dashicons-media-document"></i>
                <?php _e('Export as PDF', 'xelite-repost-engine'); ?>
            </button>
        </div>
    </div>
</div>

        <script type="text/javascript">
        // Analytics dashboard data for JavaScript
        var xeliteAnalyticsData = <?php echo json_encode($analytics_data); ?>;
        var xeliteAnalyticsNonce = '<?php echo wp_create_nonce('xelite_analytics_nonce'); ?>';
        var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
        </script> 