<?php
/**
 * アンインストールの削除対象を検証する。実 DB の削除は行わない。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Settings\Option_Keys;
use Foriba\RubyMarkupConverter\Resolver\Post_Conversion_Override_Resolver;

/**
 * メタ削除要求を記録する。
 *
 * @param string $key メタキー.
 * @return bool 削除結果.
 */
function delete_post_meta_by_key( $key ) {
	$GLOBALS['test_deleted_post_meta'][] = $key;
	return true;
}

$deleted_options                   = array();
$GLOBALS['test_deleted_post_meta'] = array();
$GLOBALS['test_option_deleter']    = static function ( $key ) use ( &$deleted_options ) {
	$deleted_options[] = $key;
	return true;
};
try {
	define( 'WP_UNINSTALL_PLUGIN', 'ruby-markup-converter/ruby-markup-converter.php' );
	require dirname( __DIR__, 2 ) . '/uninstall.php';
	check_same( Option_Keys::values(), $deleted_options, 'All plugin options deleted' );
	check_same( true, in_array( 'rubymaco_target_category_ids', $deleted_options, true ), 'Target category option deleted' );
	check_same( array( Post_Conversion_Override_Resolver::META_KEY ), $GLOBALS['test_deleted_post_meta'], 'Only plugin post metadata deleted' );
} finally {
	unset( $GLOBALS['test_deleted_post_meta'], $GLOBALS['test_option_deleter'] );
}
