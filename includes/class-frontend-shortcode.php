<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMR_Frontend_Shortcode
{

    public function __construct()
    {
        add_shortcode('dmr_self_check', array($this, 'render_shortcode'));
        add_action('admin_post_dmr_submit_check', array($this, 'handle_submission'));
        add_action('admin_post_nopriv_dmr_submit_check', array($this, 'handle_submission'));
        add_action('wp_ajax_dmr_get_step', array($this, 'ajax_get_step'));
        add_action('wp_ajax_nopriv_dmr_get_step', array($this, 'ajax_get_step'));
        add_action('wp_ajax_dmr_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_nopriv_dmr_submit_form', array($this, 'ajax_submit_form'));
        add_action('wp_ajax_dmr_store_results', array($this, 'ajax_store_results'));
        add_action('wp_ajax_nopriv_dmr_store_results', array($this, 'ajax_store_results'));
    }

    public function render_shortcode($atts)
    {
        $config = get_option('dmr_self_check_config', array());

        if (!($config['published'] ?? false)) {
            return '<p>This tool is currently unavailable.</p>';
        }

        // Check if showing results
        if (isset($_GET['dmr_result']) && $_GET['dmr_result'] === 'success') {
            return $this->render_results();
        }

        ob_start();
        $this->render_form($config);
        return ob_get_clean();
    }

    private function render_form($config)
    {
        $step = isset($_GET['dmr_step']) ? intval($_GET['dmr_step']) : 1;
        ?>
        <div class="dmr-self-check-wrapper">
            <div class="dmr-header">
                <h2><?php echo esc_html($config['title'] ?? 'Stress Check-In'); ?></h2>
                <p><?php echo esc_html($config['intro'] ?? ''); ?></p>
            </div>

            <div class="dmr-progress">
                <div class="dmr-progress-step <?php echo $step >= 1 ? 'active' : ''; ?>">
                    <span class="dmr-progress-number">1</span>
                    <span class="dmr-progress-label">Quiz</span>
                </div>
                <div class="dmr-progress-step <?php echo $step >= 2 ? 'active' : ''; ?>">
                    <span class="dmr-progress-number">2</span>
                    <span class="dmr-progress-label">Info</span>
                </div>
                <div class="dmr-progress-step <?php echo $step >= 3 ? 'active' : ''; ?>">
                    <span class="dmr-progress-number">3</span>
                    <span class="dmr-progress-label">Review</span>
                </div>
            </div>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>" id="dmr-form" class="dmr-form">
                <?php wp_nonce_field('dmr_submit_check', 'dmr_submit_nonce'); ?>
                <input type="hidden" name="action" value="dmr_submit_check">
                <input type="hidden" name="dmr_step" id="dmr_current_step" value="<?php echo $step; ?>">

                <?php
                switch ($step) {
                    case 1:
                        $this->render_quiz_step($config);
                        break;
                    case 2:
                        $this->render_info_step($config);
                        break;
                    case 3:
                        $this->render_review_step($config);
                        break;
                }
                ?>
            </form>
        </div>
        <?php
    }

    private function render_quiz_step($config)
    {
        $questions = $config['questions'] ?? array();
        $answers = isset($_SESSION['dmr_answers']) ? $_SESSION['dmr_answers'] : array();

        ?>
        <div class="dmr-step-content" id="dmr-step-1">
            <h3>Answer the following questions</h3>
            <p class="dmr-instructions">For each item, select how often it has applied to you in the last month.</p>

            <div class="dmr-scale-legend">
                <span><strong>0</strong> Never</span>
                <span><strong>1</strong> Almost Never</span>
                <span><strong>2</strong> Sometimes</span>
                <span><strong>3</strong> Fairly Often</span>
                <span><strong>4</strong> Very Often</span>
            </div>

            <?php foreach ($questions as $index => $question): ?>
                <fieldset class="dmr-question">
                    <legend><?php echo esc_html(($index + 1) . '. ' . $question); ?></legend>
                    <div class="dmr-options">
                        <?php for ($i = 0; $i <= 4; $i++): ?>
                            <label class="dmr-radio-label">
                                <input type="radio" name="answers[<?php echo $index; ?>]" value="<?php echo $i; ?>" required>
                                <span class="dmr-radio-text"><?php echo $i; ?></span>
                            </label>
                        <?php endfor; ?>
                    </div>
                </fieldset>
            <?php endforeach; ?>

            <div class="dmr-nav-buttons">
                <button type="button" class="dmr-btn dmr-btn-primary" onclick="dmrNextStep(2)">Next</button>
            </div>
        </div>
        <?php
    }

    private function render_info_step($config)
    {
        $fields = $config['fields'] ?? array();
        ?>
        <div class="dmr-step-content" id="dmr-step-2">
            <h3>Your Information</h3>

            <?php if ($fields['full_name']['enabled'] ?? true): ?>
                <div class="dmr-field">
                    <label for="full_name">Full Name
                        <?php if ($fields['full_name']['required'] ?? true)
                            echo '<span class="required">*</span>'; ?></label>
                    <input type="text" id="full_name" name="full_name" <?php if ($fields['full_name']['required'] ?? true)
                        echo 'required'; ?>>
                </div>
            <?php endif; ?>

            <?php if ($fields['email']['enabled'] ?? true): ?>
                <div class="dmr-field">
                    <label for="email">Email
                        <?php if ($fields['email']['required'] ?? true)
                            echo '<span class="required">*</span>'; ?></label>
                    <input type="email" id="email" name="email" <?php if ($fields['email']['required'] ?? true)
                        echo 'required'; ?>>
                </div>
            <?php endif; ?>

            <?php if ($fields['phone']['enabled'] ?? true): ?>
                <div class="dmr-field">
                    <label for="phone">Phone
                        <?php if ($fields['phone']['required'] ?? false)
                            echo '<span class="required">*</span>'; ?></label>
                    <input type="tel" id="phone" name="phone" <?php if ($fields['phone']['required'] ?? false)
                        echo 'required'; ?>>
                </div>
            <?php endif; ?>

            <?php if ($fields['notes']['enabled'] ?? true): ?>
                <div class="dmr-field">
                    <label for="notes">Additional Notes</label>
                    <textarea id="notes" name="notes" rows="3"></textarea>
                </div>
            <?php endif; ?>

            <div class="dmr-nav-buttons">
                <button type="button" class="dmr-btn dmr-btn-secondary" onclick="dmrPrevStep(1)">Previous</button>
                <button type="button" class="dmr-btn dmr-btn-primary" onclick="dmrNextStep(3)">Review</button>
            </div>
        </div>
        <?php
    }

    private function render_review_step($config)
    {
        ?>
        <div class="dmr-step-content" id="dmr-step-3">
            <h3>Review Your Submission</h3>

            <div id="dmr-review-content">
                <p class="dmr-loading">Please complete the previous steps first.</p>
            </div>

            <div class="dmr-field dmr-checkbox-field">
                <label>
                    <input type="checkbox" name="consent" value="1" required>
                    <?php echo esc_html($config['consent_text'] ?? 'I agree to be contacted.'); ?> <span
                        class="required">*</span>
                </label>
            </div>

            <div class="dmr-disclaimer">
                <p><strong>Disclaimer:</strong> This self-check is for informational purposes only and does not constitute a
                    medical diagnosis or professional advice. If you are experiencing severe distress, please contact a
                    healthcare professional or crisis helpline.</p>
            </div>

            <div class="dmr-nav-buttons">
                <button type="button" class="dmr-btn dmr-btn-secondary" onclick="dmrPrevStep(2)">Previous</button>
                <button type="submit" class="dmr-btn dmr-btn-primary">Submit</button>
            </div>
        </div>
        <?php
    }

    private function render_results()
    {
        if (!isset($_SESSION['dmr_result_data'])) {
            return '<p>No results found.</p>';
        }

        $data = $_SESSION['dmr_result_data'];
        unset($_SESSION['dmr_result_data']);

        ob_start();
        ?>
        <div class="dmr-results-wrapper">
            <div class="dmr-results-header">
                <h2>Your Results</h2>
            </div>

            <div class="dmr-results-score">
                <div class="dmr-score-circle">
                    <span class="dmr-score-number"><?php echo esc_html($data['score']); ?></span>
                    <span class="dmr-score-total">/ 40</span>
                </div>
                <div class="dmr-score-category dmr-category-<?php echo esc_attr(strtolower($data['category'])); ?>">
                    <?php echo esc_html($data['category']); ?>
                </div>
            </div>

            <div class="dmr-results-recommendation">
                <h3>Next Steps</h3>
                <p><?php echo esc_html($data['recommendation']); ?></p>
            </div>

            <div class="dmr-disclaimer">
                <p><strong>Disclaimer:</strong> This self-check is for informational purposes only and does not constitute a
                    medical diagnosis or professional advice. If you are experiencing severe distress, please contact a
                    healthcare professional or crisis helpline.</p>
            </div>

            <div class="dmr-results-footer">
                <p>A copy of your results has been sent to our team. We may reach out to you at the contact information you
                    provided.</p>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    public function handle_submission()
    {
        if (!isset($_POST['dmr_submit_nonce']) || !wp_verify_nonce($_POST['dmr_submit_nonce'], 'dmr_submit_check')) {
            wp_die('Security check failed');
        }

        $config = get_option('dmr_self_check_config', array());

        // Validate and sanitize inputs
        $answers = array();
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $index => $value) {
                $answers[$index] = intval($value);
            }
        }

        if (count($answers) !== 10) {
            wp_die('Please answer all questions.');
        }

        $full_name = sanitize_text_field($_POST['full_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $consent = isset($_POST['consent']);

        if (empty($full_name) || empty($email)) {
            wp_die('Name and email are required.');
        }

        if (!$consent) {
            wp_die('You must agree to the consent statement.');
        }

        // Calculate score
        $score = $this->calculate_score($answers, $config['reversed_items']);

        // Determine category
        $category_data = $this->get_category($score, $config['ranges']);
        $category = $category_data['key'];
        $category_label = $category_data['label'];
        $recommendation = $config['recommendations'][$category] ?? '';

        // Store in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'dmr_self_checks';

        $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id() ?: null,
                'tool_type' => 'stress_check',
                'quiz_answers' => json_encode($answers),
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes,
                'score' => $score,
                'category' => $category,
                'recommendation' => $recommendation,
                'status' => 'submitted',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        // Send email to admin
        $mailer = new DMR_Mailer();
        $mailer->send_admin_notification(array(
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'notes' => $notes,
            'answers' => $answers,
            'score' => $score,
            'category' => $category_label,
            'recommendation' => $recommendation,
            'questions' => $config['questions']
        ));

        // Send email to customer
        $mailer->send_customer_notification(array(
            'full_name' => $full_name,
            'email' => $email,
            'score' => $score,
            'category' => $category_label,
            'recommendation' => $recommendation
        ));

        // Store results in session for display
        if (!session_id()) {
            session_start();
        }
        $_SESSION['dmr_result_data'] = array(
            'score' => $score,
            'category' => $category_label,
            'recommendation' => $recommendation
        );

        // Redirect to results
        $redirect_url = add_query_arg('dmr_result', 'success', wp_get_referer());
        wp_redirect($redirect_url);
        exit;
    }

    private function calculate_score($answers, $reversed_items)
    {
        $score = 0;

        foreach ($answers as $index => $value) {
            $question_num = $index + 1;

            if (in_array($question_num, $reversed_items)) {
                // Reverse scoring: 0→4, 1→3, 2→2, 3→1, 4→0
                $score += (4 - $value);
            } else {
                $score += $value;
            }
        }

        return $score;
    }

    private function get_category($score, $ranges)
    {
        foreach ($ranges as $key => $range) {
            if ($score >= $range['min'] && $score <= $range['max']) {
                return array('key' => $key, 'label' => $range['label']);
            }
        }

        return array('key' => 'moderate', 'label' => 'Moderate Stress');
    }

    /**
     * AJAX handler to get step content
     */
    public function ajax_get_step()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dmr_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        $step = isset($_POST['step']) ? intval($_POST['step']) : 1;
        $config = get_option('dmr_self_check_config', array());

        if (!($config['published'] ?? false)) {
            wp_send_json_error(array('message' => 'This tool is currently unavailable.'));
            return;
        }

        ob_start();
        switch ($step) {
            case 1:
                $this->render_quiz_step($config);
                break;
            case 2:
                $this->render_info_step($config);
                break;
            case 3:
                $this->render_review_step($config);
                break;
            default:
                $this->render_quiz_step($config);
        }
        $content = ob_get_clean();

        wp_send_json_success(array(
            'content' => $content,
            'step' => $step
        ));
    }

    /**
     * AJAX handler for form submission
     */
    public function ajax_submit_form()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dmr_ajax_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed.'));
            return;
        }

        $config = get_option('dmr_self_check_config', array());

        // Validate and sanitize inputs
        $answers = array();
        if (isset($_POST['answers']) && is_array($_POST['answers'])) {
            foreach ($_POST['answers'] as $index => $value) {
                $answers[$index] = intval($value);
            }
        }

        if (count($answers) !== 10) {
            wp_send_json_error(array('message' => 'Please answer all questions.'));
            return;
        }

        $full_name = sanitize_text_field($_POST['full_name'] ?? '');
        $email = sanitize_email($_POST['email'] ?? '');
        $phone = sanitize_text_field($_POST['phone'] ?? '');
        $notes = sanitize_textarea_field($_POST['notes'] ?? '');
        $consent = isset($_POST['consent']);

        if (empty($full_name) || empty($email)) {
            wp_send_json_error(array('message' => 'Name and email are required.'));
            return;
        }

        if (!$consent) {
            wp_send_json_error(array('message' => 'You must agree to the consent statement.'));
            return;
        }

        // Calculate score
        $score = $this->calculate_score($answers, $config['reversed_items']);

        // Determine category
        $category_data = $this->get_category($score, $config['ranges']);
        $category = $category_data['key'];
        $category_label = $category_data['label'];
        $recommendation = $config['recommendations'][$category] ?? '';

        // Store in database
        global $wpdb;
        $table_name = $wpdb->prefix . 'dmr_self_checks';

        $insert_result = $wpdb->insert(
            $table_name,
            array(
                'user_id' => get_current_user_id() ?: null,
                'tool_type' => 'stress_check',
                'quiz_answers' => json_encode($answers),
                'full_name' => $full_name,
                'email' => $email,
                'phone' => $phone,
                'notes' => $notes,
                'score' => $score,
                'category' => $category,
                'recommendation' => $recommendation,
                'status' => 'submitted',
                'created_at' => current_time('mysql'),
                'updated_at' => current_time('mysql')
            ),
            array('%d', '%s', '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%s', '%s', '%s', '%s')
        );

        if ($insert_result === false) {
            wp_send_json_error(array('message' => 'Failed to save submission. Please try again.'));
            return;
        }

        // Send email to admin
        $mailer = new DMR_Mailer();
        $mailer->send_admin_notification(array(
            'full_name' => $full_name,
            'email' => $email,
            'phone' => $phone,
            'notes' => $notes,
            'answers' => $answers,
            'score' => $score,
            'category' => $category_label,
            'recommendation' => $recommendation,
            'questions' => $config['questions']
        ));

        // Send email to customer
        $mailer->send_customer_notification(array(
            'full_name' => $full_name,
            'email' => $email,
            'score' => $score,
            'category' => $category_label,
            'recommendation' => $recommendation
        ));

        // Return success with results data
        wp_send_json_success(array(
            'message' => 'Submission successful!',
            'redirect_url' => add_query_arg('dmr_result', 'success', wp_get_referer() ?: home_url()),
            'results' => array(
                'score' => $score,
                'category' => $category_label,
                'recommendation' => $recommendation
            )
        ));
    }
}