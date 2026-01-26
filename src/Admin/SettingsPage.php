<?php

namespace SikadaWorks\SikadaAuth\Admin;

/**
 * Settings Page
 *
 * Handles the main plugin settings page with multiple tabs.
 *
 * @package SikadaWorks\SikadaAuth\Admin
 * @since 1.0.0
 */
class SettingsPage
{
    /**
     * Settings page slug
     *
     * @var string
     */
    private $page_slug = 'sikada-auth-settings';

    /**
     * Initialize the settings page
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('admin_menu', [$this, 'add_menu_page']);
        add_action('admin_init', [$this, 'register_settings']);
    }

    /**
     * Add admin menu page
     *
     * @since 1.0.0
     */
    public function add_menu_page()
    {
        add_options_page(
            __('Sikada Authorization Settings', 'sikada-auth'),
            __('Sikada Auth', 'sikada-auth'),
            'manage_options',
            $this->page_slug,
            [$this, 'render_page']
        );
    }

    /**
     * Register all plugin settings
     *
     * @since 1.0.0
     */
    public function register_settings()
    {
        // General settings
        register_setting('sikada_auth_general', 'sikada_auth_login_page');
        register_setting('sikada_auth_general', 'sikada_auth_password_reset_page');
        register_setting('sikada_auth_general', 'sikada_auth_logout_redirect');

        // Redirect settings
        register_setting('sikada_auth_redirects', 'sikada_auth_default_redirect');
        register_setting('sikada_auth_redirects', 'sikada_auth_redirect_administrator');
        register_setting('sikada_auth_redirects', 'sikada_auth_redirect_editor');
        register_setting('sikada_auth_redirects', 'sikada_auth_redirect_author');
        register_setting('sikada_auth_redirects', 'sikada_auth_redirect_contributor');
        register_setting('sikada_auth_redirects', 'sikada_auth_redirect_subscriber');

        // Security settings
        register_setting('sikada_auth_security', 'sikada_auth_enable_rate_limiting', ['default' => true]);
        register_setting('sikada_auth_security', 'sikada_auth_rate_limit_username', ['default' => 5]);
        register_setting('sikada_auth_security', 'sikada_auth_rate_limit_ip', ['default' => 10]);
        register_setting('sikada_auth_security', 'sikada_auth_ip_whitelist');
        register_setting('sikada_auth_security', 'sikada_auth_enforce_password_strength');
        register_setting('sikada_auth_security', 'sikada_auth_min_password_length', ['default' => 8]);
        register_setting('sikada_auth_security', 'sikada_auth_require_uppercase');
        register_setting('sikada_auth_security', 'sikada_auth_require_lowercase');
        register_setting('sikada_auth_security', 'sikada_auth_require_number');
        register_setting('sikada_auth_security', 'sikada_auth_require_number');
        register_setting('sikada_auth_security', 'sikada_auth_require_special');

        // Global Protection Settings
        register_setting('sikada_auth_security', 'sikada_auth_global_protection_enable');
        register_setting('sikada_auth_security', 'sikada_auth_global_protection_block_api');
        register_setting('sikada_auth_security', 'sikada_auth_global_protection_block_admin');

        // Email settings
        register_setting('sikada_auth_email', 'sikada_auth_email_from_name');
        register_setting('sikada_auth_email', 'sikada_auth_email_from_email');
        register_setting('sikada_auth_email', 'sikada_auth_email_reply_to');
        register_setting('sikada_auth_email', 'sikada_auth_enable_admin_alerts');
        register_setting('sikada_auth_email', 'sikada_auth_alert_threshold', ['default' => 3]);
        register_setting('sikada_auth_email', 'sikada_auth_alert_recipients');

        // Log settings
        register_setting('sikada_auth_logs', 'sikada_auth_log_retention_days', ['default' => 90]);

        // Localization settings
        // Login Form
        register_setting('sikada_auth_localization', 'sikada_auth_label_username', ['default' => 'Username or Email']);
        register_setting('sikada_auth_localization', 'sikada_auth_label_password', ['default' => 'Password']);
        register_setting('sikada_auth_localization', 'sikada_auth_label_remember', ['default' => 'Remember Me']);
        register_setting('sikada_auth_localization', 'sikada_auth_label_login_btn', ['default' => 'Log In']);
        register_setting('sikada_auth_localization', 'sikada_auth_label_lost_password', ['default' => 'Lost your password?']);

        // Password Reset Form
        register_setting('sikada_auth_localization', 'sikada_auth_label_reset_email', ['default' => 'Username or Email']);
        register_setting('sikada_auth_localization', 'sikada_auth_label_new_password', ['default' => 'New Password']);
        register_setting('sikada_auth_localization', 'sikada_auth_label_confirm_password', ['default' => 'Confirm New Password']);
        register_setting('sikada_auth_localization', 'sikada_auth_label_request_btn', ['default' => 'Get New Password']);
        register_setting('sikada_auth_localization', 'sikada_auth_label_reset_btn', ['default' => 'Reset Password']);

        // Design Settings
        register_setting('sikada_auth_localization', 'sikada_auth_primary_color', ['default' => '#2271b1']);
    }

