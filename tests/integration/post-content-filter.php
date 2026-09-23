<?php
/**
 * 投稿本文フィルタークラスの回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Integration\Post_Content_Filter;
use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;

$GLOBALS['test_options'] = array();
$post_content_filter     = new Post_Content_Filter( Markup_Conversion_Service::create_default(), new Apply_Mode_Resolver() );
$init_callback           = array( $post_content_filter, 'maybe_register_content_filter' );
$content_callback        = array( $post_content_filter, 'filter_content' );
check_same( false, has_filter( 'init', $init_callback ), 'constructor does not register init' );
check_same( false, has_filter( 'the_content', $content_callback ), 'constructor does not register content filter' );
$post_content_filter->register_hooks();
check_same( 10, has_filter( 'init', $init_callback ), 'init priority' );
check_same( false, has_filter( 'the_content', $content_callback ), 'content registration is deferred' );
$init_count = count( $GLOBALS['wp_filter']['init']->callbacks[10] );
$post_content_filter->register_hooks();
check_same( $init_count, count( $GLOBALS['wp_filter']['init']->callbacks[10] ), 'repeated registration does not duplicate init' );

foreach ( array( null, 'shortcode', 'all', 'unknown', false, array() ) as $apply_mode ) {
	$GLOBALS['test_options'] = null === $apply_mode ? array() : array( Option_Keys::APPLY_MODE => $apply_mode );
	remove_filter( 'the_content', $content_callback, 9 );
	call_user_func( $init_callback );
	check_same( 'all' === $apply_mode ? 9 : false, has_filter( 'the_content', $content_callback ), 'mode checked when init callback runs' );
}

$GLOBALS['test_options'] = array( Option_Keys::APPLY_MODE => 'all' );
call_user_func( $init_callback );
$content_count = count( $GLOBALS['wp_filter']['the_content']->callbacks[9] );
call_user_func( $init_callback );
check_same( $content_count, count( $GLOBALS['wp_filter']['the_content']->callbacks[9] ), 'repeated init does not duplicate conversion' );
check_same(
	'<ruby class="rubymaco-ruby" data-rt="かんじ">漢字<rp>（</rp><rt>かんじ</rt><rp>）</rp></ruby>',
	apply_filters( 'the_content', '漢字《かんじ》' ),
	'registered callback converts content'
);
$GLOBALS['test_options'][ Option_Keys::ENABLED_MARKUP_RULES ] = array();
check_same( '漢字《かんじ》', apply_filters( 'the_content', '漢字《かんじ》' ), 'service reads current settings after construction' );
remove_action( 'init', $init_callback );
remove_filter( 'the_content', $content_callback, 9 );
$GLOBALS['test_options'] = array();
