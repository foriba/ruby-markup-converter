<?php
/**
 * 実際の preg 変換で使用するルール
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup;

use Foriba\RubyMarkupConverter\Markup\Value\Rule_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 単一の正規表現による変換ルールを保持する。
 *
 * 生成時にパターンの空文字を拒否する。
 * 正規表現の構文やキャプチャ構造は検証しない。
 * 現時点ではプロパティを公開しており、生成後の変更は制限しない。
 */
final class Transform_Rule {
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
	 * 種別・正規表現から変換ルールを生成する。
	 *
	 * パターンの前後の空白は除去せず、そのまま保持する。
	 *
	 * @param Rule_Type $type ルール種別.
	 * @param string    $pattern 空文字ではない正規表現.
	 * @throws \InvalidArgumentException パターンが空文字の場合.
	 */
	public function __construct( Rule_Type $type, string $pattern ) {
		if ( '' === $pattern ) {
			throw new \InvalidArgumentException( 'TransformRule requires a non-empty pattern.' );
		}

		$this->type    = $type;
		$this->pattern = $pattern;
	}

	/**
	 * ルール種別を文字列に戻し、配列形式で返す。
	 *
	 * @return array{type:string, pattern:string} 変換ルールの配列.
	 */
	public function to_array(): array {
		return array(
			'type'    => $this->type->get_value(),
			'pattern' => $this->pattern,
		);
	}
}
