# WordPress Plugin - Coding Standards

This document outlines coding standards for WordPress plugin development based on modern best practices and PSR-4 patterns.

---

## Plugin Configuration

Before using these standards, replace the following placeholders throughout your codebase:

| Placeholder | Description | Example |
|-------------|-------------|---------|
| `{PLUGIN_NAME}` | Your plugin's display name | "My Awesome Plugin" |
| `{Vendor}` | Your vendor/organization namespace | "Acme" |
| `{PluginName}` | Your plugin's namespace component | "CoolFeature" |
| `{plugin-slug}` | Your plugin's slug for URLs/REST API | "acme-cool-feature" |
| `{plugin-text-domain}` | Your plugin's text domain for translations | "acme-cool-feature" |
| `{plugin_prefix}` | Database table prefix (without wp_) | "acme_cf" |

**Complete Namespace Example**: `{Vendor}\{PluginName}` → `Acme\CoolFeature`

---

## Plugin File Structure

### Standard Directory Structure

```
{plugin-slug}/
├── {plugin-slug}.php          # Main plugin file with header
├── uninstall.php              # Uninstall cleanup (optional)
├── composer.json              # Composer dependencies & autoloading
├── package.json               # npm dependencies & build scripts
├── webpack.config.js          # Asset build configuration
├── src/                       # PSR-4 autoloaded PHP classes
│   ├── Core/
│   │   └── Plugin.php        # Main plugin initialization class
│   ├── Admin/                # Admin-specific classes
│   ├── API/                  # REST API controllers
│   ├── Models/               # Data models
│   └── Services/             # Business logic services
├── blocks/                    # Gutenberg blocks source
│   └── {block-name}/
│       ├── block.json
│       ├── index.js
│       ├── edit.js
│       └── save.js
├── assets/                    # Compiled frontend assets
│   ├── css/
│   └── js/
├── admin/                     # Admin templates & assets
│   ├── css/
│   ├── js/
│   └── views/
├── languages/                 # Translation files (.pot, .po, .mo)
├── vendor/                    # Composer dependencies (gitignored)
├── node_modules/              # npm dependencies (gitignored)
├── docs/                      # Documentation
└── README.md
```

### Main Plugin File (`{plugin-slug}.php`)

```php
<?php
/**
 * Plugin Name: {PLUGIN_NAME}
 * Plugin URI: https://example.com/{plugin-slug}
 * Description: Brief description of the plugin
 * Version: 1.0.0
 * Requires at least: 6.0
 * Requires PHP: 7.4
 * Author: Your Name
 * Author URI: https://example.com
 * License: GPL v2 or later
 * License URI: https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain: {plugin-text-domain}
 * Domain Path: /languages
 */

namespace {Vendor}\{PluginName};

// Exit if accessed directly
if (!defined('ABSPATH')) {
    exit;
}

// Plugin constants
define('{PLUGIN_PREFIX}_VERSION', '1.0.0');
define('{PLUGIN_PREFIX}_PLUGIN_FILE', __FILE__);
define('{PLUGIN_PREFIX}_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('{PLUGIN_PREFIX}_PLUGIN_URL', plugin_dir_url(__FILE__));

// Composer autoloader
if (file_exists(__DIR__ . '/vendor/autoload.php')) {
    require_once __DIR__ . '/vendor/autoload.php';
}

// Initialize plugin
function init_plugin() {
    if (class_exists('\\{Vendor}\\{PluginName}\\Core\\Plugin')) {
        \{Vendor}\{PluginName}\Core\Plugin::get_instance();
    }
}
add_action('plugins_loaded', __NAMESPACE__ . '\\init_plugin');

// Activation hook
register_activation_hook(__FILE__, function() {
    if (class_exists('\\{Vendor}\\{PluginName}\\Core\\Plugin')) {
        \{Vendor}\{PluginName}\Core\Plugin::activate();
    }
});

// Deactivation hook
register_deactivation_hook(__FILE__, function() {
    if (class_exists('\\{Vendor}\\{PluginName}\\Core\\Plugin')) {
        \{Vendor}\{PluginName}\Core\Plugin::deactivate();
    }
});
```

