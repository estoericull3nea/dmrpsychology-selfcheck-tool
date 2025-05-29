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

    public function send_customer_notification($data)
    {
        $settings = get_option('dmr_settings', array());

        $to = $data['email'];
        $subject = 'Thank You for Your Stress Check-In Submission - DMR Psychological Services';

        $message = $this->build_customer_email_body($data);

        $headers = array(
            'Content-Type: text/html; charset=UTF-8',
            sprintf('From: %s <%s>', $settings['from_name'] ?? get_bloginfo('name'), $settings['from_email'] ?? get_option('admin_email'))
        );

        wp_mail($to, $subject, $message, $headers);
    }

    private function build_customer_email_body($data)
    {
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
                    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }

                .content {
                    background: #f9f9f9;
                    padding: 30px;
                }

                .section {
                    margin-bottom: 25px;
                }

                .section h2 {
                    color: #2e7d32;
                    border-bottom: 2px solid #2e7d32;
                    padding-bottom: 10px;
                }

                .score-box {
                    background: #fff;
                    border: 2px solid #2e7d32;
                    padding: 25px;
                    text-align: center;
                    margin: 25px 0;
                    border-radius: 8px;
                }

                .score-number {
                    font-size: 48px;
                    font-weight: bold;
                    color: #2e7d32;
                }

                .category {
                    display: inline-block;
                    padding: 8px 20px;
                    background: #2e7d32;
                    color: white;
                    border-radius: 5px;
                    margin-top: 15px;
                    font-size: 18px;
                    font-weight: 600;
                }

                .thank-you {
                    background: #e8f5e9;
                    padding: 20px;
                    border-left: 4px solid #2e7d32;
                    margin: 20px 0;
                    border-radius: 4px;
                }

                .footer {
                    background: linear-gradient(135deg, #e8f5e9 0%, #f1f8f4 100%);
                    padding: 25px 20px;
                    text-align: center;
                    font-size: 14px;
                    color: #1b5e20;
                    margin-top: 30px;
                    border-top: 3px solid #2e7d32;
                }
                
                .footer strong {
                    color: #2e7d32;
                    font-size: 16px;
                }
            </style>
        </head>

        <body>
            <div class="container">
                <div class="header">
                    <h1>Thank You for Your Submission</h1>
                </div>

                <div class="content">
                    <div class="thank-you">
                        <p style="font-size: 16px; margin: 0;"><strong>Dear
                                <?php echo esc_html($data['full_name']); ?>,</strong></p>
                        <p style="margin-top: 15px;">Thank you for completing the Stress Check-In assessment. We have received
                            your submission and appreciate you taking the time to assess your stress levels.</p>
                    </div>

                    <div class="section">
                        <p style="font-size: 15px; line-height: 1.8;">Our team has received your assessment and will review your responses. We may reach out to
                            you at the contact information you provided. If you have any questions or would like to schedule a
                            consultation, please don't hesitate to contact us.</p>
                    </div>

                    <div class="section">
                        <p style="color: #666; font-size: 14px;"><strong>Disclaimer:</strong> This self-check is for
                            informational purposes only and does not constitute a medical diagnosis or professional advice. If
                            you are experiencing severe distress, please contact a healthcare professional or crisis helpline.
                        </p>
                    </div>
                </div>

                <div class="footer">
                    <p><strong>DMR Psychological Services</strong></p>
                    <p>Thank you for trusting us with your mental health assessment.</p>
                </div>
            </div>
        </body>

        </html>
        <?php
        return ob_get_clean();
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
                    background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%);
                    color: white;
                    padding: 30px 20px;
                    text-align: center;
                }

                .content {
                    background: #f9f9f9;
                    padding: 30px;
                }

                .section {
                    margin-bottom: 25px;
                }

                .section h2 {
                    color: #2e7d32;
                    border-bottom: 2px solid #2e7d32;
                    padding-bottom: 10px;
                }

                table {
                    width: 100%;
                    border-collapse: collapse;
                    margin: 15px 0;
                }

                table td,
                table th {
                    padding: 10px;
                    text-align: left;
                    border-bottom: 1px solid #e8f5e9;
                }

                table th {
                    background: #e8f5e9;
                    color: #1b5e20;
                    font-weight: 600;
                }

                table tr:hover {
                    background: #f1f8f4;
                }

                .score-box {
                    background: #fff;
                    border: 2px solid #2e7d32;
                    padding: 25px;
                    text-align: center;
                    margin: 25px 0;
                    border-radius: 8px;
                }

                .score-number {
                    font-size: 48px;
                    font-weight: bold;
                    color: #2e7d32;
                }

                .category {
                    display: inline-block;
                    padding: 8px 20px;
                    background: #2e7d32;
                    color: white;
                    border-radius: 5px;
                    margin-top: 15px;
                    font-size: 18px;
                    font-weight: 600;
                }

                .info-box {
                    background: #e8f5e9;
                    border-left: 4px solid #2e7d32;
                    padding: 15px;
                    margin: 15px 0;
                    border-radius: 4px;
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
                                <td><a href="mailto:<?php echo esc_attr($data['email']); ?>" style="color: #2e7d32; text-decoration: none; font-weight: 600;"><?php echo esc_html($data['email']); ?></a></td>
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
                                style="background: linear-gradient(135deg, #2e7d32 0%, #1b5e20 100%); color: white; padding: 12px 25px; text-decoration: none; border-radius: 5px; display: inline-block; font-weight: 600; box-shadow: 0 2px 8px rgba(46, 125, 50, 0.3);">View
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