<?php
/**
 * 傍点スタイルを定義する enum 風クラス。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace foriba\rubymarkupconverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 傍点スタイルを定義する enum 風クラス
 */
final class RUBYMACO_Bouten_Style {
	/**
	 * 傍点を黒丸の点として表示するスタイル。
	 */
	public const DOT = 'dot';

	/**
	 * 傍点をゴマ点として表示するスタイル。
	 */
	public const SESAME = 'sesame';

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
		return self::DOT;
	}

	/**
	 * 傍点スタイルの定義一覧を返す。
	 *
	 * 配列キーは保存値として使用する傍点スタイルID。
	 *
	 * @return array<string, array{label:string}>
	 */
	public static function definitions(): array {
		return array(
			self::DOT    => array(
				'label' => __( 'dot', 'ruby-markup-converter' ), // ja-jp: '点'.
			),
			self::SESAME => array(
				'label' => __( 'sesame', 'ruby-markup-converter' ), // ja-jp: 'ゴマ点'.
			),
		);
	}

	/**
	 * 許可されている傍点スタイルID一覧を返す。
	 *
	 * @return string[]
	 */
	public static function values(): array {
		return array(
			self::DOT,
			self::SESAME,
		);
	}

	/**
	 * 傍点スタイル名を正規化する。
	 *
	 * @param string $style 傍点スタイル.
	 * @return string
	 */
	public static function normalize( string $style ): string {
		return in_array( $style, self::values(), true )
			? $style
			: self::default();
	}
}