### Composer Configuration (`composer.json`)

```json
{
    "name": "{vendor}/{plugin-slug}",
    "description": "{PLUGIN_NAME}",
    "type": "wordpress-plugin",
    "require": {
        "php": ">=7.4"
    },
    "autoload": {
        "psr-4": {
            "{Vendor}\\{PluginName}\\": "src/"
        }
    }
}
```

---

## PHP Backend Standards

### Namespacing & Organization

- **Root Namespace**: `{Vendor}\{PluginName}`
- **Directory Structure**: Follows PSR-4 autoloading
  - Classes in `src/` map to `{Vendor}\{PluginName}\{SubNamespace}\{ClassName}`
  - Examples:
    - `src/Registry/FieldRegistry.php` → `{Vendor}\{PluginName}\Registry\FieldRegistry`
    - `src/API/DataController.php` → `{Vendor}\{PluginName}\API\DataController`
    - `src/Core/Plugin.php` → `{Vendor}\{PluginName}\Core\Plugin`

### Class Structure

- **One class per file** with the filename matching the class name
- **Standard class template**:
  ```php
  <?php

  namespace {Vendor}\{PluginName}\{SubNamespace};

  /**
   * Brief Class Description
   */
  class ClassName
  {
      /**
       * Initialize
       */
      public function init()
      {
          // Hook registrations
      }

      // Additional methods
  }
  ```

### Singleton Pattern (Where Applicable)

```php
private static $instance;

public static function get_instance()
{
    if (!isset(self::$instance)) {
        self::$instance = new self();
    }
    return self::$instance;
}
```

### Plugin Initialization Pattern

```php
<?php

namespace {Vendor}\{PluginName}\Core;

class Plugin
{
    private static $instance;

    public static function get_instance()
    {
        if (!isset(self::$instance)) {
            self::$instance = new self();
        }
        return self::$instance;
    }

    private function __construct()
    {
        $this->init();
    }

    private function init()
    {
        // Load text domain
        add_action('init', [$this, 'load_textdomain']);
        
        // Register services
        $this->register_services();
    }

    public function load_textdomain()
    {
        load_plugin_textdomain(
            '{plugin-text-domain}',
            false,
            dirname(plugin_basename({PLUGIN_PREFIX}_PLUGIN_FILE)) . '/languages'
        );
    }

    private function register_services()
    {
        // Register all plugin services
        if (class_exists('{Vendor}\\{PluginName}\\Admin\\AdminUI')) {
            (new \{Vendor}\{PluginName}\Admin\AdminUI())->init();
        }
    }

    public static function activate()
    {
        // Activation logic (flush rewrite rules, create tables, etc.)
        flush_rewrite_rules();
    }

    public static function deactivate()
    {
        // Deactivation logic (flush rewrite rules, clear crons, etc.)
        flush_rewrite_rules();
    }
}
```

### Activation, Deactivation & Uninstall

#### Activation Hook
```php
public static function activate()
{
    // Create database tables
    require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
    self::create_tables();
    
    // Set default options
    add_option('{plugin_prefix}_version', {PLUGIN_PREFIX}_VERSION);
    
    // Schedule cron events
    if (!wp_next_scheduled('{plugin_prefix}_daily_task')) {
        wp_schedule_event(time(), 'daily', '{plugin_prefix}_daily_task');
    }
    
    // Flush rewrite rules if CPTs/taxonomies are registered
    flush_rewrite_rules();
    
    // Fire activation hook for extensions
    do_action('{plugin_prefix}_activated');
}
```

#### Deactivation Hook
```php
public static function deactivate()
{
    // Clear scheduled cron events
    $timestamp = wp_next_scheduled('{plugin_prefix}_daily_task');
    if ($timestamp) {
        wp_unschedule_event($timestamp, '{plugin_prefix}_daily_task');
    }
    
    // Flush rewrite rules
    flush_rewrite_rules();
    
    // Fire deactivation hook for extensions
    do_action('{plugin_prefix}_deactivated');
}
```

