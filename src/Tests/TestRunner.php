<?php

namespace SikadaWorks\SikadaAuth\Tests;

/**
 * Class TestRunner
 * 
 * Handles execution of ad-hoc verification scripts within the WordPress context.
 * 
 * Usage: Visit ?sikada_test=filename-without-extension
 * Example: ?sikada_test=verify-tables
 * 
 * Security: Only available to Admin users.
 * 
 * @package SikadaWorks\SikadaAuth\Tests
 * @since 1.0.0
 */
class TestRunner
{

    /**
     * Initialize the Test Runner.
     *
     * @since 1.0.0
     */
    public function init()
    {
        add_action('template_redirect', [$this, 'handle_test_request'], 1);
    }

    /**
     * Check for test request and execute if authorized.
     *
     * @since 1.0.0
     */
    public function handle_test_request()
    {
        if (!isset($_GET['sikada_test'])) {
            return;
        }

        // Security: Only allow administrators to run tests
        if (!current_user_can('manage_options')) {
            wp_die(
                'Access Denied: You must be an administrator to run Sikada Auth tests. (User ID: ' . get_current_user_id() . ')',
                'Access Denied',
                ['response' => 403]
            );
        }

        $test_file = sanitize_file_name($_GET['sikada_test']);
        $file_path = SIKADA_AUTH_PLUGIN_DIR . 'tests/' . $test_file . '.php';

        if (!file_exists($file_path)) {
            wp_die('Test file not found: ' . esc_html($test_file));
        }

        // Buffer output to wrap in a clean container
        ob_start();
        include $file_path;
        $output = ob_get_clean();

        // Render basic HTML wrapper
        $this->render_test_page($test_file, $output);
        exit;
    }

    /**
     * Render test page with styled output
     *
     * @since 1.0.0
     * @param string $test_file Test filename
     * @param string $output Test output
     */
    private function render_test_page($test_file, $output)
    {
        ?>
        <!DOCTYPE html>
        <html>

        <head>
            <title>Sikada Auth Test: <?php echo esc_html($test_file); ?></title>
            <style>
                body {
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif;
                    padding: 20px;
                    background: #f0f0f1;
                    color: #3c434a;
                }

                h1 {
                    border-bottom: 2px solid #2271b1;
                    padding-bottom: 10px;
                    color: #1d2327;
                }

                h2 {
                    color: #1d2327;
                    margin-top: 30px;
                }

                .test {
                    margin: 15px 0;
                    padding: 15px;
                    border-left: 4px solid #ccc;
                    background: #fff;
                    border-radius: 4px;
                }

                .test.pass {
                    border-left-color: #46b450;
                }

                .test.fail {
                    border-left-color: #dc3232;
                }

                .test-name {
                    font-weight: bold;
                    margin-bottom: 5px;
                    font-size: 16px;
                }

                .test-result {
                    color: #666;
                    font-size: 14px;
                }

                code {
                    background: #eee;
                    padding: 2px 6px;
                    border-radius: 3px;
                    font-family: monospace;
                }

                .pass {
                    color: #46b450;
                    font-weight: bold;
                }

                .fail {
                    color: #dc3232;
                    font-weight: bold;
                }

                p {
                    line-height: 1.6;
                }

                a {
                    color: #2271b1;
                    text-decoration: none;
                }

                a:hover {
                    text-decoration: underline;
                }

                .back-link {
                    margin-top: 30px;
                    padding-top: 20px;
                    border-top: 1px solid #ddd;
                }
            </style>
        </head>

        <body>
            <?php echo $output; ?>
            <div class="back-link">
                <p><a href="<?php echo admin_url(); ?>">&larr; Back to Admin</a></p>
            </div>
        </body>

        </html>
        <?php
    }
}
