<?php

namespace SikadaWorks\SikadaAuth\Auth;

use WP_User;
use WP_Error;

/**
 * Login Handler
 *
 * Handles user authentication and login processing.
 *
 * @package SikadaWorks\SikadaAuth\Auth
 * @since 1.0.0
 */
class LoginHandler
{
    /**
     * Initialize the login handler
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Register WordPress hooks
        add_filter('authenticate', [$this, 'authenticate'], 30, 3);
        add_action('wp_login', [$this, 'log_successful_login'], 10, 2);
        add_action('wp_login_failed', [$this, 'log_failed_login'], 10, 2);

        // AJAX handlers
        add_action('wp_ajax_nopriv_sikada_auth_login', [$this, 'handle_ajax_login']);
        add_action('wp_ajax_sikada_auth_login', [$this, 'handle_ajax_login']);
    }

    /**
     * Custom authentication filter
     *
     * @since 1.0.0
     * @param WP_User|WP_Error|null $user     WP_User if authenticated, WP_Error or null otherwise
     * @param string                $username Username or email address
     * @param string                $password User password
     * @return WP_User|WP_Error
     */
    public function authenticate($user, $username, $password)
    {
        // Skip if already authenticated or empty credentials
        if ($user instanceof WP_User || empty($username) || empty($password)) {
            return $user;
        }

        $ip = $this->get_user_ip();

        // Check rate limiting
        $rate_limiter = new RateLimiter();
        if ($rate_limiter->is_blocked($username, $ip)) {
            $time_remaining = $rate_limiter->get_lockout_time_remaining($username, $ip);
            $minutes = ceil($time_remaining / 60);

            // Log blocked attempt
            $this->log_blocked_attempt($username, $ip);

            return new WP_Error(
                'rate_limit_exceeded',
                sprintf(
                    __('Too many failed login attempts. Please try again in %d minutes.', 'sikada-auth'),
                    $minutes
                )
            );
        }

        // Allow other authentication to proceed
        return $user;
    }

    /**
     * Log successful login
     *
     * @since 1.0.0
     * @param string  $user_login Username
     * @param WP_User $user       WP_User object
     */
    public function log_successful_login($user_login, $user)
    {
        $logger = new LoginLogger();
        $logger->log_attempt([
            'blog_id' => get_current_blog_id(),
            'user_login' => $user_login,
            'user_id' => $user->ID,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'attempt_type' => 'login_success',
            'status' => 'success',
            'failure_reason' => null,
        ]);

        // Clear rate limit attempts on successful login
        $rate_limiter = new RateLimiter();
        $rate_limiter->clear_attempts($user_login, $this->get_user_ip());
    }

    /**
     * Log failed login
     *
     * @since 1.0.0
     * @param string   $username Username or email
     * @param WP_Error $error    WP_Error object
     */
    public function log_failed_login($username, $error)
    {
        $logger = new LoginLogger();
        $logger->log_attempt([
            'blog_id' => get_current_blog_id(),
            'user_login' => $username,
            'user_id' => null,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'attempt_type' => 'login_failed',
            'status' => 'failed',
            'failure_reason' => $error->get_error_code(),
        ]);

        // Record failed attempt for rate limiting
        $rate_limiter = new RateLimiter();
        $rate_limiter->check_and_record($username, $this->get_user_ip(), false);
    }

    /**
     * Log blocked login attempt
     *
     * @since 1.0.0
     * @param string $username Username or email
     * @param string $ip       IP address
     */
    private function log_blocked_attempt($username, $ip)
    {
        $logger = new LoginLogger();
        $logger->log_attempt([
            'blog_id' => get_current_blog_id(),
            'user_login' => $username,
            'user_id' => null,
            'ip_address' => $ip,
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'attempt_type' => 'blocked_rate_limit',
            'status' => 'blocked',
            'failure_reason' => 'rate_limit_exceeded',
        ]);
    }

    /**
     * Handle AJAX login request
     *
     * @since 1.0.0
     */
    public function handle_ajax_login()
    {
        // Verify nonce
        if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sikada_auth_nonce')) {
            wp_send_json_error([
                'message' => __('Security check failed', 'sikada-auth')
            ]);
        }

        // Get credentials
        $username = sanitize_text_field($_POST['username'] ?? '');
        $password = $_POST['password'] ?? '';
        $remember = isset($_POST['remember']);

        // Authenticate
        $user = wp_signon([
            'user_login' => $username,
            'user_password' => $password,
            'remember' => $remember
        ], is_ssl());

        if (is_wp_error($user)) {
            // Use generic error message for security (prevent user enumeration)
            $error_message = __('ERROR: Invalid username or password.', 'sikada-auth');

            // Still log the specific internal error code for admin auditing if needed
            // error_log('Login failed: ' . $user->get_error_code());

            wp_send_json_error(['message' => $error_message]);
        }

        // Get redirect URL
        $redirect_url = $this->get_redirect_url($user);

        wp_send_json_success([
            'message' => __('Login successful! Redirecting...', 'sikada-auth'),
            'redirect_url' => $redirect_url
        ]);
    }

    /**
     * Get redirect URL based on user role
     *
     * @since 1.0.0
     * @param WP_User $user WP_User object
     * @return string Redirect URL
     */
    private function get_redirect_url($user)
    {
        // Check for redirect_to parameter
        if (isset($_REQUEST['redirect_to'])) {
            return esc_url_raw($_REQUEST['redirect_to']);
        }

        // Get role-based redirect
        $roles = $user->roles;
        $role = !empty($roles) ? $roles[0] : 'subscriber';

        $redirect_url = get_option("sikada_auth_redirect_{$role}");

        if (!$redirect_url) {
            $redirect_url = get_option('sikada_auth_default_redirect', admin_url());
        }

        // Allow filtering
        return apply_filters('sikada_auth_login_redirect', $redirect_url, $user);
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
