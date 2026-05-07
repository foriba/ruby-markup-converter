<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

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
function rbmkup_get_markup_rules(): array
{
    return [
        [
            'id'                 => 'ruby_double_angle',
            'type'               => RBMKUP_RULE_TYPE_RUBY,
            'title'              => [
                __('｜BaseText《RubyAnnotation》 Markup', 'ruby-markup-converter'), // ja-jp: '｜親文字《ルビ》 記法',
                __('BaseText《RubyAnnotation》 Markup', 'ruby-markup-converter'), // ja-jp: '親文字《ルビ》 記法',
            ],
            'example'            => [
                'それが｜真実の愛《トゥルーラブ》です。',
                'それが真実《しんじつ》の愛《あい》です。',
            ],
            'description'        => __(
                'Supports both the "｜BaseText《RubyAnnotation》" and "BaseText《RubyAnnocation》" markup styles. The former explicitly specifies both the base text and ruby annotation, with no restrictions on character types. Both full-width and half-width vertical bars are supported. The latter automatically treats the preceding kanji characters as the base text, so the base text is limited to kanji.',
                'ruby-markup-converter'
            ), // ja-jp: '「｜親文字《ルビ》」および「親文字《ルビ》」の2つの形式に対応します。前者は親文字とルビを明示的に指定する形式で、親文字・ルビともに文字種の制約はありません。縦棒は全角・半角のいずれにも対応します。後者は《ルビ》の直前の漢字を自動的に親文字として扱うため、親文字は漢字限定となります。',
            'enabled_by_default' => true,
            'transform_rules'    => [
                [
                    'id'      => 'ruby_double_angle_explicit',
                    'type'    => RBMKUP_RULE_TYPE_RUBY,
                    'pattern' => '/[|｜]([^<>|｜]+?)《([^<>]+?)》/u',
                ],
                [
                    'id'      => 'ruby_double_angle_implicit',
                    'type'    => RBMKUP_RULE_TYPE_RUBY,
                    'pattern' => '/([一-龯々〆〤]+)《([^<>]+?)》/u',
                ],
            ],
        ],

        [
            'id'                 => 'ruby_parenthesis',
            'type'               => RBMKUP_RULE_TYPE_RUBY,
            'title'              => [
                __('BaseText(RubyAnnotation) Markup', 'ruby-markup-converter'), // ja-jp: '親文字(ルビ) 記法'
            ],
            'example'            => [
                'それが真実(しんじつ)の愛(あい)です。',
            ],
            'description'        => __(
                'Uses the "BaseText(RubyAnnotation)" markup style. Parentheses must be half-width characters. The kanji characters immediately preceding "(RubyAnnotation)" are automatically treated as the base text, so the base text is limited to kanji. Ruby annotation supports hiragana, katakana, prolonged sound marks, and middle dots.',
                'ruby-markup-converter'
            ), // ja-jp: '「親文字(ルビ)」の形式で指定します。()は半角限定です。(ルビ)の直前の漢字を自動的に親文字として扱うため、親文字は漢字限定となります。ルビはひらがな・カタカナ・長音符・中点に対応しています。',
            'enabled_by_default' => false,
            'transform_rules'    => [
                [
                    'id'      => 'ruby_parenthesis',
                    'type'    => RBMKUP_RULE_TYPE_RUBY,
                    'pattern' => '/([一-龯々〆〤]+)\(([ぁ-ゖァ-ヺー・]+)\)/u',
                ],
            ],
        ],

        [
            'id'                 => 'ruby_rb',
            'type'               => RBMKUP_RULE_TYPE_RUBY,
            'title'              => [
                __('[[rb:BaseText > RubyAnnotation]] Markup', 'ruby-markup-converter'), // ja-jp: '[[rb:親文字 > ルビ]] 記法',
            ],
            'example'            => [
                'それが[[rb:真実の愛 > トゥルーラブ]]です。',
            ],
            'description'        =>
            __(
                'Uses the "[[rb:BaseText > RubyAnnotation]]" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
                'ruby-markup-converter'
            ), // ja-jp; '「[[rb:親文字 > ルビ]]」の形式で、親文字とルビを明示的に指定します。親文字・ルビともに文字種の制約はありません。',
            'enabled_by_default' => false,
            'transform_rules'    => [
                [
                    'id'      => 'ruby_rb',
                    'type'    => RBMKUP_RULE_TYPE_RUBY,
                    'pattern' => '/\[\[rb:([^>\[\]]+?)\s*>\s*([^\[\]]+?)\]\]/u',
                ],
            ],
        ],

        [
            'id'                 => 'ruby_double_underscore',
            'type'               => RBMKUP_RULE_TYPE_RUBY,
            'title'              => [
                __('#BaseText__RubyAnnotation__# Markup', 'ruby-markup-converter'), // ja-jp: '#親文字__ルビ__# 記法',
            ],
            'example'            => [
                'それが#真実の愛__トゥルーラブ__#です。',
            ],
            'description'        =>
            __(
                'Uses the "#BaseText__RubyAnnotation__#" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
                'ruby-markup-converter'
            ), // ja-jp: '「#親文字__ルビ__#」の形式で、親文字とルビを明示的に指定します。親文字・ルビともに文字種の制約はありません。',
            'enabled_by_default' => false,
            'transform_rules'    => [
                [
                    'id'      => 'ruby_double_underscore',
                    'type'    => RBMKUP_RULE_TYPE_RUBY,
                    'pattern' => '/#(.+?)__(.+?)__#/u',
                ],
            ],
        ],

        [
            'id'                 => 'ruby_mediawiki',
            'type'               => RBMKUP_RULE_TYPE_RUBY,
            'title'              => [
                __('{{ruby|BaseText|RubyAnnotation}} Markup', 'ruby-markup-converter'), // ja-jp: '{{ruby|親文字|ルビ}} 記法',
            ],
            'example'            => [
                'それが{{ruby|真実の愛|トゥルーラブ}}です。',
            ],
            'description'        =>
            __(
                'Uses the "{{ruby|BaseText|RubyAnnotation}}" markup style to explicitly specify both the base text and ruby annotation. There are no character type restrictions for either the base text or the ruby annotation.',
                'ruby-markup-converter'
            ), // ja-jp: '「{{ruby|親文字|ルビ}}」の形式で、親文字とルビを明示的に指定します。親文字・ルビともに文字種の制約はありません。',
            'enabled_by_default' => false,
            'transform_rules'    => [
                [
                    'id'      => 'ruby_mediawiki',
                    'type'    => RBMKUP_RULE_TYPE_RUBY,
                    'pattern' => '/\{\{ruby\|(.+?)\|(.+?)\}\}/u',
                ],
            ],
        ],

        [
            'id'                 => 'bouten_double_bracket',
            'type'               => RBMKUP_RULE_TYPE_BOUTEN,
            'title'              => [
                __('《《Emphasis》》 Markup', 'ruby-markup-converter') // ja-jp: '《《強調》》 記法',
            ],
            'example'            => [
                'この部分が《《強調》》されます。',
            ],
            'description'        => __(
                'Adds bouten marks to each character enclosed by "《《" and "》》".',
                'ruby-markup-converter'
            ),
            // ja-jp: '「《《」と「》》」で囲まれた文字列に対して、各文字に傍点を付与します。',
            'enabled_by_default' => true,
            'transform_rules'    => [
                [
                    'id'      => 'bouten_double_bracket',
                    'type'    => RBMKUP_RULE_TYPE_BOUTEN,
                    'pattern' => '/《《([^<>]+?)》》/u',
                ],
            ],
        ],
    ];
}

