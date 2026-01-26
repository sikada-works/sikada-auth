# Step 09: Password Reset Gutenberg Block

## Objective

Create combined Password Reset Gutenberg block that displays either the reset request form or the reset password form based on URL parameters.

## Prerequisites

- Steps 01-08 completed
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## Block States

**State 1: Reset Request Form** (no URL params)
- Email/username input
- Submit button
- Back to login link
- Success message after submission

**State 2: Reset Password Form** (has `?key=` and `?login=` params)
- New password input
- Confirm password input
- Password strength indicator
- Submit button
- Success message
- Auto-redirect to login after success

**State 3: Error States**
- Invalid/expired reset key
- Link to request new reset

## Implementation

### 1. Create Block Files

**Directory**: `blocks/password-reset/`

**Files**:
- `block.json`
- `index.js`
- `edit.js`
- `save.js`
- `style.css`
- `editor.css`

### 2. Frontend Component

**File**: `blocks/password-reset/save.js`

```javascript
import { useBlockProps } from '@wordpress/block-editor';

export default function Save() {
    const blockProps = useBlockProps.save();
    
    return (
        <div {...blockProps} className="sikada-auth-password-reset">
            <div data-sikada-reset-form>
                {/* Form will be rendered by JavaScript based on URL params */}
            </div>
        </div>
    );
}
```

### 3. AJAX Handler

**Create**: `assets/js/password-reset.js`

