<?php

namespace SikadaWorks\SikadaAuth\Email;

/**
 * Email Template
 *
 * Loads and renders email templates with variable replacement.
 *
 * @package SikadaWorks\SikadaAuth\Email
 * @since 1.0.0
 */
class EmailTemplate
{
    /**
     * Get template path with theme override support
     *
     * @since 1.0.0
     * @param string $template_name Template name
     * @param string $format        'html' or 'text'
     * @return string|false Template path or false if not found
     */
    public function get_template_path($template_name, $format = 'html')
    {
        $extension = ($format === 'html') ? 'php' : 'txt';
        $filename = "{$template_name}.{$extension}";

        // Template hierarchy (child theme → parent theme → plugin)
        $locations = [
            get_stylesheet_directory() . '/sikada-auth/emails/' . $filename,
            get_template_directory() . '/sikada-auth/emails/' . $filename,
            SIKADA_AUTH_PLUGIN_DIR . 'templates/emails/' . $filename,
        ];

        foreach ($locations as $location) {
            if (file_exists($location)) {
                return $location;
            }
        }

        return false;
    }

    /**
     * Render template with variables
     *
     * @since 1.0.0
     * @param string $template_name Template name
     * @param array  $vars          Variables to replace
     * @param string $format        'html' or 'text'
     * @return string|false Rendered template or false on failure
     */
    public function render($template_name, $vars = [], $format = 'html')
    {
        $template_path = $this->get_template_path($template_name, $format);

        if (!$template_path) {
            return false;
        }

        // Merge with default vars
        $vars = array_merge($this->get_default_vars(), $vars);

        // Extract variables
        extract($vars, EXTR_SKIP);

        // Capture output
        ob_start();
        include $template_path;
        $content = ob_get_clean();

        // Apply filters
        return apply_filters('sikada_auth_email_content', $content, $template_name, $format, $vars);
    }

    /**
     * Get default template variables
     *
     * @since 1.0.0
     * @return array Default variables
     */
    private function get_default_vars()
    {
        return [
            'site_name' => get_bloginfo('name'),
            'site_url' => home_url(),
            'admin_email' => get_option('admin_email'),
        ];
    }
}
