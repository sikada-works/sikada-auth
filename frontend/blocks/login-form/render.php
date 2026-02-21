<?php
/**
 * Render template for sikada-auth/login-form
 */

// Get localization strings from settings
$label_username = get_option('sikada_auth_label_username', 'Username or Email');
$label_password = get_option('sikada_auth_label_password', 'Password');
$label_remember = get_option('sikada_auth_label_remember', 'Remember Me');
$label_login_btn = get_option('sikada_auth_label_login_btn', 'Log In');
$label_lost_password = get_option('sikada_auth_label_lost_password', 'Lost your password?');

// Get design settings
$primary_color = get_option('sikada_auth_primary_color', '#2271b1');

// Wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sikada-auth-login-form sikada-auth-wrapper',
    'style' => "--sikada-primary-color: {$primary_color};"
]);
?>
<div <?php echo $wrapper_attributes; ?>>
    <?php if (is_user_logged_in()): ?>
        <div class="sikada-message sikada-message-success">
            <?php
            $current_user = wp_get_current_user();
            printf(__('You are already logged in as <strong>%s</strong>.', 'sikada-auth'), esc_html($current_user->display_name));
            ?>
            <br>
            <a href="<?php echo esc_url(wp_logout_url(home_url())); ?>">
                <?php _e('Log out?', 'sikada-auth'); ?>
            </a>
        </div>
    <?php else: ?>
        <?php do_action('sikada_auth_before_login_form'); ?>

        <form class="sikada-login-form" data-sikada-login-form method="POST">
            <div class="sikada-form-messages"></div>

            <div class="sikada-form-field">
                <label htmlFor="sikada-username">
                    <?php echo esc_html($label_username); ?>
                </label>
                <input type="text" id="sikada-username" name="username" required autocomplete="username" />
            </div>

            <div class="sikada-form-field">
                <label htmlFor="sikada-password">
                    <?php echo esc_html($label_password); ?>
                </label>
                <input type="password" id="sikada-password" name="password" required autocomplete="current-password" />
            </div>

            <div class="sikada-form-field sikada-checkbox">
                <label>
                    <input type="checkbox" name="remember" value="1" />
                    <?php echo esc_html($label_remember); ?>
                </label>
            </div>

            <div class="sikada-form-actions">
                <button type="submit" class="sikada-button sikada-button-primary">
                    <?php echo esc_html($label_login_btn); ?>
                </button>
            </div>

            <div class="sikada-form-links">
                <a href="<?php echo esc_url(wp_lostpassword_url()); ?>" class="sikada-lost-password-link">
                    <?php echo esc_html($label_lost_password); ?>
                </a>
            </div>

            <?php wp_nonce_field('sikada_auth_login', 'sikada_auth_nonce'); ?>
        </form>

        <?php do_action('sikada_auth_after_login_form'); ?>

    <?php endif; ?>
</div>