```javascript
(function() {
    'use strict';
    
    document.addEventListener('DOMContentLoaded', function() {
        const containers = document.querySelectorAll('[data-sikada-reset-form]');
        
        containers.forEach(container => {
            initPasswordResetForm(container);
        });
    });
    
    function initPasswordResetForm(container) {
        const urlParams = new URLSearchParams(window.location.search);
        const resetKey = urlParams.get('key');
        const userLogin = urlParams.get('login');
        
        if (resetKey && userLogin) {
            renderResetForm(container, resetKey, userLogin);
        } else {
            renderRequestForm(container);
        }
    }
    
    function renderRequestForm(container) {
        container.innerHTML = `
            <form class="sikada-reset-request-form">
                <div class="sikada-form-messages"></div>
                
                <p>${window.sikadaAuthData.labels.resetInstructions || 'Enter your email address and we will send you a link to reset your password.'}</p>
                
                <div class="sikada-form-field">
                    <label for="sikada-reset-email">
                        ${window.sikadaAuthData.labels.emailUsername || 'Email or Username'}
                    </label>
                    <input type="text" id="sikada-reset-email" name="user_login" required />
                </div>
                
                <div class="sikada-form-actions">
                    <button type="submit" class="sikada-button sikada-button-primary">
                        ${window.sikadaAuthData.labels.sendResetLink || 'Send Reset Link'}
                    </button>
                </div>
                
                <div class="sikada-form-links">
                    <a href="${window.sikadaAuthData.loginUrl || '#'}">
                        ${window.sikadaAuthData.labels.backToLogin || 'Back to Login'}
                    </a>
                </div>
            </form>
        `;
        
        container.querySelector('form').addEventListener('submit', handleResetRequest);
    }
    
    function renderResetForm(container, key, login) {
        container.innerHTML = `
            <form class="sikada-reset-password-form">
                <div class="sikada-form-messages"></div>
                
                <input type="hidden" name="key" value="${key}" />
                <input type="hidden" name="login" value="${login}" />
                
                <div class="sikada-form-field">
                    <label for="sikada-new-password">
                        ${window.sikadaAuthData.labels.newPassword || 'New Password'}
                    </label>
                    <input type="password" id="sikada-new-password" name="password" required />
                    <div class="sikada-password-strength"></div>
                </div>
                
                <div class="sikada-form-field">
                    <label for="sikada-confirm-password">
                        ${window.sikadaAuthData.labels.confirmPassword || 'Confirm Password'}
                    </label>
                    <input type="password" id="sikada-confirm-password" name="password_confirm" required />
                </div>
                
                <div class="sikada-form-actions">
                    <button type="submit" class="sikada-button sikada-button-primary">
                        ${window.sikadaAuthData.labels.resetPassword || 'Reset Password'}
                    </button>
                </div>
            </form>
        `;
        
        const form = container.querySelector('form');
        form.addEventListener('submit', handlePasswordReset);
        
        // Add password strength indicator
        const passwordInput = form.querySelector('#sikada-new-password');
        passwordInput.addEventListener('input', updatePasswordStrength);
    }
    
    async function handleResetRequest(e) {
        e.preventDefault();
        
        const form = e.target;
        const submitButton = form.querySelector('button[type="submit"]');
        const messagesContainer = form.querySelector('.sikada-form-messages');
        
        const formData = new FormData(form);
        formData.append('action', 'sikada_auth_reset_request');
        formData.append('nonce', window.sikadaAuthData.nonce);
        
        submitButton.disabled = true;
        submitButton.textContent = window.sikadaAuthData.labels.sending || 'Sending...';
        
        try {
            const response = await fetch(window.sikadaAuthData.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage(messagesContainer, data.data.message, 'success');
                form.reset();
            } else {
                showMessage(messagesContainer, data.data.message, 'error');
            }
        } catch (error) {
            showMessage(messagesContainer, 'An error occurred. Please try again.', 'error');
        } finally {
            submitButton.disabled = false;
            submitButton.textContent = window.sikadaAuthData.labels.sendResetLink || 'Send Reset Link';
        }
    }
    
    async function handlePasswordReset(e) {
        e.preventDefault();
        
        const form = e.target;
        const password = form.querySelector('[name="password"]').value;
        const passwordConfirm = form.querySelector('[name="password_confirm"]').value;
        const messagesContainer = form.querySelector('.sikada-form-messages');
        
        // Validate passwords match
        if (password !== passwordConfirm) {
            showMessage(messagesContainer, 'Passwords do not match.', 'error');
            return;
        }
        
        const formData = new FormData(form);
        formData.append('action', 'sikada_auth_reset_password');
        formData.append('nonce', window.sikadaAuthData.nonce);
        
        const submitButton = form.querySelector('button[type="submit"]');
        submitButton.disabled = true;
        
        try {
            const response = await fetch(window.sikadaAuthData.ajaxUrl, {
                method: 'POST',
                body: formData
            });
            
            const data = await response.json();
            
            if (data.success) {
                showMessage(messagesContainer, data.data.message, 'success');
                
                // Redirect to login after 3 seconds
                setTimeout(() => {
                    window.location.href = window.sikadaAuthData.loginUrl;
                }, 3000);
            } else {
                showMessage(messagesContainer, data.data.message, 'error');
                submitButton.disabled = false;
            }
        } catch (error) {
            showMessage(messagesContainer, 'An error occurred. Please try again.', 'error');
            submitButton.disabled = false;
        }
    }
    
    function updatePasswordStrength(e) {
        const password = e.target.value;
        const strengthContainer = e.target.parentElement.querySelector('.sikada-password-strength');
        
        // Use zxcvbn or simple strength check
        const strength = calculatePasswordStrength(password);
        
        strengthContainer.innerHTML = `
            <div class="strength-bar strength-${strength.level}">
                <div class="strength-fill" style="width: ${strength.percent}%"></div>
            </div>
            <span class="strength-text">${strength.text}</span>
        `;
    }
    
    function calculatePasswordStrength(password) {
        // Simple strength calculation
        let score = 0;
        if (password.length >= 8) score++;
        if (password.length >= 12) score++;
        if (/[a-z]/.test(password)) score++;
        if (/[A-Z]/.test(password)) score++;
        if (/[0-9]/.test(password)) score++;
        if (/[^a-zA-Z0-9]/.test(password)) score++;
        
        const levels = ['weak', 'fair', 'good', 'strong', 'very-strong'];
        const texts = ['Weak', 'Fair', 'Good', 'Strong', 'Very Strong'];
        const level = Math.min(Math.floor(score / 1.5), 4);
        
        return {
            level: levels[level],
            percent: (score / 6) * 100,
            text: texts[level]
        };
    }
    
    function showMessage(container, message, type) {
        container.innerHTML = `<div class="sikada-message sikada-message-${type}">${message}</div>`;
    }
})();
```

### 4. PHP AJAX Handlers

**Update**: `src/Auth/PasswordResetHandler.php`

