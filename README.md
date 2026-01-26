# Sikada Authorization

A lightweight, secure, and customizable login and password reset system for WordPress.

## Features

- **Custom Login Forms**: Replace the default `wp-login.php` with a customizable Gutenberg block.
- **Frontend Password Reset**: Allow users to reset passwords directly from your site's frontend.
- **Rate Limiting**: Protect against brute-force attacks with configurable login attempt limits.
- **Login Logging**: Track all successful and failed login attempts with a searchable history.
- **Role-Based Redirects**: Send users to different pages after login based on their role.
- **Email Notifications**: Get alerted when suspicious login activity occurs.
- **Customizable Emails**: Send branded password reset emails.

## Installation

1. Upload the `sikada-auth` folder to the `/wp-content/plugins/` directory.
2. Activate the plugin through the 'Plugins' menu in WordPress.
3. Run `composer install` and `npm install && npm run build` if installing from source.

## Configuration

Navigate to **Settings > Sikada Auth** to configure:

- **General**: Set your custom Login and Password Reset pages.
- **Redirects**: Define where users go after logging in (e.g., Admins to Dashboard, Subscribers to Home).
- **Security**: Adjust rate limiting thresholds and IP whitelists.
- **Email**: Configure "From" name/email and admin alert thresholds.
- **Logs**: Set log retention policies (default: 90 days).

## Usage

### 1. Create a Login Page
1. Create a new page (e.g., "Login").
2. Add the **"Login Form"** block (`sikada-auth/login-form`).
3. Publish the page.
4. Go to **Settings > Sikada Auth** and select this page as the "Login Page".

### 2. Create a Password Reset Page
1. Create a new page (e.g., "Reset Password").
2. Add the **"Password Reset"** block (`sikada-auth/password-reset`).
   *This single block automatically handles both the "Request Link" form and the "New Password" form based on the URL.*
3. Publish the page.
4. Go to **Settings > Sikada Auth** and select this page as the "Password Reset Page".

### 3. Verification
Visit `http://your-site.com/wp-admin/?sikada_test=verify-setup` (Admin only) to verify the plugin's internal health.

## For Developers

See [docs/ARCHITECTURE.md](docs/ARCHITECTURE.md) for codebase structure and extensibility points.

## License

GPL-2.0-or-later
