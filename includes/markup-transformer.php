<?php
/**
 * 設定と既存の配列形式をクラスベースの変換処理へ接続する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Resolver\Bouten_Style_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Rendering_Method_Resolver;

use Foriba\RubyMarkupConverter\Markup\Markup_Renderer;
use Foriba\RubyMarkupConverter\Markup\Markup_Transformer;
use Foriba\RubyMarkupConverter\Markup\Transform_Rule;
use Foriba\RubyMarkupConverter\Markup\Rule_Type;

/**
 * Markup transformation pipeline.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/resolver/class-bouten-style-resolver.php';
require_once __DIR__ . '/resolver/class-bouten-rendering-method-resolver.php';
require_once __DIR__ . '/markup/class-markup-renderer.php';
require_once __DIR__ . '/markup/class-markup-transformer.php';

/**
 * Public API
 */

/**
 * 本文に有効な変換ルールを適用する。
 *
 * @param string $content 変換対象本文.
 * @return string 変換後の本文
 */
function rubymaco_transform_content_markup( string $content ): string {
	return rubymaco_apply_markup_rules(
		$content,
		rubymaco_get_enabled_rules()
	);
}

/**
 * Settings / State
 */

/**
 * 保存済み設定から有効な変換ルール一覧を返す。
 *
 * 保存されている値は管理画面用の親ルールID一覧。
 * rubymaco_get_transform_rules_for_rule_ids() 側で、
 * 親ルールIDに対応する transform_rules を展開して返す。
 *
 * @return array<int, array{
 *     id:string,
 *     type:string,
 *     pattern:string
 * }>
 */
function rubymaco_get_enabled_rules(): array {
	$enabled_rule_ids = get_option(
		RUBYMACO_OPTION_ENABLED_MARKUP_RULES,
		rubymaco_get_default_enabled_rule_ids()
	);

	if ( ! is_array( $enabled_rule_ids ) ) {
		$enabled_rule_ids = rubymaco_get_default_enabled_rule_ids();
	}

	$enabled_rule_ids = array_values(
		array_filter(
			array_map( 'strval', $enabled_rule_ids ),
			static fn( string $rule_id ): bool => '' !== $rule_id
		)
	);

	return rubymaco_get_transform_rules_for_rule_ids( $enabled_rule_ids );
}

/**
 * Core Transform
 */

/**
 * ルールを定義順に本文へ適用する。
 *
 * @param string                                                      $content         変換対象本文.
 * @param array<int, array{ id:string, type:string, pattern:string }> $rules           適用する変換ルール一覧.
 * @param string|null                                                 $bouten_style    傍点スタイル。null の場合は保存済み設定を使う.
 * @param string|null                                                 $bouten_rendering_method 傍点描画方式。null の場合は保存済み設定を使う.
 * @return string 変換後の本文.
 */
function rubymaco_apply_markup_rules(
	string $content,
	array $rules,
	?string $bouten_style = null,
	?string $bouten_rendering_method = null
): string {
	if ( empty( $rules ) ) {
		return $content;
	}

	$style_resolver = new Bouten_Style_Resolver();
	$bouten_style   = null === $bouten_style
		? $style_resolver->get()
		: $style_resolver->normalize( $bouten_style );

	$method_resolver         = new Bouten_Rendering_Method_Resolver();
	$bouten_rendering_method = null === $bouten_rendering_method
		? $method_resolver->get()
		: $method_resolver->normalize( $bouten_rendering_method );

	$transform_rules = array();
	foreach ( $rules as $rule ) {
		if ( '' === $rule['pattern'] ) {
			continue;
		}
		$transform_rules[] = new Transform_Rule(
			$rule['id'],
			Rule_Type::from( $rule['type'] ),
			$rule['pattern']
		);
	}

	return ( new Markup_Transformer( new Markup_Renderer() ) )->transform(
		$content,
		$transform_rules,
		$bouten_style,
		$bouten_rendering_method
	);
}
