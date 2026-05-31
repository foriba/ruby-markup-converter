<?php
/**
 * 管理画面の設定ページを登録し、関連ファイルとアセットを読み込む。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use foriba\rubymarkupconverter\RUBYMACO_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/class-rubymaco-settings.php';
require_once __DIR__ . '/settings-controller.php';
require_once __DIR__ . '/settings-components.php';
require_once __DIR__ . '/settings-view.php';

/**
 * WordPress フックを登録する。
 */
add_action( 'admin_menu', 'rubymaco_add_settings_page' );
add_action( 'admin_init', 'rubymaco_register_settings' );
add_action( 'admin_enqueue_scripts', 'rubymaco_enqueue_admin_assets' );

/**
 * 設定ページを管理画面に追加する。
 */
function rubymaco_add_settings_page(): void {
	add_options_page(
		__( 'Ruby Markup Converter', 'ruby-markup-converter' ),
		__( 'Ruby Markup Converter', 'ruby-markup-converter' ),
		'manage_options',
		RUBYMACO_Settings::PAGE_SLUG,
		'rubymaco_render_settings_page'
	);
}

/**
 * Assets
 */

/**
 * 設定ページ用の CSS と JavaScript を読み込む。
 *
 * @param string $hook_suffix 現在の管理画面フック名.
 */
function rubymaco_enqueue_admin_assets( string $hook_suffix ): void {
	if ( 'settings_page_' . RUBYMACO_Settings::PAGE_SLUG !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'ruby-markup-converter',
		RUBYMACO_PLUGIN_URL . 'public/css/ruby-markup-converter.css',
		array(),
		RUBYMACO_VERSION
	);

	wp_enqueue_style(
		'rubymaco-settings',
		RUBYMACO_PLUGIN_URL . 'admin/css/settings.css',
		array( 'ruby-markup-converter' ),
		(string) filemtime( RUBYMACO_PLUGIN_DIR . 'admin/css/settings.css' )
	);

	wp_enqueue_script(
		'rubymaco-settings',
		RUBYMACO_PLUGIN_URL . 'admin/js/settings.js',
		array(),
		(string) filemtime( RUBYMACO_PLUGIN_DIR . 'admin/js/settings.js' ),
		true
	);
}
