<?php
/**
 * プラグイン削除時に保存済みオプション、投稿メタ、タームメタを削除する。
 *
 * マルチサイトでは、ネットワーク内のすべてのサイトから削除する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Settings\Option_Keys;
use Foriba\RubyMarkupConverter\Resolver\Post_Conversion_Scope_Resolver;
use Foriba\RubyMarkupConverter\Settings\Category_Conversion_Scope_Settings;

if ( ! defined( 'WP_UNINSTALL_PLUGIN' ) ) {
	exit;
}

// 実行用 vendor/ がない場合でも削除できるよう、キーを定義するクラスだけを直接読み込む.
require_once __DIR__ . '/includes/settings/class-option-keys.php';
require_once __DIR__ . '/includes/resolver/class-post-conversion-scope-resolver.php';
require_once __DIR__ . '/includes/settings/class-category-conversion-scope-settings.php';

/**
 * 現在のサイトに保存したオプション、投稿メタ、タームメタを削除する。
 */
$rubymaco_delete_site_data = static function (): void {
	foreach ( Option_Keys::values() as $option_key ) {
		delete_option( $option_key );
	}
	delete_post_meta_by_key( Post_Conversion_Scope_Resolver::META_KEY );
	delete_metadata( 'term', 0, Category_Conversion_Scope_Settings::META_KEY, '', true );
};

if ( is_multisite() ) {
	// ネットワーク有効化か各サイトでの有効化かにかかわらず、全サイトを対象にする.
	$rubymaco_site_ids = get_sites(
		array(
			'fields'                 => 'ids',
			'number'                 => 0,
			'update_site_meta_cache' => false,
		)
	);
	foreach ( $rubymaco_site_ids as $rubymaco_site_id ) {
		switch_to_blog( (int) $rubymaco_site_id );
		$rubymaco_delete_site_data();
		restore_current_blog();
	}
} else {
	$rubymaco_delete_site_data();
}
