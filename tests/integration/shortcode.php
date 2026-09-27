<?php
/**
 * ショートコード接続クラスの回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Integration\Shortcode;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;

$saved_shortcode_callback = $GLOBALS['shortcode_tags']['rubymaco'];
$shortcode                = new Shortcode( Markup_Conversion_Service::create_default() );
check_same( $saved_shortcode_callback, $GLOBALS['shortcode_tags']['rubymaco'], 'construction does not replace shortcode' );
$shortcode->register_hooks();
check_same( array( $shortcode, 'render_shortcode' ), $GLOBALS['shortcode_tags']['rubymaco'], 'shortcode registration' );
$shortcode->register_hooks();
check_same( array( $shortcode, 'render_shortcode' ), $GLOBALS['shortcode_tags']['rubymaco'], 'repeated shortcode registration' );
$ruby = '<ruby class="rubymaco-ruby" data-rt="かんじ">漢字<rp>（</rp><rt>かんじ</rt><rp>）</rp></ruby>';
foreach ( array( 'shortcode', 'all' ) as $shortcode_apply_mode ) {
	$GLOBALS['test_options'] = array( Option_Keys::APPLY_MODE => $shortcode_apply_mode );
	check_same( $ruby, do_shortcode( '[rubymaco]漢字《かんじ》[/rubymaco]' ), 'shortcode converts in either mode' );
	check_same( $ruby, do_shortcode( '[rubymaco unused="value"]漢字《かんじ》[/rubymaco]' ), 'attributes are ignored' );
	check_same( '前' . $ruby . '後', do_shortcode( '前[rubymaco]漢字《かんじ》[/rubymaco]後' ), 'surrounding text is preserved' );
}
check_same( '', do_shortcode( '[rubymaco /]' ), 'self closing shortcode' );
check_same( '', do_shortcode( '[rubymaco][/rubymaco]' ), 'empty shortcode' );
check_same( '', $shortcode->render_shortcode( array() ), 'missing content' );
check_same( '[rubymaco]漢字《かんじ》[/rubymaco]', do_shortcode( '[[rubymaco]漢字《かんじ》[/rubymaco]]' ), 'escaped shortcode remains literal' );
$GLOBALS['test_options'] = array( Option_Keys::ENABLED_MARKUP_RULES => array() );
check_same( '漢字《かんじ》', do_shortcode( '[rubymaco]漢字《かんじ》[/rubymaco]' ), 'disabled rules after construction' );
$GLOBALS['test_options'] = array();
check_same( $ruby, do_shortcode( '[rubymaco]漢字《かんじ》[/rubymaco]' ), 'rules restored after construction' );
add_shortcode( 'rubymaco', $saved_shortcode_callback );
