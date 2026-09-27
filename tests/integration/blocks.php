<?php
/**
 * ブロック接続クラスの回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Integration\Blocks;
use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;

/**
 * WordPress への登録依頼を記録する部分読み込み用の代替実装。
 *
 * @param string $path メタデータのディレクトリ.
 * @param string $manifest マニフェストのパス.
 */
function wp_register_block_types_from_metadata_collection( $path, $manifest = null ) {
	$GLOBALS['test_block_registration'] = array( $path, $manifest );
}

$blocks                = new Blocks( Markup_Conversion_Service::create_default(), new Apply_Mode_Resolver(), dirname( __DIR__, 2 ) );
$block_init_callback   = array( $blocks, 'register_blocks' );
$block_render_callback = array( $blocks, 'render_content_block' );
check_same( false, has_action( 'init', $block_init_callback ), 'construction does not register blocks' );
check_same( false, has_filter( 'render_block_rubymaco/content', $block_render_callback ), 'construction does not register rendering' );
$blocks->register_hooks();
$block_hooks_before = $GLOBALS['wp_filter']['render_block_rubymaco/content']->callbacks;
$blocks->register_hooks();
check_same( $block_hooks_before, $GLOBALS['wp_filter']['render_block_rubymaco/content']->callbacks, 'repeated block hook registration is idempotent' );
check_same( 10, has_action( 'init', $block_init_callback ), 'blocks init priority' );
$blocks->register_blocks();
check_same(
	array( dirname( __DIR__, 2 ) . '/editor/build', dirname( __DIR__, 2 ) . '/editor/build/blocks-manifest.php' ),
	$GLOBALS['test_block_registration'],
	'block registration paths'
);
check_same( true, is_file( $GLOBALS['test_block_registration'][1] ), 'block manifest exists' );
$GLOBALS['test_options'] = array( Option_Keys::ENABLED_MARKUP_RULES => array() );
check_same( '漢字《かんじ》', $blocks->render_content_block( '漢字《かんじ》', array() ), 'blocks respect disabled rules after construction' );
$GLOBALS['test_options'] = array();
check_same( '', $blocks->render_content_block( '', array() ), 'empty block content' );
remove_action( 'init', $block_init_callback );
remove_filter( 'render_block_rubymaco/content', $block_render_callback, 10 );
unset( $GLOBALS['test_block_registration'] );
