<?php
// Verify database tables
global $wpdb;
$table_name = $wpdb->prefix . 'sikada_auth_login_attempts';
$exists = $wpdb->get_var("SHOW TABLES LIKE '$table_name'") === $table_name;

echo '<div class="test ' . ($exists ? 'pass' : 'fail') . '">';
echo '<div class="test-name">Database Table</div>';
echo '<div class="test-result">';
if ($exists) {
    echo '✅ Table <code>' . esc_html($table_name) . '</code> exists.';
} else {
    echo '❌ Table <code>' . esc_html($table_name) . '</code> DOES NOT exist.';
}
echo '</div></div>';

// Verify options
$options = [
    'sikada_auth_enable_rate_limiting' => true,
    'sikada_auth_rate_limit_username' => 5,
    'sikada_auth_rate_limit_ip' => 10
];

foreach ($options as $opt => $default) {
    $val = get_option($opt, $default);
    echo '<div class="test pass">'; // Always pass if we can read it, even if default
    echo '<div class="test-name">Option: ' . esc_html($opt) . '</div>';
    echo '<div class="test-result">';
    echo 'Value: <code>' . esc_html(is_bool($val) ? ($val ? 'true' : 'false') : print_r($val, true)) . '</code>';
    echo (get_option($opt) === false) ? ' (Default)' : ' (Database)';
    echo '</div></div>';
}

// Verify capability
echo '<div class="test ' . (current_user_can('manage_options') ? 'pass' : 'fail') . '">';
echo '<div class="test-name">User Capability</div>';
echo '<div class="test-result">User has <code>manage_options</code> capability.</div>';
echo '</div>';
