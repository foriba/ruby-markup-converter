<?php
/**
 * プラグインの起動処理。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter;

use Foriba\RubyMarkupConverter\Integration\Blocks;
use Foriba\RubyMarkupConverter\Integration\Frontend_Assets;
use Foriba\RubyMarkupConverter\Integration\Post_Content_Filter;
use Foriba\RubyMarkupConverter\Integration\Shortcode;
use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 関数ファイルの読み込みと WordPress フックの登録を取りまとめる。
 *
 * メインファイルでプラグイン情報とオートローダーを準備した後に起動する。
 * 変換処理、設定の正規化、プラグイン情報の定義は担当しない。
 */
final class Bootstrap {
	/**
	 * プラグインの配置情報とバージョン。
	 *
	 * @var Plugin_Info
	 */
	private Plugin_Info $plugin_info;

	/**
	 * 起動に必要なプラグイン情報を受け取る。
	 *
	 * @param Plugin_Info $plugin_info プラグイン情報.
	 */
	public function __construct( Plugin_Info $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	/**
	 * このインスタンスで起動済みかどうか。
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * 必要な機能を読み込み、従来と同じタイミングで登録する。
	 *
	 * 同じインスタンスでの再呼び出しは何もしない。
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		require_once $this->plugin_info->get_directory_path() . 'includes/settings/definitions.php';

		// 全文変換モードに応じて本文フィルターを有効にする起動処理を登録する.
		( new Post_Content_Filter(
			Markup_Conversion_Service::create_default(),
			new Apply_Mode_Resolver()
		) )->register_hooks();

		// 独自ブロックの登録と、ブロック内の記法変換をフックに接続する.
		( new Blocks(
			Markup_Conversion_Service::create_default(),
			new Apply_Mode_Resolver(),
			$this->plugin_info->get_directory_path()
		) )->register_hooks();

		// [rubymaco] 内の記法を変換するショートコードを登録する.
		( new Shortcode(
			Markup_Conversion_Service::create_default()
		) )->register_hooks();

		// 公開画面でルビ・傍点用 CSS を読み込むフックを登録する.
		( new Frontend_Assets(
			$this->plugin_info->get_directory_url(),
			$this->plugin_info->get_version()
		) )->register_hooks();

		if ( is_admin() ) {
			$this->boot_admin();
		}

		$this->booted = true;
	}

	/**
	 * 管理画面用の関数を読み込み、フックを登録する。
	 */
	private function boot_admin(): void {
		require_once $this->plugin_info->get_directory_path() . 'admin/settings-controller.php';
		require_once $this->plugin_info->get_directory_path() . 'admin/settings-components.php';
		require_once $this->plugin_info->get_directory_path() . 'admin/settings-view.php';
		require_once $this->plugin_info->get_directory_path() . 'admin/settings-page.php';

		add_action( 'admin_menu', 'rubymaco_add_settings_page' );
		add_action( 'admin_init', 'rubymaco_register_settings' );
		add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_assets' ) );
	}

	/**
	 * 関数ベースの管理画面へプラグイン情報を渡す。
	 *
	 * @param string $hook_suffix 現在の管理画面フック名.
	 */
	public function enqueue_admin_assets( string $hook_suffix ): void {
		rubymaco_enqueue_admin_assets( $hook_suffix, $this->plugin_info );
	}
}
