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

/**
 * 単一の正規表現による変換ルールを保持する。
 *
 * 生成時に ID とパターンの空文字を拒否する。
 * 正規表現の構文やキャプチャ構造は検証しない。
 * 現時点ではプロパティを公開しており、生成後の変更は制限しない。
 */
final class Transform_Rule {
	/**
	 * 親ルールの ID とは別に、個々の変換ルールを識別する ID。
	 *
	 * @var string
	 */
	public string $id;

	/**
	 * ルビまたは傍点の種別。
	 *
	 * @var Rule_Type
	 */
	public Rule_Type $type;

	/**
	 * 区切り文字と修飾子を含む正規表現。
	 *
	 * 変換側は、ルビでは第1キャプチャを親文字、第2キャプチャをルビ文字列、
	 * 傍点では第1キャプチャを対象文字列として扱う。
	 *
	 * @var string
	 */
	public string $pattern;

	/**
	 * ID・種別・正規表現から変換ルールを生成する。
	 *
	 * ID とパターンの前後の空白は除去せず、そのまま保持する。
	 *
	 * @param string    $id 空文字ではない変換ルール ID.
	 * @param Rule_Type $type ルール種別.
	 * @param string    $pattern 空文字ではない正規表現.
	 * @throws \InvalidArgumentException ID またはパターンが空文字の場合.
	 */
	public function __construct( string $id, Rule_Type $type, string $pattern ) {
		if ( '' === $id || '' === $pattern ) {
			throw new \InvalidArgumentException( 'TransformRule requires a non-empty id and pattern.' );
		}

		$this->id      = $id;
		$this->type    = $type;
		$this->pattern = $pattern;
	}

	/**
	 * 従来の配列形式から変換ルールを生成する。
	 *
	 * 必須キー id・type・pattern は文字列を要求する。欠落・型違い、ID・パターンの
	 * 空文字、未定義の種別は null を返す。追加のキーは無視する。
	 * 正規表現の構文やキャプチャ構造は検証しない。
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
	 * ルール種別を文字列に戻し、従来の配列形式で返す。
	 *
	 * @return array{id:string, type:string, pattern:string} 変換ルールの配列.
	 */
	public function to_array(): array {
		return array(
			'id'      => $this->id,
			'type'    => $this->type->get_value(),
			'pattern' => $this->pattern,
		);
	}
}
