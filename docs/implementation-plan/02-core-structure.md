# Step 02: Core Plugin Structure

## Objective

Set up the core plugin architecture including authentication handlers, rate limiter, login logger, and email system classes.

## Prerequisites

- Step 01: Database Schema completed
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## Classes to Create

### 1. LoginHandler

**File**: `src/Auth/LoginHandler.php`  
**Namespace**: `SikadaWorks\SikadaAuth\Auth`

**Purpose**: Handle login authentication, validation, and WordPress hook integration

**Methods**:
- `init()` - Register WordPress hooks
- `authenticate($user, $username, $password)` - Custom authentication logic
- `handle_login_request()` - Process AJAX login requests
- `validate_credentials($username, $password)` - Validate user credentials
- `check_rate_limit($username, $ip)` - Check if login allowed
- `get_redirect_url($user)` - Get role-based redirect URL

**Hooks to Implement**:
- `authenticate` filter - Custom authentication
- `wp_login` action - Log successful login
- `wp_login_failed` action - Log failed login
- `login_redirect` filter - Role-based redirects

### 2. PasswordResetHandler

**File**: `src/Auth/PasswordResetHandler.php`  
**Namespace**: `SikadaWorks\SikadaAuth\Auth`

**Purpose**: Handle password reset requests and form submissions

**Methods**:
- `init()` - Register WordPress hooks
- `handle_reset_request()` - Process AJAX reset request
- `handle_reset_form()` - Process AJAX password reset
- `validate_reset_key($key, $login)` - Validate reset key
- `validate_password_strength($password)` - Check password requirements
- `send_reset_email($user)` - Send password reset email

**Hooks to Implement**:
- `retrieve_password` action - Custom reset request handling
- `password_reset` action - Log password reset
- `validate_password_reset` filter - Custom validation

### 3. RateLimiter

**File**: `src/Auth/RateLimiter.php`  
**Namespace**: `SikadaWorks\SikadaAuth\Auth`

**Purpose**: Implement rate limiting for login attempts

**Methods**:
- `check_username_limit($username)` - Check username-based limit
- `check_ip_limit($ip)` - Check IP-based limit
- `is_blocked($username, $ip)` - Check if blocked
- `record_attempt($username, $ip, $success)` - Record attempt
- `get_lockout_duration($username, $ip)` - Calculate progressive lockout
- `is_ip_whitelisted($ip)` - Check whitelist
- `clear_attempts($username, $ip)` - Clear on successful login

**Storage**: Use WordPress transients for fast, auto-expiring storage

**Transient Keys**:
- `sikada_auth_attempts_user_{username}` - Username attempt count
- `sikada_auth_attempts_ip_{ip}` - IP attempt count
- `sikada_auth_lockout_user_{username}` - Username lockout
- `sikada_auth_lockout_ip_{ip}` - IP lockout

### 4. LoginLogger

**File**: `src/Auth/LoginLogger.php`  
**Namespace**: `SikadaWorks\SikadaAuth\Auth`

**Purpose**: Log all authentication attempts to database

**Methods**:
- `log_attempt($data)` - Insert log entry
- `get_recent_attempts($args)` - Query recent attempts
- `get_user_attempts($user_id, $limit)` - Get user's login history
- `cleanup_old_logs($days)` - Delete old log entries
- `get_stats($blog_id)` - Get login statistics

**Log Data Structure**:
```php
[
    'blog_id' => get_current_blog_id(),
    'user_login' => $username,
    'user_id' => $user_id,
    'ip_address' => $_SERVER['REMOTE_ADDR'],
    'user_agent' => $_SERVER['HTTP_USER_AGENT'],
    'attempt_type' => 'login_success',
    'status' => 'success',
    'failure_reason' => null
]
```

### 5. EmailTemplate

**File**: `src/Email/EmailTemplate.php`  
**Namespace**: `SikadaWorks\SikadaAuth\Email`

**Purpose**: Load and render email templates with variable replacement

**Methods**:
- `get_template($template_name, $format)` - Load template file
- `render($template_name, $vars, $format)` - Render with variables
- `get_template_path($template_name, $format)` - Find template (theme override)
- `replace_variables($content, $vars)` - Replace {placeholders}
- `get_default_vars()` - Get standard variables (site_name, etc.)

**Template Hierarchy**:
1. Child theme: `child-theme/sikada-auth/emails/{template}.php`
2. Parent theme: `theme/sikada-auth/emails/{template}.php`
3. Plugin: `sikada-auth/templates/emails/{template}.php`

### 6. EmailSender

**File**: `src/Email/EmailSender.php`  
**Namespace**: `SikadaWorks\SikadaAuth\Email`

**Purpose**: Send emails using templates and custom headers

