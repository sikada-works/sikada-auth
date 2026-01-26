# Step 07: Admin Settings Panel

## Objective

Create comprehensive admin settings panel for configuring all plugin features including page assignments, redirects, rate limiting, email settings, and security options.

## Prerequisites

- Steps 01-06 completed
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## Settings Structure

### Settings Tabs

1. **General** - Page assignments, basic settings
2. **Redirects** - Role-based login redirects
3. **Security** - Rate limiting, password strength
4. **Email** - Email headers, alert configuration
5. **Logs** - View logs, retention settings

## Implementation

### 1. Create SettingsPage Class

**File**: `src/Admin/SettingsPage.php`

**Key Methods**:
- `init()` - Register hooks
- `add_menu_page()` - Add admin menu
- `register_settings()` - Register all settings
- `render_page()` - Render settings page with tabs
- `render_general_tab()` - Page assignments
- `render_redirects_tab()` - Role-based redirects
- `render_security_tab()` - Rate limiting, password strength
- `render_email_tab()` - Email configuration
- `render_logs_tab()` - View login logs

### 2. Settings to Register

**General Tab**:
- `sikada_auth_login_page` - Login page ID
- `sikada_auth_password_reset_page` - Password reset page ID
- `sikada_auth_logout_redirect` - Logout redirect URL

**Redirects Tab**:
- `sikada_auth_default_redirect` - Default login redirect
- `sikada_auth_redirect_administrator` - Admin redirect
- `sikada_auth_redirect_editor` - Editor redirect
- `sikada_auth_redirect_author` - Author redirect
- `sikada_auth_redirect_contributor` - Contributor redirect
- `sikada_auth_redirect_subscriber` - Subscriber redirect

**Security Tab**:
- `sikada_auth_enable_rate_limiting` - Enable/disable
- `sikada_auth_rate_limit_username` - Max attempts per username
- `sikada_auth_rate_limit_ip` - Max attempts per IP
- `sikada_auth_ip_whitelist` - Whitelisted IPs
- `sikada_auth_enforce_password_strength` - Enable/disable
- `sikada_auth_min_password_length` - Minimum length
- `sikada_auth_require_uppercase` - Require uppercase
- `sikada_auth_require_lowercase` - Require lowercase
- `sikada_auth_require_number` - Require number
- `sikada_auth_require_special` - Require special char

**Email Tab**:
- `sikada_auth_email_from_name` - From name
- `sikada_auth_email_from_email` - From email
- `sikada_auth_email_reply_to` - Reply-to email
- `sikada_auth_enable_admin_alerts` - Enable alerts
- `sikada_auth_alert_threshold` - Alert threshold
- `sikada_auth_alert_recipients` - Alert recipients

**Logs Tab**:
- `sikada_auth_log_retention_days` - Log retention (days)

### 3. Create LoginLogsPage Class

**File**: `src/Admin/LoginLogsPage.php`

Display login logs with filtering:
- Filter by date range
- Filter by user
- Filter by IP
- Filter by status
- Export to CSV
- Pagination

## Testing Checklist

- [ ] Settings page accessible in admin menu
- [ ] All tabs render correctly
- [ ] Settings save properly
- [ ] Page selectors work
- [ ] Role-based redirects configurable
- [ ] Rate limiting settings apply
- [ ] Password strength settings apply
- [ ] Email settings apply
- [ ] Login logs display correctly
- [ ] Filters work on logs page
- [ ] Export to CSV works
- [ ] Multisite: Network settings work

## Acceptance Criteria

1. ✅ Complete admin settings panel with all tabs
2. ✅ All settings save and load correctly
3. ✅ Login logs viewer functional
4. ✅ Multisite support (network + per-site settings)
5. ✅ User-friendly interface
6. ✅ Follows WordPress admin UI patterns
7. ✅ Follows all coding standards

## Agent Execution Prompt

```
Create admin settings panel for Sikada Authorization:

1. Create src/Admin/SettingsPage.php:
   - Multi-tab settings page
   - Register all settings with WordPress Settings API
   - Render forms for each tab
   - Use WordPress UI components (wp_dropdown_pages, etc.)

2. Create src/Admin/LoginLogsPage.php:
   - Display login logs in WP_List_Table format
   - Add filters (date, user, IP, status)
   - Add export to CSV functionality
   - Pagination support

3. Update src/Core/Plugin.php:
   - Register SettingsPage and LoginLogsPage

Requirements:
- Use WordPress Settings API
- Proper nonce verification
- Input sanitization
- Capability checks (manage_options)
- Multisite support
- Follow coding standards in docs/CODING_STANDARDS.md

Test by accessing admin panel and configuring settings.
```

## Dependencies

**Required Before This Step**:
- Steps 01-06

**Required After This Step**:
- Steps 08-09 (blocks use these settings)

## Related Files

- `src/Admin/SettingsPage.php`
- `src/Admin/LoginLogsPage.php`
- `src/Core/Plugin.php`

---

**Status**: Ready for Implementation  
**Estimated Time**: 4-5 hours  
**Complexity**: High
