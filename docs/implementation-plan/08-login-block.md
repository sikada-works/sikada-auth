# Step 08: Login Gutenberg Block

## Objective

Create AJAX-based Login Gutenberg block with real-time validation and user-friendly error handling.

## Prerequisites

- Steps 01-07 completed
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## Block Features

- Username/email input
- Password input
- Remember me checkbox
- Submit button
- Error message display
- Success message display
- Loading state
- Links to password reset page
- AJAX submission (no page reload)
- Real-time validation
- Minimal default styling

## Implementation

### 1. Create Block Files

**Directory**: `blocks/login-form/`

**Files**:
- `block.json` - Block metadata
- `index.js` - Block registration
- `edit.js` - Editor component
- `save.js` - Frontend save
- `style.css` - Frontend styles
- `editor.css` - Editor styles

### 2. Block Registration

**File**: `blocks/login-form/block.json`

```json
{
  "$schema": "https://schemas.wp.org/trunk/block.json",
  "apiVersion": 2,
  "name": "sikada-auth/login-form",
  "title": "Login Form",
  "category": "widgets",
  "icon": "lock",
  "description": "Custom login form",
  "supports": {
    "html": false,
    "align": true
  },
  "textdomain": "sikada-auth",
  "editorScript": "file:./index.js",
  "editorStyle": "file:./editor.css",
  "style": "file:./style.css"
}
```

### 3. Frontend Component

**File**: `blocks/login-form/save.js`

```javascript
import { useBlockProps } from '@wordpress/block-editor';

export default function Save() {
    const blockProps = useBlockProps.save();
    
    return (
        <div {...blockProps} className="sikada-auth-login-form">
            <form className="sikada-login-form" data-sikada-login-form>
                <div className="sikada-form-messages"></div>
                
                <div className="sikada-form-field">
                    <label htmlFor="sikada-username">
                        {window.sikadaAuthData?.labels?.username || 'Username or Email'}
                    </label>
                    <input 
                        type="text" 
                        id="sikada-username" 
                        name="username" 
                        required 
                        autoComplete="username"
                    />
                </div>
                
                <div className="sikada-form-field">
                    <label htmlFor="sikada-password">
                        {window.sikadaAuthData?.labels?.password || 'Password'}
                    </label>
                    <input 
                        type="password" 
                        id="sikada-password" 
                        name="password" 
                        required 
                        autoComplete="current-password"
                    />
                </div>
                
                <div className="sikada-form-field sikada-checkbox">
                    <label>
                        <input type="checkbox" name="remember" value="1" />
                        {window.sikadaAuthData?.labels?.remember || 'Remember Me'}
                    </label>
                </div>
                
                <div className="sikada-form-actions">
                    <button type="submit" className="sikada-button sikada-button-primary">
                        {window.sikadaAuthData?.labels?.login || 'Log In'}
                    </button>
                </div>
                
                <div className="sikada-form-links">
                    <a href={window.sikadaAuthData?.passwordResetUrl || '#'}>
                        {window.sikadaAuthData?.labels?.lostPassword || 'Lost your password?'}
                    </a>
                </div>
            </form>
        </div>
    );
}
```

### 4. AJAX Handler

**Create**: `assets/js/login-form.js`

```javascript
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const forms = document.querySelectorAll('[data-sikada-login-form]');
        
        forms.forEach(form => {
            form.addEventListener('submit', handleLogin);
        });
    });
    
    async function handleLogin(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const messagesContainer = form.querySelector('.sikada-form-messages');
        
        // Get form data
        const formData = new FormData(form);
        formData.append('action', 'sikada_auth_login');
        formData.append('nonce', window.sikadaAuthData.nonce);
        
        // Show loading state
        submitButton.disabled = true;
        submitButton.textContent = window.sikadaAuthData.labels.loading || 'Logging in...';
        messagesContainer.innerHTML = '';
        
        try {
            const response = await fetch(window.sikadaAuthData.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                // Show success message
                showMessage(messagesContainer, data.data.message, 'success');
                
                // Redirect after 1 second
                setTimeout(() => {
                    window.location.href = data.data.redirect_url;
                }, 1000);
            } else {
                // Show error message
                showMessage(messagesContainer, data.data.message, 'error');
                submitButton.disabled = false;
                submitButton.textContent = window.sikadaAuthData.labels.login || 'Log In';
            }
        } catch (error) {
            showMessage(messagesContainer, 'An error occurred. Please try again.', 'error');
            submitButton.disabled = false;
            submitButton.textContent = window.sikadaAuthData.labels.login || 'Log In';
        }
    }
    
    function showMessage(container, message, type) {
        container.innerHTML = `<div class="sikada-message sikada-message-${type}">${message}</div>`;
    }
})();
```

