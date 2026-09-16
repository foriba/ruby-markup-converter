<?php
/**
 * ルビ・傍点のルール取得に関する互換関数。
 *
 * クラスで管理するルールを、既存の呼び出し側が利用する配列形式で返す。
 * 既存の関数名と戻り値の形式を維持し、段階的なクラス移行を支える。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Markup\Rule_Type;
use Foriba\RubyMarkupConverter\Markup\Markup_Rule;
use Foriba\RubyMarkupConverter\Markup\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Markup\Transform_Rule;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/markup/class-rule-type.php';
require_once __DIR__ . '/markup/class-transform-rule.php';
require_once __DIR__ . '/markup/class-markup-rule.php';
require_once __DIR__ . '/markup/class-markup-rule-registry.php';

/**
 * ルール定義
 *
 * 管理画面に表示するルール単位を親として定義し、
 * その中に実際の変換処理で使用するルールを transform_rules として持たせる。
 */

/**
 * 管理画面に表示する記法ルール一覧を返す。
 *
 * 各ルールは、管理画面での表示・保存単位を表す。
 * 実際の変換処理で使用する正規表現は transform_rules に定義する。
 *
 * @return array<int, array{
 *     id:string,
 *     type:string,
 *     title:string[],
 *     example:string[],
 *     description:string,
 *     enabled_by_default:bool,
 *     transform_rules:array<int, array{
 *         id:string,
 *         type:string,
 *         pattern:string
 *     }>
 * }>
 */
function rubymaco_get_markup_rules(): array {
	return array_map(
		fn( Markup_Rule $rule ): array => $rule->to_array(),
		Markup_Rule_Registry::instance()->all()
	);
}

	/**
	 * 指定した管理画面用ルールID一覧に対応する変換ルール一覧を返す。
	 *
	 * 親ルールの定義順、および transform_rules の定義順を維持して返す。
	 *
	 * @param string[] $rule_ids 管理画面用ルールID一覧.
	 * @return array<int, array{
	 *     id:string,
	 *     type:string,
	 *     pattern:string
	 * }>
	 */
function rubymaco_get_transform_rules_for_rule_ids( array $rule_ids ): array {
	return array_map(
		fn( Transform_Rule $rule ): array => $rule->to_array(),
		Markup_Rule_Registry::instance()->transform_rules_for( $rule_ids )
	);
}

	/**
	 * 変換ルール一覧を正規化する。
	 *
	 * 不完全なルールや、未定義のルール種別を持つルールは除外する。
	 *
	 * @param array<int, mixed> $transform_rules 正規化対象の変換ルール一覧.
	 * @return array<int, array{
	 *     id:string,
	 *     type:string,
	 *     pattern:string
	 * }>
	 */
function rubymaco_normalize_transform_rules( array $transform_rules ): array {
	$rules = array();

	foreach ( $transform_rules as $rule ) {
		if ( ! is_array( $rule ) ) {
			continue;
		}

		$normalized = Transform_Rule::try_from_array( $rule );
		if ( null !== $normalized ) {
			$rules[] = $normalized->to_array();
		}
	}

	return $rules;
}


	/**
	 * 管理画面に表示する記法ルール一覧を返す。
	 *
	 * 現在は rubymaco_get_markup_rules() と同じ内容を返すが、
	 * 管理画面用の表示制御を将来追加できるよう、呼び出し口を分けておく。
	 *
	 * @return array<int, array{
	 *     id:string,
	 *     type:string,
	 *     title:string[],
	 *     example:string[],
	 *     description:string,
	 *     enabled_by_default:bool,
	 *     transform_rules:array<int, array{
	 *         id:string,
	 *         type:string,
	 *         pattern:string
	 *     }>
	 * }>
	 */
function rubymaco_get_markup_rules_for_settings_view(): array {
	return rubymaco_get_markup_rules();
}

	/**
	 * 初期状態で有効にする管理画面用ルールID一覧を返す。
	 *
	 * @return string[]
	 */
function rubymaco_get_default_enabled_rule_ids(): array {
	return Markup_Rule_Registry::instance()->default_enabled_ids();
}


/**
 * 許可されたルール種別の文字列一覧を返す。
 *
 * @return string[] ルール種別一覧。
 */
function rubymaco_get_allowed_rule_types(): array {
	return Rule_Type::values();
}

/**
 * 有効な記法ルールID一覧を正規化する。
 *
 * 空文字を除外し、レジストリに定義された親ルールIDだけを残す。
 * 入力順と重複IDを維持し、空の一覧は空配列のまま返す。
 *
 * @param string[] $rule_ids 正規化対象のルールID一覧.
 * @return string[] 正規化済みのルールID一覧。
 */
function rubymaco_normalize_enabled_rule_ids( array $rule_ids ): array {
	$rule_ids = array_values(
		array_filter(
			array_map( 'strval', $rule_ids ),
			static fn( string $rule_id ): bool => '' !== $rule_id
		)
	);

	$allowed_rule_ids = Markup_Rule_Registry::instance()->ids();

	return array_values( array_intersect( $rule_ids, $allowed_rule_ids ) );
}
