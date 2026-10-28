<?php
/**
 * 標準カテゴリー画面の保存を検証する。認証・DB は代替実装。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;

/**
 * 翻訳済み文言のエスケープを代替する。
 *
 * @param string $text 文言.
 * @param string $domain ドメイン.
 * @return string エスケープ済み文言.
 */
function esc_html__( $text, $domain ) {
	\check_same( 'ruby-markup-converter', $domain, 'translation domain' );
	return esc_html( $text );
}

/**
 * チェック属性出力の代替処理。
 *
 * @param bool $value 選択状態.
 */
function checked( $value ) {
	if ( $value ) {
		echo 'checked="checked"';
	}
}

/**
 * 保存失敗時の表示を例外で捕捉する。
 *
 * @param string $message 表示文言.
 * @throws \RuntimeException テスト用の停止.
 */
function wp_die( $message ) {
	throw new \RuntimeException( $message ); // phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Test exception captures the message; no HTML output.
}

/**
 * Nonce 検証の代替処理。
 *
 * @param string $nonce 送信値.
 * @param string $action 対象操作.
 * @return bool 一致する場合に true.
 */
function wp_verify_nonce( $nonce, $action ) {
	return $nonce === $action;
}

/**
 * Nonce 出力の代替処理。
 *
 * @param string $action 対象操作.
 * @param string $name 入力名.
 * @param bool   $referer リファラー.
 */
function wp_nonce_field( $action, $name, $referer ) {
	\check_same( false, $referer, 'no additional referer field' );
	echo '<input name="' . esc_attr( $name ) . '" value="' . esc_attr( $action ) . '">';
}

/**
 * 祖先取得の代替処理。
 *
 * @param int    $id カテゴリー ID.
 * @param string $taxonomy タクソノミー.
 * @param string $type 種別.
 * @return int[] 祖先.
 */
function get_ancestors( $id, $taxonomy, $type ) {
	\check_same( 'category', $taxonomy, 'category ancestors' );
	\check_same( 'taxonomy', $type, 'taxonomy resource type' );
	return 2 === $id ? array( 1 ) : array();
}

$category_before                     = $GLOBALS['test_options'];
$post_before                         = $_POST; // phpcs:ignore WordPress.Security.NonceVerification.Missing -- CLI fixture backup, not request processing.
$GLOBALS['test_existing_categories'] = array( 1, 2, 3 );
$GLOBALS['test_target_admin']        = true;
$GLOBALS['test_admin_capabilities']  = array(
	'manage_categories' => true,
	'manage_options'    => false,
);
$GLOBALS['test_term_meta']           = array();
$category_controller                 = new Admin_Category_Controller( new Target_Category_Settings() );
try {
	\check_same( true, isset( $GLOBALS['wp_filter']['category_add_form_fields'] ), 'category add hook registered' );
	\check_same( true, isset( $GLOBALS['wp_filter']['category_edit_form_fields'] ), 'category edit hook registered' );
	$GLOBALS['test_term_meta'] = array( 1 => Target_Category_Settings::FULL_CONTENT );
	$_POST                     = array(
		'rubymaco_category_nonce'   => 'rubymaco_category_0',
		'rubymaco_category_enabled' => '1',
	);
	$category_controller->save_created_category( 2 );
	\check_same(
		array(
			1 => Target_Category_Settings::FULL_CONTENT,
			2 => Target_Category_Settings::FULL_CONTENT,
		),
		$GLOBALS['test_term_meta'],
		'editor adds category without replacing others'
	);
	$_POST = array(
		'rubymaco_category_nonce'   => 'rubymaco_category_2',
		'rubymaco_category_enabled' => '0',
	);
	$category_controller->save_edited_category( 2 );
	\check_same( array( 1 => Target_Category_Settings::FULL_CONTENT ), $GLOBALS['test_term_meta'], 'explicit unchecked removes only edited category' );
	foreach ( array(
		array(),
		array( 'rubymaco_category_enabled' => '0' ),
		array(
			'rubymaco_category_nonce'   => 'invalid',
			'rubymaco_category_enabled' => '0',
		),
		array(
			'rubymaco_category_nonce'   => 'rubymaco_category_2',
			'rubymaco_category_enabled' => '0',
		),
		array(
			'rubymaco_category_nonce'   => 'rubymaco_category_1',
			'rubymaco_category_enabled' => array( '0' ),
		),
	) as $invalid_post ) {
		$_POST = $invalid_post;
		$category_controller->save_edited_category( 1 );
		\check_same( array( 1 => Target_Category_Settings::FULL_CONTENT ), $GLOBALS['test_term_meta'], 'missing, wrong nonce, quick edit and malformed input preserve selection' );
	}
	$_POST                              = array(
		'rubymaco_category_nonce'   => 'rubymaco_category_1',
		'rubymaco_category_enabled' => '0',
	);
	$GLOBALS['test_admin_capabilities'] = array();
	$category_controller->save_edited_category( 1 );
	\check_same( array( 1 => Target_Category_Settings::FULL_CONTENT ), $GLOBALS['test_term_meta'], 'unauthorized request preserves selection' );
	$GLOBALS['test_admin_capabilities'] = array( 'manage_categories' => true );
	ob_start();
	$category_controller->render_edit_field(
		new \WP_Term(
			(object) array(
				'term_id'  => 2,
				'taxonomy' => 'category',
			)
		)
	);
	$category_html = ob_get_clean();
	\check_same( true, false !== strpos( $category_html, 'selected parent category' ), 'inherited category explained' );
	\check_same( false, false !== strpos( $category_html, 'checked=' ), 'inherited selection not stored as explicit check' );
	$category_controller->save_edited_category( 1 );
	\check_same( array(), $GLOBALS['test_term_meta'], 'last category can be unchecked' );
	$GLOBALS['test_options'][ Option_Keys::APPLY_MODE ] = 'all';
	ob_start();
	$category_controller->render_add_field();
	$category_html = ob_get_clean();
	\check_same( true, false !== strpos( $category_html, 'Full-content mode' ), 'full-content mode explained' );
	\check_same( true, false !== strpos( $category_html, '<label for="rubymaco-category-enabled">Ruby Markup Converter</label>' ), 'add form title labels the checkbox' );
	$GLOBALS['test_admin_capabilities'] = array();
	ob_start();
	$category_controller->render_add_field();
	\check_same( '', ob_get_clean(), 'unauthorized user has no field' );
	$GLOBALS['test_admin_capabilities'] = array( 'manage_categories' => true );
	$GLOBALS['test_term_meta_writer']   = static function () {
		return false;
	};
	$_POST                              = array(
		'rubymaco_category_nonce'   => 'rubymaco_category_0',
		'rubymaco_category_enabled' => '1',
	);
	$reported                           = false;
	try {
		$category_controller->save_created_category( 3 );
	} catch ( \RuntimeException $error ) {
		$reported = false !== strpos( $error->getMessage(), 'could not be saved' );
	}
	\check_same( true, $reported, 'failed setting write reports that category itself was saved' );
} finally {
	$_POST                     = $post_before;
	$GLOBALS['test_options']   = $category_before;
	$GLOBALS['test_term_meta'] = array();
	unset( $GLOBALS['test_existing_categories'], $GLOBALS['test_target_admin'], $GLOBALS['test_admin_capabilities'], $GLOBALS['test_term_meta_writer'] );
}
