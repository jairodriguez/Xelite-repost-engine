<?php
/**
 * Admin Field Callbacks for Xelite Repost Engine
 *
 * @package XeliteRepostEngine
 * @since 1.0.0
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    exit;
}

/**
 * Admin field callbacks trait
 */
trait XeliteRepostEngine_Admin_Fields {
    
    /**
     * General settings section callback
     */
    public function general_settings_section_callback() {
        echo '<p>' . esc_html__('Configure basic plugin settings and preferences.', 'xelite-repost-engine') . '</p>';
    }
    
    /**
     * API settings section callback
     */
    public function api_settings_section_callback() {
        echo '<p>' . esc_html__('Configure API keys for X (Twitter) and OpenAI integration. These are required for the plugin to function properly.', 'xelite-repost-engine') . '</p>';
        echo '<p><strong>' . esc_html__('Note:', 'xelite-repost-engine') . '</strong> ' . esc_html__('API keys are stored securely in the WordPress options table.', 'xelite-repost-engine') . '</p>';
    }
    
    /**
     * Target accounts section callback
     */
    public function target_accounts_section_callback() {
        echo '<p>' . esc_html__('Add X (Twitter) accounts to monitor for repost patterns. The plugin will analyze these accounts to identify what content gets reposted.', 'xelite-repost-engine') . '</p>';
    }
    
    /**
     * Advanced settings section callback
     */
    public function advanced_settings_section_callback() {
        echo '<p>' . esc_html__('Advanced configuration options for power users. Modify these settings only if you understand their implications.', 'xelite-repost-engine') . '</p>';
    }
    
    /**
     * Tools section callback
     */
    public function tools_section_callback() {
        echo '<p>' . esc_html__('Database management tools and utilities for maintaining your repost data.', 'xelite-repost-engine') . '</p>';
    }
    
    /**
     * Checkbox field callback
     */
    public function checkbox_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : false;
        