#### Uninstall (`uninstall.php`)
```php
<?php
/**
 * Uninstall script - only runs when plugin is deleted
 */

// Exit if not called by WordPress
if (!defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

global $wpdb;

// Delete options
delete_option('{plugin_prefix}_version');
delete_option('{plugin_prefix}_settings');

// Delete tables
$wpdb->query("DROP TABLE IF EXISTS {$wpdb->prefix}{plugin_prefix}_data");

// Delete user meta
$wpdb->query("DELETE FROM {$wpdb->usermeta} WHERE meta_key LIKE '{plugin_prefix}_%'");

// Clear any cached data
wp_cache_flush();
```

### Database Operations

#### Table Naming
- Prefix: `$wpdb->prefix . '{plugin_prefix}_'`
- Examples: `{plugin_prefix}_data`, `{plugin_prefix}_audit_log`, `{plugin_prefix}_config`
- Define table constants in `Schema.php`:
  ```php
  const TABLE_DATA = '{plugin_prefix}_data';
  ```
- Example usage:
  ```php
  global $wpdb;
  $table_name = $wpdb->prefix . self::TABLE_DATA;
  ```

#### Schema Management - dbDelta Formatting

**CRITICAL**: `dbDelta()` is extremely sensitive to formatting. Follow these rules exactly:

- **Two spaces** (not tabs) between PRIMARY KEY and the definition
- **Two spaces** between KEY and the definition
- Each column definition on its own line
- No spaces after opening parenthesis
- One space after comma in column definitions
- No trailing spaces anywhere
- SQL keywords must be uppercase
- **Must include** `require_once(ABSPATH . 'wp-admin/includes/upgrade.php');`

**Correct Example:**
```php
global $wpdb;
$table_name = $wpdb->prefix . '{plugin_prefix}_data';
$charset_collate = $wpdb->get_charset_collate();

$sql = "CREATE TABLE $table_name (
  id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
  site_id bigint(20) unsigned NOT NULL,
  data_key varchar(255) NOT NULL,
  data_value longtext,
  created_at datetime DEFAULT CURRENT_TIMESTAMP,
  updated_at datetime DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
  PRIMARY KEY  (id),
  KEY site_id (site_id),
  KEY data_key (data_key)
) $charset_collate;";

require_once(ABSPATH . 'wp-admin/includes/upgrade.php');
dbDelta($sql);
```

**Common dbDelta Errors to Avoid:**
```php
// ❌ WRONG - tabs instead of spaces
PRIMARY KEY	(id)

// ❌ WRONG - only one space after PRIMARY KEY
PRIMARY KEY (id)

// ✅ CORRECT - two spaces after PRIMARY KEY
PRIMARY KEY  (id)

// ❌ WRONG - no space after KEY name
KEY site_id(site_id)

// ✅ CORRECT - space after KEY name
KEY site_id (site_id)

// ❌ WRONG - lowercase keywords
primary key  (id)

// ✅ CORRECT - uppercase keywords
PRIMARY KEY  (id)
```

#### Prepared Statements
- **Always use** `$wpdb->prepare()` for user input
- Examples:
  ```php
  $wpdb->get_row($wpdb->prepare("SELECT * FROM $table WHERE id = %d", $id), ARRAY_A);
  $wpdb->update($table, $data, ['id' => $id]); // WordPress handles escaping
  ```

### WordPress API Controllers

#### Controller Structure
- Extend `WP_REST_Controller`
- Namespace: `{plugin-slug}/v1`
- Standard properties:
  ```php
  protected $namespace = '{plugin-slug}/v1';
  protected $rest_base = 'resource';
  ```

#### Route Registration
```php
public function register_routes()
{
    register_rest_route($this->namespace, '/' . $this->rest_base . '/(?P<id>[\d]+)', [
        [
            'methods' => WP_REST_Server::READABLE,
            'callback' => [$this, 'get_item'],
            'permission_callback' => [$this, 'check_permission'],
        ],
    ]);
}
```

#### Permission Callbacks
- Check permissions via capability checks or custom access control
- Return `WP_Error` for failures:
  ```php
  return new WP_Error('rest_forbidden', __('Message', '{plugin-text-domain}'), ['status' => 403]);
  ```

