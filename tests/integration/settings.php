<?php
/**
 * 保存設定と呼び出し元の回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Resolver\Bouten_Style_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Rendering_Method_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

$service = Markup_Conversion_Service::create_default();
$ruby    = '<ruby class="rubymaco-ruby" data-rt="かんじ">漢字<rp>（</rp><rt>かんじ</rt><rp>）</rp></ruby>';

check_same( 'rubymaco_bouten_style', RUBYMACO_OPTION_BOUTEN_STYLE, 'style key' );
check_same( 'rubymaco_bouten_renderer', RUBYMACO_OPTION_BOUTEN_RENDERER, 'method key' );
foreach ( array(
	array( RUBYMACO_OPTION_BOUTEN_STYLE, new Bouten_Style_Resolver(), 'rubymaco_sanitize_bouten_style', array( 'dot', 'sesame' ), 'dot' ),
	array( RUBYMACO_OPTION_BOUTEN_RENDERER, new Bouten_Rendering_Method_Resolver(), 'rubymaco_sanitize_bouten_renderer', array( 'text_emphasis', 'custom' ), 'text_emphasis' ),
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
$GLOBALS['test_options'] = array( RUBYMACO_OPTION_ENABLED_MARKUP_RULES => array() );
check_same( '漢字《かんじ》', $service->convert( '漢字《かんじ》' ), 'disabled conversion' );
$GLOBALS['test_options'] = array( RUBYMACO_OPTION_ENABLED_MARKUP_RULES => 'invalid' );
check_same( $ruby, $service->convert( '漢字《かんじ》' ), 'invalid selection fallback' );
$GLOBALS['test_options'] = array();
check_same( $ruby, rubymaco_shortcode( array(), '漢字《かんじ》' ), 'shortcode' );
check_same( $ruby, rubymaco_filter_the_content( '漢字《かんじ》' ), 'content filter' );
check_same( $ruby, rubymaco_render_content_block( '漢字《かんじ》', array() ), 'block' );
$GLOBALS['test_options'][ RUBYMACO_OPTION_APPLY_MODE ] = 'all';
check_same( '漢字《かんじ》', rubymaco_render_content_block( '漢字《かんじ》', array() ), 'all mode bypasses block conversion' );
$view = rubymaco_get_admin_settings_view_data();
check_same( array( 'ruby_double_angle', 'bouten_double_bracket' ), $view['enabled_rule_ids'], 'default IDs' );
check_same( $ruby, rubymaco_render_admin_rule_preview( '漢字《かんじ》', $view['rules'][0], 'dot', 'text_emphasis' ), 'admin preview' );
$GLOBALS['test_options'] = array();
