<?php
/**
 * ルビ・傍点のルール種別
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 許可されたルール種別を表す値オブジェクト。
 *
 * ファクトリーメソッドは種別ごとに同じインスタンスを返す。
 * 保持する値は作成後に変更しない。
 */
final class Rule_Type {
	/**
	 * ルビの種別値。
	 */
	public const RUBY = 'ruby';

	/**
	 * 傍点の種別値。
	 */
	public const BOUTEN = 'bouten';

	/**
	 * 種別値をキーとする共有インスタンス。
	 *
	 * @var array<string, self>
	 */
	private static $instances = array();

	/**
	 * 検証済みの種別値。
	 *
	 * @var string
	 */
	private string $value;

	/**
	 * 検証済みの種別値から生成する。
	 *
	 * @param string $value try_from() で検証した種別値.
	 */
	private function __construct( string $value ) {
		$this->value = $value;
	}

	/**
	 * ルール種別の文字列を返す。
	 *
	 * @return string ルール種別.
	 */
	public function get_value(): string {
		return $this->value;
	}

	/**
	 * ルビの共有インスタンスを返す。
	 *
	 * @return self ルビの種別.
	 */
	public static function ruby(): self {
		return self::from( self::RUBY );
	}

	/**
	 * 傍点の共有インスタンスを返す。
	 *
	 * @return self 傍点の種別.
	 */
	public static function bouten(): self {
		return self::from( self::BOUTEN );
	}

	/**
	 * 種別値から共有インスタンスを返す。不正値は例外とする。
	 *
	 * @param string $value 種別値。大文字・小文字や前後の空白は補正しない.
	 * @return self 対応する種別.
	 * @throws \InvalidArgumentException 未定義の種別値の場合.
	 */
	public static function from( string $value ): self {
		$type = self::try_from( $value );
		if ( null === $type ) {
			throw new \InvalidArgumentException(
				'Unknown rule type.'
			);
		}

		return $type;
	}

	/**
	 * 種別値から共有インスタンスを返す。不正値は null とする。
	 *
	 * @param string $value 種別値。大文字・小文字や前後の空白は補正しない.
	 * @return self|null 対応する種別、または未定義の場合に null.
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
	 * 許可された種別値をルビ、傍点の順に返す。
	 *
	 * @return string[] 種別値の一覧.
	 */
	public static function values(): array {
		return array(
			self::RUBY,
			self::BOUTEN,
		);
	}

	/**
	 * 全種別の共有インスタンスをルビ、傍点の順に返す。
	 *
	 * @return self[] 種別の一覧.
	 */
	public static function cases(): array {
		$cases = array();

		foreach ( self::values() as $value ) {
			$cases[] = self::from( $value );
		}

		return $cases;
	}

	/**
	 * インスタンスの同一性ではなく、種別値が等しいかを判定する。
	 *
	 * @param self $other 比較対象の種別.
	 * @return bool 種別値が等しい場合に true.
	 */
	public function equals( self $other ): bool {
		return $this->value === $other->value;
	}
}