    /**
     * Render settings page
     *
     * @since 1.0.0
     */
    public function render_page()
    {
        // Check user capabilities
        if (!current_user_can('manage_options')) {
            return;
        }

        // Get active tab
        $active_tab = isset($_GET['tab']) ? sanitize_text_field($_GET['tab']) : 'general';

        ?>
        <div class="wrap">
            <h1>
                <?php echo esc_html(get_admin_page_title()); ?>
            </h1>

            <h2 class="nav-tab-wrapper">
                <a href="?page=<?php echo $this->page_slug; ?>&tab=general"
                    class="nav-tab <?php echo $active_tab === 'general' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('General', 'sikada-auth'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=redirects"
                    class="nav-tab <?php echo $active_tab === 'redirects' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Redirects', 'sikada-auth'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=security"
                    class="nav-tab <?php echo $active_tab === 'security' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Security', 'sikada-auth'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=email"
                    class="nav-tab <?php echo $active_tab === 'email' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Email', 'sikada-auth'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=logs"
                    class="nav-tab <?php echo $active_tab === 'logs' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Logs', 'sikada-auth'); ?>
                </a>
                <a href="?page=<?php echo $this->page_slug; ?>&tab=localization"
                    class="nav-tab <?php echo $active_tab === 'localization' ? 'nav-tab-active' : ''; ?>">
                    <?php _e('Customization', 'sikada-auth'); ?>
                </a>
            </h2>

            <form method="post" action="options.php">
                <?php
                switch ($active_tab) {
                    case 'general':
                        $this->render_general_tab();
                        break;
                    case 'redirects':
                        $this->render_redirects_tab();
                        break;
                    case 'security':
                        $this->render_security_tab();
                        break;
                    case 'email':
                        $this->render_email_tab();
                        break;
                    case 'logs':
                        $this->render_logs_tab();
                        break;
                    case 'localization':
                        $this->render_localization_tab();
                        break;
                }
                ?>
            </form>
        </div>
        <?php
    }

