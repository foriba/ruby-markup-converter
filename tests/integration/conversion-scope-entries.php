<?php
/**
 * 投稿文脈と三つの変換入口の接続を検証する。
 *
 * @package RubyMarkupConverter
 */

// phpcs:disable WordPress.WP.GlobalVariablesOverride -- Simulate WordPress post contexts; restored in finally.
use Foriba\RubyMarkupConverter\Integration\Blocks;
use Foriba\RubyMarkupConverter\Integration\Post_Content_Filter;
use Foriba\RubyMarkupConverter\Integration\Post_Context;
use Foriba\RubyMarkupConverter\Integration\Shortcode;
use Foriba\RubyMarkupConverter\Resolver\Conversion_Scope_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;
use Foriba\RubyMarkupConverter\Settings\Category_Conversion_Scope_Settings;

require_once ABSPATH . WPINC . '/class-wp-post.php';
require_once ABSPATH . WPINC . '/class-wp-block.php';
$entry_resolver                     = Conversion_Scope_Resolver::create_default();
$entry_converter                    = Markup_Conversion_Service::create_default();
$entry_filter                       = new Post_Content_Filter( $entry_converter, $entry_resolver );
$entry_blocks                       = new Blocks( $entry_converter, $entry_resolver, dirname( __DIR__, 2 ) );
$entry_shortcode                    = new Shortcode( $entry_converter, $entry_resolver );
$entry_options_before               = $GLOBALS['test_options'];
$entry_post_before                  = $GLOBALS['post'] ?? null;
$entry_shortcode_before             = $GLOBALS['shortcode_tags']['rubymaco'];
$entry_source                       = '漢字《かんじ》';
$entry_html                         = '<ruby class="rubymaco-ruby" data-rt="かんじ">漢字<rp>（</rp><rt>かんじ</rt><rp>）</rp></ruby>';
$GLOBALS['test_override_reads']     = array();
$GLOBALS['test_category_reads']     = array();
$GLOBALS['test_term_meta']          = array();
$GLOBALS['test_category_ancestors'] = array( 2 => array( 1 ) );
$GLOBALS['test_category_terms']     = array( 10 => array( (object) array( 'term_id' => 2 ) ) );
$GLOBALS['test_override_meta']      = array();
// コンストラクターの登録処理とは独立して、ブロックの公開文脈を検証する.
$context_block          = ( new ReflectionClass( WP_Block::class ) )->newInstanceWithoutConstructor();
$context_block->context = array( 'postId' => 10 );
try {
	$GLOBALS['post'] = new WP_Post(
		(object) array(
			'ID'        => 10,
			'post_type' => 'post',
		)
	);
	$entry_shortcode->register_hooks();
	foreach ( array(
		array( 'none', 'all', true, false, false ),
		array( 'none', 'all', false, false, false ),
		array( 'none', 'shortcode', true, false, false ),
		array( 'none', 'shortcode', false, false, false ),
		array( 'full_content', 'all', true, true, true ),
		array( 'full_content', 'all', false, true, true ),
		array( 'full_content', 'shortcode', true, true, true ),
		array( 'full_content', 'shortcode', false, true, true ),
		array( 'inherit', 'all', true, true, true ),
		array( 'inherit', 'all', false, true, true ),
		array( 'inherit', 'shortcode', true, true, true ),
		array( 'inherit', 'shortcode', false, false, true ),
	) as $entry_case ) {
		list( $override, $entry_mode, $category, $full, $area ) = $entry_case;
		$GLOBALS['test_override_meta'][10]                      = $override;
		$GLOBALS['test_options']                                = array( Option_Keys::CONVERSION_MODE => $entry_mode );
		$GLOBALS['test_term_meta']                              = $category ? array( 1 => Category_Conversion_Scope_Settings::FULL_CONTENT ) : array();
		check_same( $full ? $entry_html : $entry_source, $entry_filter->filter_content( $entry_source ), 'Full content priority' );
		check_same( $area ? $entry_html : $entry_source, $entry_blocks->render_content_block( $entry_source, array(), $context_block ), 'Block priority' );
		check_same( $area ? $entry_html : $entry_source, do_shortcode( '[rubymaco]' . $entry_source . '[/rubymaco]' ), 'Shortcode priority and delimiters removed' );
	}
	$GLOBALS['test_options']       = array( Option_Keys::CONVERSION_MODE => 'all' );
	$GLOBALS['test_override_meta'] = array(
		10 => 'full_content',
		20 => 'none',
	);
	$context_block->context        = array( 'postId' => 20 );
	check_same( $entry_source, $entry_blocks->render_content_block( $entry_source, array(), $context_block ), 'Block context overrides outer global post' );
	$GLOBALS['post']        = new WP_Post(
		(object) array(
			'ID'        => 20,
			'post_type' => 'page',
		)
	);
	$context_block->context = array( 'postId' => 10 );
	check_same( $entry_html, $entry_blocks->render_content_block( $entry_source, array(), $context_block ), 'Enabled inner block ignores disabled outer post' );
	check_same( $entry_source, $entry_filter->filter_content( $entry_source ), 'Page disable overrides global all' );
	check_same( $entry_source, do_shortcode( '[rubymaco]' . $entry_source . '[/rubymaco]' ), 'Page disable suppresses shortcode' );
	$entry_mixed_source = $entry_source . '《《強調》》';
	check_same( $entry_mixed_source, $entry_filter->filter_content( $entry_mixed_source ), 'Disabled post preserves ruby and bouten' );
	check_same( $entry_mixed_source, $entry_blocks->render_content_block( $entry_mixed_source, array() ), 'Disabled block preserves ruby and bouten' );
	check_same( $entry_mixed_source, do_shortcode( '[rubymaco]' . $entry_mixed_source . '[/rubymaco]' ), 'Disabled shortcode preserves ruby and bouten' );
	add_shortcode(
		'rubymaco_test_inner',
		static function () use ( $entry_source ) {
			return $entry_source;
		}
	);
	check_same( $entry_source, do_shortcode( '[rubymaco][rubymaco_test_inner][/rubymaco]' ), 'Disabled shortcode still expands nested shortcodes' );
	remove_shortcode( 'rubymaco_test_inner' );
	$GLOBALS['test_override_meta'][20] = 'full_content';
	check_same( $entry_html, $entry_filter->filter_content( $entry_source ), 'Page enable is reflected immediately' );
	$GLOBALS['post'] = null;
	check_same( 0, Post_Context::current_id(), 'No post context means unknown ID' );
	check_same( $entry_html, $entry_filter->filter_content( $entry_source ), 'Unknown ID retains global full mode' );
	$GLOBALS['test_options'] = array();
	check_same( $entry_source, $entry_filter->filter_content( $entry_source ), 'Unknown ID defaults to explicit areas' );
	check_same( $entry_html, do_shortcode( '[rubymaco]' . $entry_source . '[/rubymaco]' ), 'Standalone shortcode remains available' );
	$context_block->context = array( 'postId' => 'invalid' );
	check_same( 0, Post_Context::block_id( $context_block ), 'Malformed explicit block context is not guessed' );
	$GLOBALS['post']         = new WP_Post( (object) array( 'ID' => 10 ) );
	$GLOBALS['test_options'] = array( Option_Keys::CONVERSION_MODE => 'all' );
	$nested                  = '<div class="wp-block-post-content">' . $entry_source . '</div>';
	check_same( $entry_html . $nested, $entry_blocks->render_content_block( $entry_source . $nested, array() ), 'Outer explicit area preserves another post boundary' );
	foreach ( array( 'wp-block-query', 'wp-block-post-template' ) as $boundary_class ) {
		$entry_query = '<div class="' . $boundary_class . '">' . $entry_source . '</div>';
		check_same( $entry_html . $entry_query, $entry_filter->filter_content( $entry_source . $entry_query ), 'Full conversion preserves foreign post templates before rendering' );
		check_same( $entry_html . $entry_query, $entry_blocks->render_content_block( $entry_source . $entry_query, array() ), 'Outer explicit area preserves query output' );
	}
	check_same( $entry_html, $entry_blocks->render_content_block( $entry_filter->filter_content( $entry_source ), array() ), 'Full content then block does not double convert' );
	check_same(
		'<!-- wp:paragraph --><p>' . $entry_html . '</p><!-- /wp:paragraph -->',
		$entry_filter->filter_content( '<!-- wp:paragraph --><p>' . $entry_source . '</p><!-- /wp:paragraph -->' ),
		'Full content preserves serialized block comments'
	);
	$entry_manifest = require dirname( __DIR__, 2 ) . '/editor/build/blocks-manifest.php';
	check_same( array( 'postId' ), $entry_manifest['content']['usesContext'], 'Built metadata requests post context' );

	// do_blocks 相当の後段で別投稿を描画しても、親の全文変換を再適用しない.
	$entry_filter->register_content_filter();
	$render_nested_post = static function ( $content ) use ( $entry_filter, $entry_source ) {
		$outer_post                        = $GLOBALS['post'];
		$GLOBALS['post']                   = new WP_Post( (object) array( 'ID' => 20 ) );
		$GLOBALS['test_override_meta'][20] = 'none';
		try {
			return $content . '<div class="wp-block-post-content">' . $entry_filter->filter_content( $entry_source ) . '</div>';
		} finally {
			$GLOBALS['post'] = $outer_post;
		}
	};
	add_filter( 'the_content', $render_nested_post, 9 );
	try {
		check_same( $entry_html . $nested, apply_filters( 'the_content', $entry_source ), 'Outer full conversion runs before foreign post rendering' );
	} finally {
		remove_filter( 'the_content', $render_nested_post, 9 );
		remove_filter( 'the_content', array( $entry_filter, 'filter_content' ), 8 );
	}
} finally {
	$GLOBALS['post']         = $entry_post_before;
	$GLOBALS['test_options'] = $entry_options_before;
	add_shortcode( 'rubymaco', $entry_shortcode_before );
	$GLOBALS['test_term_meta'] = array();
	unset( $GLOBALS['test_override_reads'], $GLOBALS['test_category_reads'], $GLOBALS['test_category_ancestors'], $GLOBALS['test_category_terms'], $GLOBALS['test_override_meta'] );
}
