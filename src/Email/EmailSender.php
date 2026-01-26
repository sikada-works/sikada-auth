<?php

namespace SikadaWorks\SikadaAuth\Email;

/**
 * Email Sender
 *
 * Sends emails using templates and custom headers.
 *
 * @package SikadaWorks\SikadaAuth\Email
 * @since 1.0.0
 */
class EmailSender
{
    /**
     * Send email using template
     *
     * @since 1.0.0
     * @param string $to            Recipient email
     * @param string $template_name Template name
     * @param array  $vars          Template variables
     * @return bool True on success, false on failure
     */
    public function send($to, $template_name, $vars = [])
    {
        $template = new EmailTemplate();

        // Get subject from filter
        $subject = apply_filters(
            "sikada_auth_email_subject_{$template_name}",
            $this->get_default_subject($template_name),
            $vars
        );

        // Render HTML version
        $html_content = $template->render($template_name, $vars, 'html');

        // Render plain text version
        $text_content = $template->render($template_name, $vars, 'text');

        // Use HTML if available, otherwise text
        $content = $html_content ? $html_content : $text_content;
        $content_type = $html_content ? 'text/html' : 'text/plain';

        // Set content type
        add_filter('wp_mail_content_type', function () use ($content_type) {
            return $content_type;
        });

        // Set custom headers
        $headers = $this->get_headers();

        // Send email
        $result = wp_mail($to, $subject, $content, $headers);

        // Reset content type
        remove_all_filters('wp_mail_content_type');

        return $result;
    }

    /**
     * Send admin alert
     *
     * @since 1.0.0
     * @param string $subject Subject line
     * @param array  $data    Alert data
     * @return bool True on success
     */
    public function send_admin_alert($subject, $data)
    {
        $recipients = get_option('sikada_auth_alert_recipients', get_option('admin_email'));
        $recipients = array_map('trim', explode(',', $recipients));

        $message = sprintf(
            __('Security Alert: %s', 'sikada-auth'),
            print_r($data, true)
        );

        $headers = $this->get_headers();

        foreach ($recipients as $recipient) {
            wp_mail($recipient, $subject, $message, $headers);
        }

        return true;
    }

    /**
     * Get email headers
     *
     * @since 1.0.0
     * @return array Email headers
     */
    private function get_headers()
    {
        $from_name = get_option('sikada_auth_email_from_name', get_bloginfo('name'));
        $from_email = get_option('sikada_auth_email_from_email', get_option('admin_email'));
        $reply_to = get_option('sikada_auth_email_reply_to', $from_email);

        return [
            "From: {$from_name} <{$from_email}>",
            "Reply-To: {$reply_to}",
        ];
    }

    /**
     * Get default subject for template
     *
     * @since 1.0.0
     * @param string $template_name Template name
     * @return string Default subject
     */
    private function get_default_subject($template_name)
    {
        $subjects = [
            'password-reset-request' => __('Password Reset Request', 'sikada-auth'),
            'password-changed' => __('Your Password Has Been Changed', 'sikada-auth'),
            'email-changed' => __('Your Email Address Has Been Changed', 'sikada-auth'),
            'admin-blocked-ip-alert' => __('Security Alert: Multiple Failed Login Attempts', 'sikada-auth'),
        ];

        return $subjects[$template_name] ?? __('Notification', 'sikada-auth');
    }
}
