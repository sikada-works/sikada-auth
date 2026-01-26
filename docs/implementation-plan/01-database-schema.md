# Step 01: Database Schema

## Objective

Create the database schema for the Sikada Authorization plugin, including the login attempts table for logging and audit trail functionality.

## Prerequisites

- Plugin structure initialized
- Database Schema class exists at `src/Database/Schema.php`
- Coding standards reviewed: [CODING_STANDARDS.md](../CODING_STANDARDS.md)

## Database Tables

### Table 1: Login Attempts Log

**Table Name**: `{prefix}sikada_auth_login_attempts`

**Purpose**: Network-wide logging of all authentication attempts for security audit and rate limiting.

**Schema**:
```sql
CREATE TABLE {prefix}sikada_auth_login_attempts (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  blog_id bigint(20) unsigned NOT NULL DEFAULT 1,
  user_login varchar(255) NOT NULL,
  user_id bigint(20) unsigned DEFAULT NULL,
  ip_address varchar(45) NOT NULL,
  user_agent text,
  attempt_type varchar(50) NOT NULL,
  status varchar(20) NOT NULL,
  failure_reason varchar(255) DEFAULT NULL,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY blog_id (blog_id),
  KEY user_login (user_login),
  KEY user_id (user_id),
  KEY ip_address (ip_address),
  KEY created_at (created_at),
  KEY attempt_type (attempt_type),
  KEY status (status)
) {charset_collate};
```

**Column Descriptions**:
- `id`: Auto-incrementing primary key
- `blog_id`: Site ID for multisite (1 for single site)
- `user_login`: Username or email attempted
- `user_id`: WordPress user ID (NULL if user doesn't exist)
- `ip_address`: IPv4 or IPv6 address
- `user_agent`: Browser user agent string
- `attempt_type`: Type of attempt (see values below)
- `status`: Result of attempt (see values below)
- `failure_reason`: Specific reason for failure
- `created_at`: Timestamp of attempt

**Attempt Types**:
- `login_success` - Successful login
- `login_failed` - Failed login attempt
- `password_reset_request` - Password reset requested
- `password_reset_completed` - Password successfully reset
- `logout` - User logged out
- `blocked_rate_limit` - Blocked by rate limiter

**Status Values**:
- `success` - Attempt succeeded
- `failed` - Attempt failed
- `blocked` - Blocked by security measures

**Failure Reasons**:
- `invalid_username` - Username doesn't exist
- `incorrect_password` - Wrong password
- `rate_limit_username` - Too many attempts for username
- `rate_limit_ip` - Too many attempts from IP
- `invalid_reset_key` - Invalid or expired reset key
- `weak_password` - Password doesn't meet strength requirements

## Implementation Tasks

### 1. Update Schema.php

**File**: `src/Database/Schema.php`

**Requirements**:
- Add table constant: `const TABLE_LOGIN_ATTEMPTS = 'sikada_auth_login_attempts';`
- Implement `create_tables()` method with proper dbDelta formatting
- Follow dbDelta formatting rules exactly (see coding standards)
- Add error logging for failed table creation
- Include charset collation: `$wpdb->get_charset_collate()`

**Critical dbDelta Rules**:
- Two spaces (not tabs) after `PRIMARY KEY`
- Two spaces after `KEY`
- Uppercase SQL keywords
- No trailing spaces
- Each column on separate line

### 2. Update Plugin Activation

**File**: `src/Core/Plugin.php`

**Requirements**:
- Call `Schema::create_tables()` in `activate()` method
- Ensure proper error handling
- Log any schema errors

### 3. Update Uninstall Script

**File**: `uninstall.php`

**Requirements**:
- Add table cleanup: `DROP TABLE IF EXISTS {$wpdb->prefix}sikada_auth_login_attempts`
- Ensure multisite compatibility (loop through all sites if network-wide)

## Testing Checklist

- [ ] Plugin activates without errors
- [ ] Table is created with correct structure
- [ ] Table has proper indexes
- [ ] Table uses correct charset/collation
- [ ] Multisite: Table created on network activation
- [ ] Multisite: Table accessible from all sites
- [ ] Plugin deactivation doesn't drop table
- [ ] Plugin deletion (uninstall) drops table correctly

## Acceptance Criteria

1. ✅ Database table created successfully on plugin activation
2. ✅ Table structure matches specification exactly
3. ✅ All indexes created properly
4. ✅ Works on single site and multisite
5. ✅ Uninstall script properly removes table
6. ✅ No PHP errors or warnings
7. ✅ Follows all coding standards (PSR-4, WordPress, dbDelta rules)

## Agent Execution Prompt

```
Create the database schema for the Sikada Authorization plugin following these requirements:

1. Update src/Database/Schema.php:
   - Add TABLE_LOGIN_ATTEMPTS constant
   - Implement create_tables() method
   - Create sikada_auth_login_attempts table with exact schema provided
   - Follow dbDelta formatting rules precisely (two spaces after PRIMARY KEY and KEY)
   - Use $wpdb->get_charset_collate()
   - Add error logging

2. Update src/Core/Plugin.php:
   - Call Schema::create_tables() in activate() method
   - Add proper error handling

3. Update uninstall.php:
   - Add DROP TABLE statement for sikada_auth_login_attempts
   - Handle multisite (loop through sites if needed)

Follow all standards in docs/CODING_STANDARDS.md, especially:
- PSR-4 namespace: SikadaWorks\SikadaAuth\Database
- Proper PHPDoc comments
- Error logging
- dbDelta formatting rules

Test by activating/deactivating plugin and verify table creation.
```

## Dependencies

**Required Before This Step**:
- None (first implementation step)

**Required After This Step**:
- Step 02: Core Structure (uses this schema)
- Step 04: Rate Limiting (queries this table)
- Step 05: Login Logging (inserts into this table)

## Related Files

- `src/Database/Schema.php` - Main implementation
- `src/Core/Plugin.php` - Activation hook
- `uninstall.php` - Cleanup
- `docs/CODING_STANDARDS.md` - Reference

## Notes

- This is a network-wide table for multisite installations
- The `blog_id` column allows filtering logs per site
- Indexes are crucial for performance with large log volumes
- Consider adding a cleanup cron job in future phase to auto-delete old logs

---

**Status**: Ready for Implementation  
**Estimated Time**: 1-2 hours  
**Complexity**: Medium
