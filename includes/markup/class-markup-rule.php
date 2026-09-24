<?php
/**
 * 管理画面の表示・保存単位となる親ルール
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
 * 設定画面で選択する記法の表示情報と変換ルール一覧を保持する。
 *
 * 保存する選択値には親ルールの ID を使う。
 * 種別は子ルールから導出し、子ルール一覧とともに生成後の変更を禁止する。
 * 表示情報などの公開プロパティは生成後も変更可能。
 */
final class Markup_Rule {
	/**
	 * 設定の保存・選択に使う親ルール ID。
	 *
	 * @var string
	 */
	public string $id;

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
	public array $titles;

	/**
	 * 記法の入力例一覧。
	 *
	 * @var string[]
	 */
	public array $examples;

	/**
	 * 記法の説明文。
	 *
	 * @var string
	 */
	public string $description;

	/**
	 * 設定未保存時に有効とするか。
	 *
	 * @var bool
	 */
	public bool $enabled_by_default;

	/**
	 * この記法に属する、適用順に並べた変換ルール一覧。
	 *
	 * @var Transform_Rule[]
	 */
	private array $transform_rules;

	/**
	 * 表示情報と変換ルールから親ルールを生成する。
	 *
	 * ID の空文字と、子ルールの空配列・型違い・種別の混在を拒否する。
	 * 表示名と入力例の要素型は実行時には検証しない。
	 * 翻訳済みの表示文言を呼び出し側から渡す。設定の保存や翻訳は行わない。
	 *
	 * @param string                  $id 空文字ではない親ルール ID.
	 * @param string[]                $titles 記法の表示名一覧.
	 * @param string[]                $examples 記法の入力例一覧.
	 * @param string                  $description 記法の説明文.
	 * @param bool                    $enabled_by_default 初期状態で有効とするか.
	 * @param array<array-key, mixed> $transform_rules 検証対象の一覧。すべて同種の Transform_Rule を要求する.
	 * @throws \InvalidArgumentException 親ルール ID または子ルール一覧が不正な場合.
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
		$this->titles             = $titles;
		$this->examples           = $examples;
		$this->description        = $description;
		$this->enabled_by_default = $enabled_by_default;
		$this->transform_rules    = $rules;
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
	 * 適用順の子ルール一覧を取得する。
	 *
	 * @return Transform_Rule[] 変更不可の子ルールを含む一覧.
	 */
	public function get_transform_rules(): array {
		return $this->transform_rules;
	}
}
