<?php

namespace SikadaWorks\SikadaAuth\Redirect;

/**
 * URL Redirector
 *
 * Handles URL redirection from WordPress default login URLs to custom pages.
 *
 * @package SikadaWorks\SikadaAuth\Redirect
 * @since 1.0.0
 */
class URLRedirector
{
    /**
     * Initialize the URL redirector
     *
     * @since 1.0.0
     */
    public function init()
    {
        // Redirect wp-login.php to custom pages
        add_action('init', [$this, 'redirect_login_page'], 1);

        // NUCLEAR OPTION: Raw redirect for admin, bypassing auth_redirect
        add_action('init', [$this, 'raw_admin_redirect'], 1);

        // Filter login/logout/password reset URLs
        add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
        add_filter('logout_url', [$this, 'filter_logout_url'], 10, 2);
        add_filter('lostpassword_url', [$this, 'filter_lostpassword_url'], 10, 2);
        add_filter('register_url', [$this, 'filter_register_url'], 10);
    }

    /**
     * Raw redirect for admin pages to bypass environment issues
     *
     * @since 1.0.0
     */
    public function raw_admin_redirect()
    {
        // Only run on admin pages
        if (!is_admin()) {
            return;
        }

        // Allow AJAX
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // Check if user is logged in
        if (is_user_logged_in()) {
            return;
        }

        // Construct clean URL
        $custom_url = $this->get_login_page_url();
        if (!$custom_url) {
            return; // Let WP handle it
        }

        $path = wp_parse_url($custom_url, PHP_URL_PATH);
        $home = get_option('home');
        $home_parsed = wp_parse_url($home);
        $base = $home_parsed['scheme'] . '://' . $home_parsed['host'];
        $final_url = $base . $path; // Explicitly no port

        // Add redirect_to
        $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
        $final_url = add_query_arg('redirect_to', urlencode($current_url), $final_url);

        // RAW HEADER
        header("Location: " . $final_url);
        exit;
    }

    /**
     * Redirect wp-login.php to custom pages
     *
     * @since 1.0.0
     */
    public function redirect_login_page()
    {
        global $pagenow;

        // Only on wp-login.php
        if ($pagenow !== 'wp-login.php') {
            return;
        }

        // Don't redirect if already on custom page
        if ($this->is_custom_auth_page()) {
            return;
        }

        // Get action parameter
        $action = isset($_GET['action']) ? $_GET['action'] : 'login';

        $redirect_url = '';

        switch ($action) {
            case 'logout':
                // Process logout, then redirect
                $this->handle_logout();
                break;

            case 'lostpassword':
            case 'retrievepassword':
                // Redirect to password reset page
                $redirect_url = $this->get_password_reset_page_url();
                break;

            case 'rp':
            case 'resetpass':
                // Redirect to password reset page with key/login params
                $key = isset($_GET['key']) ? $_GET['key'] : '';
                $login = isset($_GET['login']) ? $_GET['login'] : '';
                $redirect_url = add_query_arg([
                    'key' => $key,
                    'login' => $login
                ], $this->get_password_reset_page_url());
                break;

            case 'register':
                // Redirect to registration page (Phase 2 - stub for now)
                $redirect_url = $this->get_login_page_url();
                break;

            default:
                // Redirect to login page
                $redirect_url = $this->get_login_page_url();

                // Preserve redirect_to parameter
                if (isset($_GET['redirect_to'])) {
                    $redirect_url = add_query_arg('redirect_to', urlencode($_GET['redirect_to']), $redirect_url);
                }
                break;
        }

        // Only redirect if we have a valid custom URL
        if (!empty($redirect_url) && $redirect_url !== false) {
            // Parse the constructed REDIRECT URL
            $parsed = wp_parse_url($redirect_url);
            $path = isset($parsed['path']) ? $parsed['path'] : '';
            $query = isset($parsed['query']) ? $parsed['query'] : '';

            // Reconstruct relative URL with query params if they exist
            if ($path) {
                $final_url = $path;
                if ($query) {
                    $final_url .= '?' . $query;
                }
            } else {
                $final_url = $redirect_url;
            }

            wp_redirect($final_url);
            exit;
        }
        // If no custom pages configured, let WordPress handle it normally
    }

