<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMR_Settings
{

    public function __construct()
    {
        add_action('admin_post_dmr_save_settings', array($this, 'save_settings'));
        add_action('wp_ajax_dmr_test_smtp', array($this, 'test_smtp'));
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $defaults = $this->get_defaults();
        $settings = wp_parse_args(get_option('dmr_settings', array()), $defaults);

        ?>
        <div class="wrap">
            <h1>DMR Services Settings</h1>

            <?php if (isset($_GET['saved'])): ?>
                <div class="notice notice-success is-dismissible">
                    <p>Settings saved successfully!</p>
                </div>
            <?php endif; ?>

            <form method="post" action="<?php echo admin_url('admin-post.php'); ?>">
                <?php wp_nonce_field('dmr_save_settings', 'dmr_settings_nonce'); ?>
                <input type="hidden" name="action" value="dmr_save_settings">

                <h2>Email Notifications</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="admin_email">Admin Email Recipient</label></th>
                        <td>
                            <input type="email" id="admin_email" name="admin_email"
                                value="<?php echo esc_attr($settings['admin_email']); ?>" class="regular-text">
                            <p class="description">Email address to receive submission notifications. Defaults to site admin
                                email.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="from_name">Email From Name</label></th>
                        <td>
                            <input type="text" id="from_name" name="from_name"
                                value="<?php echo esc_attr($settings['from_name']); ?>" class="regular-text">
                            <p class="description">Name shown in the "From" field of notification emails.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="from_email">Email From Address</label></th>
                        <td>
                            <input type="email" id="from_email" name="from_email"
                                value="<?php echo esc_attr($settings['from_email']); ?>" class="regular-text">
                            <p class="description">Email address shown in the "From" field of notification emails.</p>
                        </td>
                    </tr>
                </table>

                <h2>SMTP Configuration</h2>
                <table class="form-table">
                    <tr>
                        <th><label for="smtp_host">SMTP Host</label></th>
                        <td>
                            <input type="text" id="smtp_host" name="smtp_host"
                                value="<?php echo esc_attr($settings['smtp_host'] ?? ''); ?>" class="regular-text" placeholder="smtp.gmail.com">
                            <p class="description">SMTP server hostname (e.g., smtp.gmail.com).</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="smtp_port">SMTP Port</label></th>
                        <td>
                            <input type="number" id="smtp_port" name="smtp_port"
                                value="<?php echo esc_attr($settings['smtp_port'] ?? '587'); ?>" class="small-text" placeholder="587">
                            <p class="description">SMTP server port (587 for TLS, 465 for SSL).</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="smtp_encryption">Encryption</label></th>
                        <td>
                            <select id="smtp_encryption" name="smtp_encryption">
                                <option value="none" <?php selected($settings['smtp_encryption'] ?? 'tls', 'none'); ?>>None</option>
                                <option value="tls" <?php selected($settings['smtp_encryption'] ?? 'tls', 'tls'); ?>>TLS</option>
                                <option value="ssl" <?php selected($settings['smtp_encryption'] ?? 'tls', 'ssl'); ?>>SSL</option>
                            </select>
                            <p class="description">Encryption method for SMTP connection.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="smtp_username">SMTP Username</label></th>
                        <td>
                            <input type="text" id="smtp_username" name="smtp_username"
                                value="<?php echo esc_attr($settings['smtp_username'] ?? ''); ?>" class="regular-text" placeholder="your-email@gmail.com">
                            <p class="description">SMTP authentication username (usually your email address).</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="smtp_password">SMTP Password</label></th>
                        <td>
                            <input type="password" id="smtp_password" name="smtp_password"
                                value="<?php echo esc_attr($settings['smtp_password'] ?? ''); ?>" class="regular-text" placeholder="Your app password">
                            <p class="description">SMTP authentication password or app password.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Enable SMTP</th>
                        <td>
                            <label>
                                <input type="checkbox" name="smtp_enabled" value="1" <?php checked($settings['smtp_enabled'] ?? false, true); ?>>
                                Use SMTP for sending emails
                            </label>
                            <p class="description">When enabled, emails will be sent via SMTP instead of PHP's mail() function.</p>
                        </td>
                    </tr>
                    <tr>
                        <th>Test SMTP</th>
                        <td>
                            <button type="button" id="dmr-test-smtp" class="button">Send Test Email</button>
                            <span id="dmr-smtp-test-result" style="margin-left: 10px;"></span>
                            <p class="description">Send a test email to verify your SMTP configuration is working correctly. The test email will be sent to your admin email address.</p>
                        </td>
                    </tr>
                </table>

                <h2>Feature Toggles</h2>
                <table class="form-table">
                    <tr>
                        <th>Email Notifications</th>
                        <td>
                            <label>
                                <input type="checkbox" name="enable_notifications" value="1" <?php checked($settings['enable_notifications'], true); ?>>
                                Send email notifications for new submissions
                            </label>
                        </td>
                    </tr>
                    <tr>
                        <th>Store User Data</th>
                        <td>
                            <label>
                                <input type="checkbox" name="store_user_id" value="1" <?php checked($settings['store_user_id'], true); ?>>
                                Link submissions to logged-in users
                            </label>
                        </td>
                    </tr>
                </table>

                <h2>Homepage Popup Settings</h2>
                <table class="form-table">
                    <tr>
                        <th>Auto-Show Popup</th>
                        <td>
                            <label>
                                <input type="checkbox" name="auto_show_popup" value="1" <?php checked($settings['auto_show_popup'], true); ?>>
                                Automatically show self-check popup on homepage load
                            </label>
                            <p class="description">When enabled, the self-check assessment popup will appear automatically when
                                visitors land on the homepage.</p>
                        </td>
                    </tr>
                    <tr>
                        <th><label for="popup_delay">Popup Delay (seconds)</label></th>
                        <td>
                            <input type="number" id="popup_delay" name="popup_delay"
                                value="<?php echo esc_attr($settings['popup_delay']); ?>" min="0" max="10" step="0.5"
                                class="small-text">
                            <p class="description">Delay in seconds before showing the popup. Default: 1 second.</p>
                        </td>
                    </tr>
                </table>

                <p class="submit">
                    <button type="submit" class="button button-primary">Save Settings</button>
                </p>
            </form>
        </div>
        <?php
    }

    private function get_defaults()
    {
        return array(
            'admin_email' => get_option('admin_email'),
            'from_name' => get_bloginfo('name'),
            'from_email' => get_option('admin_email'),
            'enable_notifications' => true,
            'store_user_id' => true,
            'auto_show_popup' => false,
            'popup_delay' => 1,
            'smtp_enabled' => true,
            'smtp_host' => 'smtp.gmail.com',
            'smtp_port' => '587',
            'smtp_encryption' => 'tls',
            'smtp_username' => 'noreply050623@gmail.com',
            'smtp_password' => 'xlqhaxnjiowsxuyr'
        );
    }

    public function save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('dmr_save_settings', 'dmr_settings_nonce');

        // Get existing settings to preserve password if not changed
        $existing_settings = get_option('dmr_settings', array());
        
        $settings = array(
            'admin_email' => sanitize_email($_POST['admin_email']),
            'from_name' => sanitize_text_field($_POST['from_name']),
            'from_email' => sanitize_email($_POST['from_email']),
            'enable_notifications' => isset($_POST['enable_notifications']),
            'store_user_id' => isset($_POST['store_user_id']),
            'auto_show_popup' => isset($_POST['auto_show_popup']),
            'popup_delay' => floatval($_POST['popup_delay'] ?? 1),
            'smtp_enabled' => isset($_POST['smtp_enabled']),
            'smtp_host' => sanitize_text_field($_POST['smtp_host'] ?? ''),
            'smtp_port' => intval($_POST['smtp_port'] ?? 587),
            'smtp_encryption' => sanitize_text_field($_POST['smtp_encryption'] ?? 'tls'),
            'smtp_username' => sanitize_text_field($_POST['smtp_username'] ?? ''),
            'smtp_password' => !empty($_POST['smtp_password']) ? sanitize_text_field($_POST['smtp_password']) : ($existing_settings['smtp_password'] ?? '')
        );

        update_option('dmr_settings', $settings);

        wp_redirect(admin_url('admin.php?page=dmr-settings&saved=1'));
        exit;
    }

    /**
     * AJAX handler for testing SMTP configuration
     */
    public function test_smtp()
    {
        // Check permissions
        if (!current_user_can('manage_options')) {
            wp_send_json_error(array('message' => 'Unauthorized'));
            return;
        }

        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'dmr_test_smtp_nonce')) {
            wp_send_json_error(array('message' => 'Security check failed'));
            return;
        }

        $settings = get_option('dmr_settings', array());
        $defaults = $this->get_defaults();
        $settings = wp_parse_args($settings, $defaults);

        // Check if SMTP is enabled
        if (!($settings['smtp_enabled'] ?? false)) {
            wp_send_json_error(array('message' => 'SMTP is not enabled. Please enable SMTP first.'));
            return;
        }

        // Get admin email
        $admin_email = $settings['admin_email'] ?? get_option('admin_email');
        
        if (empty($admin_email)) {
            wp_send_json_error(array('message' => 'Admin email is not set.'));
            return;
        }

        // Send test email
        $subject = 'DMR Services - SMTP Test Email';
        $message = '<html><body>';
        $message .= '<h2>SMTP Test Email</h2>';
        $message .= '<p>This is a test email from DMR Services plugin.</p>';
        $message .= '<p>If you received this email, your SMTP configuration is working correctly!</p>';
        $message .= '<hr>';
        $message .= '<p><strong>SMTP Configuration:</strong></p>';
        $message .= '<ul>';
        $message .= '<li>Host: ' . esc_html($settings['smtp_host'] ?? '') . '</li>';
        $message .= '<li>Port: ' . esc_html($settings['smtp_port'] ?? '') . '</li>';
        $message .= '<li>Encryption: ' . esc_html(strtoupper($settings['smtp_encryption'] ?? '')) . '</li>';
        $message .= '<li>Username: ' . esc_html($settings['smtp_username'] ?? '') . '</li>';
        $message .= '</ul>';
        $message .= '<p><small>Sent at: ' . current_time('mysql') . '</small></p>';
        $message .= '</body></html>';

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $settings['from_name'] ?? get_bloginfo('name'), $settings['from_email'] ?? get_option('admin_email'))
        );

        $result = wp_mail($admin_email, $subject, $message, $headers);

        if ($result) {
            wp_send_json_success(array(
                'message' => sprintf('Test email sent successfully to %s! Please check your inbox.', $admin_email)
            ));
        } else {
            global $phpmailer;
            $error_message = 'Failed to send test email.';
            if (isset($phpmailer) && !empty($phpmailer->ErrorInfo)) {
                $error_message .= ' Error: ' . $phpmailer->ErrorInfo;
            }
            wp_send_json_error(array('message' => $error_message));
        }
    }
}