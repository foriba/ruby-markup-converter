<?php
/**
 * フロントエンド用アセットの登録内容を検証する。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Integration\Frontend_Assets;
use Foriba\RubyMarkupConverter\Plugin_Info;
use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;

/**
 * WordPress への CSS 読み込み依頼を記録する部分読み込み用の代替実装。
 *
 * @param string           $handle 登録名.
 * @param string           $src    CSS の URL.
 * @param string[]         $deps   依存ハンドル.
 * @param string|bool|null $ver    バージョン.
 * @param string           $media  メディア.
 */
function wp_enqueue_style( $handle, $src = '', $deps = array(), $ver = false, $media = 'all' ) {
	$GLOBALS['test_enqueued_styles'][] = array( $handle, $src, $deps, $ver, $media );
}

/**
 * WordPress へのスクリプト読み込み依頼を記録する。
 *
 * @param string           $handle 登録名.
 * @param string           $src    URL.
 * @param string[]         $deps   依存ハンドル.
 * @param string|bool|null $ver    バージョン.
 * @param array|bool       $args   読み込み指定.
 */
function wp_enqueue_script( $handle, $src = '', $deps = array(), $ver = false, $args = array() ) {
	$GLOBALS['test_enqueued_scripts'][] = array( $handle, $src, $deps, $ver, $args );
}

$GLOBALS['test_enqueued_styles'] = array();
do_action( 'wp_enqueue_scripts' );
check_same(
	array( array( 'ruby-markup-converter', plugin_dir_url( dirname( __DIR__, 2 ) . '/ruby-markup-converter.php' ) . 'public/css/ruby-markup-converter.css', array(), get_file_data( dirname( __DIR__, 2 ) . '/ruby-markup-converter.php', array( 'version' => 'Version' ) )['version'], 'all' ) ),
	$GLOBALS['test_enqueued_styles'],
	'Bootstrap enqueues frontend stylesheet with plugin version'
);

foreach ( array( 'https://example.invalid/plugin', 'https://example.invalid/plugin/' ) as $test_plugin_url ) {
	$GLOBALS['test_enqueued_styles'] = array();
	$assets                          = new Frontend_Assets( $test_plugin_url, '9.8.7' );
	$assets_callback                 = array( $assets, 'enqueue_styles' );
	check_same( array(), $GLOBALS['test_enqueued_styles'], 'construction does not enqueue CSS' );
	check_same( false, has_action( 'wp_enqueue_scripts', $assets_callback ), 'construction does not register assets hook' );
	$assets->register_hooks();
	check_same( 10, has_action( 'wp_enqueue_scripts', $assets_callback ), 'assets hook priority' );
	$assets_hooks_before = $GLOBALS['wp_filter']['wp_enqueue_scripts']->callbacks;
	$assets->register_hooks();
	check_same( $assets_hooks_before, $GLOBALS['wp_filter']['wp_enqueue_scripts']->callbacks, 'repeated assets registration is idempotent' );
	check_same( array(), $GLOBALS['test_enqueued_styles'], 'hook registration defers enqueueing' );
	$assets->enqueue_styles();
	check_same(
		array( array( 'ruby-markup-converter', 'https://example.invalid/plugin/public/css/ruby-markup-converter.css', array(), '9.8.7', 'all' ) ),
		$GLOBALS['test_enqueued_styles'],
		'assets use injected URL and version'
	);
	remove_action( 'wp_enqueue_scripts', $assets_callback );
}
$admin_plugin_info                = new Plugin_Info( dirname( __DIR__, 2 ) . '/ruby-markup-converter.php' );
$GLOBALS['test_enqueued_styles']  = array();
$GLOBALS['test_enqueued_scripts'] = array();
do_action( 'admin_enqueue_scripts', 'dashboard' );
check_same( array(), $GLOBALS['test_enqueued_styles'], 'other admin pages do not enqueue styles' );
check_same( array(), $GLOBALS['test_enqueued_scripts'], 'other admin pages do not enqueue scripts' );
do_action( 'admin_enqueue_scripts', 'settings_page_' . Settings_Identifiers::PAGE_SLUG );
check_same(
	array(
		array( 'ruby-markup-converter', $admin_plugin_info->get_directory_url() . 'public/css/ruby-markup-converter.css', array(), $admin_plugin_info->get_version(), 'all' ),
		array( 'rubymaco-settings', $admin_plugin_info->get_directory_url() . 'admin/css/settings.css', array( 'ruby-markup-converter' ), (string) filemtime( $admin_plugin_info->get_directory_path() . 'admin/css/settings.css' ), 'all' ),
	),
	$GLOBALS['test_enqueued_styles'],
	'admin styles use injected plugin info'
);
check_same(
	array( array( 'rubymaco-settings', $admin_plugin_info->get_directory_url() . 'admin/js/settings.js', array(), (string) filemtime( $admin_plugin_info->get_directory_path() . 'admin/js/settings.js' ), true ) ),
	$GLOBALS['test_enqueued_scripts'],
	'admin scripts preserve version and footer settings'
);
unset( $GLOBALS['test_enqueued_styles'], $GLOBALS['test_enqueued_scripts'] );