### Custom Post Types & Taxonomies

#### Custom Post Type Registration
```php
public function register_post_types()
{
    $args = [
        'label' => __('Items', '{plugin-text-domain}'),
        'public' => false,
        'show_ui' => true,
        'show_in_menu' => true,
        'show_in_rest' => true, // Enable Gutenberg
        'capability_type' => 'post',
        'hierarchical' => false,
        'supports' => ['title', 'editor', 'custom-fields'],
        'has_archive' => false,
        'rewrite' => ['slug' => '{plugin-slug}-item'],
    ];
    
    register_post_type('{plugin_prefix}_item', $args);
}
add_action('init', [$this, 'register_post_types']);
```

#### Taxonomy Registration
```php
public function register_taxonomies()
{
    $args = [
        'label' => __('Categories', '{plugin-text-domain}'),
        'hierarchical' => true,
        'public' => true,
        'show_in_rest' => true,
        'rewrite' => ['slug' => '{plugin-slug}-category'],
    ];
    
    register_taxonomy('{plugin_prefix}_category', ['{plugin_prefix}_item'], $args);
}
add_action('init', [$this, 'register_taxonomies']);
```

### Hooks & Filters for Extensibility

**Always provide hooks and filters** to make your plugin extensible.

#### Naming Convention
- Actions: `{plugin_prefix}_{context}_{action}`
- Filters: `{plugin_prefix}_{context}_{filter_type}`

#### Action Hooks (do_action)

Use before/after patterns:
```php
public function process_data($data)
{
    // Pre-processing hook
    do_action('{plugin_prefix}_before_process_data', $data);
    
    // Process data
    $result = $this->perform_processing($data);
    
    // Post-processing hook with result
    do_action('{plugin_prefix}_after_process_data', $data, $result);
    
    return $result;
}
```

Common action hook patterns:
```php
// Before save
do_action('{plugin_prefix}_before_save_item', $item_id, $data);

// After save
do_action('{plugin_prefix}_after_save_item', $item_id, $data);

// On delete
do_action('{plugin_prefix}_before_delete_item', $item_id);
do_action('{plugin_prefix}_after_delete_item', $item_id);

// Plugin initialization
do_action('{plugin_prefix}_loaded');
```

#### Filter Hooks (apply_filters)

Allow modification of data and behavior:
```php
public function get_settings()
{
    $defaults = [
        'option1' => 'value1',
        'option2' => 'value2',
    ];
    
    $settings = apply_filters('{plugin_prefix}_default_settings', $defaults);
    
    return $settings;
}

public function format_output($content)
{
    // Allow filtering the content before processing
    $content = apply_filters('{plugin_prefix}_pre_format_content', $content);
    
    // Format content
    $formatted = $this->do_formatting($content);
    
    // Allow filtering the final output
    return apply_filters('{plugin_prefix}_formatted_content', $formatted, $content);
}
```

Common filter patterns:
```php
// Modify query arguments
$args = apply_filters('{plugin_prefix}_query_args', $args, $context);

// Modify output/display
$html = apply_filters('{plugin_prefix}_item_html', $html, $item);

// Modify capabilities
$caps = apply_filters('{plugin_prefix}_user_capabilities', $caps, $user_id);

// Modify available options
$statuses = apply_filters('{plugin_prefix}_available_statuses', $statuses);
```

#### Document Your Hooks

Always document hooks for developers:
```php
/**
 * Fires after an item is saved to the database.
 *
 * @since 1.0.0
 *
 * @param int   $item_id The item ID.
 * @param array $data    The item data that was saved.
 */
do_action('{plugin_prefix}_after_save_item', $item_id, $data);

/**
 * Filters the formatted content output.
 *
 * @since 1.0.0
 *
 * @param string $formatted The formatted content.
 * @param string $original  The original content before formatting.
 * @return string The modified formatted content.
 */
return apply_filters('{plugin_prefix}_formatted_content', $formatted, $original);
```

### Asset Management

