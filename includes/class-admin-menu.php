<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMR_Admin_Menu
{

    public function __construct()
    {
        add_action('admin_menu', array($this, 'add_admin_menu'));
    }

    public function add_admin_menu()
    {
        // Top-level menu
        add_menu_page(
            'DMR Services',
            'DMR Services',
            'manage_options',
            'dmr-services',
            array($this, 'dashboard_page'),
            'dashicons-chart-line',
            30
        );

        // Dashboard (redirect to self-check)
        add_submenu_page(
            'dmr-services',
            'Dashboard',
            'Dashboard',
            'manage_options',
            'dmr-services',
            array($this, 'dashboard_page')
        );

        // Self-Check Builder
        add_submenu_page(
            'dmr-services',
            'Self-Check Builder',
            'Self-Check',
            'manage_options',
            'dmr-self-check',
            array($this, 'self_check_page')
        );

        // Submissions
        add_submenu_page(
            'dmr-services',
            'Submissions',
            'Submissions',
            'manage_options',
            'dmr-submissions',
            array($this, 'submissions_page')
        );

        // Settings
        add_submenu_page(
            'dmr-services',
            'Settings',
            'Settings',
            'manage_options',
            'dmr-settings',
            array($this, 'settings_page')
        );
    }

    public function dashboard_page()
    {
        if (!current_user_can('manage_options')) {
            wp_die(__('You do not have sufficient permissions to access this page.'));
        }

        echo '<div class="wrap">';
        echo '<h1>DMR Services Dashboard</h1>';
        echo '<p>Welcome to DMR Services. Use the menu to configure your Stress Check-In tool.</p>';
        echo '<div class="dmr-dashboard-cards">';
        echo '<div class="dmr-card">';
        echo '<h2>Self-Check Builder</h2>';
        echo '<p>Configure and publish your stress check-in questionnaire.</p>';
        echo '<a href="' . admin_url('admin.php?page=dmr-self-check') . '" class="button button-primary">Go to Builder</a>';
        echo '</div>';
        echo '<div class="dmr-card">';
        echo '<h2>Submissions</h2>';
        echo '<p>View and manage visitor submissions.</p>';
        echo '<a href="' . admin_url('admin.php?page=dmr-submissions') . '" class="button button-primary">View Submissions</a>';
        echo '</div>';
        echo '</div>';
        echo '</div>';
    }

    public function self_check_page()
    {
        $config_handler = new DMR_Self_Check_Config();
        $config_handler->render_page();
    }

    public function submissions_page()
    {
        $submissions_handler = new DMR_Submissions();
        $submissions_handler->render_page();
    }

    public function settings_page()
    {
        $settings_handler = new DMR_Settings();
        $settings_handler->render_page();
    }
}