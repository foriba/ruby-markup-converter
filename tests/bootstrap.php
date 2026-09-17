<?php
// Load real WordPress text/HTML APIs without wp-load.php or a database.
$root = getenv('WP_ROOT');
if (!$root || !is_file(rtrim($root, '/\\') . '/wp-includes/version.php')) {
    throw new RuntimeException('Set WP_ROOT to a WordPress installation (6.8 or later).');
}
define('ABSPATH', rtrim($root, '/\\') . '/');
define('WPINC', 'wp-includes');
define('WP_DEBUG', false);
require ABSPATH . WPINC . '/version.php';
if (version_compare($wp_version, '6.8', '<')) {
    throw new RuntimeException('WordPress 6.8 or later is required.');
}
foreach (['compat-utf8.php', 'utf8.php'] as $optional) {
    if (is_file(ABSPATH . WPINC . '/' . $optional)) {
        require_once ABSPATH . WPINC . '/' . $optional;
    }
}
foreach (['compat.php', 'plugin.php', 'formatting.php', 'kses.php', 'shortcodes.php', 'class-wp-token-map.php', 'html-api/html5-named-character-references.php', 'html-api/class-wp-html-tag-processor.php'] as $file) {
    require_once ABSPATH . WPINC . '/' . $file;
}
foreach (glob(ABSPATH . WPINC . '/html-api/class-*.php') as $file) {
    require_once $file;
}
$GLOBALS['test_options'] = [];
function get_option($key, $default = false) {
    if ($key === 'blog_charset') { return 'UTF-8'; }
    return array_key_exists($key, $GLOBALS['test_options']) ? $GLOBALS['test_options'][$key] : $default;
}
function update_option($key, $value, $autoload = null) { throw new RuntimeException('Unexpected database write'); }
function add_option($key, $value = '', $deprecated = '', $autoload = null) { throw new RuntimeException('Unexpected database write'); }
function delete_option($key) { throw new RuntimeException('Unexpected database write'); }
function __($text, $domain = '') { return $text; }
function wp_allowed_protocols() { return ['http', 'https', 'mailto']; }
function _canonical_charset($charset) { return is_utf8_charset($charset) ? 'UTF-8' : $charset; }
function is_utf8_charset($charset = null) { return $charset === null || in_array(strtolower($charset), ['utf8', 'utf-8'], true); }
function is_admin() { return true; }
function plugins_url($path = '', $plugin = '') { return 'https://example.invalid/plugin/' . $path; }
require dirname(__DIR__) . '/ruby-markup-converter.php';
