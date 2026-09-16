<?php
/**
 * 保存済み設定値の取得と正規化を行うヘルパー関数群。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Markup\Bouten_Rendering_Method;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/markup/class-bouten-rendering-method.php';

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
 * 傍点の描画方式を返す。
 *
 * @return Bouten_Rendering_Method 検証済みの傍点描画方式.
 */
function rubymaco_get_bouten_renderer(): Bouten_Rendering_Method {
	return Bouten_Rendering_Method::from(
		rubymaco_get_option_choice(
			RUBYMACO_OPTION_BOUTEN_RENDERER,
			rubymaco_get_allowed_bouten_renderers(),
			RUBYMACO_DEFAULT_BOUTEN_RENDERER
		)
	);
}

/**
 * 傍点の描画方式を正規化する。
 *
 * @param string $renderer 傍点描画方式.
 * @return Bouten_Rendering_Method 検証済みの傍点描画方式.
 */
function rubymaco_normalize_bouten_renderer( string $renderer ): Bouten_Rendering_Method {
	return Bouten_Rendering_Method::try_from( $renderer ) ?? Bouten_Rendering_Method::from( RUBYMACO_DEFAULT_BOUTEN_RENDERER );
}

/**
 * 適用モードIDを返す。
 *
 * @return string 'shortcode' または 'all'
 */
function rubymaco_get_apply_mode(): string {
	return rubymaco_get_option_choice(
		RUBYMACO_OPTION_APPLY_MODE,
		rubymaco_get_allowed_apply_modes(),
		RUBYMACO_DEFAULT_APPLY_MODE
	);
}

/**
 * 適用モードIDを正規化する。
 *
 * 未定義の適用モードIDが渡された場合はデフォルト値を返す。
 *
 * @param string $apply_mode 適用モードID.
 * @return string 正規化済みの適用モードID
 */
function rubymaco_normalize_apply_mode( string $apply_mode ): string {
	return in_array( $apply_mode, rubymaco_get_allowed_apply_modes(), true )
		? $apply_mode
		: RUBYMACO_DEFAULT_APPLY_MODE;
}
