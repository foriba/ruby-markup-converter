<?php
/**
 * React 管理画面の起動とアセット読み込み。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Plugin_Info;
use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 移行中はメニューに出さない別ページで起動し、既存の設定画面を維持する。
 */
final class Admin_App {
	/**
	 * プラグイン情報。
	 *
	 * @var Plugin_Info
	 */
	private Plugin_Info $plugin_info;

	/**
	 * 登録されたページのフック名。
	 *
	 * @var string
	 */
	private string $page_hook = '';

	/**
	 * 配置情報を受け取る。
	 *
	 * @param Plugin_Info $plugin_info プラグイン情報.
	 */
	public function __construct( Plugin_Info $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	/** 起動フックを登録する。 */
	public function register_hooks(): void {
		add_action( 'admin_menu', array( $this, 'register_page' ) );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
	}

	/** メニューを増やさず、管理者用ページを登録する。 */
	public function register_page(): void {
		$hook            = add_submenu_page(
			'options.php',
			__( 'Ruby Markup Converter', 'ruby-markup-converter' ),
			__( 'Ruby Markup Converter', 'ruby-markup-converter' ),
			Settings_Identifiers::CAPABILITY,
			Settings_Identifiers::PAGE_SLUG . '-react',
			array( $this, 'render_page' )
		);
		$this->page_hook = is_string( $hook ) ? $hook : '';
	}

	/**
	 * この画面だけにビルド済みアセットを読み込む。
	 *
	 * @param string $hook_suffix 現在のページ.
	 */
	public function enqueue_assets( string $hook_suffix ): void {
		if (
			'' === $this->page_hook
			|| $hook_suffix !== $this->page_hook
			|| ! current_user_can( Settings_Identifiers::CAPABILITY )
		) {
			return;
		}
		$directory = $this->plugin_info->get_directory_path() . 'admin/build/';
		if (
			! is_file( $directory . 'index.asset.php' )
			|| ! is_file( $directory . 'index.js' ) ) {
			return;
		}
		$asset = require $directory . 'index.asset.php';
		wp_enqueue_script(
			'rubymaco-admin-app',
			$this->plugin_info->get_directory_url() . 'admin/build/index.js',
			$asset['dependencies'],
			$asset['version'],
			true
		);
		// 標準コンポーネントと、画面配置・例文用のスタイルを読み込む.
		wp_enqueue_style( 'wp-components' );
		wp_enqueue_style(
			'rubymaco-admin-app',
			$this->plugin_info->get_directory_url() . 'admin/build/index.css',
			array( 'wp-components' ),
			$asset['version']
		);
		// 投稿と同じ傍点をプレビューするための描画用スタイルは維持する.
		wp_enqueue_style(
			'rubymaco-admin-preview',
			$this->plugin_info->get_directory_url() . 'public/css/ruby-markup-converter.css',
			array(),
			$this->plugin_info->get_version()
		);
		$messages = Admin_Page_Data::create_default()->get()['messages'];
		wp_add_inline_script(
			'rubymaco-admin-app',
			'window.rubymacoAdmin = ' . wp_json_encode(
				array(
					'url'        => add_query_arg( '_locale', 'user', rest_url( 'rubymaco/v1/settings' ) ),
					'previewUrl' => add_query_arg( '_locale', 'user', rest_url( 'rubymaco/v1/settings/preview' ) ),
					'nonce'      => wp_create_nonce( 'wp_rest' ),
					'messages'   => $messages,
				),
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			) . ';',
			'before'
		);
	}

	/** React の描画先を出力する。 */
	public function render_page(): void {
		if ( ! current_user_can( Settings_Identifiers::CAPABILITY ) ) {
			return;
		}
		$directory = $this->plugin_info->get_directory_path() . 'admin/build/';
		if (
			! is_file( $directory . 'index.js' )
			|| ! is_file( $directory . 'index.asset.php' )
			) {
			echo '<div class="wrap"><p role="alert">';
			echo esc_html__( 'Unable to load settings.', 'ruby-markup-converter' );
			echo '</p></div>';
			return;
		}
		echo '<div class="wrap" id="rubymaco-admin-root"><p role="status">';
		echo esc_html__( 'Loading settings…', 'ruby-markup-converter' );
		echo '</p></div>';
	}
}
