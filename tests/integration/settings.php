<?php
/**
 * 保存設定と呼び出し元の回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Integration\Post_Content_Filter;

use Foriba\RubyMarkupConverter\Settings\Option_Keys;
use Foriba\RubyMarkupConverter\Settings\Settings_Service;

use Foriba\RubyMarkupConverter\Resolver\Conversion_Mode_Resolver;
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
check_same( 'rubymaco_apply_mode', Option_Keys::CONVERSION_MODE, 'apply mode key' );
foreach ( array(
	array( Option_Keys::CONVERSION_MODE, new Conversion_Mode_Resolver(), array( 'shortcode', 'all' ), 'shortcode' ),
	array( Option_Keys::BOUTEN_STYLE, new Bouten_Style_Resolver(), array( 'dot', 'sesame' ), 'dot' ),
	array( Option_Keys::BOUTEN_RENDERING_METHOD, new Bouten_Rendering_Method_Resolver(), array( 'text_emphasis', 'custom' ), 'text_emphasis' ),
) as [$key, $resolver, $valid, $default] ) {
	$GLOBALS['test_options'] = array();
	check_same( $default, $resolver->get()->get_value(), 'missing option' );
	foreach ( array_merge( $valid, array( '', 'unknown', null, false, 1, array() ) ) as $value ) {
		$GLOBALS['test_options'] = array( $key => $value );
		$expected                = in_array( $value, $valid, true ) ? $value : $default;
		check_same( $expected, $resolver->get()->get_value(), 'resolve saved value' );
		check_same( array( $key => $value ), $GLOBALS['test_options'], 'no write' );
	}
}
$GLOBALS['test_options'] = array();
check_same( $ruby, $service->convert( '漢字《かんじ》' ), 'default selection' );
$GLOBALS['test_options'] = array( Option_Keys::ENABLED_MARKUP_RULES => array() );
check_same( '漢字《かんじ》', $service->convert( '漢字《かんじ》' ), 'disabled conversion' );
$GLOBALS['test_options'] = array( Option_Keys::ENABLED_MARKUP_RULES => 'invalid' );
check_same( $ruby, $service->convert( '漢字《かんじ》' ), 'invalid selection fallback' );
$GLOBALS['test_options'] = array();
check_same( $ruby, do_shortcode( '[rubymaco]漢字《かんじ》[/rubymaco]' ), 'shortcode' );
check_same( '漢字《かんじ》', call_user_func( $registered_content_callback, '漢字《かんじ》' ), 'selected areas bypass full content' );
// phpcs:ignore WordPress.NamingConventions.ValidHookName -- WordPress の動的フック名にはブロック名のスラッシュが含まれる。
check_same( $ruby, apply_filters( 'render_block_rubymaco/content', '漢字《かんじ》', array() ), 'block' );
$GLOBALS['test_options'][ Option_Keys::CONVERSION_MODE ] = 'all';
// phpcs:ignore WordPress.NamingConventions.ValidHookName -- WordPress の動的フック名にはブロック名のスラッシュが含まれる。
check_same( $ruby, apply_filters( 'render_block_rubymaco/content', '漢字《かんじ》', array() ), 'all mode also supports standalone block rendering' );
$view = Settings_Service::create_default()->get();
check_same( array( 'ruby_double_angle', 'bouten_double_bracket' ), $view['enabled_rule_ids'], 'default IDs' );
$GLOBALS['test_options'] = array();

foreach ( array( 'shortcode', 'all', 'unknown', null, false, array() ) as $apply_mode ) {
	$GLOBALS['test_options'] = array( Option_Keys::CONVERSION_MODE => $apply_mode );
	$expected_mode           = 'all' === $apply_mode ? 'all' : 'shortcode';
	check_same( $expected_mode, Settings_Service::create_default()->get()['apply_mode'], 'display resolved apply mode' );
	remove_filter( 'the_content', $registered_content_callback, 8 );
	call_user_func( $registered_init_callback );
	check_same( 8, has_filter( 'the_content', $registered_content_callback ), 'content hook always registered' );
	check_same( 'all' === $apply_mode ? $ruby : '漢字《かんじ》', apply_filters( 'the_content', '漢字《かんじ》' ), 'registered filter respects mode' );
	// phpcs:ignore WordPress.NamingConventions.ValidHookName -- WordPress の動的フック名にはブロック名のスラッシュが含まれる。
	check_same( $ruby, apply_filters( 'render_block_rubymaco/content', '漢字《かんじ》', array() ), 'apply mode controls block conversion' );
}
remove_filter( 'the_content', $registered_content_callback, 8 );
$GLOBALS['test_options'] = array();
