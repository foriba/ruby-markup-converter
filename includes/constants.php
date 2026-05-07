<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * Shared constants for Ruby Markup Converter.
 */


/**
 * Option Keys
 */

/**
 * 有効化されている記法ルールID一覧を保存する option 名。
 */
const RBMKUP_OPTION_ENABLED_MARKUP_RULES = 'rbmkup_enabled_markup_rules';

/**
 * 傍点スタイルを保存する option 名。
 */
const RBMKUP_OPTION_BOUTEN_STYLE = 'rbmkup_bouten_style';

/**
 * 適用モードを保存する option 名。
 */
const RBMKUP_OPTION_APPLY_MODE = 'rbmkup_apply_mode';

/**
 * 傍点描画方式を保存する option 名。
 */
const RBMKUP_OPTION_BOUTEN_RENDERER = 'rbmkup_bouten_renderer';


/**
 * Settings API
 */

/**
 * WordPress Settings API で使用する設定グループ名。
 */
const RBMKUP_SETTINGS_GROUP = 'rbmkup_settings';

/**
 * 設定ページのスラッグ。
 */
const RBMKUP_SETTINGS_PAGE_SLUG = 'rubymarkup-settings';


/**
 * Rule Types
 */

/**
 * ルビ変換ルールを表すルール種別。
 */
const RBMKUP_RULE_TYPE_RUBY = 'ruby';

/**
 * 傍点変換ルールを表すルール種別。
 */
const RBMKUP_RULE_TYPE_BOUTEN = 'bouten';

/**
 * 許可されているルール種別一覧。
 *
 * @var string[]
 */
const RBMKUP_ALLOWED_RULE_TYPES = [
    RBMKUP_RULE_TYPE_RUBY,
    RBMKUP_RULE_TYPE_BOUTEN,
];

/**
 * 許可されているルール種別一覧を返す。
 *
 * @return string[]
 */
function rbmkup_get_allowed_rule_types(): array
{
    return RBMKUP_ALLOWED_RULE_TYPES;
}


/**
 * Apply Modes
 */

/**
 * ショートコード内のみを変換対象にする適用モード。
 */
const RBMKUP_APPLY_MODE_SHORTCODE = 'shortcode';

/**
 * 投稿本文全体を変換対象にする適用モード。
 */
const RBMKUP_APPLY_MODE_ALL = 'all';

/**
 * 適用モードのデフォルト値。
 */
const RBMKUP_DEFAULT_APPLY_MODE = RBMKUP_APPLY_MODE_SHORTCODE;

/**
 * 適用モードの定義一覧を返す。
 *
 * 配列キーは保存値として使用する適用モードID。
 *
 * @return array<string, array{
 *     label:string,
 *     description:string
 * }>
 */
function rbmkup_get_apply_mode_definitions(): array
{
    return [
        RBMKUP_APPLY_MODE_SHORTCODE => [
            'label'       => __('Apply Only Within Shortcodes', 'ruby-markup-converter'), // ja-jp: 'ショートコード内のみ適用'
            'description' => __(
                'Converts markup only within [rubymarkup]...[/rubymarkup] blocks. This helps prevent unintended conversions and reduces processing overhead.',
                // ja-jp: '[rubymarkup]〜[/rubymarkup] で囲まれた範囲内の記法のみを変換します。誤変換を防ぎやすく、変換処理の負荷も抑えられます。'
                'ruby-markup-converter'
            ),
        ],
        RBMKUP_APPLY_MODE_ALL => [
            'label'       => __('Apply to Entire Post Content', 'ruby-markup-converter'), // ja-jp: '投稿本文全体に適用'
            'description' => __(
                'Automatically converts markup throughout the post content without requiring shortcodes. This is more convenient, but unintended text may also be converted.',
                // ja-jp: 'ショートコードを使わず、投稿本文内の記法を自動的に変換します。手軽に使用できますが、意図しない箇所まで変換される場合があります。'
                'ruby-markup-converter'
            ),
        ],
    ];
}

