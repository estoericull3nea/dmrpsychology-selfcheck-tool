<?php
/**
 * Plugin Name: DMR Services
 * Plugin URI: https://example.com
 * Description: Interactive Stress Check-In tool with admin builder, submissions management, and email notifications
 * Version: 1.0.0
 * Author: Your Name
 * Text Domain: dmr-services
 * Domain Path: /languages
 */


if (!defined('ABSPATH')) {
    exit;
}

// Define plugin constants
define('DMR_VERSION', '1.0.0');
define('DMR_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('DMR_PLUGIN_URL', plugin_dir_url(__FILE__));
define('DMR_PLUGIN_BASENAME', plugin_basename(__FILE__));

// Include required files
require_once DMR_PLUGIN_DIR . 'includes/class-activator.php';
require_once DMR_PLUGIN_DIR . 'includes/class-admin-menu.php';
require_once DMR_PLUGIN_DIR . 'includes/class-self-check-config.php';
require_once DMR_PLUGIN_DIR . 'includes/class-frontend-shortcode.php';
require_once DMR_PLUGIN_DIR . 'includes/class-submissions.php';
require_once DMR_PLUGIN_DIR . 'includes/class-settings.php';
require_once DMR_PLUGIN_DIR . 'includes/class-mailer.php';

// Activation hook
register_activation_hook(__FILE__, array('DMR_Activator', 'activate'));

// Initialize plugin
function dmr_services_init()
{
    // Initialize admin menu
    if (is_admin()) {
        new DMR_Admin_Menu();
        new DMR_Self_Check_Config();
        new DMR_Submissions();
        new DMR_Settings();
    }

    // Initialize frontend
    new DMR_Frontend_Shortcode();
    new DMR_Mailer();
}
add_action('plugins_loaded', 'dmr_services_init');

// Enqueue admin assets
function dmr_admin_assets($hook)
{
    if (strpos($hook, 'dmr-services') === false) {
        return;
    }

    wp_enqueue_style('dmr-admin-css', DMR_PLUGIN_URL . 'assets/admin.css', array(), DMR_VERSION);
    wp_enqueue_script('dmr-admin-js', DMR_PLUGIN_URL . 'assets/admin.js', array('jquery'), DMR_VERSION, true);

    wp_localize_script('dmr-admin-js', 'dmrAdmin', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dmr_admin_nonce')
    ));
}
add_action('admin_enqueue_scripts', 'dmr_admin_assets');

// Enqueue public assets
function dmr_public_assets()
{
    wp_enqueue_style('dmr-public-css', DMR_PLUGIN_URL . 'assets/public.css', array(), DMR_VERSION);
    wp_enqueue_script('dmr-public-js', DMR_PLUGIN_URL . 'assets/public.js', array('jquery'), DMR_VERSION, true);

    wp_localize_script('dmr-public-js', 'dmrAjax', array(
        'ajaxurl' => admin_url('admin-ajax.php'),
        'nonce' => wp_create_nonce('dmr_ajax_nonce')
    ));
}
add_action('wp_enqueue_scripts', 'dmr_public_assets');

// Configure SMTP for PHPMailer
function dmr_configure_smtp($phpmailer)
{
    $settings = get_option('dmr_settings', array());
    
    // Only configure if SMTP is enabled
    if (!($settings['smtp_enabled'] ?? false)) {
        return;
    }
    
    // Get SMTP settings
    $smtp_host = $settings['smtp_host'] ?? '';
    $smtp_port = $settings['smtp_port'] ?? 587;
    $smtp_encryption = $settings['smtp_encryption'] ?? 'tls';
    $smtp_username = $settings['smtp_username'] ?? '';
    $smtp_password = $settings['smtp_password'] ?? '';
    
    // Validate required settings
    if (empty($smtp_host) || empty($smtp_username) || empty($smtp_password)) {
        return;
    }
    
    // Configure PHPMailer to use SMTP
    $phpmailer->isSMTP();
    $phpmailer->Host = $smtp_host;
    $phpmailer->SMTPAuth = true;
    $phpmailer->Port = $smtp_port;
    $phpmailer->Username = $smtp_username;
    $phpmailer->Password = $smtp_password;
    
    // Set encryption
    if ($smtp_encryption === 'ssl') {
        $phpmailer->SMTPSecure = 'ssl';
    } elseif ($smtp_encryption === 'tls') {
        $phpmailer->SMTPSecure = 'tls';
    }
    
    // Additional settings for better compatibility
    $phpmailer->SMTPAutoTLS = false;
    $phpmailer->CharSet = 'UTF-8';
}
add_action('phpmailer_init', 'dmr_configure_smtp');