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

use Foriba\RubyMarkupConverter\Bootstrap;
use Foriba\RubyMarkupConverter\Plugin_Info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/vendor/autoload.php';

( new Bootstrap( new Plugin_Info( __FILE__ ) ) )->boot();
