# Redirect Settings

This tab controls where users are sent **after they successfully log in**. You can set a global default or customize it based on the user's role (e.g., Administrators go to the Dashboard, while Subscribers go to the Homepage).

## Settings Explained

### Default Redirect
*   **What it does:** The fallback destination for any user not covered by a specific role rule below.
*   **How to set it:** Enter a full URL (e.g., `https://mysite.com/welcome/`).
*   **Default Behavior:** If left blank, users will be sent to the WordPress Dashboard (`/wp-admin/`).

### Role-Based Redirects
These settings allow you to override the default for specific types of users.

*   **Administrator:** Usually matches your site functionality. Often left blank (defaults to Dashboard) so admins can manage the site.
*   **Editor / Author / Contributor:** Set these if your team needs to go straight to a specific publishing dashboard or frontend editor.
*   **Subscriber:** This is the most common setting for membership sites. You might want to send Subscribers to a "My Profile" page or the "Members Area" instead of the confusing WordPress Dashboard.

> **Tip:** If a specific redirect is not set for a role, the **Default Redirect** (top setting) will be used.
