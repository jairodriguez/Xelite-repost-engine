<?php
/**
 * Few-Shot Learning Admin Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get few-shot collector instance
$container = XeliteRepostEngine_Container::instance();
$few_shot_collector = $container->get('few_shot_collector');

// Get statistics
$stats = $few_shot_collector->get_few_shot_stats();
$categories = $few_shot_collector->get_categories();

// Get current examples
$examples = $few_shot_collector->get_few_shot_examples(array(), 50);

// Enqueue scripts and styles
wp_enqueue_script('jquery');
wp_enqueue_script('wp-util');
?>

<div class="wrap xelite-few-shot-learning">
    <h1><?php _e('Few-Shot Learning Management', 'xelite-repost-engine'); ?></h1>
    
    <!-- Statistics Overview -->
    <div class="xelite-stats-overview">
        <div class="xelite-stat-card">
            <h3><?php _e('Total Examples', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-stat-value"><?php echo esc_html($stats['total_examples']); ?></div>
        </div>
        <div class="xelite-stat-card">
            <h3><?php _e('Active Examples', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-stat-value"><?php echo esc_html($stats['active_examples']); ?></div>
        </div>
        <div class="xelite-stat-card">
            <h3><?php _e('Avg Engagement', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-stat-value"><?php echo esc_html(round($stats['avg_engagement'] * 100, 1)); ?>%</div>
        </div>
        <div class="xelite-stat-card">
            <h3><?php _e('Categories', 'xelite-repost-engine'); ?></h3>
            <div class="xelite-stat-value"><?php echo esc_html(count($categories)); ?></div>
        </div>
    </div>

    <!-- Auto-Identification Section -->
    <div class="xelite-auto-identification">
        <h2><?php _e('Auto-Identify Examples', 'xelite-repost-engine'); ?></h2>
        <p><?php _e('Automatically identify high-performing reposts as few-shot examples based on engagement thresholds.', 'xelite-repost-engine'); ?></p>
        
        <div class="xelite-auto-identify-form">
            <table class="form-table">
                <tr>
                    <th scope="row">
                        <label for="auto_threshold"><?php _e('Engagement Threshold', 'xelite-repost-engine'); ?></label>
                    </th>
                    <td>
                        <input type="range" id="auto_threshold" name="auto_threshold" min="0.1" max="1.0" step="0.05" value="0.75" />
                        <span id="threshold_value">0.75</span>
                        <p class="description"><?php _e('Minimum engagement score for automatic inclusion (0.1 - 1.0)', 'xelite-repost-engine'); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row">
                        <label for="auto_limit"><?php _e('Process Limit', 'xelite-repost-engine'); ?></label>
                    </th>
                    <td>
                        <input type="number" id="auto_limit" name="auto_limit" min="10" max="500" value="50" />
                        <p class="description"><?php _e('Maximum number of reposts to process for identification', 'xelite-repost-engine'); ?></p>
                    </td>
                </tr>
            </table>
            
            <button type="button" id="run_auto_identify" class="button button-primary">
                <?php _e('Run Auto-Identification', 'xelite-repost-engine'); ?>
            </button>
            
            <div id="auto_identify_results" class="xelite-results" style="display: none;"></div>
        </div>
    </div>

    <!-- Examples Management -->
    <div class="xelite-examples-management">
        <h2><?php _e('Manage Examples', 'xelite-repost-engine'); ?></h2>
        
        <!-- Filters -->
        <div class="xelite-examples-filters">
            <select id="category_filter">
                <option value=""><?php _e('All Categories', 'xelite-repost-engine'); ?></option>
                <?php foreach ($categories as $category): ?>
                    <option value="<?php echo esc_attr($category['id']); ?>">
                        <?php echo esc_html($category['name']); ?>
                    </option>
                <?php endforeach; ?>
            </select>
            
            <select id="content_type_filter">
                <option value=""><?php _e('All Content Types', 'xelite-repost-engine'); ?></option>
                <option value="text"><?php _e('Text', 'xelite-repost-engine'); ?></option>
                <option value="image"><?php _e('Image', 'xelite-repost-engine'); ?></option>
                <option value="video"><?php _e('Video', 'xelite-repost-engine'); ?></option>
                <option value="media"><?php _e('Media', 'xelite-repost-engine'); ?></option>
            </select>
            
            <input type="number" id="min_engagement_filter" placeholder="<?php _e('Min Engagement', 'xelite-repost-engine'); ?>" min="0" max="1" step="0.1" />
            
            <button type="button" id="apply_filters" class="button">
                <?php _e('Apply Filters', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" id="clear_filters" class="button">
                <?php _e('Clear Filters', 'xelite-repost-engine'); ?>
            </button>
        </div>

        <!-- Examples List -->
        <div class="xelite-examples-list">
            <?php if (empty($examples)): ?>
                <div class="xelite-no-examples">
                    <p><?php _e('No few-shot examples found. Use the auto-identification feature or manually add examples.', 'xelite-repost-engine'); ?></p>
                </div>
            <?php else: ?>
                <table class="wp-list-table widefat fixed striped">
                    <thead>
                        <tr>
                            <th><?php _e('Content', 'xelite-repost-engine'); ?></th>
                            <th><?php _e('Source', 'xelite-repost-engine'); ?></th>
                            <th><?php _e('Category', 'xelite-repost-engine'); ?></th>
                            <th><?php _e('Engagement', 'xelite-repost-engine'); ?></th>
                            <th><?php _e('Usage', 'xelite-repost-engine'); ?></th>
                            <th><?php _e('Actions', 'xelite-repost-engine'); ?></th>
                        </tr>
                    </thead>
                    <tbody id="examples_table_body">
                        <?php foreach ($examples as $example): ?>
                            <tr data-example-id="<?php echo esc_attr($example['id']); ?>">
                                <td>
                                    <div class="xelite-example-content">
                                        <div class="xelite-example-text">
                                            <?php echo esc_html(wp_trim_words($example['original_text'], 20)); ?>
                                        </div>
                                        <div class="xelite-example-meta">
                                            <span class="xelite-content-type"><?php echo esc_html($example['content_type']); ?></span>
                                            <span class="xelite-content-length"><?php echo esc_html($example['content_length']); ?> chars</span>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <span class="xelite-source-handle">@<?php echo esc_html($example['source_handle']); ?></span>
                                </td>
                                <td>
                                    <select class="xelite-category-select" data-example-id="<?php echo esc_attr($example['id']); ?>">
                                        <option value=""><?php _e('No Category', 'xelite-repost-engine'); ?></option>
                                        <?php foreach ($categories as $category): ?>
                                            <option value="<?php echo esc_attr($category['id']); ?>" 
                                                    <?php selected($example['category_id'], $category['id']); ?>>
                                                <?php echo esc_html($category['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </td>
                                <td>
                                    <div class="xelite-engagement-info">
                                        <div class="xelite-engagement-score">
                                            <?php echo esc_html(round($example['engagement_score'] * 100, 1)); ?>%
                                        </div>
                                        <div class="xelite-engagement-details">
                                            <small>
                                                ❤️ <?php echo esc_html($example['like_count']); ?> |
                                                🔄 <?php echo esc_html($example['retweet_count']); ?> |
                                                💬 <?php echo esc_html($example['reply_count']); ?>
                                            </small>
                                        </div>
                                    </div>
                                </td>
                                <td>
                                    <div class="xelite-usage-info">
                                        <div class="xelite-usage-count">
                                            <?php echo esc_html($example['usage_count']); ?> uses
                                        </div>
                                        <?php if ($example['success_rate'] > 0): ?>
                                            <div class="xelite-success-rate">
                                                <?php echo esc_html(round($example['success_rate'] * 100, 1)); ?>% success
                                            </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                                <td>
                                    <div class="xelite-example-actions">
                                        <button type="button" class="button xelite-view-example" data-example-id="<?php echo esc_attr($example['id']); ?>">
                                            <?php _e('View', 'xelite-repost-engine'); ?>
                                        </button>
                                        <button type="button" class="button xelite-remove-example" data-example-id="<?php echo esc_attr($example['id']); ?>">
                                            <?php _e('Remove', 'xelite-repost-engine'); ?>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            <?php endif; ?>
        </div>
    </div>

    <!-- Example Details Modal -->
    <div id="example_modal" class="xelite-modal" style="display: none;">
        <div class="xelite-modal-content">
            <span class="xelite-modal-close">&times;</span>
            <h2><?php _e('Example Details', 'xelite-repost-engine'); ?></h2>
            <div id="example_modal_content"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var xeliteFewShotNonce = '<?php echo wp_create_nonce('xelite_few_shot_nonce'); ?>';
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    
    // Threshold slider
    $('#auto_threshold').on('input', function() {
        $('#threshold_value').text($(this).val());
    });
    
    // Auto-identification
    $('#run_auto_identify').on('click', function() {
        var button = $(this);
        var threshold = $('#auto_threshold').val();
        var limit = $('#auto_limit').val();
        
        button.prop('disabled', true).text('<?php _e('Processing...', 'xelite-repost-engine'); ?>');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_auto_identify_examples',
                nonce: xeliteFewShotNonce,
                threshold: threshold,
                limit: limit
            },
            success: function(response) {
                if (response.success) {
                    $('#auto_identify_results').html(
                        '<div class="notice notice-success"><p>' + response.data.message + '</p></div>'
                    ).show();
                    
                    // Reload examples list
                    loadExamples();
                } else {
                    $('#auto_identify_results').html(
                        '<div class="notice notice-error"><p>' + response.data + '</p></div>'
                    ).show();
                }
            },
            error: function() {
                $('#auto_identify_results').html(
                    '<div class="notice notice-error"><p><?php _e('An error occurred while processing.', 'xelite-repost-engine'); ?></p></div>'
                ).show();
            },
            complete: function() {
                button.prop('disabled', false).text('<?php _e('Run Auto-Identification', 'xelite-repost-engine'); ?>');
            }
        });
    });
    
    // Category updates
    $('.xelite-category-select').on('change', function() {
        var exampleId = $(this).data('example-id');
        var categoryId = $(this).val();
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_update_example_category',
                nonce: xeliteFewShotNonce,
                example_id: exampleId,
                category_id: categoryId
            },
            success: function(response) {
                if (response.success) {
                    // Show success message
                    showMessage('<?php _e('Category updated successfully', 'xelite-repost-engine'); ?>', 'success');
                } else {
                    showMessage('<?php _e('Failed to update category', 'xelite-repost-engine'); ?>', 'error');
                }
            },
            error: function() {
                showMessage('<?php _e('An error occurred while updating category', 'xelite-repost-engine'); ?>', 'error');
            }
        });
    });
    
    // Remove example
    $('.xelite-remove-example').on('click', function() {
        if (!confirm('<?php _e('Are you sure you want to remove this example?', 'xelite-repost-engine'); ?>')) {
            return;
        }
        
        var exampleId = $(this).data('example-id');
        var row = $(this).closest('tr');
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_remove_few_shot_example',
                nonce: xeliteFewShotNonce,
                example_id: exampleId
            },
            success: function(response) {
                if (response.success) {
                    row.fadeOut(function() {
                        $(this).remove();
                    });
                    showMessage('<?php _e('Example removed successfully', 'xelite-repost-engine'); ?>', 'success');
                } else {
                    showMessage('<?php _e('Failed to remove example', 'xelite-repost-engine'); ?>', 'error');
                }
            },
            error: function() {
                showMessage('<?php _e('An error occurred while removing example', 'xelite-repost-engine'); ?>', 'error');
            }
        });
    });
    
    // View example details
    $('.xelite-view-example').on('click', function() {
        var exampleId = $(this).data('example-id');
        // This would load detailed information about the example
        // For now, just show a placeholder
        $('#example_modal_content').html('<p><?php _e('Detailed example information would be displayed here.', 'xelite-repost-engine'); ?></p>');
        $('#example_modal').show();
    });
    
    // Modal close
    $('.xelite-modal-close').on('click', function() {
        $('#example_modal').hide();
    });
    
    // Close modal when clicking outside
    $(window).on('click', function(event) {
        if ($(event.target).hasClass('xelite-modal')) {
            $('.xelite-modal').hide();
        }
    });
    
    // Apply filters
    $('#apply_filters').on('click', function() {
        loadExamples();
    });
    
    // Clear filters
    $('#clear_filters').on('click', function() {
        $('#category_filter').val('');
        $('#content_type_filter').val('');
        $('#min_engagement_filter').val('');
        loadExamples();
    });
    
    // Load examples with filters
    function loadExamples() {
        var filters = {
            category_id: $('#category_filter').val(),
            content_type: $('#content_type_filter').val(),
            min_engagement: $('#min_engagement_filter').val()
        };
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_get_few_shot_examples',
                nonce: xeliteFewShotNonce,
                filters: filters,
                limit: 50
            },
            success: function(response) {
                if (response.success) {
                    updateExamplesTable(response.data.examples);
                }
            }
        });
    }
    
    // Update examples table
    function updateExamplesTable(examples) {
        var tbody = $('#examples_table_body');
        tbody.empty();
        
        if (examples.length === 0) {
            tbody.html('<tr><td colspan="6"><?php _e('No examples found matching the filters.', 'xelite-repost-engine'); ?></td></tr>');
            return;
        }
        
        examples.forEach(function(example) {
            var row = createExampleRow(example);
            tbody.append(row);
        });
    }
    
    // Create example row
    function createExampleRow(example) {
        return '<tr data-example-id="' + example.id + '">' +
            '<td><div class="xelite-example-content">' +
                '<div class="xelite-example-text">' + example.original_text.substring(0, 100) + '...</div>' +
                '<div class="xelite-example-meta">' +
                    '<span class="xelite-content-type">' + example.content_type + '</span>' +
                    '<span class="xelite-content-length">' + example.content_length + ' chars</span>' +
                '</div></div></td>' +
            '<td><span class="xelite-source-handle">@' + example.source_handle + '</span></td>' +
            '<td><select class="xelite-category-select" data-example-id="' + example.id + '">' +
                '<option value=""><?php _e('No Category', 'xelite-repost-engine'); ?></option>' +
                // Categories would be populated here
            '</select></td>' +
            '<td><div class="xelite-engagement-info">' +
                '<div class="xelite-engagement-score">' + (example.engagement_score * 100).toFixed(1) + '%</div>' +
                '<div class="xelite-engagement-details"><small>' +
                    '❤️ ' + example.like_count + ' | 🔄 ' + example.retweet_count + ' | 💬 ' + example.reply_count +
                '</small></div></div></td>' +
            '<td><div class="xelite-usage-info">' +
                '<div class="xelite-usage-count">' + example.usage_count + ' uses</div>' +
                (example.success_rate > 0 ? '<div class="xelite-success-rate">' + (example.success_rate * 100).toFixed(1) + '% success</div>' : '') +
            '</div></td>' +
            '<td><div class="xelite-example-actions">' +
                '<button type="button" class="button xelite-view-example" data-example-id="' + example.id + '"><?php _e('View', 'xelite-repost-engine'); ?></button>' +
                '<button type="button" class="button xelite-remove-example" data-example-id="' + example.id + '"><?php _e('Remove', 'xelite-repost-engine'); ?></button>' +
            '</div></td></tr>';
    }
    
    // Show message
    function showMessage(message, type) {
        var noticeClass = type === 'success' ? 'notice-success' : 'notice-error';
        var notice = $('<div class="notice ' + noticeClass + '"><p>' + message + '</p></div>');
        
        $('.wrap').prepend(notice);
        
        setTimeout(function() {
            notice.fadeOut(function() {
                $(this).remove();
            });
        }, 3000);
    }
});
</script>

<style>
.xelite-few-shot-learning {
    max-width: 1200px;
}

.xelite-stats-overview {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.xelite-stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    text-align: center;
}

.xelite-stat-card h3 {
    margin: 0 0 10px 0;
    color: #646970;
    font-size: 14px;
    font-weight: 500;
}

.xelite-stat-value {
    font-size: 24px;
    font-weight: 600;
    color: #1d2327;
}

.xelite-auto-identification,
.xelite-examples-management {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0, 0, 0, 0.1);
    margin-bottom: 20px;
}

.xelite-examples-filters {
    display: flex;
    gap: 10px;
    margin-bottom: 20px;
    align-items: center;
    flex-wrap: wrap;
}

.xelite-examples-filters select,
.xelite-examples-filters input {
    min-width: 150px;
}

.xelite-example-content {
    max-width: 300px;
}

.xelite-example-text {
    font-size: 14px;
    line-height: 1.4;
    margin-bottom: 5px;
}

.xelite-example-meta {
    font-size: 12px;
    color: #646970;
}

.xelite-example-meta span {
    margin-right: 10px;
}

.xelite-engagement-info,
.xelite-usage-info {
    text-align: center;
}

.xelite-engagement-score,
.xelite-usage-count {
    font-weight: 600;
    font-size: 14px;
}

.xelite-engagement-details,
.xelite-success-rate {
    font-size: 12px;
    color: #646970;
}

.xelite-example-actions {
    display: flex;
    gap: 5px;
}

.xelite-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0, 0, 0, 0.5);
}

.xelite-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 20px;
    border-radius: 8px;
    width: 80%;
    max-width: 600px;
    position: relative;
}

.xelite-modal-close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #646970;
}

.xelite-modal-close:hover {
    color: #1d2327;
}

.xelite-results {
    margin-top: 20px;
}

.xelite-no-examples {
    text-align: center;
    padding: 40px;
    color: #646970;
}
</style> 