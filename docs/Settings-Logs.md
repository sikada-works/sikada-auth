# Logs

This tab displays a history of login activity on your site. It is a "View Only" audit trail.

## Settings

### Log Retention (Days)
*   **What it does:** Controls how long the plugin keeps history before automatically deleting old records.
*   **Why it matters:** Keeping logs forever can bloat your database.
*   **Recommendation:** `90` days is standard. Use `30` for busy sites or `365` for strict compliance needs.

## The Log Table

The table below the settings shows recent activity:

*   **Time:** When the event happened.
*   **User:** The username attempted.
*   **IP Address:** The network location of the user.
*   **Type:**
    *   `Success`: Valid login.
    *   `Failed`: Wrong password or username.
    *   `Blocked`: The system stopped this user/IP because of too many failures.
*   **Reason:** Detailed error note (e.g., "Invalid Password").

> **Tip:** You can use the search bar at the top right of the table to look for specific IP addresses or Usernames to investigate suspicious activity.
