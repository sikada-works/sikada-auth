<?php
/**
 * Admin Alert: Blocked IP (HTML)
 */
?>
<!DOCTYPE html>
<html>

<head>
    <style>
        body {
            font-family: sans-serif;
        }
    </style>
</head>

<body>
    <h2>Security Alert: Suspicious Activity</h2>
    <p>Multiple failed login attempts were detected from the following IP:</p>
    <ul>
        <li><strong>IP Address:</strong>
            <?php echo esc_html($ip_address); ?>
        </li>
        <li><strong>User Agent:</strong>
            <?php echo esc_html($user_agent); ?>
        </li>
        <li><strong>Timestamp:</strong>
            <?php echo esc_html(current_time('mysql')); ?>
        </li>
    </ul>
    <p>This IP has been temporarily blocked.</p>
</body>

</html>