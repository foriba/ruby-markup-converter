<?php
/**
 * Ruby Markup Converter blocks.
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Markup\Apply_Mode;
use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

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

	return ( new Apply_Mode_Resolver() )->get()->equals( Apply_Mode::all() )
		? $block_content
		: Markup_Conversion_Service::create_default()->convert( $block_content );
}
