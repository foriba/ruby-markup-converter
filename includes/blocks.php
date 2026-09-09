<?php
/**
 * Ruby Markup Converter blocks.
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_action( 'init', 'rubymaco_register_blocks' );

add_filter(
	'render_block_rubymaco/content',
	'rubymaco_render_content_block',
	10,
	2
);

/**
 * Ruby Markup Converter blocks を登録する。
 */
function rubymaco_register_blocks(): void {
	wp_register_block_types_from_metadata_collection(
		RUBYMACO_PLUGIN_DIR . 'editor/build',
		RUBYMACO_PLUGIN_DIR . 'editor/build/blocks-manifest.php'
	);
}

/**
 * Ruby Markup Converter ブロック内の記法を変換する。
 *
 * @param string               $block_content ブロックのレンダリング済み HTML.
 * @param array<string, mixed> $block         ブロック情報.
 * @return string 変換後の HTML.
 */
function rubymaco_render_content_block( string $block_content, array $block ): string {
	unset( $block );

	return RUBYMACO_APPLY_MODE_ALL === rubymaco_get_apply_mode()
		? $block_content
		: rubymaco_transform_content_markup( $block_content );
}
