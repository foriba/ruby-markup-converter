<?php
/**
 * Ruby Markup Converter
 *
 * @package           RubyMarkupConverter
 * @author            Foriba
 * @copyright         2026 Foriba
 * @license           GPL-2.0-or-later
 *
 * @wordpress-plugin
 * Plugin Name:       Ruby Markup Converter
 * Plugin URI:        https://github.com/foriba/ruby-markup-converter/
 * Description:       Converts ruby and emphasis mark notation from Japanese novel-sharing sites for WordPress, without modifying the original text.
 * Version:           1.3.0
 * Requires at least: 6.8
 * Requires PHP:      7.4
 * Author:            Foriba
 * Author URI:        https://github.com/foriba/
 * Text Domain:       ruby-markup-converter
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Bootstrap;
use Foriba\RubyMarkupConverter\Plugin_Info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// 実行用 vendor/ を生成せずに設置された場合は、致命的エラーにせず管理画面で知らせる.
if ( ! is_readable( __DIR__ . '/vendor/autoload.php' ) ) {
	add_action(
		'admin_notices',
		static function (): void {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			wp_admin_notice(
				esc_html__(
					'Ruby Markup Converter could not start because required files are missing. Please reinstall the plugin.',
					'ruby-markup-converter'
				),
				array( 'type' => 'error' )
			);
		}
	);
	return;
}

require_once __DIR__ . '/vendor/autoload.php';

( new Bootstrap(
	new Plugin_Info( __FILE__ )
) )->boot();