### 5. PHP AJAX Handler

**Update**: `src/Auth/LoginHandler.php`

```php
public function init()
{
    // ... existing hooks ...
    
    // AJAX handlers
    add_action('wp_ajax_nopriv_sikada_auth_login', [$this, 'handle_ajax_login']);
    add_action('wp_ajax_sikada_auth_login', [$this, 'handle_ajax_login']);
}

public function handle_ajax_login()
{
    // Verify nonce
    if (!isset($_POST['nonce']) || !wp_verify_nonce($_POST['nonce'], 'sikada_auth_nonce')) {
        wp_send_json_error([
            'message' => __('Security check failed', 'sikada-auth')
        ]);
    }
    
    // Get credentials
    $username = sanitize_text_field($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $remember = isset($_POST['remember']);
    
    // Authenticate
    $user = wp_signon([
        'user_login' => $username,
        'user_password' => $password,
        'remember' => $remember
    ], is_ssl());
    
    if (is_wp_error($user)) {
        wp_send_json_error([
            'message' => $user->get_error_message()
        ]);
    }
    
    // Get redirect URL
    $redirect_url = $this->get_redirect_url($user);
    
    wp_send_json_success([
        'message' => __('Login successful! Redirecting...', 'sikada-auth'),
        'redirect_url' => $redirect_url
    ]);
}
```

### 6. Enqueue Scripts

**Update**: `src/Core/Plugin.php`

```php
private function register_services()
{
    // ... existing services ...
    
    // Enqueue frontend scripts
    add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
}

public function enqueue_frontend_scripts()
{
    if (has_block('sikada-auth/login-form')) {
        wp_enqueue_script(
            'sikada-auth-login-form',
            SIKADA_AUTH_PLUGIN_URL . 'assets/js/login-form.js',
            [],
            SIKADA_AUTH_VERSION,
            true
        );
        
        wp_localize_script('sikada-auth-login-form', 'sikadaAuthData', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('sikada_auth_nonce'),
            'passwordResetUrl' => $this->get_password_reset_url(),
            'labels' => [
                'username' => __('Username or Email', 'sikada-auth'),
                'password' => __('Password', 'sikada-auth'),
                'remember' => __('Remember Me', 'sikada-auth'),
                'login' => __('Log In', 'sikada-auth'),
                'loading' => __('Logging in...', 'sikada-auth'),
                'lostPassword' => __('Lost your password?', 'sikada-auth'),
            ]
        ]);
    }
}
```

## Testing Checklist

- [ ] Block appears in block inserter
- [ ] Block renders in editor
- [ ] Block renders on frontend
- [ ] AJAX login works
- [ ] Error messages display correctly
- [ ] Success message and redirect work
- [ ] Remember me checkbox works
- [ ] Password reset link works
- [ ] Loading state displays
- [ ] Nonce verification works
- [ ] Rate limiting integrates correctly
- [ ] Login is logged to database

## Acceptance Criteria

1. ✅ Login block functional with AJAX
2. ✅ Real-time validation and error handling
3. ✅ User-friendly messages
4. ✅ Integrates with rate limiting
5. ✅ Integrates with login logging
6. ✅ Minimal, theme-friendly styling
7. ✅ Accessibility compliant
8. ✅ Follows all coding standards

## Agent Execution Prompt

```
Create Login Gutenberg block for Sikada Authorization:

1. Create blocks/login-form/ directory with:
   - block.json - Block metadata
   - index.js, edit.js, save.js - React components
   - style.css, editor.css - Styles

2. Create assets/js/login-form.js:
   - AJAX form submission handler
   - Error/success message display
   - Loading states

3. Update src/Auth/LoginHandler.php:
   - Add handle_ajax_login() method
   - Verify nonce, authenticate, return JSON

4. Update src/Core/Plugin.php:
   - Enqueue frontend scripts conditionally
   - Localize script with ajaxUrl, nonce, labels

Requirements:
- Use @wordpress/block-editor components
- AJAX submission (no page reload)
- Proper nonce verification
- User-friendly error messages
- Minimal default styling
- Follow coding standards in docs/CODING_STANDARDS.md

Test by adding block to page and logging in.
```

## Dependencies

**Required Before This Step**:
- Steps 01-07

**Required After This Step**:
- Step 10: Testing

## Related Files

- `blocks/login-form/*`
- `assets/js/login-form.js`
- `src/Auth/LoginHandler.php`
- `src/Core/Plugin.php`

---

**Status**: Ready for Implementation  
**Estimated Time**: 3-4 hours  
**Complexity**: Medium-High
