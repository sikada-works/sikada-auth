<?php

namespace SikadaWorks\SikadaAuth\Core;

/**
 * Main Plugin Class
 *
 * Handles plugin initialization and service registration.
 *
 * @since 1.0.0
 */
class Plugin
{
	/**
	 * Plugin instance
	 *
	 * @var Plugin
	 */
	private static $instance;

	/**
	 * Get plugin instance
	 *
	 * @since 1.0.0
	 * @return Plugin
	 */
	public static function get_instance()
	{
		if (!isset(self::$instance)) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Constructor
	 *
	 * @since 1.0.0
	 */
	private function __construct()
	{
		$this->init();
	}

	/**
	 * Initialize plugin
	 *
	 * @since 1.0.0
	 */
	private function init()
	{
		// Load text domain
		add_action('init', [$this, 'load_textdomain']);

		// Register services
		$this->register_services();

		// Fire loaded hook
		do_action('sikada_auth_loaded');
	}

	/**
	 * Load plugin text domain
	 *
	 * @since 1.0.0
	 */
	public function load_textdomain()
	{
		load_plugin_textdomain(
			'sikada-auth',
			false,
			dirname(plugin_basename(SIKADA_AUTH_PLUGIN_FILE)) . '/languages'
		);
	}

	/**
	 * Register plugin services
	 *
	 * @since 1.0.0
	 */
	private function register_services()
	{
		// Register authentication handlers
		if (class_exists('SikadaWorks\\SikadaAuth\\Auth\\LoginHandler')) {
			(new \SikadaWorks\SikadaAuth\Auth\LoginHandler())->init();
		}

		if (class_exists('SikadaWorks\\SikadaAuth\\Auth\\PasswordResetHandler')) {
			(new \SikadaWorks\SikadaAuth\Auth\PasswordResetHandler())->init();
		}

		// Register URL redirector
		if (class_exists('SikadaWorks\\SikadaAuth\\Redirect\\URLRedirector')) {
			(new \SikadaWorks\SikadaAuth\Redirect\URLRedirector())->init();
		}

		// Register Global Protection
		if (class_exists('SikadaWorks\\SikadaAuth\\Redirect\\GlobalProtection')) {
			(new \SikadaWorks\SikadaAuth\Redirect\GlobalProtection())->init();
		}

		// Register admin pages
		if (class_exists('SikadaWorks\\SikadaAuth\\Admin\\SettingsPage')) {
			(new \SikadaWorks\SikadaAuth\Admin\SettingsPage())->init();
		}



		// Register test runner
		if (class_exists('SikadaWorks\\SikadaAuth\\Tests\\TestRunner')) {
			(new \SikadaWorks\SikadaAuth\Tests\TestRunner())->init();
		}

		// Register blocks
		add_action('init', [$this, 'register_blocks']);

		// Enqueue frontend scripts
		add_action('wp_enqueue_scripts', [$this, 'enqueue_frontend_scripts']);
	}

	/**
	 * Register Gutenberg blocks
	 *
	 * @since 1.0.0
	 */
	public function register_blocks()
	{
		register_block_type(SIKADA_AUTH_PLUGIN_DIR . 'blocks/login-form');
		register_block_type(SIKADA_AUTH_PLUGIN_DIR . 'blocks/password-reset');
	}

	/**
	 * Enqueue frontend scripts
	 *
	 * @since 1.0.0
	 */
	public function enqueue_frontend_scripts()
	{
		$has_login = has_block('sikada-auth/login-form');
		$has_reset = has_block('sikada-auth/password-reset');

		if ($has_login || $has_reset) {
			wp_enqueue_style(
				'sikada-auth-shared-styles',
				SIKADA_AUTH_PLUGIN_URL . 'assets/css/shared-styles.css',
				[],
				SIKADA_AUTH_VERSION
			);
		}

		if ($has_login) {
			wp_enqueue_script(
				'sikada-auth-login-form',
				SIKADA_AUTH_PLUGIN_URL . 'assets/js/login-form.js',
				[],
				SIKADA_AUTH_VERSION,
				true
			);

			wp_localize_script('sikada-auth-login-form', 'sikadaAuthData', [
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('sikada_auth_nonce'),
				'passwordResetUrl' => $this->get_password_reset_url(),
				'labels' => [
					'username' => __('Username or Email', 'sikada-auth'),
					'password' => __('Password', 'sikada-auth'),
					'remember' => __('Remember Me', 'sikada-auth'),
					'login' => __('Log In', 'sikada-auth'),
					'loading' => __('Logging in...', 'sikada-auth'),
					'lostPassword' => __('Lost your password?', 'sikada-auth'),
				]
			]);
		}

		if (has_block('sikada-auth/password-reset')) {
			wp_enqueue_script(
				'sikada-auth-password-reset',
				SIKADA_AUTH_PLUGIN_URL . 'assets/js/password-reset.js',
				[],
				SIKADA_AUTH_VERSION,
				true
			);

			wp_localize_script('sikada-auth-password-reset', 'sikadaAuthData', [
				'ajaxUrl' => admin_url('admin-ajax.php'),
				'nonce' => wp_create_nonce('sikada_auth_nonce'),
				'loginUrl' => $this->get_login_page_url(),
				'labels' => [
					'sending' => __('Sending...', 'sikada-auth'),
					'saving' => __('Saving...', 'sikada-auth'),
					'mismatch' => __('Passwords do not match.', 'sikada-auth'),
					'loading' => __('Processing...', 'sikada-auth'),
				]
			]);
		}
	}

	/**
	 * Get login page URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_login_page_url()
	{
		$page_id = get_option('sikada_auth_login_page');
		if ($page_id) {
			return get_permalink($page_id);
		}
		return wp_login_url();
	}

	/**
	 * Get password reset URL
	 *
	 * @since 1.0.0
	 * @return string
	 */
	private function get_password_reset_url()
	{
		$page_id = get_option('sikada_auth_password_reset_page');
		if ($page_id) {
			return get_permalink($page_id);
		}
		return wp_lostpassword_url();
	}

	/**
	 * Plugin activation
	 *
	 * @since 1.0.0
	 */
	public static function activate()
	{
		// Create database tables
		if (class_exists('SikadaWorks\\SikadaAuth\\Database\\Schema')) {
			\SikadaWorks\SikadaAuth\Database\Schema::create_tables();
		}

		// Set default options
		add_option('sikada_auth_version', SIKADA_AUTH_VERSION);

		// Schedule cron events (example)
		// if (!wp_next_scheduled('sikada_auth_daily_task')) {
		// 	wp_schedule_event(time(), 'daily', 'sikada_auth_daily_task');
		// }

		// Flush rewrite rules if CPTs/taxonomies are registered
		flush_rewrite_rules();

		// Fire activation hook for extensions
		do_action('sikada_auth_activated');
	}

	/**
	 * Plugin deactivation
	 *
	 * @since 1.0.0
	 */
	public static function deactivate()
	{
		// Clear scheduled cron events (example)
		// $timestamp = wp_next_scheduled('sikada_auth_daily_task');
		// if ($timestamp) {
		// 	wp_unschedule_event($timestamp, 'sikada_auth_daily_task');
		// }

		// Flush rewrite rules
		flush_rewrite_rules();

		// Fire deactivation hook for extensions
		do_action('sikada_auth_deactivated');
	}
}
