<?php
/**
 * 管理画面の設定登録と表示用データの整形を行う。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;

use Foriba\RubyMarkupConverter\Settings\Option_Keys;

use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Markup\Markup_Rule;
use Foriba\RubyMarkupConverter\Markup\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Markup\Transform_Rule;

use Foriba\RubyMarkupConverter\Resolver\Bouten_Style_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Rendering_Method_Resolver;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Settings registration and view data preparation for the admin screen.
 */

/**
 * Settings Registration
 */

/**
 * 設定項目を WordPress Settings API に登録する。
 */
function rubymaco_register_settings(): void {
	register_setting(
		Settings_Identifiers::GROUP,
		Option_Keys::ENABLED_MARKUP_RULES,
		array(
			'type'              => 'array',
			'sanitize_callback' => 'rubymaco_sanitize_enabled_markup_rules',
			'default'           => Markup_Rule_Registry::instance()->default_enabled_ids(),
		)
	);

	register_setting(
		Settings_Identifiers::GROUP,
		Option_Keys::BOUTEN_STYLE,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'rubymaco_sanitize_bouten_style',
			'default'           => ( new Bouten_Style_Resolver() )->default_value()->get_value(),
		)
	);

	register_setting(
		Settings_Identifiers::GROUP,
		Option_Keys::BOUTEN_RENDERING_METHOD,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'rubymaco_sanitize_bouten_renderer',
			'default'           => ( new Bouten_Rendering_Method_Resolver() )->default_value()->get_value(),
		)
	);

	register_setting(
		Settings_Identifiers::GROUP,
		Option_Keys::APPLY_MODE,
		array(
			'type'              => 'string',
			'sanitize_callback' => 'rubymaco_sanitize_apply_mode',
			'default'           => ( new Apply_Mode_Resolver() )->default_value()->get_value(),
		)
	);
}

/**
 * Sanitizers
 */

/**
 * 有効な記法ルールID一覧を検証・正規化して返す。
 *
 * @param mixed $value Settings API から渡される値.
 * @return string[]
 */
function rubymaco_sanitize_enabled_markup_rules( $value ): array {
	if ( ! is_array( $value ) ) {
		return Markup_Rule_Registry::instance()->default_enabled_ids();
	}

	$rule_ids = array_values(
		array_filter(
			array_map( 'sanitize_text_field', wp_unslash( $value ) )
		)
	);

	return array_values( array_intersect( $rule_ids, Markup_Rule_Registry::instance()->ids() ) );
}

/**
 * 傍点スタイルの設定値を検証・正規化して返す。
 *
 * @param mixed $value Settings API から渡される値.
 * @return string
 */
function rubymaco_sanitize_bouten_style( $value ): string {
	return ( new Bouten_Style_Resolver() )->normalize(
		sanitize_text_field( wp_unslash( (string) $value ) )
	)->get_value();
}

/**
 * 傍点の描画方式の設定値を検証・正規化して返す。
 *
 * @param mixed $value Settings API から渡される値.
 * @return string
 */
function rubymaco_sanitize_bouten_renderer( $value ): string {
	return ( new Bouten_Rendering_Method_Resolver() )->normalize(
		sanitize_text_field( wp_unslash( (string) $value ) )
	)->get_value();
}

/**
 * 適用範囲の設定値を検証・正規化して返す。
 *
 * @param mixed $value Settings API から渡される値.
 * @return string
 */
function rubymaco_sanitize_apply_mode( $value ): string {
	return ( new Apply_Mode_Resolver() )->normalize(
		is_string( $value ) ? sanitize_text_field( wp_unslash( $value ) ) : ''
	)->get_value();
}

/**
 * Settings / State
 */

/**
 * 管理画面表示用の設定値を取得する。
 *
 * @return array{
 *     rules: array<int, array{
 *         id:string,
 *         type:string,
 *         titles:string[],
 *         examples:string[],
 *         description:string,
 *         enabled_by_default:bool,
 *         transform_rules:Transform_Rule[],
 *         is_enabled:bool
 *     }>,
 *     apply_mode_choices: array<int, array{
 *         id:string,
 *         value:string,
 *         label:string,
 *         description:string,
 *         is_selected:bool
 *     }>,
 *     bouten_style_choices: array<int, array{
 *         id:string,
 *         value:string,
 *         label:string,
 *         description:string,
 *         is_selected:bool
 *     }>,
 *     bouten_renderer_choices: array<int, array{
 *         id:string,
 *         value:string,
 *         label:string,
 *         description:string,
 *         is_selected:bool
 *     }>,
 *     enabled_rule_ids: string[],
 *     current_bouten_style: string,
 *     current_bouten_renderer: string,
 *     current_apply_mode: string
 * }
 */
