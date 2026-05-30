<?php
/**
 * プラグイン削除時に保存済みオプションを削除する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use foriba\rubymarkupconverter\RUBYMACO_Option_Key;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/class-rubymaco-option-key.php';

foreach ( RUBYMACO_Option_Key::values() as $rubymaco_option_key ) {
	delete_option( $rubymaco_option_key );
}
