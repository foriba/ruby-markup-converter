<?php
/**
 * 投稿本文への自動ルビ・傍点変換を適用する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter( 'the_content', 'rubymaco_filter_the_content', 9 );

/**
 * WordPressフック
 */

/**
 * 投稿本文にルビ・傍点変換を適用する。
 *
 * 適用モードが投稿本文全体に設定されている場合のみ、
 * 本文を変換して返す。その他の場合は元の本文をそのまま返す。
 *
 * @param string $content 投稿本文.
 * @return string 変換後、または未変換の投稿本文.
 */
function rubymaco_filter_the_content( string $content ): string {
	$content = wp_kses_post( $content );

	if ( rubymaco_get_apply_mode() !== RUBYMACO_APPLY_MODE_ALL ) {
		return $content;
	}

	return wp_kses_post( rubymaco_transform_content_markup( $content ) );
}
