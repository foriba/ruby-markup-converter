<?php
/**
 * 管理画面の設定ページを登録し、関連ファイルとアセットを読み込む。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;
use Foriba\RubyMarkupConverter\Plugin_Info;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Admin Page
 */

/**
 * 設定画面を管理画面に登録する。
 */
function rubymaco_add_settings_page(): void {
	add_options_page(
		__( 'Ruby Markup Converter', 'ruby-markup-converter' ),
		__( 'Ruby Markup Converter', 'ruby-markup-converter' ),
		Settings_Identifiers::CAPABILITY,
		Settings_Identifiers::PAGE_SLUG,
		'rubymaco_render_settings_page'
	);
}

/**
 * Assets
 */

/**
 * 設定ページ用の CSS と JavaScript を読み込む。
 *
 * @param string      $hook_suffix 現在の管理画面フック名.
 * @param Plugin_Info $plugin_info プラグイン情報.
 */
function rubymaco_enqueue_admin_assets( string $hook_suffix, Plugin_Info $plugin_info ): void {
	if ( 'settings_page_' . Settings_Identifiers::PAGE_SLUG !== $hook_suffix ) {
		return;
	}

	wp_enqueue_style(
		'ruby-markup-converter',
		$plugin_info->get_directory_url() . 'public/css/ruby-markup-converter.css',
		array(),
		$plugin_info->get_version()
	);

	wp_enqueue_style(
		'rubymaco-settings',
		$plugin_info->get_directory_url() . 'admin/css/settings.css',
		array( 'ruby-markup-converter' ),
		(string) filemtime( $plugin_info->get_directory_path() . 'admin/css/settings.css' )
	);

	wp_enqueue_script(
		'rubymaco-settings',
		$plugin_info->get_directory_url() . 'admin/js/settings.js',
		array(),
		(string) filemtime( $plugin_info->get_directory_path() . 'admin/js/settings.js' ),
		true
	);
}