        ?>
        <label>
            <input type="checkbox" 
                   name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
                   value="1" 
                   <?php checked($value, true); ?> />
            <?php echo esc_html($description); ?>
        </label>
        <?php
    }
    
    /**
     * Text field callback
     */
    public function text_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <input type="text" 
               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="<?php echo esc_attr($placeholder); ?>" />
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Password field callback
     */
    public function password_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $test_button = isset($args['test_button']) ? $args['test_button'] : false;
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <div class="password-field-container">
            <input type="password" 
                   name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
                   value="<?php echo esc_attr($value); ?>" 
                   class="regular-text"
                   autocomplete="off" />
            
            <?php if ($test_button) : ?>
                <button type="button" class="button button-secondary test-api-button" data-field="<?php echo esc_attr($field); ?>">
                    <span class="dashicons dashicons-admin-network"></span>
                    <?php esc_html_e('Test Connection', 'xelite-repost-engine'); ?>
                </button>
            <?php endif; ?>
            
            <button type="button" class="button button-link toggle-password">
                <span class="dashicons dashicons-visibility"></span>
                <?php esc_html_e('Show', 'xelite-repost-engine'); ?>
            </button>
        </div>
        
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        
        <div class="api-test-result" id="test-result-<?php echo esc_attr($field); ?>" style="display: none;">
            <span class="test-status"></span>
            <span class="test-message"></span>
        </div>
        <?php
    }
    
    /**
     * Number field callback
     */
    public function number_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $min = isset($args['min']) ? $args['min'] : 0;
        $max = isset($args['max']) ? $args['max'] : 999999;
        $step = isset($args['step']) ? $args['step'] : 1;
        $value = isset($settings[$field]) ? $settings[$field] : $min;
        
        ?>
        <input type="number" 
               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
               value="<?php echo esc_attr($value); ?>" 
               class="small-text"
               min="<?php echo esc_attr($min); ?>"
               max="<?php echo esc_attr($max); ?>"
               step="<?php echo esc_attr($step); ?>" />
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Select field callback
     */
    public function select_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $options = isset($args['options']) ? $args['options'] : array();
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <select name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]">
            <?php foreach ($options as $option_value => $option_label) : ?>
                <option value="<?php echo esc_attr($option_value); ?>" <?php selected($value, $option_value); ?>>
                    <?php echo esc_html($option_label); ?>
                </option>
            <?php endforeach; ?>
        </select>
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Textarea field callback
     */
    public function textarea_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $rows = isset($args['rows']) ? $args['rows'] : 5;
        $cols = isset($args['cols']) ? $args['cols'] : 50;
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <textarea name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
                  rows="<?php echo esc_attr($rows); ?>" 
                  cols="<?php echo esc_attr($cols); ?>"
                  class="large-text"
                  placeholder="<?php echo esc_attr($placeholder); ?>"><?php echo esc_textarea($value); ?></textarea>
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Repeater field callback for target accounts
     */
    public function repeater_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $fields = isset($args['fields']) ? $args['fields'] : array();
        $accounts = isset($settings[$field]) ? $settings[$field] : array();
        
        // Ensure we have at least one empty row
        if (empty($accounts)) {
            $accounts = array(array());
        }
        
        ?>
        <div class="repeater-field" data-field="<?php echo esc_attr($field); ?>">
            <div class="repeater-items">
                <?php foreach ($accounts as $index => $account) : ?>
                    <div class="repeater-item" data-index="<?php echo esc_attr($index); ?>">
                        <div class="repeater-item-header">
                            <span class="item-number"><?php echo esc_html($index + 1); ?></span>
                            <button type="button" class="button button-link remove-item">
                                <span class="dashicons dashicons-trash"></span>
                                <?php esc_html_e('Remove', 'xelite-repost-engine'); ?>
                            </button>
                        </div>
                        
                        <div class="repeater-item-content">
                            <?php foreach ($fields as $field_key => $field_config) : ?>
                                <div class="field-group">
                                    <label class="field-label">
                                        <?php echo esc_html($field_config['label']); ?>
                                    </label>
                                    
                                    <?php
                                    $field_value = isset($account[$field_key]) ? $account[$field_key] : '';
                                    $field_type = $field_config['type'];
                                    $field_placeholder = isset($field_config['placeholder']) ? $field_config['placeholder'] : '';
                                    
                                    switch ($field_type) {
                                        case 'text':
                                            ?>
                                            <input type="text" 
                                                   name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>][<?php echo esc_attr($index); ?>][<?php echo esc_attr($field_key); ?>]" 
                                                   value="<?php echo esc_attr($field_value); ?>" 
                                                   class="regular-text"
                                                   placeholder="<?php echo esc_attr($field_placeholder); ?>" />
                                            <?php
                                            break;
                                            
                                        case 'checkbox':
                                            ?>
                                            <label>
                                                <input type="checkbox" 
                                                       name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>][<?php echo esc_attr($index); ?>][<?php echo esc_attr($field_key); ?>]" 
                                                       value="1" 
                                                       <?php checked($field_value, true); ?> />
                                                <?php esc_html_e('Enabled', 'xelite-repost-engine'); ?>
                                            </label>
                                            <?php
                                            break;
                                    }
                                    ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
            
            <button type="button" class="button button-secondary add-item">
                <span class="dashicons dashicons-plus"></span>
                <?php esc_html_e('Add Account', 'xelite-repost-engine'); ?>
            </button>
        </div>
        
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        
        <script type="text/template" id="repeater-template-<?php echo esc_attr($field); ?>">
            <div class="repeater-item" data-index="{{index}}">
                <div class="repeater-item-header">
                    <span class="item-number">{{number}}</span>
                    <button type="button" class="button button-link remove-item">
                        <span class="dashicons dashicons-trash"></span>
                        <?php esc_html_e('Remove', 'xelite-repost-engine'); ?>
                    </button>
                </div>
                
                <div class="repeater-item-content">
                    <?php foreach ($fields as $field_key => $field_config) : ?>
                        <div class="field-group">
                            <label class="field-label">
                                <?php echo esc_html($field_config['label']); ?>
                            </label>
                            
                            <?php
                            $field_type = $field_config['type'];
                            $field_placeholder = isset($field_config['placeholder']) ? $field_config['placeholder'] : '';
                            
                            switch ($field_type) {
                                case 'text':
                                    ?>
                                    <input type="text" 
                                           name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>][{{index}}][<?php echo esc_attr($field_key); ?>]" 
                                           value="" 
                                           class="regular-text"
                                           placeholder="<?php echo esc_attr($field_placeholder); ?>" />
                                    <?php
                                    break;
                                    
                                case 'checkbox':
                                    ?>
                                    <label>
                                        <input type="checkbox" 
                                               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>][{{index}}][<?php echo esc_attr($field_key); ?>]" 
                                               value="1" 
                                               checked />
                                        <?php esc_html_e('Enabled', 'xelite-repost-engine'); ?>
                                    </label>
                                    <?php
                                    break;
                            }
                            ?>
                        </div>
                    <?php endforeach; ?>
                </div>
            </div>
        </script>
        <?php
    }
    
    /**
     * Color field callback
     */
    public function color_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '#000000';
        
        ?>
        <input type="color" 
               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
               value="<?php echo esc_attr($value); ?>" />
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Date field callback
     */
    public function date_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <input type="date" 
               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
               value="<?php echo esc_attr($value); ?>" />
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Time field callback
     */
    public function time_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <input type="time" 
               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
               value="<?php echo esc_attr($value); ?>" />
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * URL field callback
     */
    public function url_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <input type="url" 
               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="<?php echo esc_attr($placeholder); ?>" />
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Email field callback
     */
    public function email_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $placeholder = isset($args['placeholder']) ? $args['placeholder'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <input type="email" 
               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
               value="<?php echo esc_attr($value); ?>" 
               class="regular-text"
               placeholder="<?php echo esc_attr($placeholder); ?>" />
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * File upload field callback
     */
    public function file_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $accept = isset($args['accept']) ? $args['accept'] : '';
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <input type="file" 
               name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
               accept="<?php echo esc_attr($accept); ?>" />
        
        <?php if ($value) : ?>
            <p class="current-file">
                <?php esc_html_e('Current file:', 'xelite-repost-engine'); ?> 
                <strong><?php echo esc_html($value); ?></strong>
            </p>
        <?php endif; ?>
        
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Radio field callback
     */
    public function radio_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $options = isset($args['options']) ? $args['options'] : array();
        $value = isset($settings[$field]) ? $settings[$field] : '';
        
        ?>
        <fieldset>
            <?php foreach ($options as $option_value => $option_label) : ?>
                <label>
                    <input type="radio" 
                           name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>]" 
                           value="<?php echo esc_attr($option_value); ?>" 
                           <?php checked($value, $option_value); ?> />
                    <?php echo esc_html($option_label); ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Multi-checkbox field callback
     */
    public function multi_checkbox_field_callback($args) {
        $settings = $this->get_settings();
        $field = $args['field'];
        $description = isset($args['description']) ? $args['description'] : '';
        $options = isset($args['options']) ? $args['options'] : array();
        $value = isset($settings[$field]) ? $settings[$field] : array();
        
        if (!is_array($value)) {
            $value = array();
        }
        
        ?>
        <fieldset>
            <?php foreach ($options as $option_value => $option_label) : ?>
                <label>
                    <input type="checkbox" 
                           name="<?php echo esc_attr($this->option_name); ?>[<?php echo esc_attr($field); ?>][]" 
                           value="<?php echo esc_attr($option_value); ?>" 
                           <?php checked(in_array($option_value, $value), true); ?> />
                    <?php echo esc_html($option_label); ?>
                </label><br>
            <?php endforeach; ?>
        </fieldset>
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        <?php
    }
    
    /**
     * Connection status field callback
     */
    public function connection_status_field_callback($args) {
        $description = isset($args['description']) ? $args['description'] : '';
        
        ?>
        <div class="api-connection-status">
            <div class="connection-item">
                <span class="connection-label"><?php _e('X (Twitter) API:', 'xelite-repost-engine'); ?></span>
                <span class="connection-status unknown" id="x-api-status">
                    <span class="dashicons dashicons-minus"></span>
                    <?php _e('Not tested', 'xelite-repost-engine'); ?>
                </span>
                <button type="button" class="button button-small test-connection" data-api="x">
                    <?php _e('Test Now', 'xelite-repost-engine'); ?>
                </button>
            </div>
            
            <div class="connection-item">
                <span class="connection-label"><?php _e('OpenAI API:', 'xelite-repost-engine'); ?></span>
                <span class="connection-status unknown" id="openai-api-status">
                    <span class="dashicons dashicons-minus"></span>
                    <?php _e('Not tested', 'xelite-repost-engine'); ?>
                </span>
                <button type="button" class="button button-small test-connection" data-api="openai">
                    <?php _e('Test Now', 'xelite-repost-engine'); ?>
                </button>
            </div>
        </div>
        
        <?php if ($description) : ?>
            <p class="description"><?php echo esc_html($description); ?></p>
        <?php endif; ?>
        
        <div class="api-test-info">
            <h4><?php _e('API Requirements:', 'xelite-repost-engine'); ?></h4>
            <ul>
                <li><strong><?php _e('X (Twitter) API:', 'xelite-repost-engine'); ?></strong> <?php _e('Requires Bearer Token with read access to tweets and users.', 'xelite-repost-engine'); ?></li>
                <li><strong><?php _e('OpenAI API:', 'xelite-repost-engine'); ?></strong> <?php _e('Requires API key with access to GPT models for content generation.', 'xelite-repost-engine'); ?></li>
            </ul>
        </div>
        <?php
    }
} 