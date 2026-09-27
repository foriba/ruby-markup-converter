<?php
/**
 * フロントエンド用アセットの読み込みを WordPress に接続する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * フロントエンド用 CSS を登録する。
 *
 * 生成しただけではフックを登録しない。
 */
final class Frontend_Assets {
	/**
	 * プラグインの公開 URL。
	 *
	 * @var string
	 */
	private string $plugin_url;

	/**
	 * キャッシュ更新に使うプラグインのバージョン。
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * アセットの参照元とバージョンを受け取る。
	 *
	 * @param string $plugin_url プラグインの公開 URL.
	 * @param string $version    プラグインのバージョン.
	 */
	public function __construct( string $plugin_url, string $version ) {
		$this->plugin_url = rtrim( $plugin_url, '/' );
		$this->version    = $version;
	}

	/**
	 * CSS 読み込みのフックを登録する。
	 *
	 * 同じインスタンスの再登録は WordPress が同一コールバックとして扱う。
	 */
	public function register_hooks(): void {
		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_styles' ) );
	}

	/**
	 * フロントエンド用 CSS をプラグインのバージョン付きで読み込む。
	 */
	public function enqueue_styles(): void {
		wp_enqueue_style(
			'ruby-markup-converter',
			$this->plugin_url . '/public/css/ruby-markup-converter.css',
			array(),
			$this->version
		);
	}
}
