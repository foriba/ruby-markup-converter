<?php
/**
 * React 起動用アセットのページ制限とビルド成果物を検証する。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Plugin_Info;

/**
 * 管理ページ登録の代替実装。
 *
 * @param string   $title タイトル.
 * @param string   $menu メニュー名.
 * @param string   $capability 権限.
 * @param string   $slug スラッグ.
 * @param callable $callback 描画処理.
 * @return string 登録フック.
 */
function add_options_page( $title, $menu, $capability, $slug, $callback ) {
	\check_same( 'manage_options', $capability, 'React page requires management capability' );
	\check_same( 'rubymaco-settings', $slug, 'React page slug' );
	\check_same( true, is_callable( $callback ), 'render callback callable' );
	return 'settings_page_rubymaco-settings';
}

$admin_app                        = new Admin_App( new Plugin_Info( dirname( __DIR__, 2 ) . '/ruby-markup-converter.php' ) );
$GLOBALS['test_enqueued_scripts'] = array();
$GLOBALS['test_enqueued_styles']  = array();
$admin_app->enqueue_assets( 'unrelated' );
\check_same( array(), $GLOBALS['test_enqueued_scripts'], 'unregistered page does not enqueue' );
$admin_app->register_page();
$admin_app->enqueue_assets( 'settings_page_rubymaco-settings-react' );
\check_same( array(), $GLOBALS['test_enqueued_scripts'], 'retired preview page does not enqueue React' );
$GLOBALS['test_rest_allowed'] = false;
$admin_app->enqueue_assets( 'settings_page_rubymaco-settings' );
\check_same( array(), $GLOBALS['test_enqueued_scripts'], 'unauthorized request does not enqueue React' );
ob_start();
$admin_app->render_page();
\check_same( '', ob_get_clean(), 'unauthorized request has no root element' );
unset( $GLOBALS['test_rest_allowed'] );
$admin_asset = require dirname( __DIR__, 2 ) . '/admin/build/index.asset.php';
\check_same( true, in_array( 'wp-element', $admin_asset['dependencies'], true ), 'React uses WordPress element dependency' );
\check_same( true, in_array( 'wp-components', $admin_asset['dependencies'], true ), 'UI uses WordPress standard components' );
\check_same( true, in_array( 'wp-api-fetch', $admin_asset['dependencies'], true ), 'REST requests use WordPress api-fetch' );
\check_same( true, is_string( $admin_asset['version'] ) && '' !== $admin_asset['version'], 'build supplies cache version' );
foreach ( array( 'index.js', 'index.css' ) as $admin_file ) {
	\check_same( true, is_file( dirname( __DIR__, 2 ) . '/admin/build/' . $admin_file ), 'admin build output exists' );
}