#### Enqueuing Styles
```php
public function enqueue_admin_styles($hook)
{
    // Only load on specific admin pages
    if ('toplevel_page_{plugin-slug}' !== $hook) {
        return;
    }
    
    wp_enqueue_style(
        '{plugin-slug}-admin',
        {PLUGIN_PREFIX}_PLUGIN_URL . 'admin/css/admin.css',
        [],
        {PLUGIN_PREFIX}_VERSION,
        'all'
    );
}
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_styles']);
```

#### Enqueuing Scripts
```php
public function enqueue_admin_scripts($hook)
{
    if ('toplevel_page_{plugin-slug}' !== $hook) {
        return;
    }
    
    wp_enqueue_script(
        '{plugin-slug}-admin',
        {PLUGIN_PREFIX}_PLUGIN_URL . 'admin/js/admin.js',
        ['jquery', 'wp-api'], // Dependencies
        {PLUGIN_PREFIX}_VERSION,
        true // Load in footer
    );
    
    // Localize script data
    wp_localize_script('{plugin-slug}-admin', '{pluginPrefix}Data', [
        'apiBase' => rest_url('{plugin-slug}/v1'),
        'nonce' => wp_create_nonce('wp_rest'),
        'ajaxUrl' => admin_url('admin-ajax.php'),
        'settings' => $this->get_frontend_settings(),
    ]);
}
add_action('admin_enqueue_scripts', [$this, 'enqueue_admin_scripts']);
```

#### Block Asset Registration
```php
public function register_block_assets()
{
    $asset_file = include({PLUGIN_PREFIX}_PLUGIN_DIR . 'build/index.asset.php');
    
    wp_register_script(
        '{plugin-slug}-blocks',
        {PLUGIN_PREFIX}_PLUGIN_URL . 'build/index.js',
        $asset_file['dependencies'],
        $asset_file['version']
    );
    
    wp_register_style(
        '{plugin-slug}-blocks',
        {PLUGIN_PREFIX}_PLUGIN_URL . 'build/index.css',
        [],
        {PLUGIN_PREFIX}_VERSION
    );
}
add_action('init', [$this, 'register_block_assets']);
```

### Security & Validation

#### Input Sanitization
```php
// Text fields
$value = sanitize_text_field($_POST['field']);

// Textareas
$value = sanitize_textarea_field($_POST['field']);

// Email
$email = sanitize_email($_POST['email']);

// URL
$url = esc_url_raw($_POST['url']);

// HTML (limited tags)
$html = wp_kses_post($_POST['content']);

// Integer
$id = absint($_POST['id']);
```

#### Output Escaping
```php
// HTML content
echo esc_html($content);

// Attributes
echo '<div class="' . esc_attr($class) . '">';

// URLs
echo '<a href="' . esc_url($url) . '">';

// JavaScript strings
echo '<script>var name = "' . esc_js($name) . '";</script>';

// Textarea content
echo '<textarea>' . esc_textarea($content) . '</textarea>';
```

#### Nonce Verification
```php
// Create nonce in form
wp_nonce_field('{plugin_prefix}_action', '{plugin_prefix}_nonce');

// Verify nonce on submission
if (!isset($_POST['{plugin_prefix}_nonce']) || 
    !wp_verify_nonce($_POST['{plugin_prefix}_nonce'], '{plugin_prefix}_action')) {
    wp_die(__('Security check failed', '{plugin-text-domain}'));
}
```

#### Capability Checks
```php
// Check user capabilities
if (!current_user_can('manage_options')) {
    wp_die(__('Insufficient permissions', '{plugin-text-domain}'));
}

// Custom capability check
if (!current_user_can('edit_{plugin_prefix}_items')) {
    return new WP_Error('forbidden', __('Access denied', '{plugin-text-domain}'));
}
```

### Data Transformation

- **DB Columns → API Keys**: Use transformation mapping for API responses
  ```php
  $db_to_key = [];
  foreach ($fields as $key => $config) {
      if (!empty($config['db_column'])) {
          $db_to_key[$config['db_column']] = $key;
      }
  }
  ```

### Error Handling

- Use `WP_Error` for API/Controller errors
- Log critical errors: `error_log("{PLUGIN_NAME}: " . $message);`
- Throw exceptions for schema/validation failures