**Methods**:
- `send($to, $template_name, $vars)` - Send email
- `get_headers()` - Get custom email headers from settings
- `get_from_name()` - Get from name from settings
- `get_from_email()` - Get from email from settings
- `send_admin_alert($subject, $message)` - Send alert to admin

**WordPress Hooks**:
- `wp_mail_from` filter - Custom from email
- `wp_mail_from_name` filter - Custom from name
- `wp_mail_content_type` filter - HTML or plain text

## Implementation Tasks

### 1. Create Auth Classes

Create `LoginHandler.php`, `PasswordResetHandler.php`, `RateLimiter.php`, and `LoginLogger.php` with:
- Proper namespace and use statements
- Singleton pattern (where appropriate)
- `init()` method for hook registration
- PHPDoc comments for all methods
- Input sanitization and output escaping
- Error handling with WP_Error
- Extensibility hooks (do_action, apply_filters)

### 2. Create Email Classes

Create `EmailTemplate.php` and `EmailSender.php` with:
- Template loading with fallback hierarchy
- Variable replacement system
- HTML and plain text support
- Custom header support
- Error logging

### 3. Register Services in Plugin.php

Update `src/Core/Plugin.php` `register_services()`:
```php
// Register authentication handlers
if (class_exists('SikadaWorks\\SikadaAuth\\Auth\\LoginHandler')) {
    (new \SikadaWorks\SikadaAuth\Auth\LoginHandler())->init();
}

if (class_exists('SikadaWorks\\SikadaAuth\\Auth\\PasswordResetHandler')) {
    (new \SikadaWorks\SikadaAuth\Auth\PasswordResetHandler())->init();
}
```

### 4. Create Helper Functions File (Optional)

**File**: `src/functions.php`

Common helper functions:
- `sikada_auth_get_ip()` - Get user IP address
- `sikada_auth_get_user_agent()` - Get user agent
- `sikada_auth_is_multisite()` - Check if multisite
- `sikada_auth_get_option($key, $default)` - Get plugin option
- `sikada_auth_update_option($key, $value)` - Update plugin option

## Testing Checklist

- [ ] All classes load without errors
- [ ] Namespaces are correct
- [ ] Singleton patterns work correctly
- [ ] WordPress hooks are registered
- [ ] No PHP warnings or notices
- [ ] Autoloading works (composer dump-autoload)
- [ ] Classes follow coding standards

## Acceptance Criteria

1. ✅ All 6 core classes created with proper structure
2. ✅ Classes registered in Plugin.php
3. ✅ No PHP errors on plugin activation
4. ✅ Proper PSR-4 namespace structure
5. ✅ All methods have PHPDoc comments
6. ✅ Follows all coding standards
7. ✅ Extensibility hooks included

## Agent Execution Prompt

```
Create the core plugin structure for Sikada Authorization:

1. Create Auth classes in src/Auth/:
   - LoginHandler.php - Handle login authentication
   - PasswordResetHandler.php - Handle password resets
   - RateLimiter.php - Implement rate limiting with transients
   - LoginLogger.php - Log attempts to database

2. Create Email classes in src/Email/:
   - EmailTemplate.php - Load/render email templates
   - EmailSender.php - Send emails with custom headers

3. Update src/Core/Plugin.php:
   - Register all new services in register_services()

Requirements:
- Namespace: SikadaWorks\SikadaAuth\{SubNamespace}
- Follow PSR-4 autoloading
- Include init() method for hook registration
- Add PHPDoc comments
- Use singleton pattern where appropriate
- Include extensibility hooks (do_action, apply_filters)
- Proper error handling with WP_Error
- Input sanitization and output escaping

Follow all standards in docs/CODING_STANDARDS.md.

Note: Methods can be stubbed initially - full implementation comes in later steps.
```

## Dependencies

**Required Before This Step**:
- Step 01: Database Schema

**Required After This Step**:
- Step 03: URL Redirection (uses LoginHandler)
- Step 04: Rate Limiting (implements RateLimiter)
- Step 05: Login Logging (implements LoginLogger)
- Step 06: Email Templates (implements Email classes)

## Related Files

- `src/Auth/*.php` - Authentication classes
- `src/Email/*.php` - Email classes
- `src/Core/Plugin.php` - Service registration
- `composer.json` - PSR-4 autoloading
- `docs/CODING_STANDARDS.md` - Reference

## Notes

- Classes can be stubbed initially with method signatures
- Full implementation happens in subsequent steps
- Focus on proper structure and architecture
- Ensure all classes are autoloaded correctly
- Run `composer dump-autoload` after creating classes

---

**Status**: Ready for Implementation  
**Estimated Time**: 2-3 hours  
**Complexity**: Medium-High
