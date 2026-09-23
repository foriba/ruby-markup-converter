<?php
/**
 * 変換適用モードの値オブジェクト。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup\Value;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 許可された変換適用モードを表す。
 *
 * 値ごとに同じインスタンスを返し、保持する値は作成後に変更しない。
 * 設定の取得、翻訳、不正値からデフォルト値への補正は行わない。
 */
final class Apply_Mode {
	/**
	 * ブロックまたはショートコードで指定した範囲を変換する保存値。
	 *
	 * 既存の設定との互換性のため、保存値 shortcode を維持する。
	 */
	public const SELECTED_AREAS = 'shortcode';

	/**
	 * 本文全体を変換する保存値。
	 */
	public const ALL = 'all';

	/**
	 * 値をキーとする共有インスタンス。
	 *
	 * @var array<string, self>
	 */
	private static $instances = array();

	/**
	 * 検証済みの値。
	 *
	 * @var string
	 */
	private string $value;

	/**
	 * 検証済みの値から生成する。
	 *
	 * @param string $value try_from() で検証した値.
	 */
	private function __construct( string $value ) {
		$this->value = $value;
	}

	/**
	 * 保存値として使用する文字列を返す。
	 *
	 * @return string 変換適用モードの値.
	 */
	public function get_value(): string {
		return $this->value;
	}

	/**
	 * 指定範囲を変換するモードの共有インスタンスを返す。
	 *
	 * @return self 対応する変換適用モード.
	 */
	public static function selected_areas(): self {
		return self::from( self::SELECTED_AREAS );
	}

	/**
	 * 本文全体を変換するモードの共有インスタンスを返す。
	 *
	 * @return self 対応する変換適用モード.
	 */
	public static function all(): self {
		return self::from( self::ALL );
	}

	/**
	 * 文字列から共有インスタンスを返す。不正値は例外とする。
	 *
	 * @param string $value 保存値。大文字・小文字や前後の空白は補正しない.
	 * @return self 対応する変換適用モード.
	 * @throws \InvalidArgumentException 未定義の値の場合.
	 */
	public static function from( string $value ): self {
		$instance = self::try_from( $value );
		if ( null === $instance ) {
			throw new \InvalidArgumentException( 'Unknown apply mode.' );
		}

		return $instance;
	}

	/**
	 * 文字列から共有インスタンスを返す。不正値は null とする。
	 *
	 * @param string $value 保存値。大文字・小文字や前後の空白は補正しない.
	 * @return self|null 対応する値、または未定義の場合に null.
	 */
	public static function try_from( string $value ): ?self {
		if ( ! in_array( $value, self::values(), true ) ) {
			return null;
		}

		if ( ! isset( self::$instances[ $value ] ) ) {
			self::$instances[ $value ] = new self( $value );
		}

		return self::$instances[ $value ];
	}

	/**
	 * 許可された値を既存の設定候補と同じ順序で返す。
	 *
	 * @return string[] shortcode、all の順の保存値一覧.
	 */
	public static function values(): array {
		return array(
			self::SELECTED_AREAS,
			self::ALL,
		);
	}

	/**
	 * 全値の共有インスタンスを values() と同じ順序で返す。
	 *
	 * @return self[] 変換適用モードの一覧.
	 */
	public static function cases(): array {
		$cases = array();

		foreach ( self::values() as $value ) {
			$cases[] = self::from( $value );
		}

		return $cases;
	}

	/**
	 * インスタンスの同一性ではなく、保持する値が等しいかを判定する。
	 *
	 * @param self $other 比較対象.
	 * @return bool 値が等しい場合に true.
	 */
	public function equals( self $other ): bool {
		return $this->value === $other->value;
	}
}