### Service Registration

- Register services in `Plugin::register_services()`
- Pattern:
  ```php
  if (class_exists('{Vendor}\\{PluginName}\\Namespace\\ClassName')) {
      (new \\{Vendor}\\{PluginName}\\Namespace\\ClassName())->init();
  }
  ```

### Hooks & WordPress Lifecycle

- Use `add_action()` and `add_filter()` in `init()` methods
- Common hooks:
  - `init` - Register CPTs, taxonomies
  - `rest_api_init` - Register REST routes
  - `wp_enqueue_scripts` - Enqueue frontend assets
  - `admin_enqueue_scripts` - Enqueue admin assets
  - `plugins_loaded` - Initialize plugin

---

## Gutenberg Block Development Standards

### File Organization

- Blocks: `blocks/{block-name}/`
  - `block.json` - Block metadata
  - `index.js` - Block registration
  - `edit.js` - Editor interface
  - `save.js` - Saved content (for dynamic blocks, this may return null)
  - `style.scss` - Frontend styles
  - `editor.scss` - Editor-only styles
- Shared Components: `blocks/components/{component-name}.js`
- Utilities: `blocks/utils/`

### Block Registration

#### block.json (Preferred Method)
```json
{
    "$schema": "https://schemas.wp.org/trunk/block.json",
    "apiVersion": 2,
    "name": "{plugin-slug}/{block-name}",
    "title": "Block Title",
    "category": "widgets",
    "icon": "star-filled",
    "description": "Block description",
    "supports": {
        "html": false,
        "align": true
    },
    "textdomain": "{plugin-text-domain}",
    "editorScript": "file:./index.js",
    "editorStyle": "file:./editor.css",
    "style": "file:./style.css",
    "attributes": {
        "content": {
            "type": "string",
            "default": ""
        }
    }
}
```

#### JavaScript Registration
```javascript
import { registerBlockType } from '@wordpress/blocks';
import edit from './edit';
import save from './save';
import metadata from './block.json';

registerBlockType(metadata.name, {
    edit,
    save,
});
```

#### PHP Registration
```php
public function register_blocks()
{
    register_block_type({PLUGIN_PREFIX}_PLUGIN_DIR . 'blocks/{block-name}');
}
add_action('init', [$this, 'register_blocks']);
```

### TypeScript/JavaScript Conventions

- **Use TypeScript** for type safety (recommended but optional)
- **Define interfaces** for component props:
  ```typescript
  interface BlockEditProps {
      attributes: {
          content: string;
          backgroundColor: string;
      };
      setAttributes: (attrs: Partial<BlockEditProps['attributes']>) => void;
      clientId: string;
  }
  ```
- **Import WordPress packages** properly:
  ```javascript
  import { useBlockProps } from '@wordpress/block-editor';
  import { Button, PanelBody } from '@wordpress/components';
  import { __ } from '@wordpress/i18n';
  ```

### Component Structure

#### Block Edit Component
```javascript
import { useBlockProps, InspectorControls, RichText } from '@wordpress/block-editor';
import { PanelBody, TextControl } from '@wordpress/components';
import { __ } from '@wordpress/i18n';

export default function Edit({ attributes, setAttributes }) {
    const blockProps = useBlockProps();
    const { content, heading } = attributes;

    return (
        <>
            <InspectorControls>
                <PanelBody title={__('Settings', '{plugin-text-domain}')}>
                    <TextControl
                        label={__('Heading', '{plugin-text-domain}')}
                        value={heading}
                        onChange={(value) => setAttributes({ heading: value })}
                    />
                </PanelBody>
            </InspectorControls>
            <div {...blockProps}>
                <RichText
                    tagName="p"
                    value={content}
                    onChange={(value) => setAttributes({ content: value })}
                    placeholder={__('Enter content...', '{plugin-text-domain}')}
                />
            </div>
        </>
    );
}
```

