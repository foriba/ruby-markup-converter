<?php
/**
 * 全文変換モードと WordPress の本文フィルターを接続する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Markup\Value\Apply_Mode;
use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 起動時の init フックで適用モードに応じた本文変換を登録する。
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
	private Markup_Conversion_Service $service;

	/**
	 * 適用モードの取得元。
	 *
	 * @var Apply_Mode_Resolver
	 */
	private Apply_Mode_Resolver $apply_mode_resolver;

	/**
	 * 必要な依存オブジェクトを受け取る。設定の取得やフック登録は行わない。
	 *
	 * @param Markup_Conversion_Service $service             本文の変換サービス.
	 * @param Apply_Mode_Resolver       $apply_mode_resolver 適用モードの取得元.
	 */
	public function __construct( Markup_Conversion_Service $service, Apply_Mode_Resolver $apply_mode_resolver ) {
		$this->service             = $service;
		$this->apply_mode_resolver = $apply_mode_resolver;
	}

	/**
	 * 適用モードを判定する起動時のフックを登録する。
	 *
	 * 同じインスタンスの再登録は WordPress が同一コールバックとして扱う。
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'maybe_register_content_filter' ) );
	}

	/**
	 * 全文変換モードの場合のみ本文フィルターを登録する。
	 *
	 * 設定はオブジェクト生成時ではなく init のコールバック実行時に取得する。
	 * 従来どおり、本文フィルターの優先度は 9 とする。
	 */
	public function maybe_register_content_filter(): void {
		if ( ! $this->apply_mode_resolver->get()->equals( Apply_Mode::all() ) ) {
			return;
		}

		add_filter( 'the_content', array( $this, 'filter_content' ), 9 );
	}

	/**
	 * 投稿本文の変換をサービスへ委譲する。
	 *
	 * @param string $content HTML を含む投稿本文.
	 * @return string 変換後の投稿本文.
	 */
	public function filter_content( string $content ): string {
		return $this->service->convert( $content );
	}
}
