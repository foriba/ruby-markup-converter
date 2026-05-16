<?php
/**
 * Plugin Name
 *
 * @package           PluginPackage
 * @author            Foriba
 * @copyright         2026 Foriba
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Ruby Markup Converter
 * Plugin URI:        https://github.com/foriba/ruby-markup-converter/
 * Description:       Automatically convert plain-text ruby notations into display-ready HTML.
 * Version:           0.9.0
 * Requires at least: 6.4
 * Requires PHP:      7.4
 * Author:            Foriba
 * Author URI:        https://github.com/foriba/
 * Text Domain:       ruby-markup-converter
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.txt
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Bootstrap
 *
 * Define plugin-wide constants and load feature modules.
 */

define( 'RUBYMACO_PLUGIN_FILE', __FILE__ );
define( 'RUBYMACO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
define( 'RUBYMACO_PLUGIN_URL', plugin_dir_url( __FILE__ ) );

const RUBYMACO_VERSION = '0.9.0';

require_once RUBYMACO_PLUGIN_DIR . '/includes/constants.php';

require_once RUBYMACO_PLUGIN_DIR . '/includes/markup-rules.php';
require_once RUBYMACO_PLUGIN_DIR . '/includes/markup-transformer.php';

require_once RUBYMACO_PLUGIN_DIR . '/includes/content-filter.php';
require_once RUBYMACO_PLUGIN_DIR . '/includes/shortcode.php';

require_once RUBYMACO_PLUGIN_DIR . '/includes/frontend-assets.php';
require_once RUBYMACO_PLUGIN_DIR . '/includes/settings-helpers.php';

if ( is_admin() ) {
	require_once RUBYMACO_PLUGIN_DIR . '/admin/settings-page.php';
}
