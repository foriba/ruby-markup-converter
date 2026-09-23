<?php
/**
 * 保存設定と呼び出し元の回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Integration\Post_Content_Filter;

use Foriba\RubyMarkupConverter\Settings\Option_Keys;

use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Style_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Rendering_Method_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

$registered_content_filters = array_values(
	array_filter(
		$GLOBALS['wp_filter']['init']->callbacks[10],
		static fn( array $entry ): bool => is_array( $entry['function'] ) && $entry['function'][0] instanceof Post_Content_Filter
	)
);
check_same( 1, count( $registered_content_filters ), 'Bootstrap registers one post content filter' );
$registered_init_callback    = $registered_content_filters[0]['function'];
$registered_content_callback = array( $registered_init_callback[0], 'filter_content' );

$service = Markup_Conversion_Service::create_default();
$ruby    = '<ruby class="rubymaco-ruby" data-rt="かんじ">漢字<rp>（</rp><rt>かんじ</rt><rp>）</rp></ruby>';

check_same( 'rubymaco_bouten_style', Option_Keys::BOUTEN_STYLE, 'style key' );
check_same( 'rubymaco_bouten_renderer', Option_Keys::BOUTEN_RENDERING_METHOD, 'method key' );
check_same( 'rubymaco_apply_mode', Option_Keys::APPLY_MODE, 'apply mode key' );
foreach ( array(
	array( Option_Keys::APPLY_MODE, new Apply_Mode_Resolver(), 'rubymaco_sanitize_apply_mode', array( 'shortcode', 'all' ), 'shortcode' ),
	array( Option_Keys::BOUTEN_STYLE, new Bouten_Style_Resolver(), 'rubymaco_sanitize_bouten_style', array( 'dot', 'sesame' ), 'dot' ),
	array( Option_Keys::BOUTEN_RENDERING_METHOD, new Bouten_Rendering_Method_Resolver(), 'rubymaco_sanitize_bouten_renderer', array( 'text_emphasis', 'custom' ), 'text_emphasis' ),
) as [$key, $resolver, $sanitize, $valid, $default] ) {
	$GLOBALS['test_options'] = array();
	check_same( $default, $resolver->get()->get_value(), 'missing option' );
	foreach ( array_merge( $valid, array( '', 'unknown', null, false, 1, array() ) ) as $value ) {
		$GLOBALS['test_options'] = array( $key => $value );
		$expected                = in_array( $value, $valid, true ) ? $value : $default;
		check_same( $expected, $resolver->get()->get_value(), 'resolve saved value' );
		if ( is_string( $value ) ) {
			check_same( $expected, $sanitize( $value ), 'sanitize string' ); }
		check_same( array( $key => $value ), $GLOBALS['test_options'], 'no write' );
	}
}
check_same( array(), rubymaco_sanitize_enabled_markup_rules( array( '' ) ), 'all unchecked' );
check_same( array(), rubymaco_sanitize_enabled_markup_rules( array( 'unknown' ) ), 'unknown ID' );
check_same( array( 'ruby_double_angle', 'ruby_double_angle' ), rubymaco_sanitize_enabled_markup_rules( array( 'ruby_double_angle', '', 'ruby_double_angle' ) ), 'selection order and duplicates' );
$GLOBALS['test_options'] = array();
check_same( $ruby, $service->convert( '漢字《かんじ》' ), 'default selection' );
$GLOBALS['test_options'] = array( Option_Keys::ENABLED_MARKUP_RULES => array() );
check_same( '漢字《かんじ》', $service->convert( '漢字《かんじ》' ), 'disabled conversion' );
$GLOBALS['test_options'] = array( Option_Keys::ENABLED_MARKUP_RULES => 'invalid' );
check_same( $ruby, $service->convert( '漢字《かんじ》' ), 'invalid selection fallback' );
$GLOBALS['test_options'] = array();
check_same( $ruby, rubymaco_shortcode( array(), '漢字《かんじ》' ), 'shortcode' );
check_same( $ruby, call_user_func( $registered_content_callback, '漢字《かんじ》' ), 'content filter' );
check_same( $ruby, rubymaco_render_content_block( '漢字《かんじ》', array() ), 'block' );
$GLOBALS['test_options'][ Option_Keys::APPLY_MODE ] = 'all';
check_same( '漢字《かんじ》', rubymaco_render_content_block( '漢字《かんじ》', array() ), 'all mode bypasses block conversion' );
$view = rubymaco_get_admin_settings_view_data();
check_same( array( 'ruby_double_angle', 'bouten_double_bracket' ), $view['enabled_rule_ids'], 'default IDs' );
check_same( $ruby, rubymaco_render_admin_rule_preview( '漢字《かんじ》', $view['rules'][0], 'dot', 'text_emphasis' ), 'admin preview' );
$GLOBALS['test_options'] = array();

foreach ( array( 'shortcode', 'all', 'unknown', null, false, array() ) as $apply_mode ) {
	$GLOBALS['test_options'] = array( Option_Keys::APPLY_MODE => $apply_mode );
	$expected_mode           = 'all' === $apply_mode ? 'all' : 'shortcode';
	check_same( $expected_mode, rubymaco_sanitize_apply_mode( $apply_mode ), 'sanitize apply mode input' );
	check_same( $expected_mode, rubymaco_get_admin_settings_view_data()['current_apply_mode'], 'display resolved apply mode' );
	remove_filter( 'the_content', $registered_content_callback, 9 );
	call_user_func( $registered_init_callback );
	check_same( 'all' === $apply_mode ? 9 : false, has_filter( 'the_content', $registered_content_callback ), 'apply mode controls content hook' );
	check_same( 'all' === $apply_mode ? $ruby : '漢字《かんじ》', apply_filters( 'the_content', '漢字《かんじ》' ), 'registered filter respects mode' );
	check_same( 'all' === $apply_mode ? '漢字《かんじ》' : $ruby, rubymaco_render_content_block( '漢字《かんじ》', array() ), 'apply mode controls block conversion' );
}
remove_filter( 'the_content', $registered_content_callback, 9 );
$GLOBALS['test_options'] = array();