#### Block Save Component
```javascript
import { useBlockProps, RichText } from '@wordpress/block-editor';

export default function Save({ attributes }) {
    const blockProps = useBlockProps.save();
    const { content, heading } = attributes;

    return (
        <div {...blockProps}>
            {heading && <h3>{heading}</h3>}
            <RichText.Content tagName="p" value={content} />
        </div>
    );
}
```

#### Dynamic Block (Server-Side Rendering)
```javascript
// save.js - return null for dynamic blocks
export default function Save() {
    return null;
}
```

```php
// PHP render callback
public function register_dynamic_block()
{
    register_block_type({PLUGIN_PREFIX}_PLUGIN_DIR . 'blocks/dynamic-block', [
        'render_callback' => [$this, 'render_dynamic_block'],
    ]);
}

public function render_dynamic_block($attributes, $content)
{
    $data = $this->get_dynamic_data();
    
    ob_start();
    ?>
    <div class="wp-block-{plugin-slug}-dynamic-block">
        <?php echo esc_html($data); ?>
    </div>
    <?php
    return ob_get_clean();
}
```

#### Memoization
- Use `memo()` for performance-critical components
- Use `useCallback()` for event handlers passed as props
- Use `useMemo()` for expensive calculations

```javascript
import { memo, useCallback } from '@wordpress/element';

const CustomComponent = memo(function CustomComponent({ data, onChange }) {
    const handleChange = useCallback((value) => {
        onChange(value);
    }, [onChange]);

    return (
        // Component JSX
    );
});
```

### State Management

- **Local state**: Use `useState` from `@wordpress/element`
  ```javascript
  import { useState } from '@wordpress/element';
  const [isActive, setIsActive] = useState(false);
  ```

- **Global state**: Use `@wordpress/data` stores
  ```javascript
  import { useSelect, useDispatch } from '@wordpress/data';
  
  const { posts, isResolving } = useSelect((select) => ({
      posts: select('core').getEntityRecords('postType', 'post'),
      isResolving: select('core/data').isResolving('core', 'getEntityRecords', ['postType', 'post']),
  }));
  
  const { savePost } = useDispatch('core/editor');
  ```

- **Custom data stores**: Create your own stores
  ```javascript
  import { createReduxStore, register } from '@wordpress/data';
  
  const store = createReduxStore('{plugin-slug}/store', {
      reducer(state = { items: [] }, action) {
          // Reducer logic
      },
      actions: {
          setItems(items) {
              return { type: 'SET_ITEMS', items };
          },
      },
      selectors: {
          getItems(state) {
              return state.items;
          },
      },
  });
  
  register(store);
  ```

### Styling

Gutenberg blocks support multiple styling approaches:

- **Vanilla CSS/SCSS**: Standard approach, compiled via webpack
  ```scss
  .wp-block-{plugin-slug}-block-name {
      padding: 1rem;
      background: #fff;
      
      &.is-active {
          border: 2px solid #007cba;
      }
  }
  ```

- **WordPress Component Styles**: Use `@wordpress/components` built-in styling
  ```javascript
  import { Card, CardBody } from '@wordpress/components';
  // Components come pre-styled
  ```

- **Optional: TailwindCSS**: Can be integrated via webpack configuration
  ```javascript
  className="p-4 bg-white rounded-lg shadow-md"
  ```

### WordPress Components

Use `@wordpress/components` for UI consistency:

```javascript
import {
    Button,
    TextControl,
    SelectControl,
    ToggleControl,
    PanelBody,
    PanelRow,
    ColorPalette,
    RangeControl,
    CheckboxControl,
    RadioControl,
} from '@wordpress/components';
```

### Data Fetching

- Use `@wordpress/api-fetch` for REST API calls:
  ```javascript
  import apiFetch from '@wordpress/api-fetch';
  
  apiFetch({ path: '/{plugin-slug}/v1/resource' })
      .then((data) => {
          // Handle response
      })
      .catch((error) => {
          // Handle error
      });
  ```

- For entity data, use `@wordpress/core-data`:
  ```javascript
  import { useEntityRecords } from '@wordpress/core-data';
  
  const { records, isResolving, hasResolved } = useEntityRecords('postType', 'post', {
      per_page: 10,
      status: 'publish',
  });
  ```

---

## Optional: Modern Frontend Frameworks

