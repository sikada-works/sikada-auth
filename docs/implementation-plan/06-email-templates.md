# Step 06: Email Template System

## Objective

Create file-based email template system with theme override support for all authentication-related emails.

## Prerequisites

- Step 02: Core Structure (Email classes created)
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## Email Templates to Create

1. **password-reset-request** - Password reset link email
2. **password-changed** - Password successfully changed notification
3. **email-changed** - Email address changed notification
4. **admin-blocked-ip-alert** - Admin alert for blocked IPs

## Implementation

### 1. Create Email Templates

**Directory**: `templates/emails/`

**For each template, create both `.php` (HTML) and `.txt` (plain text) versions**

**Example: password-reset-request.php**:
```php
<?php
/**
 * Password Reset Request Email Template (HTML)
 *
 * Available variables:
 * - $site_name
 * - $site_url
 * - $user_login
 * - $user_email
 * - $user_display_name
 * - $reset_link
 * - $reset_key
 * - $expiration_time
 */
?>
<!DOCTYPE html>
<html>
<head>
    <meta charset="UTF-8">
    <style>
        body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
        .container { max-width: 600px; margin: 0 auto; padding: 20px; }
        .button { display: inline-block; padding: 12px 24px; background: #0073aa; color: #fff; text-decoration: none; border-radius: 4px; }
    </style>
</head>
<body>
    <div class="container">
        <h2><?php echo esc_html(sprintf(__('Password Reset Request for %s', 'sikada-auth'), $site_name)); ?></h2>
        
        <p><?php echo esc_html(sprintf(__('Hello %s,', 'sikada-auth'), $user_display_name)); ?></p>
        
        <p><?php _e('Someone has requested a password reset for the following account:', 'sikada-auth'); ?></p>
        
        <p>
            <strong><?php _e('Site:', 'sikada-auth'); ?></strong> <?php echo esc_html($site_name); ?><br>
            <strong><?php _e('Username:', 'sikada-auth'); ?></strong> <?php echo esc_html($user_login); ?>
        </p>
        
        <p><?php _e('If this was a mistake, just ignore this email and nothing will happen.', 'sikada-auth'); ?></p>
        
        <p><?php _e('To reset your password, visit the following address:', 'sikada-auth'); ?></p>
        
        <p><a href="<?php echo esc_url($reset_link); ?>" class="button"><?php _e('Reset Password', 'sikada-auth'); ?></a></p>
        
        <p><?php echo esc_html(sprintf(__('This link will expire in %s.', 'sikada-auth'), $expiration_time)); ?></p>
    </div>
</body>
</html>
```

**Example: password-reset-request.txt**:
```
<?php echo sprintf(__('Password Reset Request for %s', 'sikada-auth'), $site_name); ?>

<?php echo sprintf(__('Hello %s,', 'sikada-auth'), $user_display_name); ?>

<?php _e('Someone has requested a password reset for the following account:', 'sikada-auth'); ?>

<?php _e('Site:', 'sikada-auth'); ?> <?php echo $site_name; ?>

<?php _e('Username:', 'sikada-auth'); ?> <?php echo $user_login; ?>

<?php _e('If this was a mistake, just ignore this email and nothing will happen.', 'sikada-auth'); ?>

<?php _e('To reset your password, visit the following address:', 'sikada-auth'); ?>

<?php echo $reset_link; ?>

<?php echo sprintf(__('This link will expire in %s.', 'sikada-auth'), $expiration_time); ?>
```

### 2. Complete EmailTemplate Class

**File**: `src/Email/EmailTemplate.php`

