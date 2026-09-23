<?php
/**
 * 独自ブロックの登録と記法変換を WordPress に接続する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Markup\Apply_Mode;
use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ブロックの登録とレンダリング済み HTML の変換を担当する。
 *
 * 変換処理はサービスへ委譲する。生成しただけではフックを登録しない。
 */
final class Blocks {
	/**
	 * プラグインのディレクトリ。
	 *
	 * @var string
	 */
	private string $plugin_directory;

	/**
	 * 記法の変換サービス。
	 *
	 * @var Markup_Conversion_Service
	 */
	private Markup_Conversion_Service $service;

	/**
	 * 適用モードの取得元。
	 *
	 * @var Apply_Mode_Resolver
	 */
	private Apply_Mode_Resolver $apply_mode_resolver;

	/**
	 * 必要な依存を受け取る。設定の取得やフック登録は行わない。
	 *
	 * @param Markup_Conversion_Service $service             記法の変換サービス.
	 * @param Apply_Mode_Resolver       $apply_mode_resolver 適用モードの取得元.
	 * @param string                    $plugin_directory   プラグインのディレクトリ.
	 */
	public function __construct( Markup_Conversion_Service $service, Apply_Mode_Resolver $apply_mode_resolver, string $plugin_directory ) {
		$this->service             = $service;
		$this->apply_mode_resolver = $apply_mode_resolver;
		$this->plugin_directory    = rtrim( $plugin_directory, '/\\' ) . '/';
	}

	/**
	 * 登録と変換のフックを登録する。
	 *
	 * 同じインスタンスの再登録は WordPress が同一コールバックとして扱う。
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );
		add_filter( 'render_block_rubymaco/content', array( $this, 'render_content_block' ), 10, 2 );
	}

	/**
	 * ビルド済みのメタデータからブロックを登録する。
	 */
	public function register_blocks(): void {
		wp_register_block_types_from_metadata_collection(
			$this->plugin_directory . 'editor/build',
			$this->plugin_directory . 'editor/build/blocks-manifest.php'
		);
	}

	/**
	 * ブロック内の記法を変換する。全文変換モードでは本文側に任せる。
	 *
	 * 適用モードは生成時ではなく、レンダリングのたびに取得する。
	 *
	 * @param string               $block_content レンダリング済み HTML.
	 * @param array<string, mixed> $block         ブロック情報.
	 * @return string 変換後の HTML.
	 */
	public function render_content_block( string $block_content, array $block ): string {
		unset( $block );

		return $this->apply_mode_resolver->get()->equals( Apply_Mode::all() )
			? $block_content
			: $this->service->convert( $block_content );
	}
}
