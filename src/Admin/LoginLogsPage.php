<?php

namespace SikadaWorks\SikadaAuth\Admin;

if (!class_exists('WP_List_Table')) {
    require_once(ABSPATH . 'wp-admin/includes/class-wp-list-table.php');
}

use SikadaWorks\SikadaAuth\Auth\LoginLogger;

/**
 * Login Logs Page
 *
 * Implements WP_List_Table to display login attempts with filtering and pagination.
 *
 * @package SikadaWorks\SikadaAuth\Admin
 * @since 1.0.0
 */
class LoginLogsPage extends \WP_List_Table
{
    /**
     * Login logger instance
     *
     * @var LoginLogger
     */
    private $logger;

    /**
     * Constructor
     *
     * @since 1.0.0
     */
    public function __construct()
    {
        parent::__construct([
            'singular' => __('Login Log', 'sikada-auth'),
            'plural' => __('Login Logs', 'sikada-auth'),
            'ajax' => false,
        ]);

        $this->logger = new LoginLogger();
    }

    /**
     * Get columns
     *
     * @since 1.0.0
     * @return array Columns
     */
    public function get_columns()
    {
        return [
            'created_at' => __('Date/Time', 'sikada-auth'),
            'user_login' => __('Username', 'sikada-auth'),
            'ip_address' => __('IP Address', 'sikada-auth'),
            'attempt_type' => __('Type', 'sikada-auth'),
            'status' => __('Status', 'sikada-auth'),
            'failure_reason' => __('Reason', 'sikada-auth'),
        ];
    }

    /**
     * Get sortable columns
     *
     * @since 1.0.0
     * @return array Sortable columns
     */
    public function get_sortable_columns()
    {
        return [
            'created_at' => ['created_at', true], // True means already sorted desc
            'user_login' => ['user_login', false],
            'ip_address' => ['ip_address', false],
            'status' => ['status', false],
            'attempt_type' => ['attempt_type', false],
        ];
    }

    /**
     * Prepare items for display
     *
     * @since 1.0.0
     */
    public function prepare_items()
    {
        $columns = $this->get_columns();
        $hidden = [];
        $sortable = $this->get_sortable_columns();

        $this->_column_headers = [$columns, $hidden, $sortable];

        // Handle ordering
        $orderby = isset($_GET['orderby']) ? sanitize_sql_orderby($_GET['orderby']) : 'created_at';
        $order = isset($_GET['order']) ? sanitize_text_field($_GET['order']) : 'DESC';

        // Handle pagination
        $per_page = 20;
        $current_page = $this->get_pagenum();
        $offset = ($current_page - 1) * $per_page;

        // Handle filtering
        $filters = [
            'limit' => $per_page,
            'offset' => $offset,
            'orderby' => $orderby,
            'order' => $order,
        ];

        // Basic filtering (can be expanded)
        // Note: The LoginLogger needs to support searching/filtering if we add search box UI
        // user_id, ip_address, status could be passed here if UI inputs exist

        // Get items
        $this->items = $this->logger->get_recent_attempts($filters);

        // Get total items for pagination
        // Note: LoginLogger needs a count method or we do a separate count query.
        // For now, let's use get_stats or assume a count method exists/should be added.
        // Using stats for total count approximation or need a count query.
        // Let's assume we implement a count method in Logger or just use get_stats()['total'] 
        // if filtering isn't active, but with filtering we need a specific count.

        // For MVP, using stats['total'] if no filters applied.
        $stats = $this->logger->get_stats();
        $total_items = $stats['total'];

        $this->set_pagination_args([
            'total_items' => $total_items,
            'per_page' => $per_page,
            'total_pages' => ceil($total_items / $per_page),
        ]);
    }

    /**
     * Column default
     *
     * @since 1.0.0
     * @param object $item        Row item
     * @param string $column_name Column name
     * @return string Column content
     */
    public function column_default($item, $column_name)
    {
        switch ($column_name) {
            case 'created_at':
            case 'user_login':
            case 'ip_address':
            case 'attempt_type':
            case 'failure_reason':
                return esc_html($item->$column_name);
            case 'status':
                $status_classes = [
                    'success' => 'updated',
                    'failed' => 'error',
                    'blocked' => 'error',
                ];
                $class = isset($status_classes[$item->status]) ? $status_classes[$item->status] : '';
                // Use a badge-like span or just text
                return sprintf(
                    '<span class="sikada-log-status %s">%s</span>',
                    esc_attr($class),
                    esc_html(ucfirst($item->status))
                );
            default:
                return print_r($item, true);
        }
    }

    /**
     * Display the table
     * Does NOT override display(), inheriting parent.
     */
}
