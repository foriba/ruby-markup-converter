<?php
/**
 * 投稿ごとの変換範囲と WordPress の本文フィルターを接続する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Resolver\Conversion_Target_Resolver;
use Foriba\RubyMarkupConverter\Service\Conversion_Target_Policy;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 起動時に本文フィルターを登録し、投稿ごとに適用を判定する。
 *
 * 変換はサービスに委譲し、HTML 生成や設定値の正規化は担当しない。
 * 生成しただけではフックを登録しない。register_hooks() で明示的に登録する。
 */
final class Post_Content_Filter {
	/**
	 * 本文の変換サービス。
	 *
	 * @var Markup_Conversion_Service
	 */
	private Markup_Conversion_Service $markup_conversion_service;

	/**
	 * 投稿ごとの変換範囲の取得元。
	 *
	 * @var Conversion_Target_Resolver
	 */
	private Conversion_Target_Resolver $conversion_target_resolver;

	/**
	 * 必要な依存オブジェクトを受け取る。設定の取得やフック登録は行わない。
	 *
	 * @param Markup_Conversion_Service  $markup_conversion_service 本文の変換サービス.
	 * @param Conversion_Target_Resolver $conversion_target_resolver 変換範囲の取得元.
	 */
	public function __construct(
		Markup_Conversion_Service $markup_conversion_service,
		Conversion_Target_Resolver $conversion_target_resolver
	) {
		$this->markup_conversion_service  = $markup_conversion_service;
		$this->conversion_target_resolver = $conversion_target_resolver;
	}

	/**
	 * 本文フィルターを接続する起動時のフックを登録する。
	 *
	 * 同じインスタンスの再登録は WordPress が同一コールバックとして扱う。
	 */
	public function register_hooks(): void {
		add_action(
			'init',
			array( $this, 'register_content_filter' )
		);
	}

	/**
	 * 設定にかかわらず本文フィルターを登録する。
	 *
	 * ブロック描画の do_blocks() より前の優先度 8 で、その投稿の保存本文を変換する。
	 * 描画済みの別投稿の本文を外側の投稿設定で再変換しない。
	 */
	public function register_content_filter(): void {
		add_filter(
			'the_content',
			array( $this, 'filter_content' ),
			8
		);
	}

	/**
	 * 投稿本文の変換をサービスへ委譲する。
	 *
	 * @param string $content HTML を含む投稿本文.
	 * @return string 変換後の投稿本文.
	 */
	public function filter_content( string $content ): string {
		if ( Conversion_Target_Policy::FULL_CONTENT !== $this->conversion_target_resolver->get( Post_Context::current_id() ) ) {
			return $content;
		}
		return $this->markup_conversion_service->convert( $content );
	}
}
