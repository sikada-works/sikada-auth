# Security Settings

This functional tab protects your site from hackers and brute-force attacks. It controls lockouts and password strength requirements.

## Rate Limiting (Brute Force Protection)

### Enable Rate Limiting
*   **What it does:** Turns the security system on or off.
*   **Recommendation:** Keep this **CHECKED** at all times to protect your site.

### Max Attempts per Username
*   **What it does:** Locks a specific username after a certain number of failed password guesses.
*   **Recommended Value:** `5` attempts.

### Max Attempts per IP
*   **What it does:** Locks an IP address (device) if it fails to log in too many times, regardless of which username it tries.
*   **Recommended Value:** `10` attempts.

### IP Whitelist
*   **What it does:** A list of "safe" IP addresses that will **never** be locked out, even if they fail to log in 100 times.
*   **How to use:** Enter IP addresses separated by commas (e.g., `192.168.1.1, 10.0.0.5`). Useful for office networks or admin locations.

---

## Password Strength

### Enforce Password Strength
*   **What it does:** Forces users to choose a strong password when they reset or change their credentials.
*   **Recommendation:** Check this box to prevent users from setting passwords like "123456".

### Minimum Password Length
*   **What it does:** Sets the shortest allowed password.
*   **Recommendation:** `8` characters minimum (12 is better for high security).

### Password Requirements
Check these boxes to enforce complexity rules:
*   **Require uppercase letter:** (A-Z)
*   **Require lowercase letter:** (a-z)
*   **Require number:** (0-9)
*   **Require special character:** (!@#$%)
