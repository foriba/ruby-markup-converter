<?php
/**
 * 傍点描画方法を定義する enum 風クラス。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace foriba\rubymarkupconverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 傍点描画方法を定義する enum 風クラス。
 */
final class RUBYMACO_Bouten_Renderer {
	/**
	 * 独自HTML構造で傍点を描画する方式。
	 */
	public const CUSTOM = 'custom';

	/**
	 * CSS text-emphasis で傍点を描画する方式。
	 */
	public const TEXT_EMPHASIS = 'text_emphasis';

	/**
	 * インスタンス化を防ぐ。
	 */
	private function __construct() {}

	/**
	 * デフォルトの適用モードを返す。
	 *
	 * @return string デフォルトの適用モードID。
	 */
	public static function default(): string {
		return self::CUSTOM;
	}

	/**
	 * 傍点描画方式の定義一覧を返す。
	 *
	 * 配列キーは保存値として使用する傍点描画方式ID。
	 *
	 * @return array<string, array{label:string, description:string}>
	 */
	public static function definitions(): array {
		return array(
			self::CUSTOM        => array(
				'label'       => __( 'Custom Renderer', 'ruby-markup-converter' ), // ja-jp: '独自実装'.
				'description' => __(
					'Renders bouten marks by wrapping each character in separate HTML elements. Recommended when you want finer control over positioning and appearance via CSS.',
					'ruby-markup-converter'
				), // ja-jp: '文字ごとにHTMLを分けて傍点を描画します。CSSによる位置調整や見た目のカスタマイズがしやすい方式です。'.
			),
			self::TEXT_EMPHASIS => array(
				'label'       => __( 'CSS text-emphasis', 'ruby-markup-converter' ), // ja-jp: 'CSS text-emphasis'.
				'description' => __(
					'Renders bouten marks using the W3C-standard text-emphasis property. The appearance, including mark position and line spacing, depends on each browser’s implementation.',
					'ruby-markup-converter'
				), // ja-jp: 'W3C標準の「text-emphasis」プロパティを使用して傍点を描画します。表示位置や行間などの見た目は、各ブラウザの実装に依存します。'.
			),
		);
	}

	/**
	 * 許可されている傍点描画方式ID一覧を返す。
	 *
	 * @return string[]
	 */
	public static function values(): array {
		return array_keys( self::definitions() );
	}

	/**
	 * 傍点描画方式を正規化する。
	 *
	 * @param string $renderer 傍点描画方式.
	 * @return string
	 */
	public static function normalize( string $renderer ): string {
		return in_array( $renderer, self::values(), true )
			? $renderer
			: self::default();
	}
}
