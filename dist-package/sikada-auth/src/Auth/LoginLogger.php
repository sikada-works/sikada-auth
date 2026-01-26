<?php

namespace SikadaWorks\SikadaAuth\Auth;

/**
 * Login Logger
 *
 * Logs all authentication attempts to the database for security audit trail.
 *
 * @package SikadaWorks\SikadaAuth\Auth
 * @since 1.0.0
 */
class LoginLogger
{
    /**
     * Log login attempt
     *
     * @since 1.0.0
     * @param array $data Attempt data
     * @return int|false Insert ID on success, false on failure
     */
    public function log_attempt(array $data)
    {
        global $wpdb;

        $table = $wpdb->prefix . \SikadaWorks\SikadaAuth\Database\Schema::TABLE_LOGIN_ATTEMPTS;

        $defaults = [
            'blog_id' => get_current_blog_id(),
            'user_login' => '',
            'user_id' => null,
            'ip_address' => $this->get_user_ip(),
            'user_agent' => $_SERVER['HTTP_USER_AGENT'] ?? '',
            'attempt_type' => 'login_failed',
            'status' => 'failed',
            'failure_reason' => null,
        ];

        $data = wp_parse_args($data, $defaults);

        $result = $wpdb->insert($table, $data, [
            '%d', // blog_id
            '%s', // user_login
            '%d', // user_id
            '%s', // ip_address
            '%s', // user_agent
            '%s', // attempt_type
            '%s', // status
            '%s', // failure_reason
        ]);

        if ($result) {
            do_action('sikada_auth_login_attempt_logged', $wpdb->insert_id, $data);
            return $wpdb->insert_id;
        }

        return false;
    }

    /**
     * Get recent login attempts
     *
     * @since 1.0.0
     * @param array $args Query arguments
     * @return array Login attempts
     */
    public function get_recent_attempts($args = [])
    {
        global $wpdb;

        $defaults = [
            'blog_id' => get_current_blog_id(),
            'limit' => 100,
            'offset' => 0,
            'user_id' => null,
            'ip_address' => null,
            'status' => null,
            'orderby' => 'created_at',
            'order' => 'DESC',
        ];

        $args = wp_parse_args($args, $defaults);

        $table = $wpdb->prefix . \SikadaWorks\SikadaAuth\Database\Schema::TABLE_LOGIN_ATTEMPTS;

        // Build WHERE clause
        $where = ['blog_id = %d'];
        $where_values = [$args['blog_id']];

        if ($args['user_id']) {
            $where[] = 'user_id = %d';
            $where_values[] = $args['user_id'];
        }

        if ($args['ip_address']) {
            $where[] = 'ip_address = %s';
            $where_values[] = $args['ip_address'];
        }

        if ($args['status']) {
            $where[] = 'status = %s';
            $where_values[] = $args['status'];
        }

        $where_clause = implode(' AND ', $where);

        // Build query
        $query = "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY {$args['orderby']} {$args['order']} LIMIT %d OFFSET %d";
        $where_values[] = $args['limit'];
        $where_values[] = $args['offset'];

        $prepared_query = $wpdb->prepare($query, $where_values);

        return $wpdb->get_results($prepared_query);
    }

    /**
     * Get user login history
     *
     * @since 1.0.0
     * @param int $user_id User ID
     * @param int $limit   Number of records
     * @return array Login history
     */
    public function get_user_login_history($user_id, $limit = 10)
    {
        return $this->get_recent_attempts([
            'user_id' => $user_id,
            'attempt_type' => 'login_success',
            'limit' => $limit,
        ]);
    }

    /**
     * Cleanup old logs
     *
     * @since 1.0.0
     * @param int $days Number of days to keep
     * @return int|false Number of rows deleted
     */
    public function cleanup_old_logs($days = 90)
    {
        global $wpdb;

        $table = $wpdb->prefix . \SikadaWorks\SikadaAuth\Database\Schema::TABLE_LOGIN_ATTEMPTS;
        $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));

        return $wpdb->query($wpdb->prepare(
            "DELETE FROM {$table} WHERE created_at < %s",
            $date
        ));
    }

    /**
     * Get login statistics
     *
     * @since 1.0.0
     * @param int $blog_id Blog ID
     * @return array Statistics
     */
    public function get_stats($blog_id = null)
    {
        global $wpdb;

        if (is_null($blog_id)) {
            $blog_id = get_current_blog_id();
        }

        $table = $wpdb->prefix . \SikadaWorks\SikadaAuth\Database\Schema::TABLE_LOGIN_ATTEMPTS;

        $stats = [
            'total' => 0,
            'success' => 0,
            'failed' => 0,
            'blocked' => 0,
        ];

        // Get total count
        $stats['total'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE blog_id = %d",
            $blog_id
        ));

        // Get success count
        $stats['success'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE blog_id = %d AND status = 'success'",
            $blog_id
        ));

        // Get failed count
        $stats['failed'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE blog_id = %d AND status = 'failed'",
            $blog_id
        ));

        // Get blocked count
        $stats['blocked'] = (int) $wpdb->get_var($wpdb->prepare(
            "SELECT COUNT(*) FROM {$table} WHERE blog_id = %d AND status = 'blocked'",
            $blog_id
        ));

        return $stats;
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
