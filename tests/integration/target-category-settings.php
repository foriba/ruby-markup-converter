<?php
/**
 * 対象カテゴリの保存・取得を検証する。タームメタと権限は代替実装。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Settings;

/**
 * 管理者権限の代替処理。
 *
 * @param string $capability 権限.
 * @return bool 管理者か.
 */
function current_user_can( $capability ) {
	\check_same( Settings_Identifiers::CATEGORY_CAPABILITY, $capability, 'Category save requires category management permission' );
	return $GLOBALS['test_target_admin'];
}

$target_settings                     = new Target_Category_Settings();
$GLOBALS['test_term_meta']           = array();
$GLOBALS['test_existing_categories'] = array( 1, 2, 3 );
$GLOBALS['test_target_admin']        = true;
$target_writes                       = 0;
$GLOBALS['test_term_meta_writer']    = static function ( $term_id, $value ) use ( &$target_writes ) {
	++$target_writes;
	if ( null === $value ) {
		unset( $GLOBALS['test_term_meta'][ $term_id ] );
	} else {
		$GLOBALS['test_term_meta'][ $term_id ] = $value;
	}
	return true;
};
try {
	\check_same( false, $target_settings->is_enabled( 1 ), 'Missing category meta is not a target' );
	$target_settings->save( 3, true );
	\check_same( array( 3 => Target_Category_Settings::ENABLED ), $GLOBALS['test_term_meta'], 'Enabled category stores only its own meta' );
	\check_same( true, $target_settings->is_enabled( 3 ), 'Read saved category' );
	\check_same( false, $target_settings->is_enabled( 1 ), 'Other categories remain unselected' );
	$target_settings->save( 3, true );
	\check_same( true, $target_settings->is_enabled( 3 ), 'Unchanged save succeeds' );
	$target_settings->save( 1, true );
	$target_settings->save( 3, false );
	\check_same( array( 1 => Target_Category_Settings::ENABLED ), $GLOBALS['test_term_meta'], 'Disabling deletes only that category meta' );
	$target_settings->save( 2, false );
	\check_same( array( 1 => Target_Category_Settings::ENABLED ), $GLOBALS['test_term_meta'], 'Disabling an unselected category succeeds without changes' );
	foreach ( array( 0, -1, 999 ) as $invalid_id ) {
		\check_same( false, $target_settings->is_enabled( $invalid_id ), 'Invalid or missing category is not a target' );
	}
	$writes_before_invalid = $target_writes;
	foreach ( array( 0, -1, 999 ) as $invalid_id ) {
		$rejected = false;
		try {
			$target_settings->save( $invalid_id, true );
		} catch ( \InvalidArgumentException $error ) {
			$rejected = true;
		}
		\check_same( true, $rejected, 'Reject invalid or deleted category before writing' );
	}
	\check_same( $writes_before_invalid, $target_writes, 'No writes on invalid input' );
	foreach ( array( true, 1, 'true', 'yes', '' ) as $unexpected_value ) {
		$GLOBALS['test_term_meta'][2] = $unexpected_value;
		\check_same( false, $target_settings->is_enabled( 2 ), 'Only the defined stored value enables a category' );
	}
	unset( $GLOBALS['test_term_meta'][2] );
	$GLOBALS['test_target_admin'] = false;
	$rejected                     = false;
	try {
		$target_settings->save( 2, true );
	} catch ( \RuntimeException $error ) {
		$rejected = true;
	}
	\check_same( true, $rejected, 'Unauthorized category save rejected' );
	\check_same( $writes_before_invalid, $target_writes, 'No unauthorized writes' );
	$GLOBALS['test_target_admin'] = true;
	foreach ( array( array( 2, true ), array( 1, false ) ) as $failed_case ) {
		$GLOBALS['test_term_meta_writer'] = static function () {
			return false;
		};
		$rejected                         = false;
		try {
			$target_settings->save( $failed_case[0], $failed_case[1] );
		} catch ( \RuntimeException $error ) {
			$rejected = true;
		}
		\check_same( true, $rejected, 'Failed write is reported' );
	}
} finally {
	$GLOBALS['test_term_meta'] = array();
	unset( $GLOBALS['test_existing_categories'], $GLOBALS['test_target_admin'], $GLOBALS['test_term_meta_writer'] );
}
