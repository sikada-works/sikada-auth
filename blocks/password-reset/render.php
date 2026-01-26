<?php
/**
 * Dynamic render callback for Password Reset block
 */

// Determine state based on URL parameters
$is_reset_action = isset($_GET['key']) && (isset($_GET['login']) || isset($_GET['user_login']));

// Wrapper attributes
// Get localization strings from settings
$label_reset_email = get_option('sikada_auth_label_reset_email', 'Username or Email');
$label_new_password = get_option('sikada_auth_label_new_password', 'New Password');
$label_confirm_password = get_option('sikada_auth_label_confirm_password', 'Confirm New Password');
$label_request_btn = get_option('sikada_auth_label_request_btn', 'Get New Password');
$label_reset_btn = get_option('sikada_auth_label_reset_btn', 'Reset Password');

// Get design settings
$primary_color = get_option('sikada_auth_primary_color', '#2271b1');

// Wrapper attributes
$wrapper_attributes = get_block_wrapper_attributes([
    'class' => 'sikada-auth-password-reset',
    'style' => "--sikada-primary-color: {$primary_color};"
]);
?>

<div <?php echo $wrapper_attributes; ?>>

    <?php if ($is_reset_action): ?>

        <!-- PASSWORD RESET FORM (Set New Password) -->
        <form class="sikada-reset-form" data-sikada-reset-form>
            <div class="sikada-form-messages"></div>

            <div class="sikada-form-field">
                <label htmlFor="sikada-new-password">
                    <?php echo esc_html($label_new_password); ?>
                </label>
                <input type="password" id="sikada-new-password" name="password" required autocomplete="new-password" />
            </div>

            <div class="sikada-form-field">
                <label htmlFor="sikada-confirm-password">
                    <?php echo esc_html($label_confirm_password); ?>
                </label>
                <input type="password" id="sikada-confirm-password" name="password_confirmation" required
                    autocomplete="new-password" />
            </div>

            <div class="sikada-form-actions">
                <button type="submit" class="sikada-button sikada-button-primary">
                    <?php echo esc_html($label_reset_btn); ?>
                </button>
            </div>
        </form>

    <?php else: ?>

        <!-- PASSWORD RESET REQUEST (Email Form) -->
        <form class="sikada-reset-request-form" data-sikada-reset-request-form>
            <div class="sikada-form-messages"></div>

            <p class="sikada-form-description">
                <?php _e('Lost your password? Please enter your username or email address. You will receive a link to create a new password via email.', 'sikada-auth'); ?>
            </p>

            <div class="sikada-form-field">
                <label htmlFor="sikada-user-login">
                    <?php echo esc_html($label_reset_email); ?>
                </label>
                <input type="text" id="sikada-user-login" name="user_login" required autocomplete="username" />
            </div>

            <div class="sikada-form-actions">
                <button type="submit" class="sikada-button sikada-button-primary">
                    <?php echo esc_html($label_request_btn); ?>
                </button>
            </div>

            <div class="sikada-form-links">
                <?php
                $login_page_id = get_option('sikada_auth_login_page');
                $login_url = $login_page_id ? get_permalink($login_page_id) : wp_login_url();

                // We could localize 'Log In' link text here too, but reusing the login button label might be confusing.
                // For now keeping 'Log in' static or we could add another setting.
                ?>
                <a href="<?php echo esc_url($login_url); ?>" class="sikada-login-link">
                    <?php _e('Log in', 'sikada-auth'); ?>
                </a>
            </div>
        </form>

    <?php endif; ?>

</div>