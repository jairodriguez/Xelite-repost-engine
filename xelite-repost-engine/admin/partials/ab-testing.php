<?php
/**
 * A/B Testing Admin Template
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

// Get A/B testing service instance
$container = XeliteRepostEngine_Container::instance();
$ab_testing = $container->get('ab_testing');
$prompt_builder = $container->get('prompt_builder');

// Get available templates for test creation
$available_templates = $prompt_builder->get_available_templates();

// Get active tests
$active_tests = $ab_testing->get_active_tests();

// Enqueue scripts and styles
wp_enqueue_script('jquery');
wp_enqueue_script('wp-util');
wp_enqueue_script('chart-js');
?>

<div class="wrap xelite-ab-testing">
    <h1><?php _e('A/B Testing for Few-Shot Prompt Optimization', 'xelite-repost-engine'); ?></h1>
    
    <!-- Overview Statistics -->
    <div class="xelite-ab-overview">
        <div class="xelite-stats-grid">
            <div class="xelite-stat-card">
                <h3><?php echo count($active_tests); ?></h3>
                <p><?php _e('Active Tests', 'xelite-repost-engine'); ?></p>
            </div>
            <div class="xelite-stat-card">
                <h3><?php echo count(array_filter($active_tests, function($test) { return $test['status'] === 'completed'; })); ?></h3>
                <p><?php _e('Completed Tests', 'xelite-repost-engine'); ?></p>
            </div>
            <div class="xelite-stat-card">
                <h3><?php echo count($available_templates); ?></h3>
                <p><?php _e('Available Templates', 'xelite-repost-engine'); ?></p>
            </div>
        </div>
    </div>

    <!-- Create New Test Section -->
    <div class="xelite-create-test">
        <h2><?php _e('Create New A/B Test', 'xelite-repost-engine'); ?></h2>
        
        <form id="create_ab_test_form" class="xelite-test-form">
            <div class="xelite-form-row">
                <div class="xelite-form-group">
                    <label for="test_name"><?php _e('Test Name', 'xelite-repost-engine'); ?></label>
                    <input type="text" id="test_name" name="test_name" required 
                           placeholder="<?php _e('e.g., Few-Shot Example Count Optimization', 'xelite-repost-engine'); ?>">
                </div>
                
                <div class="xelite-form-group">
                    <label for="test_type"><?php _e('Test Type', 'xelite-repost-engine'); ?></label>
                    <select id="test_type" name="test_type" required>
                        <option value=""><?php _e('Select Test Type', 'xelite-repost-engine'); ?></option>
                        <option value="prompt_template"><?php _e('Prompt Template', 'xelite-repost-engine'); ?></option>
                        <option value="example_count"><?php _e('Example Count', 'xelite-repost-engine'); ?></option>
                        <option value="example_selection"><?php _e('Example Selection Strategy', 'xelite-repost-engine'); ?></option>
                        <option value="temperature"><?php _e('Temperature Setting', 'xelite-repost-engine'); ?></option>
                        <option value="max_tokens"><?php _e('Max Tokens', 'xelite-repost-engine'); ?></option>
                    </select>
                </div>
            </div>

            <div class="xelite-form-row">
                <div class="xelite-form-group">
                    <label for="min_sample_size"><?php _e('Minimum Sample Size', 'xelite-repost-engine'); ?></label>
                    <input type="number" id="min_sample_size" name="min_sample_size" value="100" min="10" max="10000">
                    <small><?php _e('Minimum number of content generations needed for statistical significance', 'xelite-repost-engine'); ?></small>
                </div>
                
                <div class="xelite-form-group">
                    <label for="confidence_level"><?php _e('Confidence Level (%)', 'xelite-repost-engine'); ?></label>
                    <input type="number" id="confidence_level" name="confidence_level" value="95" min="80" max="99" step="1">
                    <small><?php _e('Statistical confidence level required to declare a winner', 'xelite-repost-engine'); ?></small>
                </div>
            </div>

            <!-- Variations Section -->
            <div class="xelite-variations-section">
                <h3><?php _e('Test Variations', 'xelite-repost-engine'); ?></h3>
                <div id="variations_container">
                    <!-- Variations will be added here dynamically -->
                </div>
                <button type="button" id="add_variation" class="button button-secondary">
                    <span class="dashicons dashicons-plus"></span>
                    <?php _e('Add Variation', 'xelite-repost-engine'); ?>
                </button>
            </div>

            <div class="xelite-form-actions">
                <button type="submit" class="button button-primary">
                    <?php _e('Create A/B Test', 'xelite-repost-engine'); ?>
                </button>
                <button type="button" id="preview_test" class="button button-secondary">
                    <?php _e('Preview Test', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </form>
    </div>

    <!-- Active Tests Section -->
    <div class="xelite-active-tests">
        <h2><?php _e('Active A/B Tests', 'xelite-repost-engine'); ?></h2>
        
        <?php if (empty($active_tests)): ?>
            <div class="xelite-no-tests">
                <p><?php _e('No active A/B tests found. Create a new test to get started.', 'xelite-repost-engine'); ?></p>
            </div>
        <?php else: ?>
            <div class="xelite-tests-grid">
                <?php foreach ($active_tests as $test): ?>
                    <div class="xelite-test-card" data-test-id="<?php echo esc_attr($test['id']); ?>">
                        <div class="xelite-test-header">
                            <h3><?php echo esc_html($test['test_name']); ?></h3>
                            <span class="xelite-test-status <?php echo esc_attr($test['status']); ?>">
                                <?php echo esc_html(ucfirst($test['status'])); ?>
                            </span>
                        </div>
                        
                        <div class="xelite-test-details">
                            <p><strong><?php _e('Type:', 'xelite-repost-engine'); ?></strong> <?php echo esc_html($test['test_type']); ?></p>
                            <p><strong><?php _e('Started:', 'xelite-repost-engine'); ?></strong> <?php echo esc_html(date('M j, Y', strtotime($test['start_date']))); ?></p>
                            <p><strong><?php _e('Sample Size:', 'xelite-repost-engine'); ?></strong> <?php echo esc_html($test['min_sample_size']); ?></p>
                        </div>
                        
                        <div class="xelite-test-actions">
                            <button type="button" class="button button-secondary view-results" data-test-id="<?php echo esc_attr($test['id']); ?>">
                                <?php _e('View Results', 'xelite-repost-engine'); ?>
                            </button>
                            <button type="button" class="button button-secondary stop-test" data-test-id="<?php echo esc_attr($test['id']); ?>">
                                <?php _e('Stop Test', 'xelite-repost-engine'); ?>
                            </button>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
    </div>

    <!-- Test Results Modal -->
    <div id="test_results_modal" class="xelite-modal" style="display: none;">
        <div class="xelite-modal-content xelite-large-modal">
            <span class="xelite-modal-close">&times;</span>
            <div id="test_results_content"></div>
        </div>
    </div>

    <!-- Test Preview Modal -->
    <div id="test_preview_modal" class="xelite-modal" style="display: none;">
        <div class="xelite-modal-content">
            <span class="xelite-modal-close">&times;</span>
            <h2><?php _e('Test Preview', 'xelite-repost-engine'); ?></h2>
            <div id="test_preview_content"></div>
        </div>
    </div>
</div>

<script type="text/javascript">
jQuery(document).ready(function($) {
    var xeliteABTestingNonce = '<?php echo wp_create_nonce('xelite_ab_testing_nonce'); ?>';
    var ajaxurl = '<?php echo admin_url('admin-ajax.php'); ?>';
    var availableTemplates = <?php echo json_encode($available_templates); ?>;
    var variationCounter = 0;

    // Initialize the form
    initABTestingForm();

    // Add variation button
    $('#add_variation').on('click', function() {
        addVariation();
    });

    // Form submission
    $('#create_ab_test_form').on('submit', function(e) {
        e.preventDefault();
        createABTest();
    });

    // Preview test button
    $('#preview_test').on('click', function() {
        previewTest();
    });

    // View results buttons
    $('.view-results').on('click', function() {
        var testId = $(this).data('test-id');
        viewTestResults(testId);
    });

    // Stop test buttons
    $('.stop-test').on('click', function() {
        var testId = $(this).data('test-id');
        if (confirm('<?php _e('Are you sure you want to stop this test?', 'xelite-repost-engine'); ?>')) {
            stopABTest(testId);
        }
    });

    // Modal close buttons
    $('.xelite-modal-close').on('click', function() {
        $(this).closest('.xelite-modal').hide();
    });

    // Close modal when clicking outside
    $('.xelite-modal').on('click', function(e) {
        if (e.target === this) {
            $(this).hide();
        }
    });

    function initABTestingForm() {
        // Add initial variations
        addVariation();
        addVariation();
    }

    function addVariation() {
        variationCounter++;
        var variationHtml = `
            <div class="xelite-variation" data-variation="${variationCounter}">
                <div class="xelite-variation-header">
                    <h4>Variation ${String.fromCharCode(64 + variationCounter)}</h4>
                    <button type="button" class="button button-small remove-variation" data-variation="${variationCounter}">
                        <span class="dashicons dashicons-trash"></span>
                    </button>
                </div>
                
                <div class="xelite-form-row">
                    <div class="xelite-form-group">
                        <label>Variation Name</label>
                        <input type="text" name="variations[${variationCounter}][name]" 
                               value="Variation ${String.fromCharCode(64 + variationCounter)}" required>
                    </div>
                    
                    <div class="xelite-form-group">
                        <label>
                            <input type="radio" name="control_variation" value="${variationCounter}" 
                                   ${variationCounter === 1 ? 'checked' : ''}>
                            Control Variation
                        </label>
                    </div>
                </div>
                
                <div class="xelite-variation-config">
                    <h5>Configuration</h5>
                    <div class="xelite-form-row">
                        <div class="xelite-form-group">
                            <label>Template Type</label>
                            <select name="variations[${variationCounter}][config][template_type]" class="template-type-select">
                                <option value="">Select Template</option>
                                ${generateTemplateOptions()}
                            </select>
                        </div>
                        
                        <div class="xelite-form-group">
                            <label>Max Examples</label>
                            <input type="number" name="variations[${variationCounter}][config][max_examples]" 
                                   value="3" min="1" max="10">
                        </div>
                    </div>
                    
                    <div class="xelite-form-row">
                        <div class="xelite-form-group">
                            <label>Temperature</label>
                            <input type="number" name="variations[${variationCounter}][config][temperature]" 
                                   value="0.7" min="0" max="2" step="0.1">
                        </div>
                        
                        <div class="xelite-form-group">
                            <label>Max Tokens</label>
                            <input type="number" name="variations[${variationCounter}][config][max_tokens]" 
                                   value="3000" min="100" max="8000">
                        </div>
                    </div>
                    
                    <div class="xelite-form-group">
                        <label>Example Selection Strategy</label>
                        <select name="variations[${variationCounter}][config][selection_strategy]">
                            <option value="engagement_score">Engagement Score</option>
                            <option value="similar_content">Similar Content</option>
                            <option value="category_match">Category Match</option>
                            <option value="random">Random</option>
                        </select>
                    </div>
                </div>
            </div>
        `;
        
        $('#variations_container').append(variationHtml);
        
        // Remove variation button
        $('.remove-variation').off('click').on('click', function() {
            var variation = $(this).data('variation');
            if ($('.xelite-variation').length > 2) {
                $(`.xelite-variation[data-variation="${variation}"]`).remove();
                updateVariationLabels();
            } else {
                alert('<?php _e('At least 2 variations are required for A/B testing.', 'xelite-repost-engine'); ?>');
            }
        });
    }

    function generateTemplateOptions() {
        var options = '';
        for (var type in availableTemplates) {
            var template = availableTemplates[type];
            options += `<option value="${type}">${template.description} (${template.version})</option>`;
        }
        return options;
    }

    function updateVariationLabels() {
        $('.xelite-variation').each(function(index) {
            var letter = String.fromCharCode(65 + index);
            $(this).find('h4').text('Variation ' + letter);
            $(this).find('input[name*="[name]"]').val('Variation ' + letter);
        });
    }

    function createABTest() {
        var formData = new FormData($('#create_ab_test_form')[0]);
        formData.append('action', 'xelite_create_ab_test');
        formData.append('nonce', xeliteABTestingNonce);
        
        // Convert form data to JSON
        var testConfig = {
            test_name: formData.get('test_name'),
            test_type: formData.get('test_type'),
            min_sample_size: parseInt(formData.get('min_sample_size')),
            confidence_level: parseFloat(formData.get('confidence_level')),
            variations: []
        };
        
        // Collect variations
        $('.xelite-variation').each(function() {
            var variation = $(this);
            var variationData = {
                name: variation.find('input[name*="[name]"]').val(),
                is_control: variation.find('input[name="control_variation"]:checked').length > 0,
                config: {
                    template_type: variation.find('select[name*="[template_type]"]').val(),
                    max_examples: parseInt(variation.find('input[name*="[max_examples]"]').val()),
                    temperature: parseFloat(variation.find('input[name*="[temperature]"]').val()),
                    max_tokens: parseInt(variation.find('input[name*="[max_tokens]"]').val()),
                    selection_strategy: variation.find('select[name*="[selection_strategy]"]').val()
                }
            };
            testConfig.variations.push(variationData);
        });
        
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_create_ab_test',
                nonce: xeliteABTestingNonce,
                test_config: JSON.stringify(testConfig)
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('A/B test created successfully!', 'xelite-repost-engine'); ?>');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('<?php _e('Failed to create A/B test. Please try again.', 'xelite-repost-engine'); ?>');
            }
        });
    }

    function previewTest() {
        var formData = new FormData($('#create_ab_test_form')[0]);
        var previewHtml = '<h3><?php _e('Test Configuration Preview', 'xelite-repost-engine'); ?></h3>';
        previewHtml += '<div class="xelite-preview-content">';
        previewHtml += '<p><strong><?php _e('Test Name:', 'xelite-repost-engine'); ?></strong> ' + formData.get('test_name') + '</p>';
        previewHtml += '<p><strong><?php _e('Test Type:', 'xelite-repost-engine'); ?></strong> ' + formData.get('test_type') + '</p>';
        previewHtml += '<p><strong><?php _e('Sample Size:', 'xelite-repost-engine'); ?></strong> ' + formData.get('min_sample_size') + '</p>';
        previewHtml += '<p><strong><?php _e('Confidence Level:', 'xelite-repost-engine'); ?></strong> ' + formData.get('confidence_level') + '%</p>';
        
        previewHtml += '<h4><?php _e('Variations:', 'xelite-repost-engine'); ?></h4>';
        $('.xelite-variation').each(function(index) {
            var variation = $(this);
            var letter = String.fromCharCode(65 + index);
            previewHtml += '<div class="xelite-preview-variation">';
            previewHtml += '<h5>Variation ' + letter + '</h5>';
            previewHtml += '<ul>';
            previewHtml += '<li><strong><?php _e('Name:', 'xelite-repost-engine'); ?></strong> ' + variation.find('input[name*="[name]"]').val() + '</li>';
            previewHtml += '<li><strong><?php _e('Template:', 'xelite-repost-engine'); ?></strong> ' + variation.find('select[name*="[template_type]"] option:selected').text() + '</li>';
            previewHtml += '<li><strong><?php _e('Max Examples:', 'xelite-repost-engine'); ?></strong> ' + variation.find('input[name*="[max_examples]"]').val() + '</li>';
            previewHtml += '<li><strong><?php _e('Temperature:', 'xelite-repost-engine'); ?></strong> ' + variation.find('input[name*="[temperature]"]').val() + '</li>';
            previewHtml += '<li><strong><?php _e('Strategy:', 'xelite-repost-engine'); ?></strong> ' + variation.find('select[name*="[selection_strategy]"] option:selected').text() + '</li>';
            if (variation.find('input[name="control_variation"]:checked').length > 0) {
                previewHtml += '<li><strong><?php _e('Control:', 'xelite-repost-engine'); ?></strong> Yes</li>';
            }
            previewHtml += '</ul>';
            previewHtml += '</div>';
        });
        
        previewHtml += '</div>';
        
        $('#test_preview_content').html(previewHtml);
        $('#test_preview_modal').show();
    }

    function viewTestResults(testId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_get_ab_test_analytics',
                nonce: xeliteABTestingNonce,
                test_id: testId
            },
            success: function(response) {
                if (response.success) {
                    displayTestResults(response.data);
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('<?php _e('Failed to load test results. Please try again.', 'xelite-repost-engine'); ?>');
            }
        });
    }

    function displayTestResults(data) {
        var html = '<h2>' + data.test.test_name + '</h2>';
        html += '<div class="xelite-test-summary">';
        html += '<p><strong><?php _e('Status:', 'xelite-repost-engine'); ?></strong> ' + data.test.status + '</p>';
        html += '<p><strong><?php _e('Type:', 'xelite-repost-engine'); ?></strong> ' + data.test.test_type + '</p>';
        html += '<p><strong><?php _e('Total Results:', 'xelite-repost-engine'); ?></strong> ' + data.summary.total_results + '</p>';
        html += '<p><strong><?php _e('Average Engagement:', 'xelite-repost-engine'); ?></strong> ' + (data.summary.avg_engagement || 0).toFixed(2) + '</p>';
        html += '<p><strong><?php _e('Overall Conversion Rate:', 'xelite-repost-engine'); ?></strong> ' + ((data.summary.overall_conversion_rate || 0) * 100).toFixed(1) + '%</p>';
        html += '</div>';
        
        html += '<div class="xelite-variations-results">';
        html += '<h3><?php _e('Variation Results', 'xelite-repost-engine'); ?></h3>';
        
        data.variations.forEach(function(variation) {
            html += '<div class="xelite-variation-result">';
            html += '<h4>' + variation.variation_name + (variation.is_control ? ' (Control)' : '') + '</h4>';
            html += '<div class="xelite-variation-metrics">';
            html += '<p><strong><?php _e('Sample Size:', 'xelite-repost-engine'); ?></strong> ' + variation.sample_size + '</p>';
            html += '<p><strong><?php _e('Engagement Score:', 'xelite-repost-engine'); ?></strong> ' + (variation.engagement_score || 0).toFixed(2) + '</p>';
            html += '<p><strong><?php _e('Conversion Rate:', 'xelite-repost-engine'); ?></strong> ' + ((variation.conversion_rate || 0) * 100).toFixed(1) + '%</p>';
            html += '<p><strong><?php _e('Results Count:', 'xelite-repost-engine'); ?></strong> ' + variation.results_count + '</p>';
            if (variation.is_winner) {
                html += '<p class="xelite-winner-badge"><?php _e('WINNER', 'xelite-repost-engine'); ?></p>';
            }
            html += '</div>';
            html += '</div>';
        });
        
        html += '</div>';
        
        $('#test_results_content').html(html);
        $('#test_results_modal').show();
    }

    function stopABTest(testId) {
        $.ajax({
            url: ajaxurl,
            type: 'POST',
            data: {
                action: 'xelite_stop_ab_test',
                nonce: xeliteABTestingNonce,
                test_id: testId
            },
            success: function(response) {
                if (response.success) {
                    alert('<?php _e('Test stopped successfully!', 'xelite-repost-engine'); ?>');
                    location.reload();
                } else {
                    alert('Error: ' + response.data);
                }
            },
            error: function() {
                alert('<?php _e('Failed to stop test. Please try again.', 'xelite-repost-engine'); ?>');
            }
        });
    }
});
</script>

<style>
.xelite-ab-testing {
    max-width: 1200px;
}

.xelite-stats-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
    gap: 20px;
    margin-bottom: 30px;
}

.xelite-stat-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    text-align: center;
}

.xelite-stat-card h3 {
    font-size: 2em;
    margin: 0 0 10px 0;
    color: #0073aa;
}

.xelite-create-test {
    background: #fff;
    padding: 30px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    margin-bottom: 30px;
}

.xelite-form-row {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 20px;
    margin-bottom: 20px;
}

.xelite-form-group {
    display: flex;
    flex-direction: column;
}

.xelite-form-group label {
    font-weight: 600;
    margin-bottom: 5px;
}

.xelite-form-group input,
.xelite-form-group select {
    padding: 8px 12px;
    border: 1px solid #ddd;
    border-radius: 4px;
}

.xelite-form-group small {
    color: #666;
    font-size: 0.9em;
    margin-top: 5px;
}

.xelite-variations-section {
    margin: 30px 0;
    padding: 20px;
    background: #f9f9f9;
    border-radius: 8px;
}

.xelite-variation {
    background: #fff;
    padding: 20px;
    margin-bottom: 20px;
    border-radius: 8px;
    border: 1px solid #ddd;
}

.xelite-variation-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.xelite-variation-header h4 {
    margin: 0;
}

.xelite-variation-config h5 {
    margin: 15px 0 10px 0;
    color: #0073aa;
}

.xelite-form-actions {
    display: flex;
    gap: 10px;
    margin-top: 20px;
}

.xelite-tests-grid {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(300px, 1fr));
    gap: 20px;
}

.xelite-test-card {
    background: #fff;
    padding: 20px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    border-left: 4px solid #0073aa;
}

.xelite-test-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 15px;
}

.xelite-test-header h3 {
    margin: 0;
}

.xelite-test-status {
    padding: 4px 8px;
    border-radius: 4px;
    font-size: 0.8em;
    font-weight: 600;
}

.xelite-test-status.active {
    background: #d4edda;
    color: #155724;
}

.xelite-test-status.completed {
    background: #d1ecf1;
    color: #0c5460;
}

.xelite-test-status.paused {
    background: #fff3cd;
    color: #856404;
}

.xelite-test-details p {
    margin: 5px 0;
    font-size: 0.9em;
}

.xelite-test-actions {
    display: flex;
    gap: 10px;
    margin-top: 15px;
}

.xelite-modal {
    position: fixed;
    z-index: 1000;
    left: 0;
    top: 0;
    width: 100%;
    height: 100%;
    background-color: rgba(0,0,0,0.5);
}

.xelite-modal-content {
    background-color: #fff;
    margin: 5% auto;
    padding: 30px;
    border-radius: 8px;
    width: 80%;
    max-width: 800px;
    max-height: 80vh;
    overflow-y: auto;
    position: relative;
}

.xelite-large-modal {
    max-width: 1000px;
}

.xelite-modal-close {
    position: absolute;
    right: 20px;
    top: 20px;
    font-size: 24px;
    cursor: pointer;
    color: #666;
}

.xelite-modal-close:hover {
    color: #000;
}

.xelite-preview-variation {
    background: #f9f9f9;
    padding: 15px;
    margin: 10px 0;
    border-radius: 4px;
}

.xelite-preview-variation h5 {
    margin: 0 0 10px 0;
    color: #0073aa;
}

.xelite-preview-variation ul {
    margin: 0;
    padding-left: 20px;
}

.xelite-variation-result {
    background: #f9f9f9;
    padding: 20px;
    margin: 15px 0;
    border-radius: 8px;
}

.xelite-variation-result h4 {
    margin: 0 0 15px 0;
    color: #0073aa;
}

.xelite-variation-metrics {
    display: grid;
    grid-template-columns: repeat(auto-fit, minmax(150px, 1fr));
    gap: 10px;
}

.xelite-winner-badge {
    background: #28a745;
    color: white;
    padding: 5px 10px;
    border-radius: 4px;
    font-weight: bold;
    text-align: center;
    margin: 10px 0 0 0;
}

.xelite-no-tests {
    text-align: center;
    padding: 40px;
    background: #f9f9f9;
    border-radius: 8px;
    color: #666;
}

@media (max-width: 768px) {
    .xelite-form-row {
        grid-template-columns: 1fr;
    }
    
    .xelite-tests-grid {
        grid-template-columns: 1fr;
    }
    
    .xelite-modal-content {
        width: 95%;
        margin: 10% auto;
    }
}
</style> 