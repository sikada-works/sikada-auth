# Step 04: Rate Limiting System

## Objective

Implement rate limiting to prevent brute force attacks with progressive lockouts based on username and IP address.

## Prerequisites

- Step 01: Database Schema completed
- Step 02: Core Structure (RateLimiter class created)
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## Rate Limiting Strategy

### Two-Tier System

**Tier 1: Per-Username**
- Max attempts: 5 (configurable)
- Time window: 15 minutes
- Lockout: Progressive (15min → 30min → 1hr → 2hr)

**Tier 2: Per-IP Address**
- Max attempts: 10 (configurable)
- Time window: 15 minutes  
- Lockout: Progressive (30min → 1hr → 2hr → 4hr)

### Progressive Lockouts

| Violation # | Username Lockout | IP Lockout |
|-------------|------------------|------------|
| 1st | 15 minutes | 30 minutes |
| 2nd | 30 minutes | 1 hour |
| 3rd | 1 hour | 2 hours |
| 4th+ | 2 hours | 4 hours |

## Implementation

### 1. Complete RateLimiter Class

**File**: `src/Auth/RateLimiter.php`

**Full Implementation**:

```php
namespace SikadaWorks\SikadaAuth\Auth;

class RateLimiter
{
    private $username_limit = 5;
    private $ip_limit = 10;
    private $time_window = 900; // 15 minutes
    
    public function __construct()
    {
        // Load settings
        $this->username_limit = get_option('sikada_auth_rate_limit_username', 5);
        $this->ip_limit = get_option('sikada_auth_rate_limit_ip', 10);
    }
    
    public function is_blocked($username, $ip)
    {
        // Check IP whitelist first
        if ($this->is_ip_whitelisted($ip)) {
            return false;
        }
        
        // Check both username and IP lockouts
        return $this->is_username_locked($username) || $this->is_ip_locked($ip);
    }
    
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
    
    private function get_attempt_count($identifier, $type)
    {
        $key = "sikada_auth_attempts_{$type}_{$identifier}";
        return (int) get_transient($key);
    }
    
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
    
    private function calculate_lockout_duration($violations, $type)
    {
        $base_duration = ($type === 'username') ? 900 : 1800; // 15min or 30min
        
        // Progressive: 1x, 2x, 4x, 8x
        $multiplier = pow(2, min($violations - 1, 3));
        
        return $base_duration * $multiplier;
    }
    
    private function is_username_locked($username)
    {
        $key = "sikada_auth_lockout_username_{$username}";
        return (bool) get_transient($key);
    }
    
    private function is_ip_locked($ip)
    {
        $key = "sikada_auth_lockout_ip_{$ip}";
        return (bool) get_transient($key);
    }
    
    private function clear_attempts($username, $ip)
    {
        delete_transient("sikada_auth_attempts_username_{$username}");
        delete_transient("sikada_auth_attempts_ip_{$ip}");
    }
    
    private function is_ip_whitelisted($ip)
    {
        $whitelist = get_option('sikada_auth_ip_whitelist', '');
        $whitelist_ips = array_map('trim', explode(',', $whitelist));
        
        return in_array($ip, $whitelist_ips);
    }
    
    private function maybe_send_admin_alert($identifier, $type, $violations)
    {
        // Check if alerts enabled
        if (!get_option('sikada_auth_enable_admin_alerts', false)) {
            return;
        }
        
        // Check threshold
        $threshold = get_option('sikada_auth_alert_threshold', 3);
        if ($violations < $threshold) {
            return;
        }
        
        // Send alert email
        $email_sender = new \SikadaWorks\SikadaAuth\Email\EmailSender();
        $email_sender->send_admin_alert(
            sprintf(__('Security Alert: Multiple Failed Login Attempts', 'sikada-auth')),
            [
                'identifier' => $identifier,
                'type' => $type,
                'violations' => $violations,
                'lockout_duration' => $this->calculate_lockout_duration($violations, $type)
            ]
        );
    }
    
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
```

### 2. Integrate with LoginHandler

**Update**: `src/Auth/LoginHandler.php`

```php
public function authenticate($user, $username, $password)
{
    // Skip if already authenticated
    if ($user instanceof \WP_User) {
        return $user;
    }
    
    $ip = $this->get_user_ip();
    $rate_limiter = new RateLimiter();
    
    // Check if blocked
    if ($rate_limiter->is_blocked($username, $ip)) {
        $time_remaining = $rate_limiter->get_lockout_time_remaining($username, $ip);
        $minutes = ceil($time_remaining / 60);
        
        // Log blocked attempt
        $this->log_blocked_attempt($username, $ip);
        
        return new \WP_Error(
            'rate_limit_exceeded',
            sprintf(
                __('Too many failed login attempts. Please try again in %d minutes.', 'sikada-auth'),
                $minutes
            )
        );
    }
    
    // Continue with normal authentication...
    // After authentication, record result
    $success = !is_wp_error($user);
    $rate_limiter->check_and_record($username, $ip, $success);
    
    return $user;
}
```

## Testing Checklist

- [ ] Failed login attempts are counted correctly
- [ ] Username limit triggers lockout at threshold
- [ ] IP limit triggers lockout at threshold
- [ ] Progressive lockouts increase duration
- [ ] Successful login clears attempt counters
- [ ] Whitelisted IPs bypass rate limiting
- [ ] Admin alerts sent when threshold reached
- [ ] Lockout time remaining calculated correctly
- [ ] Transients expire properly
- [ ] Works on multisite

## Acceptance Criteria

1. ✅ Rate limiting prevents brute force attacks
2. ✅ Progressive lockouts implemented correctly
3. ✅ IP whitelist functionality works
4. ✅ Admin alerts sent when configured
5. ✅ Successful login clears attempts
6. ✅ User-friendly error messages with time remaining
7. ✅ Follows all coding standards
8. ✅ Extensibility hooks included

## Agent Execution Prompt

```
Implement the rate limiting system for Sikada Authorization:

1. Complete src/Auth/RateLimiter.php:
   - Implement all methods for rate limiting
   - Use WordPress transients for storage
   - Progressive lockout calculation
   - IP whitelist support
   - Admin alert integration

2. Update src/Auth/LoginHandler.php:
   - Integrate rate limiter in authenticate() method
   - Check if blocked before authentication
   - Record attempt results
   - Return user-friendly error with time remaining

Requirements:
- Default limits: 5 attempts/username, 10 attempts/IP
- Progressive lockouts: 15min → 30min → 1hr → 2hr
- Use transients with auto-expiration
- Clear attempts on successful login
- Send admin alerts when threshold reached
- Include extensibility hooks
- Follow all coding standards in docs/CODING_STANDARDS.md

Test by attempting multiple failed logins and verifying lockout.
```

## Dependencies

**Required Before This Step**:
- Step 01: Database Schema
- Step 02: Core Structure (RateLimiter, LoginHandler classes)

**Required After This Step**:
- Step 05: Login Logging (logs blocked attempts)
- Step 06: Email Templates (admin alert emails)
- Step 07: Admin Settings (configure limits)

## Related Files

- `src/Auth/RateLimiter.php` - Main implementation
- `src/Auth/LoginHandler.php` - Integration point
- `src/Email/EmailSender.php` - Admin alerts
- `docs/CODING_STANDARDS.md` - Reference

## Notes

- Transients auto-expire, no manual cleanup needed
- Progressive lockouts reset after 24 hours of no violations
- IP whitelist useful for testing and trusted networks
- Consider adding a manual unlock feature in admin panel (future)

---

**Status**: Ready for Implementation  
**Estimated Time**: 2-3 hours  
**Complexity**: Medium
