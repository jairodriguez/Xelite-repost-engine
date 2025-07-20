<?php
/**
 * X Data Management Admin Page
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}
?>

<div class="wrap">
    <h1>X Data Management</h1>
    
    <div class="xelite-admin-container">
        <!-- Statistics Overview -->
        <div class="xelite-stats-grid">
            <div class="xelite-stat-card">
                <h3>Total Reposts</h3>
                <div class="stat-number"><?php echo number_format(count($reposts)); ?></div>
            </div>
            <div class="xelite-stat-card">
                <h3>Total Engagement</h3>
                <div class="stat-number"><?php echo number_format($analysis['total_engagement'] ?? 0); ?></div>
            </div>
            <div class="xelite-stat-card">
                <h3>Avg Engagement</h3>
                <div class="stat-number"><?php echo number_format($analysis['avg_engagement'] ?? 0, 1); ?></div>
            </div>
            <div class="xelite-stat-card">
                <h3>Last Updated</h3>
                <div class="stat-number"><?php echo $analysis['last_updated'] ?? 'Never'; ?></div>
            </div>
        </div>

        <!-- Action Buttons -->
        <div class="xelite-action-buttons">
            <button type="button" class="button button-primary" id="fetch-posts-btn">
                <span class="dashicons dashicons-update"></span>
                Fetch New Posts
            </button>
            <button type="button" class="button button-secondary" id="analyze-data-btn">
                <span class="dashicons dashicons-chart-line"></span>
                Analyze Data
            </button>
            <button type="button" class="button button-secondary" id="export-csv-btn">
                <span class="dashicons dashicons-download"></span>
                Export CSV
            </button>
            <button type="button" class="button button-secondary" id="export-json-btn">
                <span class="dashicons dashicons-download"></span>
                Export JSON
            </button>
            <button type="button" class="button button-link-delete" id="clear-data-btn">
                <span class="dashicons dashicons-trash"></span>
                Clear All Data
            </button>
        </div>

        <!-- Analysis Results -->
        <?php if (!empty($analysis)): ?>
        <div class="xelite-analysis-section">
            <h2>Data Analysis</h2>
            
            <div class="xelite-analysis-grid">
                <!-- Top Hashtags -->
                <div class="xelite-analysis-card">
                    <h3>Top Hashtags</h3>
                    <div class="hashtag-list">
                        <?php if (!empty($analysis['top_hashtags'])): ?>
                            <?php foreach (array_slice($analysis['top_hashtags'], 0, 5) as $hashtag => $count): ?>
                                <div class="hashtag-item">
                                    <span class="hashtag">#<?php echo esc_html($hashtag); ?></span>
                                    <span class="count"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No hashtags found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Top Mentions -->
                <div class="xelite-analysis-card">
                    <h3>Top Mentions</h3>
                    <div class="mention-list">
                        <?php if (!empty($analysis['top_mentions'])): ?>
                            <?php foreach (array_slice($analysis['top_mentions'], 0, 5) as $mention => $count): ?>
                                <div class="mention-item">
                                    <span class="mention">@<?php echo esc_html($mention); ?></span>
                                    <span class="count"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No mentions found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Content Types -->
                <div class="xelite-analysis-card">
                    <h3>Content Types</h3>
                    <div class="content-type-list">
                        <?php if (!empty($analysis['content_types'])): ?>
                            <?php foreach ($analysis['content_types'] as $type => $count): ?>
                                <div class="content-type-item">
                                    <span class="type"><?php echo esc_html(ucfirst($type)); ?></span>
                                    <span class="count"><?php echo $count; ?></span>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <p>No content types found</p>
                        <?php endif; ?>
                    </div>
                </div>

                <!-- Sentiment Distribution -->
                <div class="xelite-analysis-card">
                    <h3>Sentiment Distribution</h3>
                    <div class="sentiment-list">
                        <?php if (!empty($analysis['sentiment_distribution'])): ?>
                            <div class="sentiment-item positive">
                                <span class="label">Positive</span>
                                <span class="count"><?php echo $analysis['sentiment_distribution']['positive']; ?></span>
                            </div>
                            <div class="sentiment-item neutral">
                                <span class="label">Neutral</span>
                                <span class="count"><?php echo $analysis['sentiment_distribution']['neutral']; ?></span>
                            </div>
                            <div class="sentiment-item negative">
                                <span class="label">Negative</span>
                                <span class="count"><?php echo $analysis['sentiment_distribution']['negative']; ?></span>
                            </div>
                        <?php else: ?>
                            <p>No sentiment data found</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php endif; ?>

        <!-- Repost Data Table -->
        <div class="xelite-reposts-section">
            <h2>Stored Reposts</h2>
            
            <?php if (!empty($reposts)): ?>
                <div class="tablenav top">
                    <div class="alignleft actions">
                        <select id="bulk-action-selector-top">
                            <option value="-1">Bulk Actions</option>
                            <option value="delete">Delete</option>
                        </select>
                        <input type="submit" class="button action" value="Apply">
                    </div>
                    <div class="alignright">
                        <span class="displaying-num"><?php echo count($reposts); ?> items</span>
                    </div>
                </div>

                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <td class="manage-column column-cb check-column">
                                <input type="checkbox" id="cb-select-all-1">
                            </td>
                            <th class="manage-column column-source">Source</th>
                            <th class="manage-column column-text">Text</th>
                            <th class="manage-column column-engagement">Engagement</th>
                            <th class="manage-column column-analysis">Analysis</th>
                            <th class="manage-column column-date">Date</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($reposts as $repost): ?>
                            <?php 
                            $analysis_data = json_decode($repost['analysis_data'], true);
                            $engagement_metrics = json_decode($repost['engagement_metrics'], true);
                            $total_engagement = ($engagement_metrics['retweet_count'] ?? 0) + 
                                               ($engagement_metrics['like_count'] ?? 0) + 
                                               ($engagement_metrics['reply_count'] ?? 0) + 
                                               ($engagement_metrics['quote_count'] ?? 0);
                            ?>
                            <tr>
                                <th scope="row" class="check-column">
                                    <input type="checkbox" name="repost_ids[]" value="<?php echo $repost['id']; ?>">
                                </th>
                                <td class="column-source">
                                    <strong>@<?php echo esc_html($repost['source_handle']); ?></strong>
                                    <div class="row-actions">
                                        <span class="view">
                                            <a href="https://twitter.com/<?php echo esc_attr($repost['source_handle']); ?>/status/<?php echo esc_attr($repost['original_tweet_id']); ?>" target="_blank">View Tweet</a>
                                        </span>
                                    </div>
                                </td>
                                <td class="column-text">
                                    <div class="tweet-text">
                                        <?php echo esc_html(wp_trim_words($repost['original_text'], 20)); ?>
                                    </div>
                                    <?php if (!empty($analysis_data['hashtags'])): ?>
                                        <div class="tweet-hashtags">
                                            <?php foreach (array_slice($analysis_data['hashtags'], 0, 3) as $hashtag): ?>
                                                <span class="hashtag">#<?php echo esc_html($hashtag); ?></span>
                                            <?php endforeach; ?>
                                        </div>
                                    <?php endif; ?>
                                </td>
                                <td class="column-engagement">
                                    <div class="engagement-metrics">
                                        <span class="metric">
                                            <span class="icon">🔄</span>
                                            <?php echo number_format($engagement_metrics['retweet_count'] ?? 0); ?>
                                        </span>
                                        <span class="metric">
                                            <span class="icon">❤️</span>
                                            <?php echo number_format($engagement_metrics['like_count'] ?? 0); ?>
                                        </span>
                                        <span class="metric">
                                            <span class="icon">💬</span>
                                            <?php echo number_format($engagement_metrics['reply_count'] ?? 0); ?>
                                        </span>
                                        <span class="metric">
                                            <span class="icon">📤</span>
                                            <?php echo number_format($engagement_metrics['quote_count'] ?? 0); ?>
                                        </span>
                                    </div>
                                    <div class="total-engagement">
                                        Total: <?php echo number_format($total_engagement); ?>
                                    </div>
                                </td>
                                <td class="column-analysis">
                                    <div class="analysis-indicators">
                                        <?php if ($analysis_data['has_question'] ?? false): ?>
                                            <span class="indicator question" title="Contains question">❓</span>
                                        <?php endif; ?>
                                        <?php if ($analysis_data['has_call_to_action'] ?? false): ?>
                                            <span class="indicator cta" title="Contains call to action">🎯</span>
                                        <?php endif; ?>
                                        <span class="sentiment-score <?php echo ($analysis_data['sentiment_score'] ?? 0) > 20 ? 'positive' : (($analysis_data['sentiment_score'] ?? 0) < -20 ? 'negative' : 'neutral'); ?>">
                                            <?php echo round($analysis_data['sentiment_score'] ?? 0); ?>
                                        </span>
                                    </div>
                                    <div class="content-type">
                                        <?php echo esc_html(ucfirst($analysis_data['content_type'] ?? 'text')); ?>
                                    </div>
                                </td>
                                <td class="column-date">
                                    <?php echo esc_html(date('M j, Y g:i A', strtotime($repost['created_at']))); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <div class="tablenav bottom">
                    <div class="alignleft actions">
                        <select id="bulk-action-selector-bottom">
                            <option value="-1">Bulk Actions</option>
                            <option value="delete">Delete</option>
                        </select>
                        <input type="submit" class="button action" value="Apply">
                    </div>
                </div>
            <?php else: ?>
                <div class="xelite-empty-state">
                    <div class="empty-icon">📊</div>
                    <h3>No Repost Data Found</h3>
                    <p>No repost data has been collected yet. Use the "Fetch New Posts" button to start collecting data from your target accounts.</p>
                    <button type="button" class="button button-primary" id="fetch-posts-btn">
                        <span class="dashicons dashicons-update"></span>
                        Fetch New Posts
                    </button>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- Confirmation Modal -->
<div id="xelite-confirm-modal" class="xelite-modal" style="display: none;">
    <div class="xelite-modal-content">
        <div class="xelite-modal-header">
            <h3 id="modal-title">Confirm Action</h3>
            <span class="xelite-modal-close">&times;</span>
        </div>
        <div class="xelite-modal-body">
            <p id="modal-message">Are you sure you want to perform this action?</p>
        </div>
        <div class="xelite-modal-footer">
            <button type="button" class="button button-secondary" id="modal-cancel">Cancel</button>
            <button type="button" class="button button-primary" id="modal-confirm">Confirm</button>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    // Fetch new posts
    $('#fetch-posts-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Fetching...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_fetch_posts',
                nonce: '<?php echo wp_create_nonce('xelite_fetch_posts'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Network error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-update"></span> Fetch New Posts');
            }
        });
    });

    // Analyze data
    $('#analyze-data-btn').on('click', function() {
        var $btn = $(this);
        $btn.prop('disabled', true).text('Analyzing...');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_analyze_data',
                nonce: '<?php echo wp_create_nonce('xelite_analyze_data'); ?>'
            },
            success: function(response) {
                if (response.success) {
                    location.reload();
                } else {
                    alert('Error: ' + (response.data || 'Unknown error'));
                }
            },
            error: function() {
                alert('Network error occurred');
            },
            complete: function() {
                $btn.prop('disabled', false).html('<span class="dashicons dashicons-chart-line"></span> Analyze Data');
            }
        });
    });

    // Export CSV
    $('#export-csv-btn').on('click', function() {
        exportData('csv');
    });

    // Export JSON
    $('#export-json-btn').on('click', function() {
        exportData('json');
    });

    // Clear data
    $('#clear-data-btn').on('click', function() {
        showConfirmModal(
            'Clear All Data',
            'Are you sure you want to clear all repost data? This action cannot be undone.',
            function() {
                $.ajax({
                    url: ajaxurl,
                    type: 'POST',
                    data: {
                        action: 'xelite_clear_repost_data',
                        nonce: '<?php echo wp_create_nonce('xelite_clear_data'); ?>'
                    },
                    success: function(response) {
                        if (response.success) {
                            location.reload();
                        } else {
                            alert('Error: ' + (response.data || 'Unknown error'));
                        }
                    },
                    error: function() {
                        alert('Network error occurred');
                    }
                });
            }
        );
    });

    // Export function
    function exportData(format) {
        var form = $('<form>', {
            'method': 'POST',
            'action': ajaxurl,
            'target': '_blank'
        });

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'action',
            'value': 'xelite_export_repost_data'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'nonce',
            'value': '<?php echo wp_create_nonce('xelite_export_data'); ?>'
        }));

        form.append($('<input>', {
            'type': 'hidden',
            'name': 'format',
            'value': format
        }));

        $('body').append(form);
        form.submit();
        form.remove();
    }

    // Modal functions
    function showConfirmModal(title, message, onConfirm) {
        $('#modal-title').text(title);
        $('#modal-message').text(message);
        $('#modal-confirm').off('click').on('click', function() {
            onConfirm();
            hideModal();
        });
        $('#xelite-confirm-modal').show();
    }

    function hideModal() {
        $('#xelite-confirm-modal').hide();
    }

    $('.xelite-modal-close, #modal-cancel').on('click', hideModal);

    $(window).on('click', function(e) {
        if ($(e.target).is('#xelite-confirm-modal')) {
            hideModal();
        }
    });
});
</script> 