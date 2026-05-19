<?php
/**
 * ショートコード機能を登録し、ショートコード本文を変換する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_shortcode( 'rubymaco', 'rubymaco_shortcode' );

/**
 * WordPress フックを登録する
 */

/**
 * ショートコード内の本文を変換する。
 *
 * @param array<string, mixed> $atts    ショートコード属性.
 * @param string|null          $content ショートコード本文.
 * @return string 変換後の本文
 */
function rubymaco_shortcode( array $atts, ?string $content = null ): string {
	$content = (string) $content;

	return rubymaco_transform_content_markup( $content );
}
