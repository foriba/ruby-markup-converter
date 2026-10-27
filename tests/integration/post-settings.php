<?php
/**
 * 投稿別設定 UI のアセット読み込み条件を検証する。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Plugin_Info;

require_once ABSPATH . WPINC . '/class-wp-post.php';

/**
 * テスト画面を返す。
 *
 * @return object|null テスト画面.
 */
function get_current_screen() {
	return $GLOBALS['test_post_screen'];
}

/**
 * テスト投稿を返す。
 *
 * @return \WP_Post|null テスト投稿.
 */
function get_post() {
	return $GLOBALS['test_editor_post'];
}

/**
 * 投稿タイプの対応状況を代替する。
 *
 * @param string $type 投稿タイプ.
 * @param string $feature 機能.
 * @return bool 対応しているか.
 */
function post_type_supports( $type, $feature ) {
	\check_same( true, in_array( $type, array( 'post', 'page' ), true ), 'supported post type' );
	\check_same( 'custom-fields', $feature, 'requires REST meta support' );
	return $GLOBALS['test_editor_meta_support'];
}

/**
 * アセット登録を記録する。
 *
 * @param string $handle ハンドル.
 * @param string $src URL.
 * @param array  $deps 依存.
 * @param string $version バージョン.
 * @param bool   $footer フッター.
 */
function wp_enqueue_script( $handle, $src, $deps, $version, $footer ) {
	$GLOBALS['test_post_asset'] = array(
		'handle'  => $handle,
		'src'     => $src,
		'deps'    => $deps,
		'version' => $version,
		'footer'  => $footer,
	);
}

/**
 * JSON 出力の代替処理。
 *
 * @param mixed $value データ.
 * @param int   $flags フラグ.
 * @return string|false JSON.
 */
function wp_json_encode( $value, $flags ) {
	return json_encode( $value, $flags ); // phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Partial WordPress bootstrap.
}

/**
 * 初期データを記録する。
 *
 * @param string $handle ハンドル.
 * @param string $script コード.
 * @param string $position 位置.
 */
function wp_add_inline_script( $handle, $script, $position ) {
	\check_same( 'rubymaco-post-settings', $handle, 'settings script handle' );
	\check_same( 'before', $position, 'config precedes script' );
	$GLOBALS['test_post_config'] = $script;
}

$post_settings                       = new Admin_Post_Settings( new Plugin_Info( dirname( __DIR__, 2 ) . '/ruby-markup-converter.php' ) );
$GLOBALS['test_post_screen']         = (object) array( 'base' => 'post' );
$GLOBALS['test_editor_meta_support'] = true;
$GLOBALS['test_admin_capabilities']  = array( 'edit_post' => true );
try {
	foreach ( array( 'post', 'page' ) as $editor_post_type ) {
		$GLOBALS['test_editor_post'] = new \WP_Post(
			(object) array(
				'ID'        => 42,
				'post_type' => $editor_post_type,
			)
		);
		$post_settings->enqueue_assets();
		\check_same( 'rubymaco-post-settings', $GLOBALS['test_post_asset']['handle'], 'editor UI enqueued' );
		foreach ( array( 'wp-editor', 'wp-data', 'wp-plugins', 'wp-components' ) as $dependency ) {
			\check_same( true, in_array( $dependency, $GLOBALS['test_post_asset']['deps'], true ), 'runtime dependency declared' );
		}
		\check_same( true, false !== strpos( $GLOBALS['test_post_config'], 'this ' . $editor_post_type ), 'post-type-specific label' );
	}
	foreach ( array( 'screen', 'permission', 'meta' ) as $blocked ) {
		$GLOBALS['test_post_asset']          = null;
		$GLOBALS['test_post_screen']         = 'screen' === $blocked ? null : (object) array( 'base' => 'post' );
		$GLOBALS['test_admin_capabilities']  = array( 'edit_post' => 'permission' !== $blocked );
		$GLOBALS['test_editor_meta_support'] = 'meta' !== $blocked;
		$post_settings->enqueue_assets();
		\check_same( null, $GLOBALS['test_post_asset'], 'unsupported or unauthorized context not enqueued' );
	}
} finally {
	unset( $GLOBALS['test_post_screen'], $GLOBALS['test_editor_post'], $GLOBALS['test_editor_meta_support'], $GLOBALS['test_admin_capabilities'], $GLOBALS['test_post_asset'], $GLOBALS['test_post_config'] );
}
