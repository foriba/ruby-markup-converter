<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 保存済み option から、候補が限定された単一値を取得する。
 *
 * 値が未保存の場合はデフォルト値を使い、
 * 取得した値が許可された候補に含まれない場合もデフォルト値に戻す。
 *
 * @param string   $key            option 名
 * @param string[] $allowed_values 許可する値の一覧
 * @param string   $default        未保存時・不正値時に使うデフォルト値
 *
 * @return string 正規化済みの設定値
 */
function rbmkup_get_option_choice(string $key, array $allowed_values, string $default): string
{
    $value = get_option($key, $default);

    return in_array($value, $allowed_values, true)
        ? $value
        : $default;
}

/**
 * 有効な記法ルールID一覧を正規化する。
 *
 * 空文字を除外し、定義済みの管理画面用ルールIDだけを残す。
 *
 * @param string[] $rule_ids 正規化対象のルールID一覧
 * @return string[] 正規化済みのルールID一覧
 */
function rbmkup_normalize_enabled_rule_ids(array $rule_ids): array
{
    $rule_ids = array_values(
        array_filter(
            array_map('strval', $rule_ids),
            static fn(string $rule_id): bool => $rule_id !== ''
        )
    );

    $allowed_rule_ids = array_map(
        static fn(array $rule): string => (string) $rule['id'],
        rbmkup_get_markup_rules_for_settings_view()
    );

    return array_values(array_intersect($rule_ids, $allowed_rule_ids));
}

/**
 * 保存済みの傍点スタイルを返す。
 *
 * @return string 'dot' または 'sesame'
 */
function rbmkup_get_bouten_style(): string
{
    return rbmkup_normalize_bouten_style(
        (string) get_option(
            RBMKUP_OPTION_BOUTEN_STYLE,
            RBMKUP_DEFAULT_BOUTEN_STYLE
        )
    );
}

/**
 * 傍点スタイル名を正規化する。
 *
 * @param string $style 傍点スタイル
 * @return string 'dot' または 'sesame'
 */
function rbmkup_normalize_bouten_style(string $style): string
{
    return in_array($style, rbmkup_get_allowed_bouten_styles(), true)
        ? $style
        : RBMKUP_DEFAULT_BOUTEN_STYLE;
}

/**
 * 傍点の描画方式を返す。
 *
 * @return string 'custom' または 'text_emphasis'
 */
function rbmkup_get_bouten_renderer(): string
{
    return rbmkup_normalize_bouten_renderer(
        (string) get_option(
            RBMKUP_OPTION_BOUTEN_RENDERER,
            RBMKUP_DEFAULT_BOUTEN_RENDERER
        )
    );
}

/**
 * 傍点の描画方式を正規化する。
 *
 * @param string $renderer 傍点描画方式
 * @return string 'custom' または 'text_emphasis'
 */
function rbmkup_normalize_bouten_renderer(string $renderer): string
{
    return in_array($renderer, rbmkup_get_allowed_bouten_renderers(), true)
        ? $renderer
        : RBMKUP_DEFAULT_BOUTEN_RENDERER;
}

/**
 * 適用モードIDを返す。
 *
 * @return string 'shortcode' または 'all'
 */
function rbmkup_get_apply_mode(): string
{
    return rbmkup_normalize_apply_mode(
        (string) get_option(
            RBMKUP_OPTION_APPLY_MODE,
            RBMKUP_DEFAULT_APPLY_MODE
        )
    );
}

/**
 * 適用モードIDを正規化する。
 *
 * 未定義の適用モードIDが渡された場合はデフォルト値を返す。
 *
 * @param string $apply_mode 適用モードID
 * @return string 正規化済みの適用モードID
 */
function rbmkup_normalize_apply_mode(string $apply_mode): string
{
    return in_array($apply_mode, rbmkup_get_allowed_apply_modes(), true)
        ? $apply_mode
        : RBMKUP_DEFAULT_APPLY_MODE;
}
