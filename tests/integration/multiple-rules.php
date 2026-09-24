<?php
/**
 * 複数ルールの順序・重複・テキスト境界の回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Markup\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

$multiple_registry = Markup_Rule_Registry::instance();
$multiple_service  = Markup_Conversion_Service::create_default();
$multiple_ids      = array( 'ruby_double_angle', 'ruby_parenthesis', 'ruby_rb', 'ruby_double_underscore', 'ruby_mediawiki', 'bouten_double_bracket' );
$multiple_rules    = $multiple_registry->transform_rules_for( $multiple_ids );
check_same( $multiple_ids, $multiple_registry->ids(), 'parent rule priority' );
check_same( 7, count( $multiple_rules ), 'explicit and implicit ruby are separate rules' );
check_same( $multiple_rules, $multiple_registry->transform_rules_for( array_reverse( $multiple_ids ) ), 'selection order does not change registry priority' );
check_same( $multiple_rules, $multiple_registry->transform_rules_for( array_merge( $multiple_ids, $multiple_ids, array( '', 'unknown' ) ) ), 'duplicate and unknown selections do not add rules' );

$multiple_convert = static function ( $input, $selected_rules ) use ( $multiple_service ) {
	return $multiple_service->convert_with_rules( $input, $selected_rules, Bouten_Style::dot(), Bouten_Rendering_Method::text_emphasis() );
};
$multiple_ruby    = '<ruby class="rubymaco-ruby" data-rt="かんじ">漢字<rp>（</rp><rt>かんじ</rt><rp>）</rp></ruby>';
$multiple_bouten  = '<span class="rubymaco-bouten rubymaco-bouten--text-emphasis rubymaco-bouten--dot">強調</span>';

// 期待結果は変換器やレンダラーから生成せず、HTML を固定する.
$multiple_cases = array(
	array( '｜漢字《かんじ》と《《強調》》', $multiple_ruby . 'と' . $multiple_bouten, 'mixed syntax in one text node' ),
	array( '｜漢字《かんじ》 漢字(かんじ) [[rb:漢字 > かんじ]] #漢字__かんじ__# {{ruby|漢字|かんじ}} 《《強調》》', implode( ' ', array_fill( 0, 5, $multiple_ruby ) ) . ' ' . $multiple_bouten, 'all parent rules in one text node' ),
	array( '｜漢字《かんじ》漢字《かんじ》', $multiple_ruby . $multiple_ruby, 'explicit ruby wins over implicit ruby without leaving a bar' ),
	array( '《《漢字(かんじ)》》', '《《' . $multiple_ruby . '》》', 'generated ruby splits outer bouten syntax' ),
	array( '[[rb:漢字(かんじ) > よみ]]', '[[rb:' . $multiple_ruby . ' &gt; よみ]]', 'earlier parenthesis rule prevents enclosing rb rule' ),
	array( '｜漢字《漢字(かんじ)》', '<ruby class="rubymaco-ruby" data-rt="漢字(かんじ)">漢字<rp>（</rp><rt>漢字(かんじ)</rt><rp>）</rp></ruby>', 'later rules do not convert generated annotation' ),
	array( '《《漢字《かんじ》《《強調》》', '《《' . $multiple_ruby . $multiple_bouten, 'later rule rematches remaining text after earlier replacement' ),
	array( '漢字<em>《かんじ》</em>《《強調》》', '漢字<em>《かんじ》</em>' . $multiple_bouten, 'ruby does not span elements' ),
	array( '《《強<em>調</em>》》漢字《かんじ》', '《《強<em>調</em>》》' . $multiple_ruby, 'bouten does not span elements' ),
	array( '&amp;lt; ｜漢字《かんじ》 &amp; 《《強調》》', '&amp;lt; ' . $multiple_ruby . ' &amp; ' . $multiple_bouten, 'entities survive multiple passes' ),
);
foreach ( $multiple_cases as $multiple_case ) {
	$multiple_result = $multiple_convert( $multiple_case[0], $multiple_rules );
	check_same( $multiple_case[1], $multiple_result, $multiple_case[2] );
	check_same( $multiple_result, $multiple_convert( $multiple_result, $multiple_rules ), 'repeated conversion: ' . $multiple_case[2] );
}

check_same(
	'<span class="rubymaco-bouten rubymaco-bouten--text-emphasis rubymaco-bouten--dot">漢字(かんじ)</span>',
	$multiple_convert( '《《漢字(かんじ)》》', array_reverse( $multiple_rules ) ),
	'explicitly supplied rule order is respected and generated bouten is protected'
);
check_same(
	'漢字(かんじ)' . $multiple_bouten,
	$multiple_convert( '漢字(かんじ)《《強調》》', $multiple_registry->transform_rules_for( array( 'bouten_double_bracket' ) ) ),
	'unselected ruby syntax remains text'
);
