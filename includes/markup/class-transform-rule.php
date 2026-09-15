<?php
/**
 * 実際の preg 変換で使用するルール
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

final class Transform_Rule {
	/**
	 * @var string
	 */
	public string $id;

	/**
	 * @var Rule_Type
	 */
	public Rule_Type $type;

	/**
	 * @var string
	 */
	public string $pattern;

	public function __construct( string $id, Rule_Type $type, string $pattern ) {
		if ( '' === $id || '' === $pattern ) {
			throw new \InvalidArgumentException( 'TransformRule requires a non-empty id and pattern.' );
		}

		$this->id      = $id;
		$this->type    = $type;
		$this->pattern = $pattern;
	}

	/**
	 * 配列から変換ルールを生成する。必須項目の欠落・型違い・不正値は null を返す。
	 *
	 * @param array<string, mixed> $data 変換ルールの配列.
	 * @return self|null 生成したルール、または検証失敗時に null.
	 */
	public static function try_from_array( array $data ): ?self {
		if (
			! isset( $data['id'], $data['type'], $data['pattern'] )
			|| ! is_string( $data['id'] )
			|| ! is_string( $data['type'] )
			|| ! is_string( $data['pattern'] )
			|| '' === $data['id']
			|| '' === $data['pattern']
		) {
			return null;
		}

		$type = Rule_Type::try_from( $data['type'] );

		if ( null === $type ) {
			return null;
		}

		return new self( $data['id'], $type, $data['pattern'] );
	}

	/**
	 * @return array{id: string, type:string, pattern:string}
	 */
	public function to_array(): array {
		return array(
			'id'      => $this->id,
			'type'    => $this->type->get_value(),
			'pattern' => $this->pattern,
		);
	}
}