```php
public function init()
{
    add_action('wp_ajax_nopriv_sikada_auth_reset_request', [$this, 'handle_ajax_reset_request']);
    add_action('wp_ajax_nopriv_sikada_auth_reset_password', [$this, 'handle_ajax_reset_password']);
}

public function handle_ajax_reset_request()
{
    // Verify nonce
    check_ajax_referer('sikada_auth_nonce', 'nonce');
    
    $user_login = sanitize_text_field($_POST['user_login'] ?? '');
    
    // Get user by email or username
    $user = get_user_by('email', $user_login);
    if (!$user) {
        $user = get_user_by('login', $user_login);
    }
    
    if (!$user) {
        wp_send_json_error([
            'message' => __('Invalid email or username.', 'sikada-auth')
        ]);
    }
    
    // Generate reset key
    $key = get_password_reset_key($user);
    
    if (is_wp_error($key)) {
        wp_send_json_error([
            'message' => $key->get_error_message()
        ]);
    }
    
    // Send email
    $email_sender = new \SikadaWorks\SikadaAuth\Email\EmailSender();
    $reset_url = add_query_arg([
        'key' => $key,
        'login' => rawurlencode($user->user_login)
    ], $this->get_reset_page_url());
    
    $email_sender->send($user->user_email, 'password-reset-request', [
        'user_login' => $user->user_login,
        'user_email' => $user->user_email,
        'user_display_name' => $user->display_name,
        'reset_link' => $reset_url,
        'reset_key' => $key,
        'expiration_time' => '24 hours'
    ]);
    
    // Log attempt
    $logger = new \SikadaWorks\SikadaAuth\Auth\LoginLogger();
    $logger->log_attempt([
        'user_login' => $user->user_login,
        'user_id' => $user->ID,
        'attempt_type' => 'password_reset_request',
        'status' => 'success'
    ]);
    
    wp_send_json_success([
        'message' => __('Check your email for the password reset link.', 'sikada-auth')
    ]);
}

public function handle_ajax_reset_password()
{
    // Verify nonce
    check_ajax_referer('sikada_auth_nonce', 'nonce');
    
    $key = sanitize_text_field($_POST['key'] ?? '');
    $login = sanitize_text_field($_POST['login'] ?? '');
    $password = $_POST['password'] ?? '';
    
    // Validate reset key
    $user = check_password_reset_key($key, $login);
    
    if (is_wp_error($user)) {
        wp_send_json_error([
            'message' => __('Invalid or expired reset link.', 'sikada-auth')
        ]);
    }
    
    // Validate password strength
    $strength_validator = new \SikadaWorks\SikadaAuth\Auth\PasswordStrengthValidator();
    $validation = $strength_validator->validate($password);
    
    if (is_wp_error($validation)) {
        wp_send_json_error([
            'message' => $validation->get_error_message()
        ]);
    }
    
    // Reset password
    reset_password($user, $password);
    
    // Log attempt
    $logger = new \SikadaWorks\SikadaAuth\Auth\LoginLogger();
    $logger->log_attempt([
        'user_login' => $user->user_login,
        'user_id' => $user->ID,
        'attempt_type' => 'password_reset_completed',
        'status' => 'success'
    ]);
    
    wp_send_json_success([
        'message' => __('Password reset successfully! Redirecting to login...', 'sikada-auth')
    ]);
}
```

## Testing Checklist

- [ ] Block renders correctly
- [ ] Request form displays without URL params
- [ ] Reset form displays with URL params
- [ ] Reset request sends email
- [ ] Reset link works
- [ ] Password strength indicator works
- [ ] Password validation works
- [ ] Success messages display
- [ ] Redirect to login works
- [ ] Invalid key shows error
- [ ] All attempts logged

## Acceptance Criteria

1. ✅ Combined block with two states
2. ✅ AJAX-based form submission
3. ✅ Password strength indicator
4. ✅ Email integration
5. ✅ Logging integration
6. ✅ User-friendly error handling
7. ✅ Follows all coding standards

## Agent Execution Prompt

```
Create Password Reset Gutenberg block for Sikada Authorization:

1. Create blocks/password-reset/ with block files
2. Create assets/js/password-reset.js with:
   - State detection (URL params)
   - Request form rendering
   - Reset form rendering
   - Password strength indicator
   - AJAX handlers

3. Update src/Auth/PasswordResetHandler.php:
   - handle_ajax_reset_request()
   - handle_ajax_reset_password()
   - Email integration
   - Logging integration

4. Create src/Auth/PasswordStrengthValidator.php:
   - Validate password requirements
   - Check against blacklist

Requirements:
- Detect URL params to show correct form
- Real-time password strength
- Proper validation
- Follow coding standards in docs/CODING_STANDARDS.md

Test by requesting password reset and completing flow.
```

## Dependencies

**Required Before This Step**:
- Steps 01-08

**Required After This Step**:
- Step 10: Testing

## Related Files

- `blocks/password-reset/*`
- `assets/js/password-reset.js`
- `src/Auth/PasswordResetHandler.php`
- `src/Auth/PasswordStrengthValidator.php`

---

**Status**: Ready for Implementation  
**Estimated Time**: 4-5 hours  
**Complexity**: High
