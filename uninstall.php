<?php
/**
 * プラグイン削除時に保存済みオプションを削除する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

define( 'RUBYMACO_PLUGIN_DIR', plugin_dir_path( __FILE__ ) );
require_once RUBYMACO_PLUGIN_DIR . '/includes/constants.php';

foreach ( RUBYMACO_OPTION_KEYS as $rubymaco_option_key ) {
	delete_option( $rubymaco_option_key );
}
