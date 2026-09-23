<?php
/**
 * フロントエンド用アセットを読み込む。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * フロントエンド用の CSS を読み込む。
 */
function rubymaco_enqueue_styles(): void {
	wp_enqueue_style(
		'ruby-markup-converter',
		RUBYMACO_PLUGIN_URL . '/public/css/ruby-markup-converter.css',
		array(),
		RUBYMACO_VERSION
	);
}
