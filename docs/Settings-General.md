# General Settings

This tab handles the fundamental configuration of the Sikada Authorization plugin. These settings tell the plugin where your key pages are located.

## Settings Explained

### Login Page
*   **What it does:** Tells the plugin which page on your site contains the **Login Form** block.
*   **How to set it:** Select the page you created for logging in (e.g., "Log In" or "Sign In") from the dropdown menu.
*   **Why it matters:** When users try to access restricted content or click a "Log In" link, they will be automatically redirected to this page instead of the default WordPress login screen.
*   **Note:** If you leave this empty, the plugin will use the default WordPress login page (`/wp-login.php`).

### Password Reset Page
*   **What it does:** Tells the plugin which page contains the **Password Reset** block.
*   **How to set it:** Select the page you created for password resets (e.g., "Reset Password").
*   **Why it matters:** When users click "Lost your password?", they will be sent here to request a new one. The link emailed to them will also direct them back to this page to set their new password.

### Logout Redirect URL
*   **What it does:** Determines where a user goes immediately after they log out.
*   **How to set it:** Enter a full URL (e.g., `https://mysite.com/goodbye/`) or leave it blank.
*   **Default Behavior:** If you leave this blank, users will be redirected back to the Login Page after logging out.
