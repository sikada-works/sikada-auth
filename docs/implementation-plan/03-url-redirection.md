# Step 03: URL Redirection System

## Objective

Implement URL redirection system to intercept WordPress default login URLs and redirect to custom pages with Gutenberg blocks.

## Prerequisites

- Step 01: Database Schema completed
- Step 02: Core Structure completed
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## URLs to Intercept

### WordPress Default URLs → Custom Pages

| WordPress URL | Custom Page | Notes |
|---------------|-------------|-------|
| `/wp-login.php` | Login Page | Main login form |
| `/wp-login.php?action=lostpassword` | Password Reset Page | Reset request |
| `/wp-login.php?action=rp&key=...&login=...` | Password Reset Page | Reset form with params |
| `/wp-login.php?action=logout` | Logout (then redirect) | Process logout |
| `/wp-login.php?action=register` | Registration Page (Phase 2) | Stubbed for now |

## Implementation

### 1. Create URLRedirector Class

**File**: `src/Redirect/URLRedirector.php`  
**Namespace**: `SikadaWorks\SikadaAuth\Redirect`

**Purpose**: Handle all URL redirection logic

**Methods**:

```php
public function init()
{
    // Register WordPress hooks
    add_action('init', [$this, 'redirect_login_page'], 1);
    add_filter('login_url', [$this, 'filter_login_url'], 10, 3);
    add_filter('logout_url', [$this, 'filter_logout_url'], 10, 2);
    add_filter('lostpassword_url', [$this, 'filter_lostpassword_url'], 10, 2);
    add_filter('register_url', [$this, 'filter_register_url'], 10);
}

public function redirect_login_page()
{
    // Check if accessing wp-login.php directly
    // Redirect to appropriate custom page based on action parameter
}

public function filter_login_url($login_url, $redirect, $force_reauth)
{
    // Return custom login page URL
}

public function filter_logout_url($logout_url, $redirect)
{
    // Return custom logout URL
}

public function filter_lostpassword_url($lostpassword_url, $redirect)
{
    // Return custom password reset page URL
}

public function filter_register_url($register_url)
{
    // Return custom registration page URL (Phase 2)
}

public function get_login_page_url()
{
    // Get login page URL from settings
}

public function get_password_reset_page_url()
{
    // Get password reset page URL from settings
}

public function get_logout_redirect_url()
{
    // Get logout redirect URL from settings
}
```

### 2. Redirect Logic

**On Direct Access to wp-login.php**:

```php
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
    
    if (!empty($redirect_url)) {
        wp_safe_redirect($redirect_url);
        exit;
    }
}
```

### 3. Settings Integration

**Get Page URLs from Options**:

```php
public function get_login_page_url()
{
    $page_id = get_option('sikada_auth_login_page');
    if ($page_id) {
        return get_permalink($page_id);
    }
    // Fallback to wp-login.php if not configured
    return wp_login_url();
}

public function get_password_reset_page_url()
{
    $page_id = get_option('sikada_auth_password_reset_page');
    if ($page_id) {
        return get_permalink($page_id);
    }
    // Fallback to wp-login.php?action=lostpassword
    return wp_lostpassword_url();
}
```

### 4. Prevent Redirect Loops

**Check if Already on Custom Page**:

```php
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
```

### 5. Logout Handling

**Process Logout and Redirect**:

```php
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
            'failure_reason' => null
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
```

### 6. Register Service

**Update Plugin.php**:

```php
// In register_services() method
if (class_exists('SikadaWorks\\SikadaAuth\\Redirect\\URLRedirector')) {
    (new \SikadaWorks\SikadaAuth\Redirect\URLRedirector())->init();
}
```

## Testing Checklist

- [ ] Direct access to `/wp-login.php` redirects to custom login page
- [ ] `/wp-login.php?action=lostpassword` redirects to password reset page
- [ ] `/wp-login.php?action=rp&key=...` redirects with parameters preserved
- [ ] `/wp-login.php?action=logout` logs out and redirects correctly
- [ ] `redirect_to` parameter is preserved
- [ ] No redirect loops occur
- [ ] Logout is logged to database
- [ ] Nonce verification works on logout
- [ ] Works on multisite
- [ ] Fallback to wp-login.php if pages not configured

## Acceptance Criteria

1. ✅ All WordPress login URLs redirect to custom pages
2. ✅ URL parameters are preserved correctly
3. ✅ No redirect loops
4. ✅ Logout functionality works correctly
5. ✅ Logout is logged to database
6. ✅ Graceful fallback if custom pages not set
7. ✅ Follows all coding standards
8. ✅ Extensibility hooks included

## Agent Execution Prompt

```
Create the URL redirection system for Sikada Authorization:

1. Create src/Redirect/URLRedirector.php:
   - Namespace: SikadaWorks\SikadaAuth\Redirect
   - Implement init() method with hook registration
   - Implement redirect_login_page() to intercept wp-login.php
   - Implement URL filter methods (login_url, logout_url, etc.)
   - Handle logout with logging
   - Prevent redirect loops
   - Preserve URL parameters (redirect_to, key, login)

2. Update src/Core/Plugin.php:
   - Register URLRedirector in register_services()

Requirements:
- Check if on wp-login.php using $pagenow
- Get custom page URLs from options (sikada_auth_login_page, etc.)
- Use wp_safe_redirect() for all redirects
- Verify nonce on logout
- Log logout attempts to database
- Include extensibility hooks
- Proper error handling
- Follow all coding standards in docs/CODING_STANDARDS.md

Test by accessing wp-login.php directly and verifying redirect.
```

## Dependencies

**Required Before This Step**:
- Step 01: Database Schema
- Step 02: Core Structure (LoginLogger class)

**Required After This Step**:
- Step 07: Admin Settings (to configure page assignments)
- Step 08: Login Block (target page for redirects)
- Step 09: Password Reset Block (target page for redirects)

## Related Files

- `src/Redirect/URLRedirector.php` - Main implementation
- `src/Core/Plugin.php` - Service registration
- `src/Auth/LoginLogger.php` - For logout logging
- `docs/CODING_STANDARDS.md` - Reference

## Notes

- Graceful degradation: If custom pages not set, fall back to wp-login.php
- Prevent redirect loops by checking current page ID
- Preserve all URL parameters during redirects
- Logout must verify nonce for security
- Consider adding a filter to allow other plugins to modify redirect behavior

---

**Status**: Ready for Implementation  
**Estimated Time**: 2 hours  
**Complexity**: Medium
