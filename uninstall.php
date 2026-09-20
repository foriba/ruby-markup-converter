<?php
/**
 * プラグイン削除時に保存済みオプションを削除する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Settings\Option_Keys;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

require_once __DIR__ . '/includes/settings/class-option-keys.php';

foreach ( Option_Keys::values() as $rubymaco_option_key ) {
	delete_option( $rubymaco_option_key );
}
