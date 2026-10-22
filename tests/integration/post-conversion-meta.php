<?php
/**
 * 投稿メタ登録・権限・REST スキーマを検証する。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Resolver\Post_Conversion_Override_Resolver;

/**
 * メタ登録内容を記録する。
 *
 * @param string $post_type 投稿タイプ.
 * @param string $key メタキー.
 * @param array  $args 登録引数.
 * @return bool 登録結果.
 */
function register_post_meta( $post_type, $key, $args ) {
	\check_same( Post_Conversion_Override_Resolver::META_KEY, $key, 'Meta key registration' );
	$GLOBALS['test_conversion_meta'][ $post_type ] = $args;
	return true;
}

/**
 * 対象ユーザーの投稿編集権限を代替する。
 *
 * @param int    $user_id ユーザー ID.
 * @param string $capability 権限.
 * @param int    $object_id 投稿 ID.
 * @return bool 編集可能か.
 */
function user_can( $user_id, $capability, $object_id ) {
	\check_same( 'edit_post', $capability, 'Meta checks specific post edit capability' );
	return 7 === $user_id && 10 === $object_id;
}

$meta_callbacks = array_values(
	array_filter(
		$GLOBALS['wp_filter']['init']->callbacks[20],
		static fn( array $entry ): bool => is_array( $entry['function'] ) && $entry['function'][0] instanceof Post_Conversion_Meta
	)
);
\check_same( true, count( $meta_callbacks ) >= 1, 'Bootstrap connects meta registration' );
$meta_integration                = $meta_callbacks[0]['function'][0];
$GLOBALS['test_conversion_meta'] = array();
// WordPress の標準フィルター。REST の enum エラー内の一覧を整形する.
add_filter( 'wp_sprintf', 'wp_sprintf_l', 10, 2 );
try {
	$meta_integration->register_meta();
	\check_same( array( 'post', 'page' ), array_keys( $GLOBALS['test_conversion_meta'] ), 'Only posts and pages registered' );
	foreach ( $GLOBALS['test_conversion_meta'] as $meta_args ) {
		\check_same( true, $meta_args['single'], 'Single metadata value' );
		\check_same( 'inherit', $meta_args['default'], 'Missing metadata inherits' );
		\check_same( array( 'edit' ), $meta_args['show_in_rest']['schema']['context'], 'Metadata exposed only in edit context' );
		foreach ( array( 'inherit', 'enabled', 'disabled' ) as $valid ) {
			\check_same( true, rest_validate_value_from_schema( $valid, $meta_args['show_in_rest']['schema'] ), 'REST accepts valid metadata' );
			\check_same( $valid, call_user_func( $meta_args['sanitize_callback'], $valid ), 'Sanitizer preserves state' );
		}
		foreach ( array( 'unknown', '', true, 1, array() ) as $invalid ) {
			\check_same( true, rest_validate_value_from_schema( $invalid, $meta_args['show_in_rest']['schema'] ) instanceof \WP_Error, 'REST rejects invalid metadata' );
			\check_same( 'inherit', call_user_func( $meta_args['sanitize_callback'], $invalid ), 'Direct PHP invalid value inherits' );
		}
		foreach ( array( array( 7, 10, true ), array( 0, 10, false ), array( 8, 10, false ), array( 7, 20, false ), array( 7, 0, false ) ) as $permission_case ) {
			list( $test_user_id, $test_post_id, $expected_permission ) = $permission_case;
			\check_same( $expected_permission, call_user_func( $meta_args['auth_callback'], false, Post_Conversion_Override_Resolver::META_KEY, $test_post_id, $test_user_id ), 'Post-specific permission' );
		}
	}
} finally {
	remove_filter( 'wp_sprintf', 'wp_sprintf_l', 10 );
	unset( $GLOBALS['test_conversion_meta'] );
}