```php
public function get_template_path($template_name, $format = 'html')
{
    $extension = ($format === 'html') ? 'php' : 'txt';
    $filename = "{$template_name}.{$extension}";
    
    // Template hierarchy (child theme → parent theme → plugin)
    $locations = [
        get_stylesheet_directory() . '/sikada-auth/emails/' . $filename,
        get_template_directory() . '/sikada-auth/emails/' . $filename,
        SIKADA_AUTH_PLUGIN_DIR . 'templates/emails/' . $filename,
    ];
    
    foreach ($locations as $location) {
        if (file_exists($location)) {
            return $location;
        }
    }
    
    return false;
}

public function render($template_name, $vars = [], $format = 'html')
{
    $template_path = $this->get_template_path($template_name, $format);
    
    if (!$template_path) {
        return false;
    }
    
    // Merge with default vars
    $vars = array_merge($this->get_default_vars(), $vars);
    
    // Extract variables
    extract($vars, EXTR_SKIP);
    
    // Capture output
    ob_start();
    include $template_path;
    $content = ob_get_clean();
    
    // Apply filters
    return apply_filters('sikada_auth_email_content', $content, $template_name, $format, $vars);
}

private function get_default_vars()
{
    return [
        'site_name' => get_bloginfo('name'),
        'site_url' => home_url(),
        'admin_email' => get_option('admin_email'),
    ];
}
```

### 3. Complete EmailSender Class

**File**: `src/Email/EmailSender.php`

```php
public function send($to, $template_name, $vars = [])
{
    $template = new EmailTemplate();
    
    // Get subject from filter
    $subject = apply_filters("sikada_auth_email_subject_{$template_name}", 
        $this->get_default_subject($template_name), 
        $vars
    );
    
    // Render HTML version
    $html_content = $template->render($template_name, $vars, 'html');
    
    // Render plain text version
    $text_content = $template->render($template_name, $vars, 'text');
    
    // Set content type to HTML
    add_filter('wp_mail_content_type', function() { return 'text/html'; });
    
    // Set custom headers
    $headers = $this->get_headers();
    
    // Send email
    $result = wp_mail($to, $subject, $html_content, $headers);
    
    // Reset content type
    remove_filter('wp_mail_content_type', function() { return 'text/html'; });
    
    return $result;
}

private function get_headers()
{
    $from_name = get_option('sikada_auth_email_from_name', get_bloginfo('name'));
    $from_email = get_option('sikada_auth_email_from_email', get_option('admin_email'));
    $reply_to = get_option('sikada_auth_email_reply_to', $from_email);
    
    return [
        "From: {$from_name} <{$from_email}>",
        "Reply-To: {$reply_to}",
    ];
}
```

## Testing Checklist

- [ ] All email templates render correctly (HTML + text)
- [ ] Variables are replaced properly
- [ ] Theme override works (child theme → parent theme → plugin)
- [ ] Custom email headers applied
- [ ] Emails sent successfully
- [ ] Plain text fallback works
- [ ] Internationalization works
- [ ] Filters allow customization

## Acceptance Criteria

1. ✅ All 4 email templates created (HTML + text versions)
2. ✅ Template hierarchy works correctly
3. ✅ Variable replacement functional
4. ✅ Custom email headers applied
5. ✅ Extensibility filters included
6. ✅ Follows all coding standards

## Agent Execution Prompt

```
Create email template system for Sikada Authorization:

1. Create templates in templates/emails/:
   - password-reset-request.php and .txt
   - password-changed.php and .txt
   - email-changed.php and .txt
   - admin-blocked-ip-alert.php and .txt

2. Complete src/Email/EmailTemplate.php:
   - Implement get_template_path() with hierarchy
   - Implement render() with variable extraction
   - Implement get_default_vars()

3. Complete src/Email/EmailSender.php:
   - Implement send() method
   - Implement get_headers() for custom headers
   - Support HTML and plain text

Requirements:
- Use WordPress template hierarchy
- Proper variable escaping in templates
- Internationalization with __() and _e()
- Include extensibility filters
- Follow coding standards in docs/CODING_STANDARDS.md

Test by triggering password reset and checking email.
```

## Dependencies

**Required Before This Step**:
- Step 02: Core Structure

**Required After This Step**:
- Step 04: Rate Limiting (admin alerts)
- Step 07: Admin Settings (email header configuration)

## Related Files

- `templates/emails/*.php` and `*.txt`
- `src/Email/EmailTemplate.php`
- `src/Email/EmailSender.php`

---

**Status**: Ready for Implementation  
**Estimated Time**: 2-3 hours  
**Complexity**: Medium
