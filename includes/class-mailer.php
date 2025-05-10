<?php
// File: includes/class-mailer.php

if (!defined('ABSPATH')) {
    exit;
}

class DMR_Mailer
{

    public function send_admin_notification($data)
    {
        $settings = get_option('dmr_settings', array());

        if (!($settings['enable_notifications'] ?? true)) {
            return;
        }

        $to = $settings['admin_email'] ?? get_option('admin_email');
        $subject = sprintf('New DMR Stress Check-In Result: %s', $data['full_name']);

        $message = $this->build_email_body($data);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $settings['from_name'] ?? get_bloginfo('name'), $settings['from_email'] ?? get_option('admin_email'))
        );

        wp_mail($to, $subject, $message, $headers);
    }

    private function build_email_body($data)
    {
        $scale_labels = array('Never', 'Almost Never', 'Sometimes', 'Fairly Often', 'Very Often');

        ob_start();
        ?>
        <!DOCTYPE html>
        <html>

        <head>
            <meta charset="UTF-8">
            <style>
                body {
                    font-family: Arial, sans-serif;
                    line-height: 1.6;
                    color: #333;
                }

                .container {
                    max-width: 600px;
                    margin: 0 auto;
                    padding: 20px;
                }

                .header {
                    background: #0073aa;
                    color: white;
                    padding: 20px;
                    text-align: center;
                }

                .content {
                    background: #f9f9f9;
                    padding: 20px;
                }

                .section {
                    margin-bottom: 20px;
                }

                .section h2 {
                    color: #0073aa;
                    border-bottom: 2px solid #0073aa;
                    padding-bottom: 5px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                }

                table td,
                table th {
                    padding: 8px;
                    text-align: left;
                    border-bottom: 1px solid #ddd;
                }

                .score-box {
                    background: #fff;
                    border: 2px solid #0073aa;
                    padding: 15px;
                    text-align: center;
                    margin: 20px 0;
                }

                .score-number {
                    font-size: 48px;
                    font-weight: bold;
                    color: #0073aa;
                }

                .category {
                    display: inline-block;
                    padding: 5px 15px;
                    background: #0073aa;
                    color: white;
                    border-radius: 3px;
                    margin-top: 10px;
                }
            </style>
        </head>

        <body>
            <div class="container">
                <div class="header">
                    <h1>New Stress Check-In Submission</h1>
                </div>

                <div class="content">
                    <div class="section">
                        <p><strong>Submission Date:</strong> <?php echo date('F j, Y g:i A'); ?></p>
                    </div>

                    <div class="section">
                        <h2>Contact Information</h2>
                        <table>
                            <tr>
                                <td><strong>Name:</strong></td>
                                <td><?php echo esc_html($data['full_name']); ?></td>
                            </tr>
                            <tr>
                                <td><strong>Email:</strong></td>
                                <td><a
                                        href="mailto:<?php echo esc_attr($data['email']); ?>"><?php echo esc_html($data['email']); ?></a>
                                </td>
                            </tr>
                            <?php if (!empty($data['phone'])): ?>
                                <tr>
                                    <td><strong>Phone:</strong></td>
                                    <td><?php echo esc_html($data['phone']); ?></td>
                                </tr>
                            <?php endif; ?>
                            <?php if (!empty($data['notes'])): ?>
                                <tr>
                                    <td><strong>Notes:</strong></td>
                                    <td><?php echo nl2br(esc_html($data['notes'])); ?></td>
                                </tr>
                            <?php endif; ?>
                            <tr>
                                <td><strong>Newsletter Opt-In:</strong></td>
                                <td><?php echo $data['newsletter_opt_in'] ? 'Yes' : 'No'; ?></td>
                            </tr>
                        </table>
                    </div>

                    <div class="section">
                        <h2>Results</h2>
                        <div class="score-box">
                            <div class="score-number"><?php echo esc_html($data['score']); ?>/40</div>
                            <div class="category"><?php echo esc_html($data['category']); ?></div>
                        </div>
                        <p><strong>Recommendation:</strong> <?php echo esc_html($data['recommendation']); ?></p>
                    </div>

                    <div class="section">
                        <h2>Quiz Answers</h2>
                        <table>
                            <thead>
                                <tr>
                                    <th>#</th>
                                    <th>Question</th>
                                    <th>Response</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($data['answers'] as $index => $value): ?>
                                    <tr>
                                        <td><?php echo ($index + 1); ?></td>
                                        <td><?php echo esc_html($data['questions'][$index] ?? 'Question ' . ($index + 1)); ?></td>
                                        <td><?php echo esc_html($value . ' - ' . $scale_labels[$value]); ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>

                    <div class="section">
                        <p><a href="<?php echo admin_url('admin.php?page=dmr-submissions'); ?>"
                                style="background: #0073aa; color: white; padding: 10px 20px; text-decoration: none; border-radius: 3px; display: inline-block;">View
                                in Dashboard</a></p>
                    </div>
                </div>
            </div>
        </body>

        </html>
        <?php
        return ob_get_clean();
    }
}