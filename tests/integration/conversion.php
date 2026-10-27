<?php
/**
 * 記法変換と HTML 保護の回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;
use Foriba\RubyMarkupConverter\Markup\Rules\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Markup\Markup_Renderer;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;
$service  = Markup_Conversion_Service::create_default();
$registry = Markup_Rule_Registry::instance();
$rules    = $registry->transform_rules_for( $registry->ids() );
$ruby     = '<ruby class="rubymaco-ruby" data-rt="かんじ">漢字<rp>（</rp><rt>かんじ</rt><rp>）</rp></ruby>';
$convert  = static function ( $input ) use ( $service, $rules ) {
	return $service->convert_with_rules( $input, $rules, Bouten_Style::dot(), Bouten_Rendering_Method::text_emphasis() );
};
foreach ( array( '漢字《かんじ》', '｜漢字《かんじ》', '|漢字《かんじ》', '漢字(かんじ)', '[[rb:漢字 > かんじ]]', '#漢字__かんじ__#', '{{ruby|漢字|かんじ}}' ) as $input ) {
	check_same( $ruby, $convert( $input ), 'ruby syntax: ' . $input );
}
$ruby_html = static function ( $base, $annotation ) {
	return '<ruby class="rubymaco-ruby" data-rt="' . $annotation . '">' . $base . '<rp>（</rp><rt>' . $annotation . '</rt><rp>）</rp></ruby>';
};
$ivs_base  = "葛\u{E0100}城";
foreach ( array(
	'extension B'           => array( '𠮟責', 'しっせき', '' ),
	'compatibility kanji'   => array( '髙﨑', 'たかさき', '' ),
	'extension A'           => array( '㐂', 'き', '' ),
	'kanji zero'            => array( '〇号', 'ぜろごう', '' ),
	'iteration mark'        => array( '佐々木', 'ささき', '' ),
	'shime'                 => array( '〆切', 'しめきり', '' ),
	'variation selector'    => array( $ivs_base, 'かつらぎ', '' ),
	'small ke is not kanji' => array( '関', 'かすみがせき', '霞ヶ' ),
) as $implicit_label => $implicit_case ) {
	list( $implicit_base, $implicit_annotation, $implicit_prefix ) = $implicit_case;
	$implicit_expected = $implicit_prefix . $ruby_html( $implicit_base, $implicit_annotation );
	check_same( $implicit_expected, $convert( $implicit_prefix . $implicit_base . '《' . $implicit_annotation . '》' ), 'implicit base text with angle brackets: ' . $implicit_label );
	check_same( $implicit_expected, $convert( $implicit_prefix . $implicit_base . '(' . $implicit_annotation . ')' ), 'implicit base text with parentheses: ' . $implicit_label );
}
check_same( 'かな《かな》', $convert( 'かな《かな》' ), 'kana is not implicit base text' );
check_same( '「' . $ruby, $convert( '「漢字《かんじ》' ), 'CJK punctuation is not implicit base text' );
check_same( '、' . $ruby, $convert( '、漢字(かんじ)' ), 'CJK comma is not implicit base text' );
check_same( $ruby_html( '霞ヶ関', 'かすみがせき' ), $convert( '｜霞ヶ関《かすみがせき》' ), 'explicit base text accepts small ke' );
$bouten = '<span class="rubymaco-bouten rubymaco-bouten--text-emphasis rubymaco-bouten--dot">強調</span>';
check_same( $bouten, $convert( '《《強調》》' ), 'bouten' );
check_same( $ruby . $bouten, $convert( '漢字《かんじ》《《強調》》' ), 'rule ordering' );
check_same( '前' . $bouten, $convert( '前《《強調》》' ), 'bouten is not ruby' );
check_same( '<a title="漢字《かんじ》" href="/《《強調》》">' . $ruby . '</a>', $convert( '<a title="漢字《かんじ》" href="/《《強調》》">漢字《かんじ》</a>' ), 'attributes' );
check_same( '<!--漢字《かんじ》-->' . $ruby, $convert( '<!--漢字《かんじ》-->漢字《かんじ》' ), 'comments' );
foreach ( array( 'pre', 'code', 'script', 'style', 'textarea', 'title', 'noscript', 'template' ) as $protected_tag ) {
	$protected = '<' . $protected_tag . '>漢字《かんじ》《《強調》》</' . $protected_tag . '>';
	check_same( $protected . $ruby, $convert( $protected . '漢字《かんじ》' ), 'protected ' . $protected_tag );
}
check_same( $ruby . $bouten, $convert( $ruby . $bouten ), 'idempotent' );
foreach ( array( 'pre', 'code', 'ruby', 'noscript', 'template' ) as $protected_tag ) {
	$protected = '<' . $protected_tag . '><span>漢字《かんじ》《《強調》》</span></' . $protected_tag . '>';
	check_same( $protected . $ruby . $bouten, $convert( $protected . '漢字《かんじ》《《強調》》' ), 'excluded ancestor and following sibling ' . $protected_tag );
}
check_same( '<div><span>' . $ruby . $bouten . '</span></div>', $convert( '<div><span>漢字《かんじ》《《強調》》</span></div>' ), 'ordinary ancestors allow conversion' );
check_same( 'rubymaco-html-token-0-end ' . $ruby, $convert( 'rubymaco-html-token-0-end 漢字《かんじ》' ), 'token collision' );
check_same( '&amp;lt; ' . $ruby, $convert( '&amp;lt; 漢字《かんじ》' ), 'literal entity in plain text' );
$renderer = new Markup_Renderer();
check_same( '&amp;lt;&lt;&amp;', $renderer->render_text( '&lt;<&' ), 'plain escaping' );
foreach ( array( 'dot', 'sesame' ) as $style ) {
	foreach ( array( 'text_emphasis', 'custom' ) as $method ) {
		$sv   = Bouten_Style::from( $style );
		$mv   = Bouten_Rendering_Method::from( $method );
		$body = 'custom' === $method ? '<span class="rubymaco-bouten__char">A</span><span class="rubymaco-bouten__char">&amp;</span>' : 'A&amp;';
		$css  = 'custom' === $method ? 'custom' : 'text-emphasis';
		check_same( '<span class="rubymaco-bouten rubymaco-bouten--' . $css . ' rubymaco-bouten--' . $style . '">' . $body . '</span>', $renderer->render_bouten( 'A&', $sv, $mv ), 'renderer HTML' );
		foreach ( array(
			'A&amp;B'           => 'A&B',
			'A&amp;amp;B'       => 'A&amp;B',
			'&lt;img src=x&gt;' => '《《<img src=x>》》',
			'𠮷'                 => '𠮷',
		) as $input => $visible ) {
			$html = $service->convert_with_rules( '《《' . $input . '》》', $rules, $sv, $mv );
			check_same( $visible, html_entity_decode( wp_strip_all_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' ), 'visible text' );
			check_same( false, strpos( $html, '<img' ), 'no injected tag' );
		}
	}
}
check_same( '漢字《かんじ》', $service->convert_with_rules( '漢字《かんじ》', array(), Bouten_Style::dot(), Bouten_Rendering_Method::custom() ), 'empty rules' );
