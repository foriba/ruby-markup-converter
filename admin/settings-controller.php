<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
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
function rubymaco_register_settings(): void
{
    register_setting(
        RUBYMACO_SETTINGS_GROUP,
        RUBYMACO_OPTION_ENABLED_MARKUP_RULES,
        [
            'type'              => 'array',
            'sanitize_callback' => 'rubymaco_sanitize_enabled_markup_rules',
            'default'           => rubymaco_get_default_enabled_rule_ids(),
        ]
    );

    register_setting(
        RUBYMACO_SETTINGS_GROUP,
        RUBYMACO_OPTION_BOUTEN_STYLE,
        [
            'type'              => 'string',
            'sanitize_callback' => 'rubymaco_sanitize_bouten_style',
            'default'           => RUBYMACO_DEFAULT_BOUTEN_STYLE,
        ]
    );

    register_setting(
        RUBYMACO_SETTINGS_GROUP,
        RUBYMACO_OPTION_BOUTEN_RENDERER,
        [
            'type'              => 'string',
            'sanitize_callback' => 'rubymaco_sanitize_bouten_renderer',
            'default'           => RUBYMACO_DEFAULT_BOUTEN_RENDERER,
        ]
    );

    register_setting(
        RUBYMACO_SETTINGS_GROUP,
        RUBYMACO_OPTION_APPLY_MODE,
        [
            'type'              => 'string',
            'sanitize_callback' => 'rubymaco_sanitize_apply_mode',
            'default'           => RUBYMACO_DEFAULT_APPLY_MODE,
        ]
    );
}

/**
 * Sanitizers
 */

/**
 * 有効な記法ルールID一覧を検証・正規化して返す。
 *
 * @param mixed $value Settings API から渡される値
 * @return string[]
 */
function rubymaco_sanitize_enabled_markup_rules($value): array
{
    if (! is_array($value)) {
        return rubymaco_get_default_enabled_rule_ids();
    }

    $rule_ids = array_values(
        array_filter(
            array_map('sanitize_text_field', wp_unslash($value))
        )
    );

    return rubymaco_normalize_enabled_rule_ids($rule_ids);
}

/**
 * 傍点スタイルの設定値を検証・正規化して返す。
 *
 * @param mixed $value Settings API から渡される値
 * @return string
 */
function rubymaco_sanitize_bouten_style($value): string
{
    return rubymaco_normalize_bouten_style(
        sanitize_text_field(wp_unslash((string) $value))
    );
}

/**
 * 傍点の描画方式の設定値を検証・正規化して返す。
 *
 * @param mixed $value Settings API から渡される値
 * @return string
 */
function rubymaco_sanitize_bouten_renderer($value): string
{
    return rubymaco_normalize_bouten_renderer(
        sanitize_text_field(wp_unslash((string) $value))
    );
}

/**
 * 適用範囲の設定値を検証・正規化して返す。
 *
 * @param mixed $value Settings API から渡される値
 * @return string
 */
function rubymaco_sanitize_apply_mode($value): string
{
    return rubymaco_normalize_apply_mode(
        sanitize_text_field(wp_unslash((string) $value))
    );
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
 *         transform_rules:array<int, array{
 *             id:string,
 *             type:string,
 *             pattern:string
 *         }>,
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
function rubymaco_get_admin_settings_view_data(): array
{
    $enabled_rule_ids = get_option(
        RUBYMACO_OPTION_ENABLED_MARKUP_RULES,
        rubymaco_get_default_enabled_rule_ids()
    );

    if (! is_array($enabled_rule_ids)) {
        $enabled_rule_ids = rubymaco_get_default_enabled_rule_ids();
    }

    $enabled_rule_ids = rubymaco_normalize_enabled_rule_ids(
        array_map('strval', $enabled_rule_ids)
    );

    $current_bouten_style = rubymaco_get_option_choice(
        RUBYMACO_OPTION_BOUTEN_STYLE,
        rubymaco_get_allowed_bouten_styles(),
        RUBYMACO_DEFAULT_BOUTEN_STYLE
    );

    $current_bouten_renderer = rubymaco_get_option_choice(
        RUBYMACO_OPTION_BOUTEN_RENDERER,
        rubymaco_get_allowed_bouten_renderers(),
        RUBYMACO_DEFAULT_BOUTEN_RENDERER
    );

    $current_apply_mode = rubymaco_get_option_choice(
        RUBYMACO_OPTION_APPLY_MODE,
        rubymaco_get_allowed_apply_modes(),
        RUBYMACO_DEFAULT_APPLY_MODE
    );

    return [
        'rules'                   => rubymaco_prepare_admin_rule_view_data(
            rubymaco_get_markup_rules_for_settings_view(),
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
    ];
}

/**
 * 管理画面表示用に記法ルール一覧を整形する。
 *
 * @param array<int, array<string, mixed>> $rules
 * @param string[] $enabled_rule_ids
 * @return array<int, array{
 *     id:string,
 *     type:string,
 *     titles:string[],
 *     examples:string[],
 *     description:string,
 *     enabled_by_default:bool,
 *     transform_rules:array<int, array{
 *         id:string,
 *         type:string,
 *         pattern:string
 *     }>,
 *     is_enabled:bool
 * }>
 */
function rubymaco_prepare_admin_rule_view_data(array $rules, array $enabled_rule_ids): array
{
    $prepared_rules = [];

    foreach ($rules as $rule) {
        $rule_id = (string) ($rule['id'] ?? '');

        if ($rule_id === '') {
            continue;
        }

        $prepared_rules[] = [
            'id'                 => $rule_id,
            'type'               => (string) ($rule['type'] ?? ''),
            'titles'             => rubymaco_normalize_admin_rule_titles(
                (array) ($rule['title'] ?? [])
            ),
            'examples'           => array_values(
                array_filter(
                    array_map('strval', (array) ($rule['example'] ?? [])),
                    static fn(string $example): bool => $example !== ''
                )
            ),
            'description'        => (string) ($rule['description'] ?? ''),
            'enabled_by_default' => ! empty($rule['enabled_by_default']),
            'transform_rules'    => rubymaco_normalize_transform_rules(
                (array) ($rule['transform_rules'] ?? [])
            ),
            'is_enabled'         => in_array($rule_id, $enabled_rule_ids, true),
        ];
    }

    return $prepared_rules;
}

/**
 * 選択式フィールド表示用の候補一覧を整形する。
 *
 * @param array<string, array<string, mixed>> $definitions
 * @param string $current_value
 * @param string $id_prefix
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
    $choices = [];

    foreach ($definitions as $value => $definition) {
        $value = (string) $value;

        if ($value === '') {
            continue;
        }

        $choices[] = [
            'id'          => $id_prefix . sanitize_html_class($value),
            'value'       => $value,
            'label'       => (string) ($definition['label'] ?? $value),
            'description' => (string) ($definition['description'] ?? ''),
            'is_selected' => $current_value === $value,
        ];
    }

    return $choices;
}

/**
 * ルールタイトル一覧を表示用に正規化する。
 *
 * @param string[] $titles
 * @return string[]
 */
function rubymaco_normalize_admin_rule_titles(array $titles): array
{
    return array_values(
        array_filter(
            array_map('strval', $titles),
            static fn(string $title): bool => $title !== ''
        )
    );
}
