<?php
/**
 * ブロックエディター用アセットを読み込む。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rubymaco_enqueue_block_editor_assets(): void {
	$asset_file = RUBYMACO_PLUGIN_DIR . 'editor/build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = require $asset_file;

	wp_enqueue_script(
		'rubymaco-editor',
		RUBYMACO_PLUGIN_URL . 'editor/build/index.js',
		$asset['dependencies'],
		$asset['version'],
		true
	);
}

add_action(
	'enqueue_block_editor_assets',
	'rubymaco_enqueue_block_editor_assets'
);
