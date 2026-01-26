<?php

namespace SikadaWorks\SikadaAuth\Auth;

/**
 * Rate Limiter
 *
 * Implements rate limiting for login attempts with progressive lockouts.
 *
 * @package SikadaWorks\SikadaAuth\Auth
 * @since 1.0.0
 */
class RateLimiter
{
    /**
     * Maximum attempts per username
     *
     * @var int
     */
    private $username_limit = 5;

    /**
     * Maximum attempts per IP
     *
     * @var int
     */
    private $ip_limit = 10;

    /**
     * Time window in seconds
     *
     * @var int
     */
    private $time_window = 900; // 15 minutes

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        // Load settings
        $this->username_limit = (int) get_option('sikada_auth_rate_limit_username', 5);
        $this->ip_limit = (int) get_option('sikada_auth_rate_limit_ip', 10);
    }

    /**
     * Check if username or IP is blocked
     *
     * @since 1.0.0
     * @param string $username Username
     * @param string $ip       IP address
     * @return bool True if blocked
     */
    public function is_blocked($username, $ip)
    {
        // Check IP whitelist first
        if ($this->is_ip_whitelisted($ip)) {
            return false;
        }

        // Check both username and IP lockouts
        return $this->is_username_locked($username) || $this->is_ip_locked($ip);
    }

    /**
     * Check and record login attempt
     *
     * @since 1.0.0
     * @param string $username Username
     * @param string $ip       IP address
     * @param bool   $success  Whether login was successful
     * @return bool True if allowed, false if blocked
     */
    public function check_and_record($username, $ip, $success)
    {
        if ($success) {
            // Clear attempts on successful login
            $this->clear_attempts($username, $ip);
            return true;
        }

        // Record failed attempt
        $this->record_attempt($username, $ip);

        // Check if limits exceeded
        $username_count = $this->get_attempt_count($username, 'username');
        $ip_count = $this->get_attempt_count($ip, 'ip');

        if ($username_count >= $this->username_limit) {
            $this->set_lockout($username, 'username');
        }

        if ($ip_count >= $this->ip_limit) {
            $this->set_lockout($ip, 'ip');
        }

        return !$this->is_blocked($username, $ip);
    }

    /**
     * Get attempt count
     *
     * @since 1.0.0
     * @param string $identifier Username or IP
     * @param string $type       'username' or 'ip'
     * @return int Attempt count
     */
    private function get_attempt_count($identifier, $type)
    {
        $key = "sikada_auth_attempts_{$type}_{$identifier}";
        return (int) get_transient($key);
    }

    /**
     * Record failed attempt
     *
     * @since 1.0.0
     * @param string $username Username
     * @param string $ip       IP address
     */
    private function record_attempt($username, $ip)
    {
        // Increment username attempts
        $username_key = "sikada_auth_attempts_username_{$username}";
        $username_count = (int) get_transient($username_key);
        set_transient($username_key, $username_count + 1, $this->time_window);

        // Increment IP attempts
        $ip_key = "sikada_auth_attempts_ip_{$ip}";
        $ip_count = (int) get_transient($ip_key);
        set_transient($ip_key, $ip_count + 1, $this->time_window);
    }

    /**
     * Set lockout
     *
     * @since 1.0.0
     * @param string $identifier Username or IP
     * @param string $type       'username' or 'ip'
     */
    private function set_lockout($identifier, $type)
    {
        // Get violation count
        $violation_key = "sikada_auth_violations_{$type}_{$identifier}";
        $violations = (int) get_transient($violation_key);
        $violations++;

        // Calculate lockout duration (progressive)
        $duration = $this->calculate_lockout_duration($violations, $type);

        // Set lockout
        $lockout_key = "sikada_auth_lockout_{$type}_{$identifier}";
        set_transient($lockout_key, time() + $duration, $duration);

        // Update violation count (expires after 24 hours)
        set_transient($violation_key, $violations, DAY_IN_SECONDS);

        // Send admin alert if configured
        $this->maybe_send_admin_alert($identifier, $type, $violations);
    }

    /**
     * Calculate lockout duration
     *
     * @since 1.0.0
     * @param int    $violations Violation count
     * @param string $type       'username' or 'ip'
     * @return int Duration in seconds
     */
    private function calculate_lockout_duration($violations, $type)
    {
        $base_duration = ($type === 'username') ? 900 : 1800; // 15min or 30min

        // Progressive: 1x, 2x, 4x, 8x
        $multiplier = pow(2, min($violations - 1, 3));

        return $base_duration * $multiplier;
    }

    /**
     * Check if username is locked
     *
     * @since 1.0.0
     * @param string $username Username
     * @return bool True if locked
     */
    private function is_username_locked($username)
    {
        $key = "sikada_auth_lockout_username_{$username}";
        return (bool) get_transient($key);
    }

    /**
     * Check if IP is locked
     *
     * @since 1.0.0
     * @param string $ip IP address
     * @return bool True if locked
     */
    private function is_ip_locked($ip)
    {
        $key = "sikada_auth_lockout_ip_{$ip}";
        return (bool) get_transient($key);
    }

    /**
     * Clear attempts
     *
     * @since 1.0.0
     * @param string $username Username
     * @param string $ip       IP address
     */
    public function clear_attempts($username, $ip)
    {
        delete_transient("sikada_auth_attempts_username_{$username}");
        delete_transient("sikada_auth_attempts_ip_{$ip}");
    }

    /**
     * Check if IP is whitelisted
     *
     * @since 1.0.0
     * @param string $ip IP address
     * @return bool True if whitelisted
     */
    private function is_ip_whitelisted($ip)
    {
        $whitelist = get_option('sikada_auth_ip_whitelist', '');
        $whitelist_ips = array_map('trim', explode(',', $whitelist));

        return in_array($ip, $whitelist_ips);
    }

    /**
     * Maybe send admin alert
     *
     * @since 1.0.0
     * @param string $identifier Username or IP
     * @param string $type       'username' or 'ip'
     * @param int    $violations Violation count
     */
    private function maybe_send_admin_alert($identifier, $type, $violations)
    {
        // Check if alerts enabled
        if (!get_option('sikada_auth_enable_admin_alerts', false)) {
            return;
        }

        // Check threshold
        $threshold = (int) get_option('sikada_auth_alert_threshold', 3);
        if ($violations < $threshold) {
            return;
        }

        // Send alert email (will implement in Step 06)
        do_action('sikada_auth_rate_limit_alert', $identifier, $type, $violations);
    }

    /**
     * Get lockout time remaining
     *
     * @since 1.0.0
     * @param string $username Username
     * @param string $ip       IP address
     * @return int Seconds remaining
     */
    public function get_lockout_time_remaining($username, $ip)
    {
        $username_lockout = get_transient("sikada_auth_lockout_username_{$username}");
        $ip_lockout = get_transient("sikada_auth_lockout_ip_{$ip}");

        $max_lockout = max($username_lockout, $ip_lockout);

        if ($max_lockout) {
            return $max_lockout - time();
        }

        return 0;
    }
}
