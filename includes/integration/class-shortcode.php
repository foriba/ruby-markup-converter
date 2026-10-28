<?php
/**
 * ショートコードと記法変換を WordPress に接続する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;
use Foriba\RubyMarkupConverter\Service\Conversion_Scope_Policy;
use Foriba\RubyMarkupConverter\Resolver\Conversion_Scope_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ショートコードの登録と本文変換を担当する。
 *
 * 変換処理はサービスへ委譲する。生成しただけでは登録しない。
 */
final class Shortcode {
	/**
	 * 記法の変換サービス。
	 *
	 * @var Markup_Conversion_Service
	 */
	private Markup_Conversion_Service $markup_conversion_service;

	/**
	 * 変換範囲の取得元。
	 *
	 * @var Conversion_Scope_Resolver
	 */
	private Conversion_Scope_Resolver $conversion_scope_resolver;

	/**
	 * 変換サービスを受け取る。設定の取得や登録は行わない。
	 *
	 * @param Markup_Conversion_Service $markup_conversion_service 記法の変換サービス.
	 * @param Conversion_Scope_Resolver $conversion_scope_resolver 変換範囲の取得元.
	 */
	public function __construct(
		Markup_Conversion_Service $markup_conversion_service,
		Conversion_Scope_Resolver $conversion_scope_resolver
	) {
		$this->markup_conversion_service = $markup_conversion_service;
		$this->conversion_scope_resolver = $conversion_scope_resolver;
	}

	/**
	 * ショートコード rubymaco を登録する。
	 *
	 * 互換性維持のため、ショートコード名（投稿本文に保存）は変更不可。
	 *
	 * 同じインスタンスで再登録しても同じコールバックで上書きされる。
	 */
	public function register_hooks(): void {
		add_shortcode(
			'rubymaco',
			array( $this, 'render_shortcode' )
		);
	}

	/**
	 * ショートコード内の本文を変換する。
	 *
	 * 属性は使用しない。本文がない場合は空文字を返す。
	 * 内側のショートコードは変換範囲にかかわらず展開し、その出力も変換対象にする。
	 *
	 * @param array<string, mixed> $atts    ショートコード属性.
	 * @param string|null          $content ショートコード本文.
	 * @return string 変換後の本文.
	 */
	public function render_shortcode(
		array $atts,
		?string $content = null
	): string {
		unset( $atts );

		$content = do_shortcode( (string) $content );
		if ( Conversion_Scope_Policy::NONE === $this->conversion_scope_resolver->get( Post_Context::current_id() ) ) {
			return $content;
		}
		return $this->markup_conversion_service->convert( $content );
	}
}
