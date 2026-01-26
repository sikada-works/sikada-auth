<?php
/**
 * Uninstall script - runs when plugin is deleted
 *
 * This file is called automatically by WordPress when the plugin
 * is deleted via the WordPress admin interface.
 *
 * @package SikadaWorks\SikadaAuth
 * @since 1.0.0
 */

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
delete_option('sikada_auth_version');
delete_option('sikada_auth_settings');

// Delete transients
delete_transient('sikada_auth_cache');

// Delete tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}sikada_auth_login_attempts");

// Delete user meta created by the plugin
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE 'sikada_auth_%'");

// Delete post meta created by the plugin
$wpdb->query("DELETE FROM {$wpdb->postmeta} WHERE meta_key LIKE 'sikada_auth_%'");

// Clear any cached data
wp_cache_flush();

// Fire uninstall hook for extensions
do_action('sikada_auth_uninstalled');