For custom admin interfaces or advanced frontend applications beyond Gutenberg blocks, you may use modern frameworks.

### Framework Options

- **React + TypeScript**: For custom admin dashboards
- **Next.js**: For headless WordPress implementations
- **Vue.js**: Alternative to React
- **Vite**: For faster build tooling

### File Organization (React/TypeScript)

- Components: `client/components/{feature}/{component}.tsx`
- Hooks: `client/hooks/use-{name}.ts`
- Utils/Lib: `client/lib/{name}.ts`
- Styles: `client/styles/`

### TypeScript Conventions

```typescript
import type React from 'react';
import { useState, useCallback } from 'react';

interface ComponentProps {
    data: SomeType[];
    onAction: (id: number) => void;
}

export function Component({ data, onAction }: ComponentProps) {
    const [state, setState] = useState(false);

    const handleAction = useCallback(() => {
        // Implementation
    }, []);

    return (
        // JSX
    );
}
```

### State Management

- React hooks for local state
- Context API for shared state
- Optional: Redux, Zustand, or other state libraries for complex applications

### Data Fetching

- Use native `fetch()` with WordPress REST API
- Include nonce for authentication:
  ```typescript
  fetch(url, {
      method: 'POST',
      headers: {
          'Content-Type': 'application/json',
          'X-WP-Nonce': window.wpData.nonce,
      },
      body: JSON.stringify(data),
  })
  ```

### API Integration

- Localize script data for configuration:
  ```php
  wp_localize_script('plugin-admin', 'wpData', [
      'apiBase' => rest_url('{plugin-slug}/v1'),
      'nonce' => wp_create_nonce('wp_rest'),
  ]);
  ```

- Access in JavaScript:
  ```typescript
  const apiBase = window.wpData.apiBase;
  const nonce = window.wpData.nonce;
  ```

---

## General Best Practices

### Documentation

- **PHPDoc** for all public methods
- Include `@param`, `@return`, and `@throws` tags
- Brief class descriptions at the top of each file
- **JSDoc** for JavaScript/TypeScript functions

```php
/**
 * Retrieve item by ID
 *
 * @since 1.0.0
 *
 * @param int $item_id The item ID to retrieve.
 * @return array|WP_Error Item data array or WP_Error on failure.
 */
public function get_item($item_id)
{
    // Implementation
}
```

### Code Formatting

- **Indentation**: Tabs (converted to spaces in editor)
- **Line Length**: No strict limit, but aim for readability
- **Braces**: Opening brace on same line (K&R style)
- **Spacing**: Space after control structures (`if (`, `foreach (`, etc.)

### Naming Conventions

#### PHP
- Classes: `PascalCase`
- Methods/Functions: `snake_case`
- Constants: `UPPER_SNAKE_CASE`
- Variables: `snake_case`
- Hook names: `{plugin_prefix}_{context}_{action}`

#### JavaScript/TypeScript
- Components: `PascalCase`
- Functions/Variables: `camelCase`
- Types/Interfaces: `PascalCase`
- Constants: `UPPER_SNAKE_CASE`
- WordPress handles: `kebab-case`

### Internationalization

- **Always use translation functions**:
  ```php
  __('Text', '{plugin-text-domain}')
  _e('Text', '{plugin-text-domain}')
  _n('Singular', 'Plural', $count, '{plugin-text-domain}')
  _x('Text', 'Context', '{plugin-text-domain}')
  esc_html__('Text', '{plugin-text-domain}')
  esc_attr__('Text', '{plugin-text-domain}')
  ```
  
  ```javascript
  import { __, _n, _x } from '@wordpress/i18n';
  __('Text', '{plugin-text-domain}')
  _n('Singular', 'Plural', count, '{plugin-text-domain}')
  _x('Text', 'Context', '{plugin-text-domain}')
  ```

### Version Control

- Commit logical, atomic changes
- Use descriptive commit messages
- Follow conventional commits where appropriate
- Gitignore: `/vendor/`, `/node_modules/`, `/build/`, `.env`

---

**Reference this document for consistent coding standards across your WordPress plugin development.**
