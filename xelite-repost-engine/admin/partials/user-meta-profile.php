<?php
/**
 * User Meta Profile Interface
 *
 * Enhanced admin interface for managing user context data
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Render the user meta profile interface
 *
 * @param WP_User $user User object
 */
function xelite_repost_engine_render_user_meta_profile($user) {
    if (!current_user_can('edit_user', $user->ID)) {
        return;
    }
    
    // Get user meta service
    $user_meta = xelite_repost_engine()->container->get('user_meta');
    $context = $user_meta->get_user_context($user->ID);
    $completeness = $user_meta->is_context_complete($user->ID);
    $meta_fields = $user_meta->get_meta_fields();
    
    // Enqueue necessary scripts and styles
    wp_enqueue_script('jquery');
    wp_enqueue_script('xelite-repost-engine-admin');
    wp_enqueue_style('xelite-repost-engine-admin');
    
    ?>
    <div class="xelite-user-meta-profile">
        <h2>
            <span class="dashicons dashicons-admin-users"></span>
            <?php _e('Repost Engine Personalization', 'xelite-repost-engine'); ?>
        </h2>
        
        <!-- Progress Indicator -->
        <div class="xelite-profile-progress">
            <div class="progress-header">
                <h3><?php _e('Profile Completeness', 'xelite-repost-engine'); ?></h3>
                <span class="progress-percentage"><?php echo esc_html($completeness['completeness_percentage']); ?>%</span>
            </div>
            <div class="progress-bar">
                <div class="progress-fill" style="width: <?php echo esc_attr($completeness['completeness_percentage']); ?>%"></div>
            </div>
            <?php if (!$completeness['complete']): ?>
                <div class="progress-message">
                    <p class="notice notice-warning">
                        <strong><?php _e('Profile Incomplete:', 'xelite-repost-engine'); ?></strong>
                        <?php printf(
                            __('Complete your profile to get better AI-generated content. %d of %d required fields completed.', 'xelite-repost-engine'),
                            count($meta_fields) - count($completeness['missing_fields']),
                            count($meta_fields)
                        ); ?>
                    </p>
                </div>
            <?php else: ?>
                <div class="progress-message">
                    <p class="notice notice-success">
                        <strong><?php _e('Profile Complete!', 'xelite-repost-engine'); ?></strong>
                        <?php _e('Your profile is fully configured for optimal AI content generation.', 'xelite-repost-engine'); ?>
                    </p>
                </div>
            <?php endif; ?>
        </div>
        
        <!-- Tabbed Interface -->
        <div class="xelite-tabs">
            <nav class="xelite-tab-nav">
                <button class="xelite-tab-button active" data-tab="basic">
                    <span class="dashicons dashicons-admin-users"></span>
                    <?php _e('Basic Info', 'xelite-repost-engine'); ?>
                </button>
                <button class="xelite-tab-button" data-tab="audience">
                    <span class="dashicons dashicons-groups"></span>
                    <?php _e('Audience & Pain Points', 'xelite-repost-engine'); ?>
                </button>
                <button class="xelite-tab-button" data-tab="content">
                    <span class="dashicons dashicons-edit"></span>
                    <?php _e('Content & Style', 'xelite-repost-engine'); ?>
                </button>
                <button class="xelite-tab-button" data-tab="advanced">
                    <span class="dashicons dashicons-admin-tools"></span>
                    <?php _e('Advanced', 'xelite-repost-engine'); ?>
                </button>
            </nav>
            
            <div class="xelite-tab-content">
                <!-- Basic Info Tab -->
                <div class="xelite-tab-pane active" id="basic">
                    <h3><?php _e('Basic Information', 'xelite-repost-engine'); ?></h3>
                    <p class="description">
                        <?php _e('Tell us about yourself and your expertise.', 'xelite-repost-engine'); ?>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="topic">
                                    <?php _e('Your Topic/Niche', 'xelite-repost-engine'); ?>
                                    <span class="field-optional"><?php _e('(Optional)', 'xelite-repost-engine'); ?></span>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="topic" 
                                    id="topic" 
                                    value="<?php echo esc_attr($context['topic'] ?? ''); ?>" 
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e('e.g., Digital Marketing, Fitness, Business Strategy', 'xelite-repost-engine'); ?>"
                                />
                                <p class="description">
                                    <?php _e('What is your main topic or area of expertise? This helps AI understand your niche.', 'xelite-repost-engine'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="personal-context">
                                    <?php _e('Personal Context', 'xelite-repost-engine'); ?>
                                    <span class="field-optional"><?php _e('(Optional)', 'xelite-repost-engine'); ?></span>
                                </label>
                            </th>
                            <td>
                                <textarea 
                                    name="personal-context" 
                                    id="personal-context" 
                                    rows="4" 
                                    cols="50"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e('Tell us about your background, experience, and what makes you unique...', 'xelite-repost-engine'); ?>"
                                ><?php echo esc_textarea($context['personal-context'] ?? ''); ?></textarea>
                                <p class="description">
                                    <?php _e('Share your background, expertise, and what makes you unique. This helps AI personalize content to your voice.', 'xelite-repost-engine'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Audience & Pain Points Tab -->
                <div class="xelite-tab-pane" id="audience">
                    <h3><?php _e('Audience & Pain Points', 'xelite-repost-engine'); ?></h3>
                    <p class="description">
                        <?php _e('Define your ideal audience and their challenges.', 'xelite-repost-engine'); ?>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="dream-client">
                                    <?php _e('Dream Client', 'xelite-repost-engine'); ?>
                                    <span class="field-required">*</span>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="dream-client" 
                                    id="dream-client" 
                                    value="<?php echo esc_attr($context['dream-client'] ?? ''); ?>" 
                                    class="regular-text <?php echo empty($context['dream-client']) ? 'field-incomplete' : ''; ?>"
                                    placeholder="<?php esc_attr_e('e.g., Small business owners struggling with marketing', 'xelite-repost-engine'); ?>"
                                    required
                                />
                                <p class="description">
                                    <?php _e('Who is your ideal client or audience? Be specific about demographics, industry, or situation.', 'xelite-repost-engine'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="dream-client-pain-points">
                                    <?php _e('Client Pain Points', 'xelite-repost-engine'); ?>
                                    <span class="field-required">*</span>
                                </label>
                            </th>
                            <td>
                                <textarea 
                                    name="dream-client-pain-points" 
                                    id="dream-client-pain-points" 
                                    rows="4" 
                                    cols="50"
                                    class="regular-text <?php echo empty($context['dream-client-pain-points']) ? 'field-incomplete' : ''; ?>"
                                    placeholder="<?php esc_attr_e('What problems does your dream client face? What keeps them up at night?', 'xelite-repost-engine'); ?>"
                                    required
                                ><?php echo esc_textarea($context['dream-client-pain-points'] ?? ''); ?></textarea>
                                <p class="description">
                                    <?php _e('What problems does your dream client face? Understanding their pain points helps create more relevant content.', 'xelite-repost-engine'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Content & Style Tab -->
                <div class="xelite-tab-pane" id="content">
                    <h3><?php _e('Content & Style', 'xelite-repost-engine'); ?></h3>
                    <p class="description">
                        <?php _e('Define your content style and offers.', 'xelite-repost-engine'); ?>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="writing-style">
                                    <?php _e('Writing Style', 'xelite-repost-engine'); ?>
                                    <span class="field-required">*</span>
                                </label>
                            </th>
                            <td>
                                <input 
                                    type="text" 
                                    name="writing-style" 
                                    id="writing-style" 
                                    value="<?php echo esc_attr($context['writing-style'] ?? ''); ?>" 
                                    class="regular-text <?php echo empty($context['writing-style']) ? 'field-incomplete' : ''; ?>"
                                    placeholder="<?php esc_attr_e('e.g., Conversational, professional, humorous, direct', 'xelite-repost-engine'); ?>"
                                    required
                                />
                                <p class="description">
                                    <?php _e('How do you typically communicate? Describe your tone and style (e.g., casual, professional, humorous, direct).', 'xelite-repost-engine'); ?>
                                </p>
                            </td>
                        </tr>
                        
                        <tr>
                            <th scope="row">
                                <label for="irresistible-offer">
                                    <?php _e('Irresistible Offer', 'xelite-repost-engine'); ?>
                                    <span class="field-optional"><?php _e('(Optional)', 'xelite-repost-engine'); ?></span>
                                </label>
                            </th>
                            <td>
                                <textarea 
                                    name="irresistible-offer" 
                                    id="irresistible-offer" 
                                    rows="4" 
                                    cols="50"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e('What is your main value proposition or offer? What do you help people achieve?', 'xelite-repost-engine'); ?>"
                                ><?php echo esc_textarea($context['irresistible-offer'] ?? ''); ?></textarea>
                                <p class="description">
                                    <?php _e('What is your main value proposition or offer? This helps AI understand what you\'re promoting.', 'xelite-repost-engine'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
                
                <!-- Advanced Tab -->
                <div class="xelite-tab-pane" id="advanced">
                    <h3><?php _e('Advanced Settings', 'xelite-repost-engine'); ?></h3>
                    <p class="description">
                        <?php _e('Additional context for advanced personalization.', 'xelite-repost-engine'); ?>
                    </p>
                    
                    <table class="form-table">
                        <tr>
                            <th scope="row">
                                <label for="ikigai">
                                    <?php _e('Your Ikigai (Purpose)', 'xelite-repost-engine'); ?>
                                    <span class="field-optional"><?php _e('(Optional)', 'xelite-repost-engine'); ?></span>
                                </label>
                            </th>
                            <td>
                                <textarea 
                                    name="ikigai" 
                                    id="ikigai" 
                                    rows="4" 
                                    cols="50"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e('What you love, what you\'re good at, what the world needs, what you can be paid for...', 'xelite-repost-engine'); ?>"
                                ><?php echo esc_textarea($context['ikigai'] ?? ''); ?></textarea>
                                <p class="description">
                                    <?php _e('Your purpose and passion. What drives you? This helps AI understand your deeper motivation and create more authentic content.', 'xelite-repost-engine'); ?>
                                </p>
                            </td>
                        </tr>
                    </table>
                </div>
            </div>
        </div>
        
        <!-- Action Buttons -->
        <div class="xelite-profile-actions">
            <button type="button" class="button button-primary" id="save-user-meta">
                <span class="dashicons dashicons-saved"></span>
                <?php _e('Save Changes', 'xelite-repost-engine'); ?>
            </button>
            
            <button type="button" class="button button-secondary" id="preview-context">
                <span class="dashicons dashicons-visibility"></span>
                <?php _e('Preview Context', 'xelite-repost-engine'); ?>
            </button>
            
            <span class="spinner" id="save-spinner" style="float: none; margin-left: 10px;"></span>
        </div>
        
        <!-- Context Preview Modal -->
        <div id="context-preview-modal" class="xelite-modal" style="display: none;">
            <div class="xelite-modal-content">
                <div class="xelite-modal-header">
                    <h3><?php _e('Your Context Summary', 'xelite-repost-engine'); ?></h3>
                    <button type="button" class="xelite-modal-close">&times;</button>
                </div>
                <div class="xelite-modal-body">
                    <div id="context-preview-content">
                        <!-- Content will be populated by JavaScript -->
                    </div>
                </div>
            </div>
        </div>
    </div>
    
    <script type="text/javascript">
    jQuery(document).ready(function($) {
        // Tab functionality
        $('.xelite-tab-button').on('click', function() {
            var tab = $(this).data('tab');
            
            // Update active tab button
            $('.xelite-tab-button').removeClass('active');
            $(this).addClass('active');
            
            // Update active tab content
            $('.xelite-tab-pane').removeClass('active');
            $('#' + tab).addClass('active');
        });
        
        // Save functionality
        $('#save-user-meta').on('click', function() {
            var $button = $(this);
            var $spinner = $('#save-spinner');
            
            // Disable button and show spinner
            $button.prop('disabled', true);
            $spinner.show();
            
            // Collect form data
            var formData = {
                action: 'xelite_repost_engine_save_user_meta',
                user_id: <?php echo $user->ID; ?>,
                nonce: '<?php echo wp_create_nonce('xelite_repost_engine_user_meta'); ?>',
                topic: $('#topic').val(),
                'personal-context': $('#personal-context').val(),
                'dream-client': $('#dream-client').val(),
                'dream-client-pain-points': $('#dream-client-pain-points').val(),
                'writing-style': $('#writing-style').val(),
                'irresistible-offer': $('#irresistible-offer').val(),
                'ikigai': $('#ikigai').val()
            };
            
            // Send AJAX request
            $.post(ajaxurl, formData, function(response) {
                if (response.success) {
                    // Show success message
                    $('<div class="notice notice-success is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertAfter('.xelite-user-meta-profile h2')
                        .delay(3000)
                        .fadeOut();
                    
                    // Update progress
                    if (response.data.completeness) {
                        $('.progress-percentage').text(response.data.completeness.completeness_percentage + '%');
                        $('.progress-fill').css('width', response.data.completeness.completeness_percentage + '%');
                        
                        // Update field styling
                        $('.field-incomplete').removeClass('field-incomplete');
                        if (!response.data.completeness.complete) {
                            response.data.completeness.missing_fields.forEach(function(field) {
                                $('#' + field.key).addClass('field-incomplete');
                            });
                        }
                    }
                } else {
                    // Show error message
                    $('<div class="notice notice-error is-dismissible"><p>' + response.data.message + '</p></div>')
                        .insertAfter('.xelite-user-meta-profile h2')
                        .delay(5000)
                        .fadeOut();
                }
            }).fail(function() {
                // Show generic error
                $('<div class="notice notice-error is-dismissible"><p><?php _e('An error occurred while saving. Please try again.', 'xelite-repost-engine'); ?></p></div>')
                    .insertAfter('.xelite-user-meta-profile h2')
                    .delay(5000)
                    .fadeOut();
            }).always(function() {
                // Re-enable button and hide spinner
                $button.prop('disabled', false);
                $spinner.hide();
            });
        });
        
        // Preview functionality
        $('#preview-context').on('click', function() {
            var formData = {
                action: 'xelite_repost_engine_preview_context',
                user_id: <?php echo $user->ID; ?>,
                nonce: '<?php echo wp_create_nonce('xelite_repost_engine_user_meta'); ?>',
                topic: $('#topic').val(),
                'personal-context': $('#personal-context').val(),
                'dream-client': $('#dream-client').val(),
                'dream-client-pain-points': $('#dream-client-pain-points').val(),
                'writing-style': $('#writing-style').val(),
                'irresistible-offer': $('#irresistible-offer').val(),
                'ikigai': $('#ikigai').val()
            };
            
            $.post(ajaxurl, formData, function(response) {
                if (response.success) {
                    $('#context-preview-content').html(response.data.preview);
                    $('#context-preview-modal').show();
                }
            });
        });
        
        // Modal close functionality
        $('.xelite-modal-close, .xelite-modal').on('click', function(e) {
            if (e.target === this) {
                $('#context-preview-modal').hide();
            }
        });
        
        // Auto-save functionality (save after 3 seconds of inactivity)
        var autoSaveTimer;
        $('input, textarea').on('input', function() {
            clearTimeout(autoSaveTimer);
            autoSaveTimer = setTimeout(function() {
                $('#save-user-meta').click();
            }, 3000);
        });
    });
    </script>
    <?php
} 