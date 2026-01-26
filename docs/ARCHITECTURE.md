# Sikada Authorization - Architecture Overview

## Directories

- **`src/`**: PHP source code (PSR-4 autoloaded as `SikadaWorks\SikadaAuth`).
  - **`Admin/`**: Admin settings pages and UI logic.
  - **`Auth/`**: Core authentication handlers (`LoginHandler`, `PasswordResetHandler`, `LoginLogger`).
  - **`Core/`**: Plugin bootstrap and service registration (`Plugin.php`).
  - **`Database/`**: Database schema management.
  - **`Email/`**: Email sending and templating services.
  - **`Redirect/`**: Logic for handling login/logout redirects and URL rewriting.
  - **`Tests/`**: `TestRunner` class for internal verification.
- **`blocks/`**: Gutenberg block source files (React/JSX).
- **`assets/`**: Compiled frontend assets and raw scripts/styles.
- **`tests/`**: Ad-hoc verification scripts run by `TestRunner`.

## Key Components

### 1. Authentication Handlers
`src/Auth/LoginHandler.php` hooks into `authenticate`, `wp_login`, and `wp_login_failed`. It integrates with:
- **RateLimiter**: Checks login attempts against IP/Username limits.
- **LoginLogger**: Records the attempt outcome.

### 2. Rate Limiting
`src/Auth/RateLimiter.php` uses transients to track failed attempts.
- **Block Duration**: 15 minutes (default).
- **Triggers**: Configurable max attempts per IP or Username.

### 3. Frontend Forms (Gutenberg Blocks)
Built using `@wordpress/scripts`.
- **Ajax Handlers**: Forms submit to `admin-ajax.php`.
- **Nonce Security**: All AJAX requests require `sikada_auth_nonce`.
- **Localization**: `sikadaAuthData` global object passes config (nonce, AJAX URL, labels) to frontend.

### 4. URL Redirection
`src/Redirect/URLRedirector.php` intercepts requests to `wp-login.php`.
- Redirects to custom login page if configured.
- Prevents infinite loops by checking current page ID.

## Database Schema
**`wp_sikada_auth_login_attempts`**
- `id`: Primary Key
- `user_login`: Username attempted
- `ip_address`: Request IP
- `attempt_type`: 'login_success', 'login_failed', 'blocked_rate_limit', etc.
- `status`: 'success', 'failed', 'blocked'
- `failure_reason`: Error code (e.g., 'invalid_password')
- `created_at`: Timestamp

## Hooks & Filters

### Actions
- `sikada_auth_login_failed`: Fires after a failed login attempt.
- `sikada_auth_password_changed`: Fires after successful password reset.

### Filters
- `sikada_auth_login_redirect`: Modify the redirect URL after login.
- `sikada_auth_email_template`: Modify email template arguments before sending.

## Testing
Internal verification tool available at `?sikada_test=verify-setup`.
Scripts located in root `tests/` directory are executed by `src/Tests/TestRunner.php`.
