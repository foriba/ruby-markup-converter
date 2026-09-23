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
 * Version:           1.1.0
 * Requires at least: 6.8
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

define(
	'RUBYMACO_VERSION',
	get_file_data( __FILE__, array( 'version' => 'Version' ) )['version']
);

require_once RUBYMACO_PLUGIN_DIR . '/vendor/autoload.php';
require_once RUBYMACO_PLUGIN_DIR . '/includes/settings/definitions.php';


require_once RUBYMACO_PLUGIN_DIR . '/includes/content-filter.php';
require_once RUBYMACO_PLUGIN_DIR . '/includes/shortcode.php';

require_once RUBYMACO_PLUGIN_DIR . '/includes/frontend-assets.php';

require_once RUBYMACO_PLUGIN_DIR . '/includes/blocks.php';

if ( is_admin() ) {
	require_once RUBYMACO_PLUGIN_DIR . '/admin/settings-page.php';
}
