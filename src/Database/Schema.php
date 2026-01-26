<?php

namespace SikadaWorks\SikadaAuth\Database;

/**
 * Database Schema Management
 *
 * Handles creation and management of custom database tables.
 *
 * @since 1.0.0
 */
class Schema
{
	/**
	 * Table name constants
	 */
	const TABLE_LOGIN_ATTEMPTS = 'sikada_auth_login_attempts';

	/**
	 * Create database tables
	 *
	 * CRITICAL: Follow dbDelta formatting rules exactly:
	 * - Two spaces (not tabs) after PRIMARY KEY
	 * - Two spaces after KEY
	 * - Uppercase SQL keywords
	 * - No trailing spaces
	 *
	 * @since 1.0.0
	 */
	public static function create_tables()
	{
		global $wpdb;

		$charset_collate = $wpdb->get_charset_collate();

		// Login attempts table
		$table_name = $wpdb->prefix . self::TABLE_LOGIN_ATTEMPTS;

		$sql = "CREATE TABLE $table_name (
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
) $charset_collate;";

		require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
		dbDelta($sql);

		// Log any errors
		if ($wpdb->last_error) {
			error_log('Sikada Authorization Schema Error: ' . $wpdb->last_error);
		}
	}

	/**
	 * Get table name with prefix
	 *
	 * @since 1.0.0
	 * @param string $table Table name constant
	 * @return string Full table name with WordPress prefix
	 */
	public static function get_table_name($table)
	{
		global $wpdb;
		return $wpdb->prefix . $table;
	}
}