    /**
     * Render General tab
     *
     * @since 1.0.0
     */
    private function render_general_tab()
    {
        settings_fields('sikada_auth_general');
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="sikada_auth_login_page">
                        <?php _e('Login Page', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages([
                        'name' => 'sikada_auth_login_page',
                        'id' => 'sikada_auth_login_page',
                        'selected' => get_option('sikada_auth_login_page'),
                        'show_option_none' => __('— Select —', 'sikada-auth'),
                        'option_none_value' => '',
                    ]);
                    ?>
                    <p class="description">
                        <?php _e('Page containing the Login Form block. Leave empty to use default WordPress login.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_password_reset_page">
                        <?php _e('Password Reset Page', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <?php
                    wp_dropdown_pages([
                        'name' => 'sikada_auth_password_reset_page',
                        'id' => 'sikada_auth_password_reset_page',
                        'selected' => get_option('sikada_auth_password_reset_page'),
                        'show_option_none' => __('— Select —', 'sikada-auth'),
                        'option_none_value' => '',
                    ]);
                    ?>
                    <p class="description">
                        <?php _e('Page containing the Password Reset block.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_logout_redirect">
                        <?php _e('Logout Redirect URL', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="url" name="sikada_auth_logout_redirect" id="sikada_auth_logout_redirect"
                        value="<?php echo esc_attr(get_option('sikada_auth_logout_redirect')); ?>" class="regular-text" />
                    <p class="description">
                        <?php _e('Where to redirect users after logout. Leave empty to redirect to login page.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    <?php
    }

    /**
     * Render Redirects tab
     *
     * @since 1.0.0
     */
    private function render_redirects_tab()
    {
        settings_fields('sikada_auth_redirects');
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="sikada_auth_default_redirect">
                        <?php _e('Default Redirect', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="url" name="sikada_auth_default_redirect" id="sikada_auth_default_redirect"
                        value="<?php echo esc_attr(get_option('sikada_auth_default_redirect')); ?>" class="regular-text" />
                    <p class="description">
                        <?php _e('Default redirect URL after login. Leave empty to use WordPress admin.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
            <?php
            $roles = [
                'administrator' => __('Administrator', 'sikada-auth'),
                'editor' => __('Editor', 'sikada-auth'),
                'author' => __('Author', 'sikada-auth'),
                'contributor' => __('Contributor', 'sikada-auth'),
                'subscriber' => __('Subscriber', 'sikada-auth'),
            ];

            foreach ($roles as $role => $label):
                $option_name = "sikada_auth_redirect_{$role}";
                ?>
                <tr>
                    <th scope="row">
                        <label for="<?php echo esc_attr($option_name); ?>">
                            <?php echo esc_html($label); ?>
                        </label>
                    </th>
                    <td>
                        <input type="url" name="<?php echo esc_attr($option_name); ?>" id="<?php echo esc_attr($option_name); ?>"
                            value="<?php echo esc_attr(get_option($option_name)); ?>" class="regular-text" />
                    </td>
                </tr>
            <?php endforeach; ?>
        </table>
        <?php submit_button(); ?>
    <?php
    }

    /**
     * Render Security tab
     *
     * @since 1.0.0
     */
    private function render_security_tab()
    {
        settings_fields('sikada_auth_security');
        ?>
        <h2>
            <?php _e('Rate Limiting', 'sikada-auth'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <?php _e('Enable Rate Limiting', 'sikada-auth'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="sikada_auth_enable_rate_limiting" value="1" <?php checked(get_option('sikada_auth_enable_rate_limiting', true)); ?> />
                        <?php _e('Enable rate limiting to prevent brute force attacks', 'sikada-auth'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_rate_limit_username">
                        <?php _e('Max Attempts per Username', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="sikada_auth_rate_limit_username" id="sikada_auth_rate_limit_username"
                        value="<?php echo esc_attr(get_option('sikada_auth_rate_limit_username', 5)); ?>" min="1" max="100"
                        class="small-text" />
                    <p class="description">
                        <?php _e('Maximum failed login attempts per username in 15 minutes.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_rate_limit_ip">
                        <?php _e('Max Attempts per IP', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="sikada_auth_rate_limit_ip" id="sikada_auth_rate_limit_ip"
                        value="<?php echo esc_attr(get_option('sikada_auth_rate_limit_ip', 10)); ?>" min="1" max="100"
                        class="small-text" />
                    <p class="description">
                        <?php _e('Maximum failed login attempts per IP address in 15 minutes.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_ip_whitelist">
                        <?php _e('IP Whitelist', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <textarea name="sikada_auth_ip_whitelist" id="sikada_auth_ip_whitelist" rows="3"
                        class="large-text"><?php echo esc_textarea(get_option('sikada_auth_ip_whitelist')); ?></textarea>
                    <p class="description">
                        <?php _e('Comma-separated list of IP addresses to bypass rate limiting.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
        </table>

        <h2>
            <?php _e('Password Strength', 'sikada-auth'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <?php _e('Enforce Password Strength', 'sikada-auth'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="sikada_auth_enforce_password_strength" value="1" <?php checked(get_option('sikada_auth_enforce_password_strength')); ?> />
                        <?php _e('Require strong passwords on password reset', 'sikada-auth'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_min_password_length">
                        <?php _e('Minimum Password Length', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="sikada_auth_min_password_length" id="sikada_auth_min_password_length"
                        value="<?php echo esc_attr(get_option('sikada_auth_min_password_length', 8)); ?>" min="6" max="50"
                        class="small-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e('Password Requirements', 'sikada-auth'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="sikada_auth_require_uppercase" value="1" <?php checked(get_option('sikada_auth_require_uppercase')); ?> />
                        <?php _e('Require uppercase letter', 'sikada-auth'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="sikada_auth_require_lowercase" value="1" <?php checked(get_option('sikada_auth_require_lowercase')); ?> />
                        <?php _e('Require lowercase letter', 'sikada-auth'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="sikada_auth_require_number" value="1" <?php checked(get_option('sikada_auth_require_number')); ?> />
                        <?php _e('Require number', 'sikada-auth'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="sikada_auth_require_special" value="1" <?php checked(get_option('sikada_auth_require_special')); ?> />
                        <?php _e('Require special character', 'sikada-auth'); ?>
                    </label>
                </td>
            </tr>
        </table>

        <h2>
            <?php _e('Global Site Protection', 'sikada-auth'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <?php _e('Enable Site Protection', 'sikada-auth'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="sikada_auth_global_protection_enable" value="1" <?php checked(get_option('sikada_auth_global_protection_enable')); ?> />
                        <?php _e('Force all logged-out visitors to the Login Page.', 'sikada-auth'); ?>
                    </label>
                    <p class="description">
                        <?php _e('Redirects all frontend traffic to your configured Login Page. Useful for intranets or membership sites.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <?php _e('Strict Mode', 'sikada-auth'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="sikada_auth_global_protection_block_api" value="1" <?php checked(get_option('sikada_auth_global_protection_block_api')); ?> />
                        <?php _e('Block REST API access for non-authenticated users.', 'sikada-auth'); ?>
                    </label><br>
                    <label>
                        <input type="checkbox" name="sikada_auth_global_protection_block_admin" value="1" <?php checked(get_option('sikada_auth_global_protection_block_admin')); ?> />
                        <?php _e('Block wp-admin access (Frontend redirect usually handles this, but this adds strict enforcement).', 'sikada-auth'); ?>
                    </label>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    <?php
    }

    /**
     * Render Localization tab
     *
     * @since 1.0.0
     */
    private function render_localization_tab()
    {
        settings_fields('sikada_auth_localization');
        ?>
        <h2><?php _e('Login Form', 'sikada-auth'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="sikada_auth_label_username"><?php _e('Username Label', 'sikada-auth'); ?></label>
                </th>
                <td><input type="text" name="sikada_auth_label_username" id="sikada_auth_label_username"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_username', 'Username or Email')); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="sikada_auth_label_password"><?php _e('Password Label', 'sikada-auth'); ?></label>
                </th>
                <td><input type="text" name="sikada_auth_label_password" id="sikada_auth_label_password"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_password', 'Password')); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="sikada_auth_label_remember"><?php _e('Remember Me Label', 'sikada-auth'); ?></label>
                </th>
                <td><input type="text" name="sikada_auth_label_remember" id="sikada_auth_label_remember"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_remember', 'Remember Me')); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="sikada_auth_label_login_btn"><?php _e('Login Button', 'sikada-auth'); ?></label>
                </th>
                <td><input type="text" name="sikada_auth_label_login_btn" id="sikada_auth_label_login_btn"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_login_btn', 'Log In')); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="sikada_auth_label_lost_password"><?php _e('Lost Password Link', 'sikada-auth'); ?></label></th>
                <td><input type="text" name="sikada_auth_label_lost_password" id="sikada_auth_label_lost_password"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_lost_password', 'Lost your password?')); ?>"
                        class="regular-text" /></td>
            </tr>
        </table>

        <h2><?php _e('Password Reset Form', 'sikada-auth'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label
                        for="sikada_auth_label_reset_email"><?php _e('Email Request Label', 'sikada-auth'); ?></label></th>
                <td><input type="text" name="sikada_auth_label_reset_email" id="sikada_auth_label_reset_email"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_reset_email', 'Username or Email')); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="sikada_auth_label_request_btn"><?php _e('Request Button', 'sikada-auth'); ?></label>
                </th>
                <td><input type="text" name="sikada_auth_label_request_btn" id="sikada_auth_label_request_btn"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_request_btn', 'Get New Password')); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="sikada_auth_label_new_password"><?php _e('New Password Label', 'sikada-auth'); ?></label></th>
                <td><input type="text" name="sikada_auth_label_new_password" id="sikada_auth_label_new_password"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_new_password', 'New Password')); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label
                        for="sikada_auth_label_confirm_password"><?php _e('Confirm Password Label', 'sikada-auth'); ?></label>
                </th>
                <td><input type="text" name="sikada_auth_label_confirm_password" id="sikada_auth_label_confirm_password"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_confirm_password', 'Confirm New Password')); ?>"
                        class="regular-text" /></td>
            </tr>
            <tr>
                <th scope="row"><label for="sikada_auth_label_reset_btn"><?php _e('Reset Button', 'sikada-auth'); ?></label>
                </th>
                <td><input type="text" name="sikada_auth_label_reset_btn" id="sikada_auth_label_reset_btn"
                        value="<?php echo esc_attr(get_option('sikada_auth_label_reset_btn', 'Reset Password')); ?>"
                        class="regular-text" /></td>
            </tr>
        </table>
        </table>

        <h2><?php _e('Design', 'sikada-auth'); ?></h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row"><label for="sikada_auth_primary_color"><?php _e('Primary Color', 'sikada-auth'); ?></label></th>
                <td>
                    <input type="color" name="sikada_auth_primary_color" id="sikada_auth_primary_color"
                        value="<?php echo esc_attr(get_option('sikada_auth_primary_color', '#2271b1')); ?>" />
                    <p class="description">
                        <?php _e('Controls the button background, link color, and focus rings.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    <?php
    }

    /**
     * Render Email tab
     *
     * @since 1.0.0
     */
    private function render_email_tab()
    {
        settings_fields('sikada_auth_email');
        ?>
        <h2>
            <?php _e('Email Headers', 'sikada-auth'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="sikada_auth_email_from_name">
                        <?php _e('From Name', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" name="sikada_auth_email_from_name" id="sikada_auth_email_from_name"
                        value="<?php echo esc_attr(get_option('sikada_auth_email_from_name', get_bloginfo('name'))); ?>"
                        class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_email_from_email">
                        <?php _e('From Email', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="email" name="sikada_auth_email_from_email" id="sikada_auth_email_from_email"
                        value="<?php echo esc_attr(get_option('sikada_auth_email_from_email', get_option('admin_email'))); ?>"
                        class="regular-text" />
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_email_reply_to">
                        <?php _e('Reply-To Email', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="email" name="sikada_auth_email_reply_to" id="sikada_auth_email_reply_to"
                        value="<?php echo esc_attr(get_option('sikada_auth_email_reply_to', get_option('admin_email'))); ?>"
                        class="regular-text" />
                </td>
            </tr>
        </table>

        <h2>
            <?php _e('Admin Alerts', 'sikada-auth'); ?>
        </h2>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <?php _e('Enable Admin Alerts', 'sikada-auth'); ?>
                </th>
                <td>
                    <label>
                        <input type="checkbox" name="sikada_auth_enable_admin_alerts" value="1" <?php checked(get_option('sikada_auth_enable_admin_alerts')); ?> />
                        <?php _e('Send email alerts to administrators on security events', 'sikada-auth'); ?>
                    </label>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_alert_threshold">
                        <?php _e('Alert Threshold', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="number" name="sikada_auth_alert_threshold" id="sikada_auth_alert_threshold"
                        value="<?php echo esc_attr(get_option('sikada_auth_alert_threshold', 3)); ?>" min="1" max="100"
                        class="small-text" />
                    <p class="description">
                        <?php _e('Send alert after this many blocked login attempts.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
            <tr>
                <th scope="row">
                    <label for="sikada_auth_alert_recipients">
                        <?php _e('Alert Recipients', 'sikada-auth'); ?>
                    </label>
                </th>
                <td>
                    <input type="text" name="sikada_auth_alert_recipients" id="sikada_auth_alert_recipients"
                        value="<?php echo esc_attr(get_option('sikada_auth_alert_recipients', get_option('admin_email'))); ?>"
                        class="regular-text" />
                    <p class="description">
                        <?php _e('Comma-separated list of email addresses.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php submit_button(); ?>
    <?php
    }


    /**
     * Render Logs tab
     *
     * @since 1.0.0
     */
    private function render_logs_tab()
    {
        settings_fields('sikada_auth_logs');
        ?>
        <table class="form-table" role="presentation">
            <tr>
                <th scope="row">
                    <label for="sikada_auth_log_retention_days"><?php _e('Log Retention (Days)', 'sikada-auth'); ?></label>
                </th>
                <td>
                    <input type="number" name="sikada_auth_log_retention_days" id="sikada_auth_log_retention_days"
                        value="<?php echo esc_attr(get_option('sikada_auth_log_retention_days', 90)); ?>" min="1" max="365"
                        class="small-text" />
                    <p class="description">
                        <?php _e('Automatically delete login logs older than this many days.', 'sikada-auth'); ?>
                    </p>
                </td>
            </tr>
        </table>
        <?php
        submit_button();
        ?>
        </form>

        <hr>

        <h2><?php _e('Login Attempts Log', 'sikada-auth'); ?></h2>
        <?php

        $logs_table = new \SikadaWorks\SikadaAuth\Admin\LoginLogsPage();
        $logs_table->prepare_items();

        echo '<form method="get">';
        echo '<input type="hidden" name="page" value="' . esc_attr($_REQUEST['page']) . '" />';
        echo '<input type="hidden" name="tab" value="logs" />';
        $logs_table->display();
        echo '</form>';
    }
}
