<?php

declare(strict_types=1);

/**
 * Markup transformation pipeline.
 */

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Public API
 */

/**
 * 本文に有効な変換ルールを適用する。
 *
 * @param string $content 変換対象本文
 * @return string 変換後の本文
 */
function rbmkup_transform_content_markup(string $content): string
{
    return rbmkup_apply_markup_rules(
        $content,
        rbmkup_get_enabled_rules()
    );
}

/**
 * Settings / State
 */

/**
 * 保存済み設定から有効な変換ルール一覧を返す。
 *
 * 保存されている値は管理画面用の親ルールID一覧。
 * rbmkup_get_transform_rules_for_rule_ids() 側で、
 * 親ルールIDに対応する transform_rules を展開して返す。
 *
 * @return array<int, array{
 *     id:string,
 *     type:string,
 *     pattern:string
 * }>
 */
function rbmkup_get_enabled_rules(): array
{
    $enabled_rule_ids = get_option(
        RBMKUP_OPTION_ENABLED_MARKUP_RULES,
        rbmkup_get_default_enabled_rule_ids()
    );

    if (! is_array($enabled_rule_ids)) {
        $enabled_rule_ids = rbmkup_get_default_enabled_rule_ids();
    }

    $enabled_rule_ids = array_values(
        array_filter(
            array_map('strval', $enabled_rule_ids),
            static fn(string $rule_id): bool => $rule_id !== ''
        )
    );

    return rbmkup_get_transform_rules_for_rule_ids($enabled_rule_ids);
}

/**
 * Core Transform
 */

/**
 * ルールを定義順に本文へ適用する。
 *
 * @param string $content 変換対象本文
 * @param array<int, array{
 *     id:string,
 *     type:string,
 *     pattern:string
 * }> $rules 適用する変換ルール一覧
 * @param string|null $bouten_style 傍点スタイル。null の場合は保存済み設定を使う
 * @param string|null $bouten_renderer 傍点描画方式。null の場合は保存済み設定を使う
 * @return string
 */
function rbmkup_apply_markup_rules(
    string $content,
    array $rules,
    ?string $bouten_style = null,
    ?string $bouten_renderer = null
): string {
    if (empty($rules)) {
        return $content;
    }

    $bouten_style = rbmkup_normalize_bouten_style(
        $bouten_style ?? rbmkup_get_bouten_style()
    );

    $bouten_renderer = rbmkup_normalize_bouten_renderer(
        $bouten_renderer ?? rbmkup_get_bouten_renderer()
    );

    foreach ($rules as $rule) {
        $pattern = (string)($rule['pattern'] ?? '');
        if ($pattern === '') {
            continue;
        }

        $type = (string)($rule['type'] ?? '');

        switch ($type) {
            case RBMKUP_RULE_TYPE_RUBY:
                $content = preg_replace_callback(
                    $pattern,
                    fn($matches) => rbmkup_render_ruby(
                        $matches[1] ?? '',
                        $matches[2] ?? ''
                    ),
                    $content
                ) ?? $content;
                break;

            case RBMKUP_RULE_TYPE_BOUTEN:
                $content = preg_replace_callback(
                    $pattern,
                    fn($matches) => rbmkup_render_bouten(
                        $matches[1] ?? '',
                        $bouten_style,
                        $bouten_renderer
                    ),
                    $content
                ) ?? $content;
                break;
        }
    }

    return $content;
}

/**
 * Rendering Helpers
 */

/**
 * ルビ用 HTML を生成する。
 *
 * @param string $base_text 親文字
 * @param string $ruby_text ルビ文字列
 * @return string ルビ HTML
 */
function rbmkup_render_ruby(string $base_text, string $ruby_text): string
{
    return '<ruby class="rubymarkup-ruby" data-rt="' .
        esc_attr($ruby_text) .
        '">' .
        esc_html($base_text) .
        '<rp>（</rp><rt>' .
        esc_html($ruby_text) .
        '</rt><rp>）</rp></ruby>';
}

/**
 * 傍点用 HTML を生成する。
 *
 * @param string $text 対象文字列
 * @param string $bouten_style 傍点スタイル
 * @param string $bouten_renderer 傍点描画方式
 * @return string 傍点 HTML
 */
function rbmkup_render_bouten(
    string $text,
    string $bouten_style,
    string $bouten_renderer = RBMKUP_DEFAULT_BOUTEN_RENDERER
): string {
    $bouten_style = rbmkup_normalize_bouten_style($bouten_style);
    $bouten_renderer = rbmkup_normalize_bouten_renderer($bouten_renderer);

    return $bouten_renderer === RBMKUP_BOUTEN_RENDERER_TEXT_EMPHASIS
        ? rbmkup_render_text_emphasis_bouten($text, $bouten_style)
        : rbmkup_render_custom_bouten($text, $bouten_style);
}

/**
 * 独自実装による傍点 HTML を生成する。
 *
 * @param string $text 対象文字列
 * @param string $bouten_style 傍点スタイル
 * @return string 傍点 HTML
 */
function rbmkup_render_custom_bouten(string $text, string $bouten_style): string
{
    $chars = rbmkup_split_chars($text);
    $html  = '';

    foreach ($chars as $char) {
        $html .= '<span class="rubymarkup-bouten__char">' .
            esc_html($char) .
            '</span>';
    }

    return '<span class="rubymarkup-bouten rubymarkup-bouten--custom rubymarkup-bouten--' .
        esc_attr($bouten_style) .
        '">' .
        $html .
        '</span>';
}

/**
 * CSS text-emphasis による傍点 HTML を生成する。
 *
 * @param string $text 対象文字列
 * @param string $bouten_style 傍点スタイル
 * @return string 傍点 HTML
 */
function rbmkup_render_text_emphasis_bouten(string $text, string $bouten_style): string
{
    return '<span class="rubymarkup-bouten rubymarkup-bouten--text-emphasis rubymarkup-bouten--' .
        esc_attr($bouten_style) .
        '">' .
        esc_html($text) .
        '</span>';
}

/**
 * 文字列を Unicode 文字単位で分割する。
 *
 * @param string $text 対象文字列
 * @return array<int, string>
 */
function rbmkup_split_chars(string $text): array
{
    $chars = preg_split('//u', $text, -1, PREG_SPLIT_NO_EMPTY);

    return $chars === false ? [$text] : $chars;
}
