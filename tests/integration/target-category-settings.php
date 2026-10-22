<?php
/**
 * 対象カテゴリの保存・取得を検証する。DB と権限は代替実装。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Settings;

/**
 * カテゴリ存在確認の代替処理。
 *
 * @param int    $id カテゴリ ID.
 * @param string $taxonomy タクソノミー.
 * @return \WP_Term|null 存在するカテゴリ.
 */
function get_term( $id, $taxonomy ) {
	\check_same( 'category', $taxonomy, 'Category taxonomy validation' );
	return in_array( $id, $GLOBALS['test_existing_categories'], true )
		? new \WP_Term(
			(object) array(
				'term_id'  => $id,
				'taxonomy' => 'category',
			)
		)
		: null;
}

/**
 * 管理者権限の代替処理。
 *
 * @param string $capability 権限.
 * @return bool 管理者か.
 */
function current_user_can( $capability ) {
	\check_same( Settings_Identifiers::CAPABILITY, $capability, 'Category save requires management permission' );
	return $GLOBALS['test_target_admin'];
}

require_once ABSPATH . WPINC . '/class-wp-term.php';
$target_settings                     = new Target_Category_Settings();
$target_options_before               = $GLOBALS['test_options'];
$GLOBALS['test_existing_categories'] = array( 1, 2, 3 );
$GLOBALS['test_target_admin']        = true;
$target_writes                       = 0;
$GLOBALS['test_option_writer']       = static function ( $key, $value ) use ( &$target_writes ) {
	\check_same( Option_Keys::TARGET_CATEGORY_IDS, $key, 'Only category option written' );
	++$target_writes;
	$GLOBALS['test_options'][ $key ] = $value;
	return true;
};
try {
	$GLOBALS['test_options'] = array();
	\check_same( array(), $target_settings->get(), 'Missing category option defaults to empty' );
	\check_same( array( 1, 3 ), $target_settings->save( array( 3, 1, 3 ) ), 'Deduplicate and sort before saving' );
	\check_same( array( 1, 3 ), $target_settings->get(), 'Read saved categories' );
	\check_same( array( 1, 3 ), $target_settings->save( array( 1, 3 ) ), 'Unchanged save succeeds' );
	\check_same( array(), $target_settings->save( array() ), 'All unchecked persists empty list' );
	\check_same( array(), $target_settings->get(), 'All unchecked remains empty' );
	$writes_before_invalid = $target_writes;
	foreach ( array( null, false, '1', array( '1' ), array( 0 ), array( -1 ), array( true ), array( 1.5 ), array( 999 ), array( 1, 999 ), array( 2 => 1 ) ) as $invalid ) {
		$rejected = false;
		try {
			$target_settings->save( $invalid );
		} catch ( \InvalidArgumentException $error ) {
			$rejected = true;
		}
		\check_same( true, $rejected, 'Reject invalid category request before writing' );
	}
	\check_same( $writes_before_invalid, $target_writes, 'No partial writes on invalid input' );
	$GLOBALS['test_target_admin'] = false;
	$rejected                     = false;
	try {
		$target_settings->save( array( 1 ) );
	} catch ( \RuntimeException $error ) {
		$rejected = true;
	}
	\check_same( true, $rejected, 'Unauthorized category save rejected' );
	\check_same( $writes_before_invalid, $target_writes, 'No unauthorized writes' );
	$GLOBALS['test_options'][ Option_Keys::TARGET_CATEGORY_IDS ] = array( 3, 1, 999, '2', false, 1 );
	\check_same( array( 1, 3 ), $target_settings->get(), 'Read filters invalid and deleted categories' );
	$GLOBALS['test_existing_categories'] = array( 1, 2 );
	\check_same( array( 1 ), $target_settings->get(), 'Category deletion reflected without cache' );
	\check_same( $writes_before_invalid, $target_writes, 'Reads never repair storage' );
	$GLOBALS['test_options'][ Option_Keys::TARGET_CATEGORY_IDS ] = 'invalid';
	\check_same( array(), $target_settings->get(), 'Non-array stored value defaults to empty' );
	$GLOBALS['test_target_admin']  = true;
	$GLOBALS['test_option_writer'] = static function () {
		return false;
	};
	$rejected                      = false;
	try {
		$target_settings->save( array( 2 ) );
	} catch ( \RuntimeException $error ) {
		$rejected = true;
	}
	\check_same( true, $rejected, 'Failed write is reported' );
} finally {
	$GLOBALS['test_options'] = $target_options_before;
	unset( $GLOBALS['test_existing_categories'], $GLOBALS['test_target_admin'], $GLOBALS['test_option_writer'] );
}