/**
 * 許可されている適用モードID一覧を返す。
 *
 * @return string[]
 */
function rbmkup_get_allowed_apply_modes(): array
{
    return array_keys(rbmkup_get_apply_mode_definitions());
}

/**
 * Bouten Styles
 */

/**
 * 傍点を黒丸の点として表示するスタイル。
 */
const RBMKUP_BOUTEN_STYLE_DOT = 'dot';

/**
 * 傍点をゴマ点として表示するスタイル。
 */
const RBMKUP_BOUTEN_STYLE_SESAME = 'sesame';

/**
 * 傍点スタイルのデフォルト値。
 */
const RBMKUP_DEFAULT_BOUTEN_STYLE = RBMKUP_BOUTEN_STYLE_DOT;

/**
 * 傍点スタイルの定義一覧を返す。
 *
 * 配列キーは保存値として使用する傍点スタイルID。
 *
 * @return array<string, array{
 *     label:string,
 * }>
 */
function rbmkup_get_bouten_style_definitions(): array
{
    return [
        RBMKUP_BOUTEN_STYLE_DOT => [
            'label' => __('dot', 'ruby-markup-converter'), // ja-jp: '点'
        ],
        RBMKUP_BOUTEN_STYLE_SESAME => [
            'label' => __('sesame', 'ruby-markup-converter'), // ja-jp: 'ゴマ点'
        ],
    ];
}

/**
 * 許可されている傍点スタイルID一覧を返す。
 *
 * @return string[]
 */
function rbmkup_get_allowed_bouten_styles(): array
{
    return array_keys(rbmkup_get_bouten_style_definitions());
}


/**
 * Bouten Renderers
 */

/**
 * 独自HTML構造で傍点を描画する方式。
 */
const RBMKUP_BOUTEN_RENDERER_CUSTOM = 'custom';

/**
 * CSS text-emphasis で傍点を描画する方式。
 */
const RBMKUP_BOUTEN_RENDERER_TEXT_EMPHASIS = 'text_emphasis';

/**
 * 傍点描画方式のデフォルト値。
 */
const RBMKUP_DEFAULT_BOUTEN_RENDERER = RBMKUP_BOUTEN_RENDERER_CUSTOM;

/**
 * 傍点描画方式の定義一覧を返す。
 *
 * 配列キーは保存値として使用する傍点描画方式ID。
 *
 * @var array<string, array{
 *     label:string,
 *     description:string
 * }>
 */
function rbmkup_get_bouten_renderer_definitions(): array
{
    return [
        RBMKUP_BOUTEN_RENDERER_CUSTOM => [
            'label'       => __('Custom Renderer', 'ruby-markup-converter'), // ja-jp: '独自実装',
            'description' => __(
                'Renders bouten marks by wrapping each character in separate HTML elements. Recommended when you want finer control over positioning and appearance via CSS.',
                'ruby-markup-converter'
            ), // ja-jp: '文字ごとにHTMLを分けて傍点を描画します。CSSによる位置調整や見た目のカスタマイズがしやすい方式です。',
        ],
        RBMKUP_BOUTEN_RENDERER_TEXT_EMPHASIS => [
            'label'       => __('CSS text-emphasis', 'ruby-markup-converter'), // ja-jp: 'CSS text-emphasis',
            'description' => __(
                'Renders bouten marks using the W3C-standard text-emphasis property. The appearance, including mark position and line spacing, depends on each browser’s implementation.',
                'ruby-markup-converter'
            ), // ja-jp: 'W3C標準の「text-emphasis」プロパティを使用して傍点を描画します。表示位置や行間などの見た目は、各ブラウザの実装に依存します。',
        ],
    ];
}

/**
 * 許可されている傍点描画方式ID一覧を返す。
 *
 * @return string[]
 */
function rbmkup_get_allowed_bouten_renderers(): array
{
    return array_keys(rbmkup_get_bouten_renderer_definitions());
}
