<?php
/**
 * アンインストールの削除対象を検証する。実 DB の削除は行わない。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Settings\Option_Keys;
use Foriba\RubyMarkupConverter\Resolver\Post_Conversion_Scope_Resolver;
use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;

/**
 * メタ削除要求を、削除時のサイト ID とともに記録する。
 *
 * @param string $key メタキー.
 * @return bool 削除結果.
 */
function delete_post_meta_by_key( $key ) {
	$GLOBALS['test_deleted_post_meta'][] = array( $GLOBALS['test_current_site'], $key );
	return true;
}

/**
 * メタ一括削除要求を、削除時のサイト ID とともに記録する。
 *
 * @param string $meta_type メタの種別.
 * @param int    $object_id 対象 ID。全件削除では 0.
 * @param string $meta_key メタキー.
 * @param mixed  $meta_value メタ値。全件削除では空文字.
 * @param bool   $delete_all すべての対象から削除する場合に true.
 * @return bool 削除結果.
 */
function delete_metadata( $meta_type, $object_id, $meta_key, $meta_value, $delete_all ) {
	$GLOBALS['test_deleted_metadata'][] = array( $GLOBALS['test_current_site'], $meta_type, $object_id, $meta_key, $meta_value, $delete_all );
	return true;
}

/**
 * マルチサイトかどうかをテスト用の値で返す。
 *
 * @return bool マルチサイトの場合に true.
 */
function is_multisite() {
	return $GLOBALS['test_is_multisite'];
}

/**
 * テスト用のサイト ID 一覧を返す。
 *
 * @param array<string, mixed> $args 取得条件.
 * @return int[] サイト ID.
 */
function get_sites( $args = array() ) {
	check_same( 'ids', $args['fields'], 'Sites are fetched as IDs' );
	check_same( 0, $args['number'], 'All sites are fetched' );
	return array( 1, 2, 5 );
}

/**
 * 現在のサイトを切り替える。
 *
 * @param int $site_id サイト ID.
 * @return bool 常に true.
 */
function switch_to_blog( $site_id ) {
	$GLOBALS['test_site_stack'][] = $GLOBALS['test_current_site'];
	$GLOBALS['test_current_site'] = $site_id;
	return true;
}

/**
 * 切り替え前のサイトに戻す。
 *
 * @return bool 常に true.
 */
function restore_current_blog() {
	$GLOBALS['test_current_site'] = array_pop( $GLOBALS['test_site_stack'] );
	return true;
}

/**
 * 各サイトで想定される削除要求を組み立てる。
 *
 * @param int[] $site_ids サイト ID.
 * @return array{options:array<int, array{int, string}>, meta:array<int, array{int, string}>, metadata:array<int, array{int, string, int, string, string, bool}>} 削除要求.
 */
function test_expected_uninstall_deletions( array $site_ids ) {
	$expected = array(
		'options'  => array(),
		'meta'     => array(),
		'metadata' => array(),
	);
	foreach ( $site_ids as $site_id ) {
		foreach ( Option_Keys::values() as $key ) {
			$expected['options'][] = array( $site_id, $key );
		}
		$expected['meta'][]     = array( $site_id, Post_Conversion_Scope_Resolver::META_KEY );
		$expected['metadata'][] = array( $site_id, 'term', 0, Target_Category_Settings::META_KEY, '', true );
	}
	return $expected;
}

$deleted_options                = array();
$GLOBALS['test_option_deleter'] = static function ( $key ) use ( &$deleted_options ) {
	$deleted_options[] = array( $GLOBALS['test_current_site'], $key );
	return true;
};
try {
	define( 'WP_UNINSTALL_PLUGIN', 'ruby-markup-converter/ruby-markup-converter.php' );
	check_same( false, in_array( 'rubymaco_target_category_ids', Option_Keys::values(), true ), 'Category targets are stored as term meta, not an option' );
	foreach ( array(
		'Single site' => array( false, array( 1 ) ),
		'Multisite'   => array( true, array( 1, 2, 5 ) ),
	) as $uninstall_label => $uninstall_case ) {
		list( $GLOBALS['test_is_multisite'], $uninstall_site_ids ) = $uninstall_case;
		$deleted_options                   = array();
		$GLOBALS['test_deleted_post_meta'] = array();
		$GLOBALS['test_deleted_metadata']  = array();
		$GLOBALS['test_current_site']      = 1;
		$GLOBALS['test_site_stack']        = array();
		require dirname( __DIR__, 2 ) . '/uninstall.php';
		$uninstall_expected = test_expected_uninstall_deletions( $uninstall_site_ids );
		check_same( $uninstall_expected['options'], $deleted_options, $uninstall_label . ': all plugin options deleted on each site' );
		check_same( $uninstall_expected['meta'], $GLOBALS['test_deleted_post_meta'], $uninstall_label . ': only plugin post metadata deleted on each site' );
		check_same( $uninstall_expected['metadata'], $GLOBALS['test_deleted_metadata'], $uninstall_label . ': category term metadata deleted from all terms on each site' );
		check_same( 1, $GLOBALS['test_current_site'], $uninstall_label . ': original site restored' );
		check_same( array(), $GLOBALS['test_site_stack'], $uninstall_label . ': site switches balanced' );
	}
} finally {
	unset( $GLOBALS['test_deleted_post_meta'], $GLOBALS['test_deleted_metadata'], $GLOBALS['test_option_deleter'], $GLOBALS['test_is_multisite'], $GLOBALS['test_current_site'], $GLOBALS['test_site_stack'] );
}
