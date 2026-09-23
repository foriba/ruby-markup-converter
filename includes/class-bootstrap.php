<?php
/**
 * プラグインの起動処理。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter;

use Foriba\RubyMarkupConverter\Integration\Blocks;
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
 * メインファイルで定数とオートローダーを準備した後に起動する。
 * 変換処理、設定の正規化、プラグイン情報の定義は担当しない。
 */
final class Bootstrap {
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

		require_once RUBYMACO_PLUGIN_DIR . '/includes/settings/definitions.php';
		require_once RUBYMACO_PLUGIN_DIR . '/includes/frontend-assets.php';

		$post_content_filter = new Post_Content_Filter(
			Markup_Conversion_Service::create_default(),
			new Apply_Mode_Resolver()
		);
		$post_content_filter->register_hooks();

		$blocks = new Blocks(
			Markup_Conversion_Service::create_default(),
			new Apply_Mode_Resolver()
		);
		$blocks->register_hooks();

		$shortcode = new Shortcode( Markup_Conversion_Service::create_default() );
		$shortcode->register_hooks();

		add_action( 'wp_enqueue_scripts', 'rubymaco_enqueue_styles' );

		if ( is_admin() ) {
			$this->boot_admin();
		}

		$this->booted = true;
	}

	/**
	 * 管理画面用の関数を読み込み、フックを登録する。
	 */
	private function boot_admin(): void {
		require_once RUBYMACO_PLUGIN_DIR . '/admin/settings-controller.php';
		require_once RUBYMACO_PLUGIN_DIR . '/admin/settings-components.php';
		require_once RUBYMACO_PLUGIN_DIR . '/admin/settings-view.php';
		require_once RUBYMACO_PLUGIN_DIR . '/admin/settings-page.php';

		add_action( 'admin_menu', 'rubymaco_add_settings_page' );
		add_action( 'admin_init', 'rubymaco_register_settings' );
		add_action( 'admin_enqueue_scripts', 'rubymaco_enqueue_admin_assets' );
	}
}
