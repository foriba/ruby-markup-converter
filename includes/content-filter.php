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

add_action( 'init', 'rubymaco_maybe_add_content_filter' );

/**
 * WordPressフックを登録する。
 */

/**
 * 適用モードが投稿本文全体の場合のみ、本文変換フィルターを登録する。
 */
function rubymaco_maybe_add_content_filter(): void {
	if ( RUBYMACO_APPLY_MODE_ALL !== rubymaco_get_apply_mode() ) {
		return;
	}

	add_filter( 'the_content', 'rubymaco_filter_the_content', 9 );
}

/**
 * 投稿本文にルビ・傍点変換を適用する。
 *
 * @param string $content 投稿本文.
 * @return string 変換後の投稿本文.
 */
function rubymaco_filter_the_content( string $content ): string {
	$content = $content;

	return rubymaco_transform_content_markup( $content );
}
