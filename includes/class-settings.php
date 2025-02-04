<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMR_Settings
{

    public function __construct()
    {
        add_action('admin_post_dmr_save_settings', array($this, 'save_settings'));
    }

    public function render_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        $settings = get_option('dmr_settings', $this->get_defaults());

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
            'popup_delay' => 1
        );
    }

    public function save_settings()
    {
        if (!current_user_can('manage_options')) {
            wp_die('Unauthorized');
        }

        check_admin_referer('dmr_save_settings', 'dmr_settings_nonce');

        $settings = array(
            'admin_email' => sanitize_email($_POST['admin_email']),
            'from_name' => sanitize_text_field($_POST['from_name']),
            'from_email' => sanitize_email($_POST['from_email']),
            'enable_notifications' => isset($_POST['enable_notifications']),
            'store_user_id' => isset($_POST['store_user_id']),
            'auto_show_popup' => isset($_POST['auto_show_popup']),
            'popup_delay' => floatval($_POST['popup_delay'] ?? 1)
        );

        update_option('dmr_settings', $settings);

        wp_redirect(admin_url('admin.php?page=dmr-settings&saved=1'));
        exit;
    }
}