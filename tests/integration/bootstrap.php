<?php
/**
 * 起動時のフック登録の回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Bootstrap;

foreach ( array(
	array( 'init', 'rubymaco_maybe_add_content_filter', 10, 1 ),
	array( 'init', 'rubymaco_register_blocks', 10, 1 ),
	array( 'wp_enqueue_scripts', 'rubymaco_enqueue_styles', 10, 1 ),
	array( 'render_block_rubymaco/content', 'rubymaco_render_content_block', 10, 2 ),
	array( 'admin_menu', 'rubymaco_add_settings_page', 10, 1 ),
	array( 'admin_init', 'rubymaco_register_settings', 10, 1 ),
	array( 'admin_enqueue_scripts', 'rubymaco_enqueue_admin_assets', 10, 1 ),
) as [$hook, $callback, $priority, $accepted_args] ) {
	check_same( $priority, has_filter( $hook, $callback ), 'hook priority: ' . $callback );
	check_same( $accepted_args, $GLOBALS['wp_filter'][ $hook ]->callbacks[ $priority ][ $callback ]['accepted_args'], 'hook argument count: ' . $callback );
}
check_same( 'rubymaco_shortcode', $GLOBALS['shortcode_tags']['rubymaco'], 'shortcode callback' );
check_same( false, has_filter( 'the_content', 'rubymaco_filter_the_content' ), 'content filter remains deferred until init' );
$init_callbacks = array_keys( $GLOBALS['wp_filter']['init']->callbacks[10] );
check_same(
	true,
	array_search( 'rubymaco_maybe_add_content_filter', $init_callbacks, true ) < array_search( 'rubymaco_register_blocks', $init_callbacks, true ),
	'init callback order'
);

$bootstrap = new Bootstrap();
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
