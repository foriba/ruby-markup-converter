<?php
/**
 * 独自ブロックの登録と記法変換を WordPress に接続する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Resolver\Conversion_Scope_Resolver;
use Foriba\RubyMarkupConverter\Service\Conversion_Scope_Policy;
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
	private Markup_Conversion_Service $markup_conversion_service;

	/**
	 * 投稿ごとの変換範囲の取得元。
	 *
	 * @var Conversion_Scope_Resolver
	 */
	private Conversion_Scope_Resolver $conversion_scope_resolver;

	/**
	 * 必要な依存を受け取る。設定の取得やフック登録は行わない。
	 *
	 * @param Markup_Conversion_Service $markup_conversion_service 記法の変換サービス.
	 * @param Conversion_Scope_Resolver $conversion_scope_resolver 変換範囲の取得元.
	 * @param string                    $plugin_directory          プラグインのディレクトリ.
	 */
	public function __construct(
		Markup_Conversion_Service $markup_conversion_service,
		Conversion_Scope_Resolver $conversion_scope_resolver,
		string $plugin_directory
	) {
		$this->markup_conversion_service = $markup_conversion_service;
		$this->conversion_scope_resolver = $conversion_scope_resolver;
		$this->plugin_directory          = rtrim( $plugin_directory, '/\\' ) . '/';
	}

	/**
	 * 登録と変換のフックを登録する。
	 *
	 * 同じインスタンスの再登録は WordPress が同一コールバックとして扱う。
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_blocks' ) );

		add_filter(
			'render_block_rubymaco/content',
			array( $this, 'render_content_block' ),
			10,
			3
		);
	}

	/**
	 * ビルド済みのメタデータからブロックを登録する。
	 *
	 * 互換性維持のため、ブロック名と save() が出力する HTML（投稿本文に保存）は変更不可。
	 */
	public function register_blocks(): void {
		wp_register_block_types_from_metadata_collection(
			$this->plugin_directory . 'editor/build',
			$this->plugin_directory . 'editor/build/blocks-manifest.php'
		);
	}

	/**
	 * 投稿別の無効指定を除き、明示範囲の記法を変換する。
	 *
	 * 投稿文脈と適用範囲をレンダリングのたびに取得する。
	 * 全文変換済みの ruby 要素などは変換サービスが保護する。
	 *
	 * @param string               $block_content レンダリング済み HTML.
	 * @param array<string, mixed> $block         ブロック情報.
	 * @param \WP_Block|null       $instance      投稿文脈を持つブロック.
	 * @return string 変換後の HTML.
	 */
	public function render_content_block(
		string $block_content,
		array $block,
		?\WP_Block $instance = null
	): string {
		unset( $block );

		return Conversion_Scope_Policy::NONE === $this->conversion_scope_resolver->get( Post_Context::block_id( $instance ) )
			? $block_content
			: $this->markup_conversion_service->convert( $block_content );
	}
}
