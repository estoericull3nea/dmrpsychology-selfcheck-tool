<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMR_Self_Check_Config {
    
    private $current_step = 1;
    
    public function __construct() {
        add_action('admin_post_dmr_save_config', array($this, 'save_config'));
    }
    
    public function render_page() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }
        
        $config = get_option('dmr_self_check_config', array());
        
        if (isset($_GET['step'])) {
            $this->current_step = intval($_GET['step']);
        }
        
        ?>
        <div class="wrap dmr-builder-wrap">
            <h1>Stress Check-In Builder</h1>
            
            <div class="dmr-stepper">
                <div class="dmr-stepper-item <?php echo $this->current_step >= 1 ? 'active' : ''; ?> <?php echo $this->current_step > 1 ? 'completed' : ''; ?>">
                    <span class="dmr-stepper-number">1</span>
                    <span class="dmr-stepper-label">Quiz Setup</span>
                </div>
                <div class="dmr-stepper-item <?php echo $this->current_step >= 2 ? 'active' : ''; ?> <?php echo $this->current_step > 2 ? 'completed' : ''; ?>">
                    <span class="dmr-stepper-number">2</span>
                    <span class="dmr-stepper-label">Info Capture</span>
                </div>
                <div class="dmr-stepper-item <?php echo $this->current_step >= 3 ? 'active' : ''; ?>">
                    <span class="dmr-stepper-number">3</span>
                    <span class="dmr-stepper-label">Review & Publish</span>
                </div>
            </div>
            
            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" class="dmr-builder-form">
                <?php wp_nonce_field('dmr_save_config', 'dmr_config_nonce'); ?>
                <input type="hidden" name="action" value="dmr_save_config">
                <input type="hidden" name="current_step" value="<?php echo $this->current_step; ?>">
                
                <?php
                switch ($this->current_step) {
                    case 1:
                        $this->render_step_quiz($config);
                        break;
                    case 2:
                        $this->render_step_info($config);
                        break;
                    case 3:
                        $this->render_step_review($config);
                        break;
                }
                ?>
            </form>
        </div>
        <?php
    }
    
    private function render_step_quiz($config) {
        ?>
        <div class="dmr-step-content">
            <h2>Step 1: Quiz Setup</h2>
            
            <table class="form-table">
                <tr>
                    <th><label for="quiz_title">Quiz Title</label></th>
                    <td>
                        <input type="text" id="quiz_title" name="title" value="<?php echo esc_attr($config['title'] ?? ''); ?>" class="regular-text">
                    </td>
                </tr>
                <tr>
                    <th><label for="quiz_intro">Introduction Text</label></th>
                    <td>
                        <textarea id="quiz_intro" name="intro" rows="3" class="large-text"><?php echo esc_textarea($config['intro'] ?? ''); ?></textarea>
                    </td>
                </tr>
            </table>
            
            <h3>Questions (10 items)</h3>
            <p class="description">Edit the question text below. Items 4, 5, 7, and 8 are reverse-scored automatically.</p>
            
            <div class="dmr-questions-list">
                <?php
                $questions = $config['questions'] ?? array();
                for ($i = 0; $i < 10; $i++) {
                    $question_num = $i + 1;
                    $is_reversed = in_array($question_num, array(4, 5, 7, 8));
                    ?>
                    <div class="dmr-question-item">
                        <div class="dmr-question-header">
                            <strong>Question <?php echo $question_num; ?></strong>
                            <?php if ($is_reversed) : ?>
                                <span class="dmr-badge dmr-reversed">Reverse Scored</span>
                            <?php endif; ?>
                        </div>
                        <textarea name="questions[<?php echo $i; ?>]" rows="2" class="large-text"><?php echo esc_textarea($questions[$i] ?? ''); ?></textarea>
                    </div>
                    <?php
                }
                ?>
            </div>
            
            <h3>Interpretation & Recommendations</h3>
            <table class="form-table">
                <tr>
                    <th><label for="rec_low">Low Stress (0-13)</label></th>
                    <td>
                        <textarea id="rec_low" name="recommendations[low]" rows="2" class="large-text"><?php echo esc_textarea($config['recommendations']['low'] ?? ''); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="rec_moderate">Moderate Stress (14-26)</label></th>
                    <td>
                        <textarea id="rec_moderate" name="recommendations[moderate]" rows="2" class="large-text"><?php echo esc_textarea($config['recommendations']['moderate'] ?? ''); ?></textarea>
                    </td>
                </tr>
                <tr>
                    <th><label for="rec_high">High Stress (27-40)</label></th>
                    <td>
                        <textarea id="rec_high" name="recommendations[high]" rows="2" class="large-text"><?php echo esc_textarea($config['recommendations']['high'] ?? ''); ?></textarea>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <a href="<?php echo admin_url('admin.php?page=dmr-self-check&step=2'); ?>" class="button button-primary">Next: Info Capture</a>
            </p>
        </div>
        <?php
    }
    
    private function render_step_info($config) {
        $fields = $config['fields'] ?? array();
        ?>
        <div class="dmr-step-content">
            <h2>Step 2: Information Capture</h2>
            
            <h3>Contact Fields</h3>
            <table class="form-table">
                <tr>
                    <th>Full Name</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fields[full_name][enabled]" value="1" checked disabled>
                            Enabled (Required)
                        </label>
                        <input type="hidden" name="fields[full_name][enabled]" value="1">
                        <input type="hidden" name="fields[full_name][required]" value="1">
                    </td>
                </tr>
                <tr>
                    <th>Email</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fields[email][enabled]" value="1" checked disabled>
                            Enabled (Required)
                        </label>
                        <input type="hidden" name="fields[email][enabled]" value="1">
                        <input type="hidden" name="fields[email][required]" value="1">
                    </td>
                </tr>
                <tr>
                    <th>Phone</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fields[phone][enabled]" value="1" <?php checked($fields['phone']['enabled'] ?? true, true); ?>>
                            Enabled
                        </label>
                        <label style="margin-left: 15px;">
                            <input type="checkbox" name="fields[phone][required]" value="1" <?php checked($fields['phone']['required'] ?? false, true); ?>>
                            Required
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Additional Notes</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fields[notes][enabled]" value="1" <?php checked($fields['notes']['enabled'] ?? true, true); ?>>
                            Enabled
                        </label>
                    </td>
                </tr>
                <tr>
                    <th>Newsletter Opt-In</th>
                    <td>
                        <label>
                            <input type="checkbox" name="fields[newsletter][enabled]" value="1" <?php checked($fields['newsletter']['enabled'] ?? true, true); ?>>
                            Show newsletter checkbox
                        </label>
                    </td>
                </tr>
            </table>
            
            <h3>Consent Text</h3>
            <table class="form-table">
                <tr>
                    <th><label for="consent_text">Consent Statement</label></th>
                    <td>
                        <textarea id="consent_text" name="consent_text" rows="2" class="large-text"><?php echo esc_textarea($config['consent_text'] ?? ''); ?></textarea>
                        <p class="description">This text will appear as a required checkbox before submission.</p>
                    </td>
                </tr>
            </table>
            
            <p class="submit">
                <a href="<?php echo admin_url('admin.php?page=dmr-self-check&step=1'); ?>" class="button">Previous</a>
                <a href="<?php echo admin_url('admin.php?page=dmr-self-check&step=3'); ?>" class="button button-primary">Next: Review</a>
            </p>
        </div>
        <?php
    }
    
    private function render_step_review($config) {
        ?>
        <div class="dmr-step-content">
            <h2>Step 3: Review & Publish</h2>
            
            <div class="dmr-review-section">
                <h3>Quiz Configuration</h3>
                <p><strong>Title:</strong> <?php echo esc_html($config['title'] ?? ''); ?></p>
                <p><strong>Questions:</strong> <?php echo count($config['questions'] ?? array()); ?> items</p>
                <p><strong>Scoring:</strong> 0-40 scale with reverse scoring on items 4, 5, 7, 8</p>
            </div>
            
            <div class="dmr-review-section">
                <h3>Shortcode</h3>
                <p>Add this shortcode to any page or post:</p>
                <code class="dmr-shortcode">[dmr_self_check]</code>
                <button type="button" class="button" onclick="navigator.clipboard.writeText('[dmr_self_check]')">Copy Shortcode</button>
            </div>
            
            <div class="dmr-review-section">
                <h3>Publish Status</h3>
                <label>
                    <input type="checkbox" name="published" value="1" <?php checked($config['published'] ?? false, true); ?>>
                    <strong>Enable this tool on the frontend</strong>
                </label>
                <p class="description">When enabled, the shortcode will display the stress check-in form to visitors.</p>
            </div>
            
            <p class="submit">
                <a href="<?php echo admin_url('admin.php?page=dmr-self-check&step=2'); ?>" class="button">Previous</a>
                <button type="submit" class="button button-primary">Save Configuration</button>
            </p>
        </div>
        <?php
    }
    
    public function save_config() {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions.'));
        }
        
        check_admin_referer('dmr_save_config', 'dmr_config_nonce');
        
        $existing_config = get_option('dmr_self_check_config', array());
        
        // Merge new data with existing
        $config = array_merge($existing_config, array(
            'title' => sanitize_text_field($_POST['title'] ?? $existing_config['title']),
            'intro' => sanitize_textarea_field($_POST['intro'] ?? $existing_config['intro']),
            'published' => isset($_POST['published']) ? true : false,
            'reversed_items' => array(4, 5, 7, 8)
        ));
        
        // Questions
        if (isset($_POST['questions'])) {
            $config['questions'] = array();
            foreach ($_POST['questions'] as $question) {
                $config['questions'][] = sanitize_textarea_field($question);
            }
        }
        
        // Recommendations
        if (isset($_POST['recommendations'])) {
            $config['recommendations'] = array();
            foreach ($_POST['recommendations'] as $key => $rec) {
                $config['recommendations'][$key] = sanitize_textarea_field($rec);
            }
        }
        
        // Fields
        if (isset($_POST['fields'])) {
            $config['fields'] = array();
            foreach ($_POST['fields'] as $field_name => $field_data) {
                $config['fields'][$field_name] = array(
                    'enabled' => isset($field_data['enabled']),
                    'required' => isset($field_data['required'])
                );
            }
        }
        
        // Consent text
        if (isset($_POST['consent_text'])) {
            $config['consent_text'] = sanitize_textarea_field($_POST['consent_text']);
        }
        
        // Ranges (keep default)
        if (!isset($config['ranges'])) {
            $config['ranges'] = array(
                'low' => array('min' => 0, 'max' => 13, 'label' => 'Low Stress'),
                'moderate' => array('min' => 14, 'max' => 26, 'label' => 'Moderate Stress'),
                'high' => array('min' => 27, 'max' => 40, 'label' => 'High Perceived Stress')
            );
        }
        
        update_option('dmr_self_check_config', $config);
        
        $step = intval($_POST['current_step'] ?? 3);
        wp_redirect(admin_url('admin.php?page=dmr-self-check&step=' . $step . '&saved=1'));
        exit;
    }
}