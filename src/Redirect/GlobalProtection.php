<?php

namespace SikadaWorks\SikadaAuth\Redirect;

/**
 * Global Site Protection
 *
 * Restricts access to the entire site for logged-out users.
 *
 * @package SikadaWorks\SikadaAuth\Redirect
 * @since 1.0.3
 */
class GlobalProtection
{
    /**
     * Initialize the protection service
     *
     * @since 1.0.3
     */
    public function init()
    {
        // Hook into template_redirect for Frontend protection
        add_action('template_redirect', [$this, 'restrict_frontend_access']);

        // Hook into REST API init for API protection
        add_filter('rest_authentication_errors', [$this, 'restrict_rest_api']);

        // Hook into admin_init for Admin protection
        add_action('admin_init', [$this, 'restrict_admin_access']);
    }

    /**
     * Restrict Frontend Access
     *
     * @since 1.0.3
     */
    public function restrict_frontend_access()
    {
        // 1. Check if enabled
        if (!get_option('sikada_auth_global_protection_enable')) {
            return;
        }

        // 2. Allow if logged in
        if (is_user_logged_in()) {
            return;
        }

        // 3. Allow wp-login.php (handled by WordPress naturally, but template_redirect doesn't run there anyway)
        // This check is mainly for extra safety if someone includes this logic elsewhere.

        // 4. Allow configured Login Page and Password Reset Page
        if ($this->is_allowed_page()) {
            return;
        }

        // 5. Allow Cron / AJAX / Feed?
        if (defined('DOING_CRON') && DOING_CRON) {
            return;
        }

        // 6. Redirect to Login Page
        $login_url = $this->get_login_url();

        // Prevent redirect loop if we are somehow already attempting to go there but didn't catch it
        // (wp_redirect protects against some loops, but we should be explicit)

        wp_safe_redirect($login_url);
        exit;
    }

    /**
     * Restrict REST API Access
     *
     * @since 1.0.3
     * @param mixed $result Current authentication result
     * @return mixed Modified result or WP_Error
     */
    public function restrict_rest_api($result)
    {
        // If a previous authentication check failed, pass it through
        if (is_wp_error($result)) {
            return $result;
        }

        // Check if Global Protection AND Block API are enabled
        if (!get_option('sikada_auth_global_protection_enable') || !get_option('sikada_auth_global_protection_block_api')) {
            return $result;
        }

        // Allow if logged in
        if (is_user_logged_in()) {
            return $result;
        }

        // Allow public endpoints? Alternatively, we block everything.
        // For strict "Site Lock", we block everything.
        // Special case: Allow the Contact Form 7 or similar public endpoints? 
        // For now, blocking all.

        return new \WP_Error(
            'rest_forbidden',
            __('REST API access is restricted to authenticated users.', 'sikada-auth'),
            ['status' => 401]
        );
    }

    /**
     * Restrict Admin Access
     *
     * @since 1.0.3
     */
    public function restrict_admin_access()
    {
        // Check if Global Protection AND Block Admin are enabled
        if (!get_option('sikada_auth_global_protection_enable') || !get_option('sikada_auth_global_protection_block_admin')) {
            return;
        }

        // Allow if logged in
        if (is_user_logged_in()) {
            return;
        }

        // Allow AJAX
        if (defined('DOING_AJAX') && DOING_AJAX) {
            return;
        }

        // Redirect to Login Page
        $login_url = $this->get_login_url();
        wp_safe_redirect($login_url);
        exit;
    }

	/**
	 * Check if current page is in the allowlist
	 *
	 * @since 1.0.3
	 * @return bool
	 */
	private function is_allowed_page()
	{
		global $post;

		if (!is_object($post)) {
			return false;
		}

		$login_page_id = (int) get_option('sikada_auth_login_page');
		$reset_page_id = (int) get_option('sikada_auth_password_reset_page');
		$registration_page_id = (int) get_option('sikada_passwordless_registration_page');
		$thank_you_page_id = (int) get_option('sikada_passwordless_registration_submitted_redirect');

		// Allow Login Page
		if ($login_page_id && is_page($login_page_id)) {
			return true;
		}

		// Allow Password Reset Page
		if ($reset_page_id && is_page($reset_page_id)) {
			return true;
		}
		
		// Allow Registration Request Page (from Passwordless Login plugin)
		if ($registration_page_id && is_page($registration_page_id)) {
			return true;
		}
		
		// Allow Thank You Page (from Passwordless Login plugin)
		if ($thank_you_page_id && is_page($thank_you_page_id)) {
			return true;
		}
		
		// Allow filtering for extensions to add their own pages
		return apply_filters('sikada_auth_global_protection_is_allowed_page', false, $post);
	}

    /**
     * Get target login URL
     *
     * @since 1.0.3
     * @return string
     */
    private function get_login_url()
    {
        $page_id = get_option('sikada_auth_login_page');
        if ($page_id) {
            $url = get_permalink($page_id);
            // Append redirect_to to send them back where they came from
            $current_url = (is_ssl() ? 'https://' : 'http://') . $_SERVER['HTTP_HOST'] . $_SERVER['REQUEST_URI'];
            return add_query_arg('redirect_to', urlencode($current_url), $url);
        }
        return wp_login_url();
    }
}
