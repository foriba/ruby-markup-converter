<?php
/**
 * ルビ / 傍点のルール種別
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Rule_Type {
	public const RUBY   = 'ruby';
	public const BOUTEN = 'bouten';

	/**
	 *  @var array<string, self>
	 */
	private static $instances = array();

	/**
	 * @var string
	 */
	private string $value;

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

	public static function ruby(): self {
		return self::from( self::RUBY );
	}

	public static function bouten(): self {
		return self::from( self::BOUTEN );
	}

	public static function from( string $value ): self {
		$type = self::try_from( $value );
		if ( null === $type ) {
			throw new \InvalidArgumentException(
				sprintf( 'Unkown rule type: %s', $value )
			);
		}

		return $type;
	}

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
	 * @return string[]
	 */
	public static function values(): array {
		return array(
			self::RUBY,
			self::BOUTEN,
		);
	}

	/**
	 * @return self[]
	 */
	public static function cases(): array {
		$cases = array();

		foreach ( self::values() as $value ) {
			$cases[] = self::from( $value );
		}

		return $cases;
	}

	public function equals( self $other ): bool {
		return $this->value === $other->value;
	}
}
