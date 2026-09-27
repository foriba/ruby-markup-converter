<?php
/**
 * ショートコードと記法変換を WordPress に接続する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Service\Markup_Conversion_Service;

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
	private Markup_Conversion_Service $service;

	/**
	 * 変換サービスを受け取る。設定の取得や登録は行わない。
	 *
	 * @param Markup_Conversion_Service $service 記法の変換サービス.
	 */
	public function __construct( Markup_Conversion_Service $service ) {
		$this->service = $service;
	}

	/**
	 * ショートコード rubymaco を登録する。
	 *
	 * 同じインスタンスで再登録しても同じコールバックで上書きされる。
	 */
	public function register_hooks(): void {
		add_shortcode( 'rubymaco', array( $this, 'render_shortcode' ) );
	}

	/**
	 * ショートコード内の本文を変換する。
	 *
	 * 属性は使用しない。本文がない場合は空文字を返す。
	 *
	 * @param array<string, mixed> $atts    ショートコード属性.
	 * @param string|null          $content ショートコード本文.
	 * @return string 変換後の本文.
	 */
	public function render_shortcode( array $atts, ?string $content = null ): string {
		unset( $atts );

		return $this->service->convert( (string) $content );
	}
}