    /**
     * Filter login URL
     *
     * @since 1.0.0
     * @param string $login_url    Login URL
     * @param string $redirect     Redirect URL
     * @param bool   $force_reauth Force reauthentication
     * @return string Filtered login URL
     */
    public function filter_login_url($login_url, $redirect, $force_reauth)
    {
        $custom_url = $this->get_login_page_url();

        // Only filter if custom page is configured
        if ($custom_url && $custom_url !== false) {
            // Force CLEAN ABSOLUTE URL to override any environment confusion
            $path = wp_parse_url($custom_url, PHP_URL_PATH);

            // Rebuild absolute URL using cleaned home_url (no port)
            $home = get_option('home'); // Raw DB value, usually safest
            $home_parsed = wp_parse_url($home);
            $base = $home_parsed['scheme'] . '://' . $home_parsed['host'];

            // Force CLEAN ABSOLUTE URL to override any environment confusion
            // Use raw home option to avoid port pollution
            $path = wp_parse_url($custom_url, PHP_URL_PATH);
            $home = get_option('home');
            $home_parsed = wp_parse_url($home);
            $base = $home_parsed['scheme'] . '://' . $home_parsed['host'];
            $login_url = $base . $path;

            if (!empty($redirect)) {
                $login_url = add_query_arg('redirect_to', urlencode($redirect), $login_url);
            }
        }

        return $login_url;
    }

    /**
     * Filter logout URL
     *
     * @since 1.0.0
     * @param string $logout_url Logout URL
     * @param string $redirect   Redirect URL
     * @return string Filtered logout URL
     */
    public function filter_logout_url($logout_url, $redirect)
    {
        // Keep default logout URL with nonce
        return $logout_url;
    }

    /**
     * Filter lost password URL
     *
     * @since 1.0.0
     * @param string $lostpassword_url Lost password URL
     * @param string $redirect         Redirect URL
     * @return string Filtered URL
     */
    public function filter_lostpassword_url($lostpassword_url, $redirect)
    {
        $custom_url = $this->get_password_reset_page_url();

        // Only filter if custom page is configured
        if ($custom_url && $custom_url !== false) {
            return $custom_url;
        }

        return $lostpassword_url;
    }

    /**
     * Filter register URL
     *
     * @since 1.0.0
     * @param string $register_url Register URL
     * @return string Filtered URL
     */
    public function filter_register_url($register_url)
    {
        // Phase 2 - will implement registration page
        return $register_url;
    }

    /**
     * Handle logout
     *
     * @since 1.0.0
     */
    private function handle_logout()
    {
        // Verify nonce
        if (!isset($_GET['_wpnonce']) || !wp_verify_nonce($_GET['_wpnonce'], 'log-out')) {
            wp_die(__('Security check failed', 'sikada-auth'));
        }

        // Log the logout
        if (is_user_logged_in()) {
            $user = wp_get_current_user();
            do_action('sikada_auth_before_logout', $user);

            // Log to database
            $logger = new \SikadaWorks\SikadaAuth\Auth\LoginLogger();
            $logger->log_attempt([
                'blog_id' => get_current_blog_id(),
                'user_login' => $user->user_login,
                'user_id' => $user->ID,
                'ip_address' => $this->get_user_ip(),
                'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
                'attempt_type' => 'logout',
                'status' => 'success',
                'failure_reason' => null,
            ]);
        }

        // Perform WordPress logout
        wp_logout();

        // Get redirect URL
        $redirect_url = $this->get_logout_redirect_url();

        // Allow filtering
        $redirect_url = apply_filters('sikada_auth_logout_redirect', $redirect_url);

        wp_safe_redirect($redirect_url);
        exit;
    }

    /**
     * Get login page URL
     *
     * @since 1.0.0
     * @return string Login page URL
     */
    private function get_login_page_url()
    {
        $page_id = get_option('sikada_auth_login_page');
        if ($page_id) {
            return get_permalink($page_id);
        }
        // Return false if not configured to prevent redirect loop
        return false;
    }

    /**
     * Get password reset page URL
     *
     * @since 1.0.0
     * @return string Password reset page URL
     */
    private function get_password_reset_page_url()
    {
        $page_id = get_option('sikada_auth_password_reset_page');
        if ($page_id) {
            return get_permalink($page_id);
        }
        // Return false if not configured to prevent redirect loop
        return false;
    }

    /**
     * Get logout redirect URL
     *
     * @since 1.0.0
     * @return string Logout redirect URL
     */
    private function get_logout_redirect_url()
    {
        $redirect_url = get_option('sikada_auth_logout_redirect');

        if (!$redirect_url) {
            $redirect_url = $this->get_login_page_url();
        }

        return $redirect_url;
    }

    /**
     * Check if already on custom auth page
     *
     * @since 1.0.0
     * @return bool True if on custom page
     */
    private function is_custom_auth_page()
    {
        global $post;

        if (!is_object($post)) {
            return false;
        }

        $login_page_id = get_option('sikada_auth_login_page');
        $reset_page_id = get_option('sikada_auth_password_reset_page');

        return in_array($post->ID, [$login_page_id, $reset_page_id]);
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
