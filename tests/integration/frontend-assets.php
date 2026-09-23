<?php
/**
 * フロントエンド用アセットの登録内容を検証する。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Integration\Frontend_Assets;

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

$GLOBALS['test_enqueued_styles'] = array();
do_action( 'wp_enqueue_scripts' );
check_same(
	array( array( 'ruby-markup-converter', rtrim( RUBYMACO_PLUGIN_URL, '/' ) . '/public/css/ruby-markup-converter.css', array(), RUBYMACO_VERSION, 'all' ) ),
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
unset( $GLOBALS['test_enqueued_styles'] );
