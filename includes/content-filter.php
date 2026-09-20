<?php
/**
 * 投稿本文への自動ルビ・傍点変換を適用する。
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

add_action( 'init', 'rubymaco_maybe_add_content_filter' );

/**
 * WordPressフックを登録する。
 */

/**
 * 適用モードが投稿本文全体の場合のみ、本文変換フィルターを登録する。
 */
function rubymaco_maybe_add_content_filter(): void {
	if ( ! ( new Apply_Mode_Resolver() )->get()->equals( Apply_Mode::all() ) ) {
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
	return Markup_Conversion_Service::create_default()->convert( $content );
}
