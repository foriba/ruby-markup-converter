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