function rubymaco_get_admin_settings_view_data(): array {
	$enabled_rule_ids = get_option(
		Option_Keys::ENABLED_MARKUP_RULES,
		Markup_Rule_Registry::instance()->default_enabled_ids()
	);

	if ( ! is_array( $enabled_rule_ids ) ) {
		$enabled_rule_ids = Markup_Rule_Registry::instance()->default_enabled_ids();
	}

	$enabled_rule_ids = array_values(
		array_intersect(
			array_map( 'strval', $enabled_rule_ids ),
			Markup_Rule_Registry::instance()->ids()
		)
	);

	$current_bouten_style = ( new Bouten_Style_Resolver() )->get()->get_value();

	$current_bouten_renderer = ( new Bouten_Rendering_Method_Resolver() )->get()->get_value();

	$current_apply_mode = ( new Apply_Mode_Resolver() )->get()->get_value();

	return array(
		'rules'                   => rubymaco_prepare_admin_rule_view_data(
			Markup_Rule_Registry::instance()->for_settings_view(),
			$enabled_rule_ids
		),
		'apply_mode_choices'      => rubymaco_prepare_choice_view_data(
			rubymaco_get_apply_mode_definitions(),
			$current_apply_mode,
			'rubymaco-apply-mode-'
		),
		'bouten_style_choices'    => rubymaco_prepare_choice_view_data(
			rubymaco_get_bouten_style_definitions(),
			$current_bouten_style,
			'rubymaco-bouten-style-'
		),
		'bouten_renderer_choices' => rubymaco_prepare_choice_view_data(
			rubymaco_get_bouten_renderer_definitions(),
			$current_bouten_renderer,
			'rubymaco-bouten-renderer-'
		),
		'enabled_rule_ids'        => $enabled_rule_ids,
		'current_bouten_style'    => $current_bouten_style,
		'current_bouten_renderer' => $current_bouten_renderer,
		'current_apply_mode'      => $current_apply_mode,
	);
}

/**
 * 管理画面表示用に記法ルール一覧を整形する。
 *
 * @param Markup_Rule[] $rules            記法ルール一覧.
 * @param string[]      $enabled_rule_ids 有効化されている記法ルールID一覧.
 * @return array<int, array{
 *     id:string,
 *     type:string,
 *     titles:string[],
 *     examples:string[],
 *     description:string,
 *     enabled_by_default:bool,
 *     transform_rules:Transform_Rule[],
 *     is_enabled:bool
 * }>
 */
function rubymaco_prepare_admin_rule_view_data( array $rules, array $enabled_rule_ids ): array {
	$prepared_rules = array();

	foreach ( $rules as $rule ) {
		$rule_id = $rule->id;
		if ( '' === $rule_id ) {
			continue;
		}

		$prepared_rules[] = array(
			'id'                 => $rule_id,
			'type'               => $rule->type->get_value(),
			'titles'             => rubymaco_normalize_admin_rule_titles( $rule->titles ),
			'examples'           => array_values(
				array_filter(
					$rule->examples,
					static fn( string $example ): bool => '' !== $example
				)
			),
			'description'        => $rule->description,
			'enabled_by_default' => $rule->enabled_by_default,
			'transform_rules'    => $rule->transform_rules,
			'is_enabled'         => in_array( $rule_id, $enabled_rule_ids, true ),
		);
	}

	return $prepared_rules;
}

/**
 * 選択式フィールド表示用の候補一覧を整形する。
 *
 * @param array<string, array<string, mixed>> $definitions   選択肢定義一覧.
 * @param string                              $current_value 現在の設定値.
 * @param string                              $id_prefix     HTML ID用の接頭辞.
 * @return array<int, array{
 *     id:string,
 *     value:string,
 *     label:string,
 *     description:string,
 *     is_selected:bool
 * }>
 */
function rubymaco_prepare_choice_view_data(
	array $definitions,
	string $current_value,
	string $id_prefix
): array {
	$choices = array();

	foreach ( $definitions as $value => $definition ) {
		$value = (string) $value;

		if ( '' === $value ) {
			continue;
		}

		$choices[] = array(
			'id'          => $id_prefix . sanitize_html_class( $value ),
			'value'       => $value,
			'label'       => (string) ( $definition['label'] ?? $value ),
			'description' => (string) ( $definition['description'] ?? '' ),
			'is_selected' => $current_value === $value,
		);
	}

	return $choices;
}

/**
 * ルールタイトル一覧を表示用に正規化する。
 *
 * @param string[] $titles ルールタイトル一覧.
 * @return string[] 正規化済みのルールタイトル一覧.
 */
function rubymaco_normalize_admin_rule_titles( array $titles ): array {
	return array_values(
		array_filter(
			array_map( 'strval', $titles ),
			static fn( string $title ): bool => '' !== $title
		)
	);
}
