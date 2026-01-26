<?php

namespace SikadaWorks\SikadaAuth\Auth;

/**
 * Password Reset Handler
 *
 * Handles password reset requests and form submissions.
 *
 * @package SikadaWorks\SikadaAuth\Auth
 * @since 1.0.0
 */
class PasswordResetHandler
{
    /**
     * Initialize the password reset handler
     *
     * @since 1.0.0
     */
    public function init()
    {
        // AJAX handlers
        add_action('wp_ajax_nopriv_sikada_auth_reset_request', [$this, 'handle_ajax_reset_request']);
        add_action('wp_ajax_nopriv_sikada_auth_reset_password', [$this, 'handle_ajax_reset_password']);

        // WordPress hooks
        add_action('password_reset', [$this, 'log_password_reset'], 10, 2);
    }

    /**
     * Handle AJAX password reset request
     *
     * @since 1.0.0
     */
    public function handle_ajax_reset_request()
    {
        // Verify nonce
        check_ajax_referer('sikada_auth_nonce', 'nonce');

        $user_login = sanitize_text_field($_POST['user_login'] ?? '');

        // Get user by email or username
        $user = get_user_by('email', $user_login);
        if (!$user) {
            $user = get_user_by('login', $user_login);
        }

        if (!$user) {
            wp_send_json_error([
                'message' => __('Invalid email or username.', 'sikada-auth')
            ]);
        }

        // Generate reset key
        $key = get_password_reset_key($user);

        if (is_wp_error($key)) {
            wp_send_json_error([
                'message' => $key->get_error_message()
            ]);
        }

        // Send email
        $email_sender = new \SikadaWorks\SikadaAuth\Email\EmailSender();
        $reset_url = add_query_arg([
            'key' => $key,
            'login' => rawurlencode($user->user_login)
        ], $this->get_reset_page_url());

        $email_sender->send($user->user_email, 'password-reset-request', [
            'user_login' => $user->user_login,
            'user_email' => $user->user_email,
            'user_display_name' => $user->display_name,
            'reset_link' => $reset_url,
            'reset_key' => $key,
            'expiration_time' => '24 hours'
        ]);

        // Log attempt
        $logger = new LoginLogger();
        $logger->log_attempt([
            'blog_id' => get_current_blog_id(),
            'user_login' => $user->user_login,
            'user_id' => $user->ID,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'attempt_type' => 'password_reset_request',
            'status' => 'success',
            'failure_reason' => null,
        ]);

        wp_send_json_success([
            'message' => __('Check your email for the password reset link.', 'sikada-auth')
        ]);
    }

    /**
     * Handle AJAX password reset form submission
     *
     * @since 1.0.0
     */
    public function handle_ajax_reset_password()
    {
        // Verify nonce
        check_ajax_referer('sikada_auth_nonce', 'nonce');

        $key = sanitize_text_field($_POST['key'] ?? '');
        $login = sanitize_text_field($_POST['login'] ?? '');
        $password = $_POST['password'] ?? '';

        // Validate reset key
        $user = check_password_reset_key($key, $login);

        if (is_wp_error($user)) {
            wp_send_json_error([
                'message' => __('Invalid or expired reset link.', 'sikada-auth')
            ]);
        }

        // Validate password strength (will implement in Step 04)
        // For now, just check if password is not empty
        if (empty($password)) {
            wp_send_json_error([
                'message' => __('Password cannot be empty.', 'sikada-auth')
            ]);
        }

        // Reset password
        reset_password($user, $password);

        // Log attempt
        $logger = new LoginLogger();
        $logger->log_attempt([
            'blog_id' => get_current_blog_id(),
            'user_login' => $user->user_login,
            'user_id' => $user->ID,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'attempt_type' => 'password_reset_completed',
            'status' => 'success',
            'failure_reason' => null,
        ]);

        wp_send_json_success([
            'message' => __('Password reset successfully! Redirecting to login...', 'sikada-auth')
        ]);
    }

    /**
     * Log password reset
     *
     * @since 1.0.0
     * @param WP_User $user     WP_User object
     * @param string  $new_pass New password
     */
    public function log_password_reset($user, $new_pass)
    {
        // This hook fires when password is reset via WordPress core
        // We log it for audit trail
        do_action('sikada_auth_password_changed', $user);
    }

    /**
     * Get password reset page URL
     *
     * @since 1.0.0
     * @return string Password reset page URL
     */
    private function get_reset_page_url()
    {
        $page_id = get_option('sikada_auth_password_reset_page');
        if ($page_id) {
            return get_permalink($page_id);
        }
        // Fallback to wp-login.php
        return wp_lostpassword_url();
    }

    /**
     * Get user IP address
     *
     * @since 1.0.0
     * @return string IP address
     */
    private function get_user_ip()
    {
        $ip = '';

        if (!empty($_SERVER['HTTP_CLIENT_IP'])) {
            $ip = $_SERVER['HTTP_CLIENT_IP'];
        } elseif (!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) {
            $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
        } else {
            $ip = $_SERVER['REMOTE_ADDR'] ?? '';
        }

        return sanitize_text_field($ip);
    }
}
