# Email Settings

This tab configures the automated emails sent by the plugin, such as "Password Reset" links and security alerts.

## Email Headers

### From Name
*   **What it does:** The name that appears in the user's inbox as the sender.
*   **Example:** "My Website Support" or "The Sikada Team".
*   **Default:** Your WordPress Site Title.

### From Email
*   **What it does:** The email address the message comes *from*.
*   **Important:** Use an email address that actually exists on your domain (e.g., `noreply@yoursite.com`) to prevent emails from going to Spam.

### Reply-To Email
*   **What it does:** The address used if a user hits "Reply" on the automated email.
*   **Example:** `support@yoursite.com`.

---

## Admin Alerts

### Enable Admin Alerts
*   **What it does:** Sends an email to you (the site admin) whenever a security event happens (like someone getting locked out).
*   **Recommendation:** Check this if you want to be notified of attacks in real-time.

### Alert Threshold
*   **What it does:** Controls how sensitive the system is. "Send an email after X failed attempts."
*   **Default:** `3`.

### Alert Recipients
*   **What it does:** Who should receive these security alerts?
*   **How to set:** Enter email addresses separated by commas. Defaults to the main site administrator.
