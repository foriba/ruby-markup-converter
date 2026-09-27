<?php
/**
 * ルビ・傍点の HTML 生成。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup;

use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 未エスケープの文字列からルビ・傍点の HTML を生成する。
 *
 * HTML API のデコード済みテキスト、または通常の文字列を受け取る。
 * 文字参照のように見える入力も文字列として保持し、HTML として解釈しない。
 * 設定の取得や記法の解析は行わない。WordPress のエスケープ関数を使用する。
 */
final class Markup_Renderer {
	/**
	 * 未エスケープの通常テキストを HTML に戻す。
	 *
	 * @param string $text デコード済み、または通常の文字列.
	 * @return string エスケープ済みの HTML テキスト.
	 */
	public function render_text( string $text ): string {
		return $this->escape_text( $text );
	}

	/**
	 * ルビ用 HTML を生成する。
	 *
	 * @param string $base_text 未エスケープの親文字.
	 * @param string $ruby_text 未エスケープのルビ文字列.
	 * @return string ルビ HTML。
	 */
	public function render_ruby( string $base_text, string $ruby_text ): string {
		$html = '<ruby class="rubymaco-ruby" data-rt="' .
			esc_attr( str_replace( '&', '&amp;', $ruby_text ) ) .
			'">' . $this->escape_text( $base_text ) .
			'<rp>（</rp><rt>' . $this->escape_text( $ruby_text ) .
			'</rt><rp>）</rp></ruby>';

		return wp_kses( $html, $this->allowed_html() );
	}

	/**
	 * 傍点用 HTML を生成する。
	 *
	 * スタイル・描画方式は検証済みの値オブジェクトとして受け取る。
	 * custom 方式は Unicode コードポイント単位で分割する。書記素単位ではない。
	 *
	 * @param string                  $text                    未エスケープの対象文字列.
	 * @param Bouten_Style            $bouten_style            傍点スタイル.
	 * @param Bouten_Rendering_Method $bouten_rendering_method 傍点描画方式.
	 * @return string 傍点 HTML。
	 */
	public function render_bouten(
		string $text,
		Bouten_Style $bouten_style,
		Bouten_Rendering_Method $bouten_rendering_method
	): string {
		$is_custom = $bouten_rendering_method->equals( Bouten_Rendering_Method::custom() );
		$body      = '';

		if ( $is_custom ) {
			foreach ( $this->split_chars( $text ) as $char ) {
				$body .= '<span class="rubymaco-bouten__char">' . $this->escape_text( $char ) . '</span>';
			}
		} else {
			$body = $this->escape_text( $text );
		}

		$method = $is_custom ? 'custom' : 'text-emphasis';
		$html   = '<span class="rubymaco-bouten rubymaco-bouten--' . $method .
			' rubymaco-bouten--' . esc_attr( $bouten_style->get_value() ) . '">' . $body . '</span>';

		return wp_kses( $html, $this->allowed_html() );
	}

	/**
	 * 文字参照そのものの表示を保ってテキストをエスケープする。
	 *
	 * @param string $text 未エスケープの文字列.
	 * @return string HTML テキスト用の文字列。
	 */
	private function escape_text( string $text ): string {
		return esc_html( str_replace( '&', '&amp;', $text ) );
	}

	/**
	 * Unicode コードポイント単位で分割する。
	 *
	 * 分割に失敗した場合は元の文字列を一要素として返す。
	 *
	 * @param string $text 対象文字列.
	 * @return string[] 分割した文字列一覧。
	 */
	private function split_chars( string $text ): array {
		$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );

		return false === $chars ? array( $text ) : $chars;
	}

	/**
	 * 生成する HTML の許可リストを返す。
	 *
	 * @return array<string, array<string, bool>> タグと属性の許可リスト。
	 */
	private function allowed_html(): array {
		return array(
			'ruby' => array(
				'class'   => true,
				'data-rt' => true,
			),
			'rt'   => array(),
			'rp'   => array(),
			'span' => array( 'class' => true ),
		);
	}
}