/**
 * 指定した管理画面用ルールID一覧に対応する変換ルール一覧を返す。
 *
 * 親ルールの定義順、および transform_rules の定義順を維持して返す。
 *
 * @param string[] $rule_ids 管理画面用ルールID一覧
 * @return array<int, array{
 *     id:string,
 *     type:string,
 *     pattern:string
 * }>
 */
function rbmkup_get_transform_rules_for_rule_ids(array $rule_ids): array
{
    $rule_ids = array_values(
        array_filter(
            array_map('strval', $rule_ids),
            static fn(string $rule_id): bool => $rule_id !== ''
        )
    );

    $transform_rules = [];

    foreach (rbmkup_get_markup_rules() as $rule) {
        $rule_id = (string) ($rule['id'] ?? '');

        if (! in_array($rule_id, $rule_ids, true)) {
            continue;
        }

        $transform_rules = array_merge(
            $transform_rules,
            rbmkup_normalize_transform_rules((array) ($rule['transform_rules'] ?? []))
        );
    }

    return $transform_rules;
}

/**
 * 変換ルール一覧を正規化する。
 *
 * 不完全なルールや、未定義のルール種別を持つルールは除外する。
 *
 * @param array<int, mixed> $transform_rules 正規化対象の変換ルール一覧
 * @return array<int, array{
 *     id:string,
 *     type:string,
 *     pattern:string
 * }>
 */
function rbmkup_normalize_transform_rules(array $transform_rules): array
{
    $rules = [];
    $allowed_types = rbmkup_get_allowed_rule_types();

    foreach ($transform_rules as $rule) {
        if (
            ! is_array($rule)
            || ! isset($rule['id'], $rule['type'], $rule['pattern'])
        ) {
            continue;
        }

        $type = (string) $rule['type'];
        $pattern = (string) $rule['pattern'];

        if (
            $pattern === ''
            || ! in_array($type, $allowed_types, true)
        ) {
            continue;
        }

        $rules[] = [
            'id'      => (string) $rule['id'],
            'type'    => $type,
            'pattern' => $pattern,
        ];
    }

    return $rules;
}


/**
 * 管理画面に表示する記法ルール一覧を返す。
 *
 * 現在は rbmkup_get_markup_rules() と同じ内容を返すが、
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
function rbmkup_get_markup_rules_for_settings_view(): array
{
    return rbmkup_get_markup_rules();
}

/**
 * 初期状態で有効にする管理画面用ルールID一覧を返す。
 *
 * @return string[]
 */
function rbmkup_get_default_enabled_rule_ids(): array
{
    $default_enabled_rule_ids = [];

    foreach (rbmkup_get_markup_rules() as $rule) {
        if (! empty($rule['enabled_by_default'])) {
            $default_enabled_rule_ids[] = (string) $rule['id'];
        }
    }

    return $default_enabled_rule_ids;
}
