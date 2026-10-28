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
use Foriba\RubyMarkupConverter\Integration\Post_Conversion_Meta;
use Foriba\RubyMarkupConverter\Integration\Category_Conversion_Meta;
use Foriba\RubyMarkupConverter\Resolver\Conversion_Scope_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;
use Foriba\RubyMarkupConverter\Admin\Admin_REST_Controller;
use Foriba\RubyMarkupConverter\Admin\Admin_Page_Data;
use Foriba\RubyMarkupConverter\Admin\Admin_App;
use Foriba\RubyMarkupConverter\Admin\Admin_Post_Settings;
use Foriba\RubyMarkupConverter\Admin\Admin_Category_Controller;
use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;
use Foriba\RubyMarkupConverter\Settings\Settings_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress フックの登録を取りまとめる.
 *
 * メインファイルでプラグイン情報とオートローダーを準備した後に起動する。
 * 変換処理、設定の正規化、プラグイン情報の定義は担当しない。
 */
final class Bootstrap {
	/**
	 * プラグインの配置情報とバージョン.
	 *
	 * @var Plugin_Info
	 */
	private Plugin_Info $plugin_info;

	/**
	 * このインスタンスで起動済みかどうか.
	 *
	 * @var bool
	 */
	private bool $booted = false;

	/**
	 * 起動に必要なプラグイン情報を受け取る.
	 *
	 * @param Plugin_Info $plugin_info プラグイン情報.
	 */
	public function __construct( Plugin_Info $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	/**
	 * 必要な機能を生成し、WordPress に処理を登録する.
	 *
	 * 同じインスタンスで再度呼び出された場合は、何もせずに終了する.
	 */
	public function boot(): void {
		if ( $this->booted ) {
			return;
		}

		$conversion_scope_resolver = Conversion_Scope_Resolver::create_default();

		// 投稿ごとに変換範囲を判定する本文フィルターを登録する.
		( new Post_Content_Filter(
			Markup_Conversion_Service::create_default(),
			$conversion_scope_resolver
		) )->register_hooks();

		// 独自ブロックの登録と、ブロック内の記法変換をフックに接続する.
		( new Blocks(
			Markup_Conversion_Service::create_default(),
			$conversion_scope_resolver,
			$this->plugin_info->get_directory_path()
		) )->register_hooks();

		// [rubymaco] 内の記法を変換するショートコードを登録する.
		( new Shortcode(
			Markup_Conversion_Service::create_default(),
			$conversion_scope_resolver
		) )->register_hooks();

		// 公開画面でルビ・傍点用 CSS を読み込むフックを登録する.
		( new Frontend_Assets(
			$this->plugin_info->get_directory_url(),
			$this->plugin_info->get_version()
		) )->register_hooks();

		// 投稿別設定を標準の投稿メタ REST API に登録する.
		( new Post_Conversion_Meta() )->register_hooks();

		// カテゴリ別設定をタームメタとして登録する.
		( new Category_Conversion_Meta() )->register_hooks();

		// 標準カテゴリー画面の表示・保存を登録する.
		( new Admin_Category_Controller( new Target_Category_Settings() ) )->register_hooks();

		// REST リクエストは管理画面判定の外で登録する.
		( new Admin_REST_Controller(
			Settings_Service::create_default(),
			Admin_Page_Data::create_default()
		) )->register_hooks();

		if ( is_admin() ) {
			$this->boot_admin();
		}

		$this->booted = true;
	}

	/**
	 * React 設定画面のフックを登録する.
	 */
	private function boot_admin(): void {
		( new Admin_Post_Settings( $this->plugin_info ) )->register_hooks();
		( new Admin_App( $this->plugin_info ) )->register_hooks();
	}
}
