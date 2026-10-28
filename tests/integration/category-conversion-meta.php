<?php
/**
 * カテゴリ別設定のタームメタ登録・権限・保存値の補正を検証する。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;

/**
 * タームメタの登録内容を記録する。
 *
 * @param string $taxonomy タクソノミー.
 * @param string $key メタキー.
 * @param array  $args 登録引数.
 * @return bool 登録結果.
 */
function register_term_meta( $taxonomy, $key, $args ) {
	\check_same( Target_Category_Settings::META_KEY, $key, 'Category meta key registration' );
	$GLOBALS['test_category_meta'][ $taxonomy ] = $args;
	return true;
}

$category_meta_callbacks = array_values(
	array_filter(
		$GLOBALS['wp_filter']['init']->callbacks[20],
		static fn( array $entry ): bool => is_array( $entry['function'] ) && $entry['function'][0] instanceof Category_Conversion_Meta
	)
);
\check_same( true, count( $category_meta_callbacks ) >= 1, 'Bootstrap connects category meta registration' );
\check_same( 'register_meta', $category_meta_callbacks[0]['function'][1], 'Category meta registration callback' );
$category_meta_integration     = $category_meta_callbacks[0]['function'][0];
$GLOBALS['test_category_meta'] = array();
try {
	$category_meta_integration->register_meta();
	\check_same( array( 'category' ), array_keys( $GLOBALS['test_category_meta'] ), 'Only categories registered' );
	$category_meta_args = $GLOBALS['test_category_meta']['category'];
	\check_same( 'string', $category_meta_args['type'], 'Category meta type' );
	\check_same( true, $category_meta_args['single'], 'Single category meta value' );
	\check_same( '', $category_meta_args['default'], 'Missing category meta is not a target' );
	\check_same( false, $category_meta_args['show_in_rest'], 'Category meta is not exposed in REST' );
	\check_same( Target_Category_Settings::ENABLED, call_user_func( $category_meta_args['sanitize_callback'], Target_Category_Settings::ENABLED ), 'Sanitizer preserves enabled value' );
	foreach ( array( '', '0', 'true', true, 1, array(), null ) as $invalid ) {
		\check_same( '', call_user_func( $category_meta_args['sanitize_callback'], $invalid ), 'Direct PHP invalid value is not a target' );
	}
	foreach ( array(
		array( 7, 1, Target_Category_Settings::META_KEY, true ),
		array( 8, 1, Target_Category_Settings::META_KEY, false ),
		array( 0, 1, Target_Category_Settings::META_KEY, false ),
		array( 7, 0, Target_Category_Settings::META_KEY, false ),
		array( 7, 1, 'other_meta', false ),
	) as $permission_case ) {
		list( $test_user_id, $test_term_id, $test_meta_key, $expected_permission ) = $permission_case;
		\check_same( $expected_permission, call_user_func( $category_meta_args['auth_callback'], false, $test_meta_key, $test_term_id, $test_user_id ), 'Category meta permission' );
	}
} finally {
	unset( $GLOBALS['test_category_meta'] );
}
