<?php
/**
 * Password Reset Request Email Template (HTML)
 *
 * Variables:
 * @var string $site_name
 * @var string $site_url
 * @var string $reset_url
 * @var string $username
 */
?>
<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen, Ubuntu, Cantarell, "Open Sans", "Helvetica Neue", sans-serif;
            line-height: 1.6;
            color: #333;
        }

        .container {
            max-width: 600px;
            margin: 0 auto;
            padding: 20px;
            border: 1px solid #eee;
            border-radius: 5px;
            background: #fff;
        }

        .header {
            background: #f9f9f9;
            padding: 20px;
            text-align: center;
            border-bottom: 1px solid #eee;
        }

        .content {
            padding: 30px 20px;
        }

        .button {
            display: inline-block;
            padding: 12px 24px;
            background-color: #2271b1;
            color: #ffffff !important;
            text-decoration: none;
            border-radius: 4px;
            font-weight: bold;
            margin: 20px 0;
        }

        .footer {
            font-size: 12px;
            color: #999;
            text-align: center;
            margin-top: 20px;
            border-top: 1px solid #eee;
            padding-top: 20px;
        }
    </style>
</head>

<body>
    <div class="container">
        <div class="header">
            <h2>
                <?php echo esc_html($site_name); ?>
            </h2>
        </div>
        <div class="content">
            <p>
                <?php printf(__('Hi %s,', 'sikada-auth'), esc_html($username)); ?>
            </p>
            <p>
                <?php _e('Someone has requested a password reset for the following account:', 'sikada-auth'); ?>
            </p>
            <p><strong>
                    <?php echo esc_html($site_name); ?>
                </strong></p>
            <p>
                <?php _e('If this was a mistake, just ignore this email and nothing will happen.', 'sikada-auth'); ?>
            </p>
            <p>
                <?php _e('To reset your password, visit the following address:', 'sikada-auth'); ?>
            </p>
            <p style="text-align: center;">
                <a href="<?php echo esc_url($reset_url); ?>" class="button">
                    <?php _e('Reset Password', 'sikada-auth'); ?>
                </a>
            </p>
            <p style="font-size: 0.9em; word-break: break-all; color: #666;">
                <?php echo esc_url($reset_url); ?>
            </p>
        </div>
        <div class="footer">
            <p>&copy;
                <?php echo date('Y'); ?>
                <?php echo esc_html($site_name); ?>.
                <?php _e('All rights reserved.', 'sikada-auth'); ?>
            </p>
        </div>
    </div>
</body>

</html>