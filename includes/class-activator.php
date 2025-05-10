<?php

if (!defined('ABSPATH')) {
    exit;
}

class DMR_Activator
{

    public static function activate()
    {
        global $wpdb;

        $table_name = $wpdb->prefix . 'dmr_self_checks';
        $charset_collate = $wpdb->get_charset_collate();

        $sql = "CREATE TABLE $table_name (
            id BIGINT UNSIGNED NOT NULL AUTO_INCREMENT,
            user_id BIGINT UNSIGNED NULL,
            tool_type VARCHAR(50) NOT NULL DEFAULT 'stress_check',
            quiz_answers LONGTEXT NOT NULL,
            full_name VARCHAR(190) NOT NULL,
            email VARCHAR(190) NOT NULL,
            phone VARCHAR(50) NULL,
            notes TEXT NULL,
            score INT NULL,
            category VARCHAR(50) NULL,
            recommendation VARCHAR(190) NULL,
            status VARCHAR(30) NOT NULL DEFAULT 'submitted',
            created_at DATETIME NOT NULL,
            updated_at DATETIME NOT NULL,
            PRIMARY KEY  (id),
            KEY user_id (user_id),
            KEY email (email),
            KEY status (status),
            KEY created_at (created_at)
        ) $charset_collate;";

        require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
        dbDelta($sql);

        // Set default configuration
        self::set_default_config();
    }

    private static function set_default_config()
    {
        $default_config = array(
            'published' => false,
            'title' => 'Stress Level Self-Check',
            'intro' => 'Take a few moments to reflect on your recent experiences. Answer honestly about how often these situations have applied to you.',
            'questions' => array(
                'In the last month, how often have you felt upset because of something unexpected?',
                'In the last month, how often have you felt unable to control important things in your life?',
                'In the last month, how often have you felt nervous and stressed?',
                'In the last month, how often have you felt confident about handling personal problems?',
                'In the last month, how often have you felt things were going your way?',
                'In the last month, how often have you found that you could not cope with all the things you had to do?',
                'In the last month, how often have you been able to control irritations in your life?',
                'In the last month, how often have you felt on top of things?',
                'In the last month, how often have you been angered by things outside your control?',
                'In the last month, how often have you felt difficulties were piling up so high you could not overcome them?'
            ),
            'reversed_items' => array(4, 5, 7, 8),
            'ranges' => array(
                'low' => array('min' => 0, 'max' => 13, 'label' => 'Low Stress'),
                'moderate' => array('min' => 14, 'max' => 26, 'label' => 'Moderate Stress'),
                'high' => array('min' => 27, 'max' => 40, 'label' => 'High Perceived Stress')
            ),
            'recommendations' => array(
                'low' => 'Great job managing your stress! Consider signing up for our wellness newsletter for ongoing tips and support.',
                'moderate' => 'You may benefit from additional support. Consider booking a consultation to explore stress management strategies.',
                'high' => 'We recommend reaching out for personalized support. Consider scheduling a session to discuss effective coping strategies.'
            ),
            'fields' => array(
                'full_name' => array('required' => true, 'enabled' => true),
                'email' => array('required' => true, 'enabled' => true),
                'phone' => array('required' => false, 'enabled' => true),
                'notes' => array('required' => false, 'enabled' => true),
                'newsletter' => array('enabled' => true)
            ),
            'consent_text' => 'I agree to be contacted about my results and understand this is for informational purposes only.'
        );

        add_option('dmr_self_check_config', $default_config);
    }
}