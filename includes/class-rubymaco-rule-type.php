<?php
/**
 * ルール種別を定義する enum 風クラス。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace foriba\rubymarkupconverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ルール種別を表す enum 風クラス。
 */
final class RUBYMACO_Rule_Type {
	/**
	 * ルビ変換ルールを表すルール種別。
	 */
	public const RUBY = 'ruby';

	/**
	 * 傍点変換ルールを表すルール種別。
	 * */
	public const BOUTEN = 'bouten';

	/**
	 * インスタンス化を防ぐ。
	 */
	private function __construct() {}

	/**
	 * 許可されているルール種別一覧を返す。
	 *
	 * @var string[]
	 */
	public static function values(): array {
		return array(
			self::RUBY,
			self::BOUTEN,
		);
	}

	/**
	 * ルール種別を正規化する。
	 *
	 * 未定義のルール種別が渡された場合は空文字を返す。
	 *
	 * @param string $rule_type ルール種別。.
	 * @return string 正規化済みのルール種別。
	 */
	public static function normalize( string $rule_type ): string {
		return in_array( $rule_type, self::values(), true )
			? $rule_type
			: '';
	}

	/**
	 * 有効なルール種別かどうかを判定する。
	 *
	 * @param string $rule_type ルール種別。.
	 * @return bool 有効なルール種別の場合は true。
	 */
	public static function is_valid( string $rule_type ): bool {
		return in_array( $rule_type, self::values(), true );
	}
}
