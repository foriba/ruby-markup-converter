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
 * 配列要素の型は PHPDoc で指定し、実行時には検証しない。
 * 公開プロパティは生成後も変更可能。
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
	public Rule_Type $type;

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
	public array $transform_rules;

	/**
	 * 表示情報と変換ルールから親ルールを生成する。
	 *
	 * ID の空文字だけを検証し、文字列や配列は補正せず保持する。
	 * 翻訳済みの表示文言を呼び出し側から渡す。設定の保存や翻訳は行わない。
	 *
	 * @param string           $id 空文字ではない親ルール ID.
	 * @param Rule_Type        $type 親ルールの種別.
	 * @param string[]         $titles 記法の表示名一覧.
	 * @param string[]         $examples 記法の入力例一覧.
	 * @param string           $description 記法の説明文.
	 * @param bool             $enabled_by_default 初期状態で有効とするか.
	 * @param Transform_Rule[] $transform_rules 適用順の変換ルール一覧.
	 * @throws \InvalidArgumentException 親ルール ID が空文字の場合.
	 */
	public function __construct(
		string $id,
		Rule_Type $type,
		array $titles,
		array $examples,
		string $description,
		bool $enabled_by_default,
		array $transform_rules
	) {
		if ( '' === $id ) {
			throw new \InvalidArgumentException( 'MarkupRule id must not be empty.' );
		}

		$this->id                 = $id;
		$this->type               = $type;
		$this->titles             = $titles;
		$this->examples           = $examples;
		$this->description        = $description;
		$this->enabled_by_default = $enabled_by_default;
		$this->transform_rules    = $transform_rules;
	}

	/**
	 * 表示情報と変換ルールを従来の配列形式で返す。
	 *
	 * 種別は文字列、子ルールは各 to_array() の結果に変換する。
	 * 表示名と入力例のキーは旧形式の title・example を維持する。
	 * HTML エスケープは行わず、出力側で行う。
	 *
	 * @return array{
	 *      id:string,
	 *      type:string,
	 *      title:string[],
	 *      example:string[],
	 *      description:string,
	 *      enabled_by_default:bool,
	 *      transform_rules:array<int, array{type:string, pattern:string}>
	 * }
	 */
	public function to_array(): array {
		return array(
			'id'                 => $this->id,
			'type'               => $this->type->get_value(),
			'title'              => $this->titles,
			'example'            => $this->examples,
			'description'        => $this->description,
			'enabled_by_default' => $this->enabled_by_default,
			'transform_rules'    => array_map(
				static fn( Transform_Rule $rule ): array => $rule->to_array(),
				$this->transform_rules
			),
		);
	}
}
