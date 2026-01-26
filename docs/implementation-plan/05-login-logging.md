# Step 05: Login Attempt Logging

## Objective

Implement comprehensive logging of all authentication attempts to the database for security audit trail and user login history.

## Prerequisites

- Step 01: Database Schema completed
- Step 02: Core Structure (LoginLogger class created)
- Step 04: Rate Limiting completed
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## Implementation

### 1. Complete LoginLogger Class

**File**: `src/Auth/LoginLogger.php`

**Key Methods**:

```php
public function log_attempt(array $data)
{
    global $wpdb;
    
    $table = $wpdb->prefix . 'sikada_auth_login_attempts';
    
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
    }
    
    return $result;
}

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
    
    // Build query with proper escaping
    // Return results
}

public function get_user_login_history($user_id, $limit = 10)
{
    return $this->get_recent_attempts([
        'user_id' => $user_id,
        'attempt_type' => 'login_success',
        'limit' => $limit,
    ]);
}

public function cleanup_old_logs($days = 90)
{
    global $wpdb;
    
    $table = $wpdb->prefix . 'sikada_auth_login_attempts';
    $date = date('Y-m-d H:i:s', strtotime("-{$days} days"));
    
    return $wpdb->query($wpdb->prepare(
        "DELETE FROM {$table} WHERE created_at < %s",
        $date
    ));
}
```

### 2. Integrate with LoginHandler

**Update**: `src/Auth/LoginHandler.php`

```php
public function authenticate($user, $username, $password)
{
    $logger = new LoginLogger();
    $ip = $this->get_user_ip();
    
    // ... rate limiting check ...
    
    // Perform authentication
    $authenticated_user = wp_authenticate_username_password(null, $username, $password);
    
    if (is_wp_error($authenticated_user)) {
        // Log failed attempt
        $logger->log_attempt([
            'user_login' => $username,
            'user_id' => null,
            'ip_address' => $ip,
            'attempt_type' => 'login_failed',
            'status' => 'failed',
            'failure_reason' => $authenticated_user->get_error_code(),
        ]);
    } else {
        // Log successful login
        $logger->log_attempt([
            'user_login' => $authenticated_user->user_login,
            'user_id' => $authenticated_user->ID,
            'ip_address' => $ip,
            'attempt_type' => 'login_success',
            'status' => 'success',
            'failure_reason' => null,
        ]);
    }
    
    return $authenticated_user;
}
```

### 3. Integrate with PasswordResetHandler

**Update**: `src/Auth/PasswordResetHandler.php`

```php
public function handle_reset_request($email)
{
    $logger = new LoginLogger();
    
    // ... process reset request ...
    
    $logger->log_attempt([
        'user_login' => $email,
        'user_id' => $user->ID ?? null,
        'attempt_type' => 'password_reset_request',
        'status' => 'success',
    ]);
}

public function handle_reset_form($key, $login, $new_password)
{
    $logger = new LoginLogger();
    
    // ... validate and reset password ...
    
    $logger->log_attempt([
        'user_login' => $login,
        'user_id' => $user->ID,
        'attempt_type' => 'password_reset_completed',
        'status' => 'success',
    ]);
}
```

### 4. Add User Profile Display

**File**: `src/Admin/UserProfileExtension.php`

```php
namespace SikadaWorks\SikadaAuth\Admin;

class UserProfileExtension
{
    public function init()
    {
        add_action('show_user_profile', [$this, 'display_login_history']);
        add_action('edit_user_profile', [$this, 'display_login_history']);
    }
    
    public function display_login_history($user)
    {
        $logger = new \SikadaWorks\SikadaAuth\Auth\LoginLogger();
        $history = $logger->get_user_login_history($user->ID, 10);
        
        ?>
        <h2><?php _e('Login History', 'sikada-auth'); ?></h2>
        <table class="widefat">
            <thead>
                <tr>
                    <th><?php _e('Date/Time', 'sikada-auth'); ?></th>
                    <th><?php _e('IP Address', 'sikada-auth'); ?></th>
                    <th><?php _e('Status', 'sikada-auth'); ?></th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($history as $entry) : ?>
                <tr>
                    <td><?php echo esc_html($entry->created_at); ?></td>
                    <td><?php echo esc_html($entry->ip_address); ?></td>
                    <td><?php echo esc_html($entry->status); ?></td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
        <?php
    }
}
```

### 5. Add Cleanup Cron Job

**Update**: `src/Core/Plugin.php`

```php
public static function activate()
{
    // ... existing activation code ...
    
    // Schedule log cleanup
    if (!wp_next_scheduled('sikada_auth_cleanup_logs')) {
        wp_schedule_event(time(), 'daily', 'sikada_auth_cleanup_logs');
    }
}

private function register_services()
{
    // ... existing services ...
    
    // Register log cleanup
    add_action('sikada_auth_cleanup_logs', function() {
        $logger = new \SikadaWorks\SikadaAuth\Auth\LoginLogger();
        $retention_days = get_option('sikada_auth_log_retention_days', 90);
        $logger->cleanup_old_logs($retention_days);
    });
}
```

## Testing Checklist

- [ ] Successful logins are logged correctly
- [ ] Failed logins are logged with failure reason
- [ ] Password reset requests are logged
- [ ] Password resets are logged
- [ ] Logout events are logged
- [ ] Blocked attempts are logged
- [ ] User login history displays in profile
- [ ] Cleanup cron job removes old logs
- [ ] Multisite: blog_id is correct
- [ ] No performance issues with large log tables

## Acceptance Criteria

1. ✅ All authentication events logged to database
2. ✅ Login history displayed in user profile
3. ✅ Automatic log cleanup via cron
4. ✅ Proper indexing for query performance
5. ✅ Multisite compatibility
6. ✅ Extensibility hooks included
7. ✅ Follows all coding standards

## Agent Execution Prompt

```
Implement login attempt logging for Sikada Authorization:

1. Complete src/Auth/LoginLogger.php:
   - Implement log_attempt() to insert into database
   - Implement get_recent_attempts() with filters
   - Implement get_user_login_history()
   - Implement cleanup_old_logs()

2. Update src/Auth/LoginHandler.php:
   - Log successful and failed login attempts
   - Include failure reasons

3. Update src/Auth/PasswordResetHandler.php:
   - Log password reset requests
   - Log password reset completions

4. Create src/Admin/UserProfileExtension.php:
   - Display login history in user profile
   - Show last 10 successful logins

5. Update src/Core/Plugin.php:
   - Schedule daily log cleanup cron job
   - Register UserProfileExtension

Requirements:
- Use prepared statements for all queries
- Include proper error handling
- Add extensibility hooks
- Follow coding standards in docs/CODING_STANDARDS.md

Test by logging in/out and viewing user profile.
```

## Dependencies

**Required Before This Step**:
- Step 01: Database Schema
- Step 02: Core Structure
- Step 04: Rate Limiting

**Required After This Step**:
- Step 07: Admin Settings (log viewer, retention settings)

## Related Files

- `src/Auth/LoginLogger.php`
- `src/Auth/LoginHandler.php`
- `src/Auth/PasswordResetHandler.php`
- `src/Admin/UserProfileExtension.php`
- `src/Core/Plugin.php`

---

**Status**: Ready for Implementation  
**Estimated Time**: 2-3 hours  
**Complexity**: Medium
