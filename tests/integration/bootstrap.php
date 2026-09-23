<?php
/**
 * 起動時のフック登録の回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Integration\Blocks;
use Foriba\RubyMarkupConverter\Integration\Frontend_Assets;
use Foriba\RubyMarkupConverter\Integration\Post_Content_Filter;
use Foriba\RubyMarkupConverter\Integration\Shortcode;

use Foriba\RubyMarkupConverter\Bootstrap;
use Foriba\RubyMarkupConverter\Plugin_Info;

$registered_content_filters = array_values(
	array_filter(
		$GLOBALS['wp_filter']['init']->callbacks[10],
		static fn( array $entry ): bool => is_array( $entry['function'] ) && $entry['function'][0] instanceof Post_Content_Filter
	)
);
check_same( 1, count( $registered_content_filters ), 'Bootstrap registers one post content filter' );
$registered_init_callback    = $registered_content_filters[0]['function'];
$registered_content_callback = array( $registered_init_callback[0], 'filter_content' );

$registered_blocks = array_values(
	array_filter(
		$GLOBALS['wp_filter']['init']->callbacks[10],
		static fn( array $entry ): bool => is_array( $entry['function'] ) && $entry['function'][0] instanceof Blocks
	)
);
check_same( 1, count( $registered_blocks ), 'Bootstrap registers one blocks integration' );
$registered_block_init_callback = $registered_blocks[0]['function'];
$registered_block_callback      = array( $registered_block_init_callback[0], 'render_content_block' );

$registered_assets = array_values(
	array_filter(
		$GLOBALS['wp_filter']['wp_enqueue_scripts']->callbacks[10],
		static fn( array $entry ): bool => is_array( $entry['function'] ) && $entry['function'][0] instanceof Frontend_Assets
	)
);
check_same( 1, count( $registered_assets ), 'Bootstrap registers one frontend assets integration' );
$registered_assets_callback = $registered_assets[0]['function'];

$registered_admin_assets = array_values(
	array_filter(
		$GLOBALS['wp_filter']['admin_enqueue_scripts']->callbacks[10],
		static fn( array $entry ): bool => is_array( $entry['function'] ) && $entry['function'][0] instanceof Bootstrap
	)
);
check_same( 1, count( $registered_admin_assets ), 'Bootstrap registers one admin assets callback' );
$registered_admin_assets_callback = $registered_admin_assets[0]['function'];

foreach ( array(
	array( 'init', $registered_init_callback, 10, 1 ),
	array( 'init', $registered_block_init_callback, 10, 1 ),
	array( 'wp_enqueue_scripts', $registered_assets_callback, 10, 1 ),
	array( 'render_block_rubymaco/content', $registered_block_callback, 10, 2 ),
	array( 'admin_menu', 'rubymaco_add_settings_page', 10, 1 ),
	array( 'admin_init', 'rubymaco_register_settings', 10, 1 ),
	array( 'admin_enqueue_scripts', $registered_admin_assets_callback, 10, 1 ),
) as [$hook, $callback, $priority, $accepted_args] ) {
	check_same( $priority, has_filter( $hook, $callback ), 'hook priority: ' . $hook );
	check_same( $accepted_args, $GLOBALS['wp_filter'][ $hook ]->callbacks[ $priority ][ _wp_filter_build_unique_id( $hook, $callback, $priority ) ]['accepted_args'], 'hook argument count: ' . $hook );
}
$registered_shortcode_callback = $GLOBALS['shortcode_tags']['rubymaco'];
check_same( true, is_array( $registered_shortcode_callback ) && $registered_shortcode_callback[0] instanceof Shortcode, 'shortcode instance' );
check_same( 'render_shortcode', $registered_shortcode_callback[1], 'shortcode callback' );
check_same( false, has_filter( 'the_content', $registered_content_callback ), 'content filter remains deferred until init' );
$init_callbacks = array_keys( $GLOBALS['wp_filter']['init']->callbacks[10] );
check_same(
	true,
	array_search( _wp_filter_build_unique_id( 'init', $registered_init_callback, 10 ), $init_callbacks, true ) < array_search( _wp_filter_build_unique_id( 'init', $registered_block_init_callback, 10 ), $init_callbacks, true ),
	'init callback order'
);

$bootstrap = new Bootstrap( new Plugin_Info( dirname( __DIR__, 2 ) . '/ruby-markup-converter.php' ) );
$bootstrap->boot();
$before_boot = array();
foreach ( $GLOBALS['wp_filter'] as $hook => $hook_object ) {
	$before_boot[ $hook ] = $hook_object->callbacks;
}
$before_shortcodes = $GLOBALS['shortcode_tags'];
$bootstrap->boot();
$after_boot = array();
foreach ( $GLOBALS['wp_filter'] as $hook => $hook_object ) {
	$after_boot[ $hook ] = $hook_object->callbacks;
}
check_same( $before_boot, $after_boot, 'repeated boot leaves hooks unchanged' );
check_same( $before_shortcodes, $GLOBALS['shortcode_tags'], 'repeated boot leaves shortcodes unchanged' );
add_shortcode( 'rubymaco', $registered_shortcode_callback );
remove_action( 'admin_enqueue_scripts', array( $bootstrap, 'enqueue_admin_assets' ) );

foreach ( $GLOBALS['wp_filter']['wp_enqueue_scripts']->callbacks[10] as $entry ) {
	$callback = $entry['function'];
	if ( is_array( $callback ) && $callback[0] instanceof Frontend_Assets && $callback !== $registered_assets_callback ) {
		remove_action( 'wp_enqueue_scripts', $callback );
	}
}

// 別インスタンスの起動テストで追加したコールバックを取り除く.
foreach ( $GLOBALS['wp_filter']['init']->callbacks[10] as $entry ) {
	$callback = $entry['function'];
	if ( is_array( $callback ) && $callback[0] instanceof Post_Content_Filter && $callback !== $registered_init_callback ) {
		remove_action( 'init', $callback );
	}
	if ( is_array( $callback ) && $callback[0] instanceof Blocks && $callback !== $registered_block_init_callback ) {
		remove_action( 'init', $callback );
		remove_filter( 'render_block_rubymaco/content', array( $callback[0], 'render_content_block' ), 10 );
	}
}
