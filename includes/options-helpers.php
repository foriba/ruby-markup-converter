<?php
/**
 * 保存済み設定値の取得と正規化を行うヘルパー関数群。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use foriba\rubymarkupconverter\RUBYMACO_Apply_Mode;
use foriba\rubymarkupconverter\RUBYMACO_Option_Key;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 保存済み option から、候補が限定された単一値を取得する。
 *
 * 値が未保存の場合はデフォルト値を使い、
 * 取得した値が許可された候補に含まれない場合もデフォルト値に戻す。
 *
 * @param string   $key            option 名.
 * @param string[] $allowed_values 許可する値の一覧.
 * @param string   $default_value  未保存時・不正値時に使うデフォルト値.
 *
 * @return string 正規化済みの設定値
 */
function rubymaco_get_option_choice( string $key, array $allowed_values, string $default_value ): string {
	$value = get_option( $key, $default_value );

	if ( ! is_string( $value ) ) {
		return $default_value;
	}

	return in_array( $value, $allowed_values, true )
		? $value
		: $default_value;
}

/**
 * 有効な記法ルールID一覧を正規化する。
 *
 * 空文字を除外し、定義済みの管理画面用ルールIDだけを残す。
 *
 * @param string[] $rule_ids 正規化対象のルールID一覧.
 * @return string[] 正規化済みのルールID一覧
 */
function rubymaco_normalize_enabled_rule_ids( array $rule_ids ): array {
	$rule_ids = array_values(
		array_filter(
			array_map( 'strval', $rule_ids ),
			static fn( string $rule_id ): bool => '' !== $rule_id
		)
	);

	$allowed_rule_ids = array_map(
		static fn( array $rule ): string => (string) $rule['id'],
		rubymaco_get_markup_rules_for_settings_view()
	);

	return array_values( array_intersect( $rule_ids, $allowed_rule_ids ) );
}

/**
 * 保存済みの傍点スタイルを返す。
 *
 * @return string 'dot' または 'sesame'
 */
function rubymaco_get_bouten_style(): string {
	return rubymaco_get_option_choice(
		RUBYMACO_Option_Key::BOUTEN_STYLE,
		rubymaco_get_allowed_bouten_styles(),
		RUBYMACO_DEFAULT_BOUTEN_STYLE
	);
}

/**
 * 傍点スタイル名を正規化する。
 *
 * @param string $style 傍点スタイル.
 * @return string 'dot' または 'sesame'
 */
function rubymaco_normalize_bouten_style( string $style ): string {
	return in_array( $style, rubymaco_get_allowed_bouten_styles(), true )
		? $style
		: RUBYMACO_DEFAULT_BOUTEN_STYLE;
}

/**
 * 傍点の描画方式を返す。
 *
 * @return string 'custom' または 'text_emphasis'
 */
function rubymaco_get_bouten_renderer(): string {
	return rubymaco_get_option_choice(
		RUBYMACO_Option_Key::BOUTEN_RENDERER,
		rubymaco_get_allowed_bouten_renderers(),
		RUBYMACO_DEFAULT_BOUTEN_RENDERER
	);
}

/**
 * 傍点の描画方式を正規化する。
 *
 * @param string $renderer 傍点描画方式.
 * @return string 'custom' または 'text_emphasis'
 */
function rubymaco_normalize_bouten_renderer( string $renderer ): string {
	return in_array( $renderer, rubymaco_get_allowed_bouten_renderers(), true )
		? $renderer
		: RUBYMACO_DEFAULT_BOUTEN_RENDERER;
}

/**
 * 適用モードIDを返す。
 *
 * @return string 'shortcode' または 'all'
 */
function rubymaco_get_apply_mode(): string {
	return rubymaco_get_option_choice(
		RUBYMACO_Option_Key::APPLY_MODE,
		RUBYMACO_Apply_Mode::values(),
		RUBYMACO_Apply_Mode::default()
	);
}
