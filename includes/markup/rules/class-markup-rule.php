<?php
/**
 * 管理画面の表示・保存単位となる親ルール
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup\Rules;

use Foriba\RubyMarkupConverter\Markup\Value\Rule_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定画面で選択する記法の表示情報と変換ルール一覧を保持する。
 *
 * 保存する選択値には親ルールの ID を使う。
 * 種別は子ルールから導出する。レジストリが共有するため、すべての値を生成後に変更できない。
 */
final class Markup_Rule {
	/**
	 * 設定の保存・選択に使う親ルール ID。
	 *
	 * @var string
	 */
	private string $id;

	/**
	 * 設定画面での分類に使うルール種別。
	 *
	 * @var Rule_Type
	 */
	private Rule_Type $type;

	/**
	 * 記法の表示名一覧。
	 *
	 * @var string[]
	 */
	private array $titles;

	/**
	 * 記法の入力例一覧。
	 *
	 * @var string[]
	 */
	private array $examples;

	/**
	 * 記法の説明文。
	 *
	 * @var string
	 */
	private string $description;

	/**
	 * 設定未保存時に有効とするか。
	 *
	 * @var bool
	 */
	private bool $enabled_by_default;

	/**
	 * この記法に属する、適用順に並べた変換ルール一覧。
	 *
	 * @var Transform_Rule[]
	 */
	private array $transform_rules;

	/**
	 * 表示情報と変換ルールから親ルールを生成する。
	 *
	 * ID の空文字、文字列以外の表示名・入力例、子ルールの空配列・型違い・種別の混在を拒否する。
	 * 表示名と入力例は 0 からの連番に揃えて保持する。
	 * 翻訳済みの表示文言を呼び出し側から渡す。設定の保存や翻訳は行わない。
	 *
	 * @param string                  $id 空文字ではない親ルール ID.
	 * @param array<array-key, mixed> $titles 記法の表示名一覧。すべて文字列を要求する.
	 * @param array<array-key, mixed> $examples 記法の入力例一覧。すべて文字列を要求する.
	 * @param string                  $description 記法の説明文.
	 * @param bool                    $enabled_by_default 初期状態で有効とするか.
	 * @param array<array-key, mixed> $transform_rules 検証対象の一覧。すべて同種の Transform_Rule を要求する.
	 * @throws \InvalidArgumentException 親ルール ID、表示名、入力例、子ルール一覧が不正な場合.
	 */
	public function __construct(
		string $id,
		array $titles,
		array $examples,
		string $description,
		bool $enabled_by_default,
		array $transform_rules
	) {
		if ( '' === $id ) {
			throw new \InvalidArgumentException( 'MarkupRule id must not be empty.' );
		}

		if ( array() === $transform_rules ) {
			throw new \InvalidArgumentException( 'MarkupRule requires at least one transform rule.' );
		}

		$type  = null;
		$rules = array();
		foreach ( $transform_rules as $rule ) {
			if ( ! $rule instanceof Transform_Rule ) {
				throw new \InvalidArgumentException( 'MarkupRule requires Transform_Rule objects.' );
			}
			if ( null !== $type && ! $type->equals( $rule->get_type() ) ) {
				throw new \InvalidArgumentException( 'MarkupRule transform rules must have the same type.' );
			}
			$type    = $rule->get_type();
			$rules[] = $rule;
		}

		$this->id                 = $id;
		$this->type               = $type;
		$this->titles             = $this->to_string_list( $titles );
		$this->examples           = $this->to_string_list( $examples );
		$this->description        = $description;
		$this->enabled_by_default = $enabled_by_default;
		$this->transform_rules    = $rules;
	}

	/**
	 * 設定の保存・選択に使う親ルール ID を取得する。
	 *
	 * @return string 空文字ではない ID.
	 */
	public function get_id(): string {
		return $this->id;
	}

	/**
	 * 子ルールから導出した種別を取得する。
	 *
	 * @return Rule_Type 共通のルール種別.
	 */
	public function get_type(): Rule_Type {
		return $this->type;
	}

	/**
	 * 記法の表示名一覧を取得する。
	 *
	 * @return string[] 0 からの連番で並ぶ表示名.
	 */
	public function get_titles(): array {
		return $this->titles;
	}

	/**
	 * 記法の入力例一覧を取得する。
	 *
	 * @return string[] 0 からの連番で並ぶ入力例.
	 */
	public function get_examples(): array {
		return $this->examples;
	}

	/**
	 * 記法の説明文を取得する。
	 *
	 * @return string 説明文.
	 */
	public function get_description(): string {
		return $this->description;
	}

	/**
	 * 設定未保存時に有効とするかを取得する。
	 *
	 * @return bool 初期状態で有効な場合に true.
	 */
	public function is_enabled_by_default(): bool {
		return $this->enabled_by_default;
	}

	/**
	 * 適用順の子ルール一覧を取得する。
	 *
	 * @return Transform_Rule[] 変更不可の子ルールを含む一覧.
	 */
	public function get_transform_rules(): array {
		return $this->transform_rules;
	}

	/**
	 * 文字列だけの一覧であることを確認し、0 からの連番に揃える。
	 *
	 * @param array<array-key, mixed> $values 検証対象の一覧.
	 * @return string[] 連番の文字列一覧.
	 * @throws \InvalidArgumentException 文字列以外を含む場合.
	 */
	private function to_string_list( array $values ): array {
		$list = array();
		foreach ( $values as $value ) {
			if ( ! is_string( $value ) ) {
				throw new \InvalidArgumentException( 'MarkupRule titles and examples must contain only strings.' );
			}
			$list[] = $value;
		}
		return $list;
	}
}
