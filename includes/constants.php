<?php
/**
 * Ruby Markup Converterで使用する共通定数と設定定義を管理する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Settings API
 */

/**
 * WordPress Settings API で使用する設定グループ名。
 */
const RUBYMACO_SETTINGS_GROUP = 'rubymaco_settings';

/**
 * 設定ページのスラッグ。
 */
const RUBYMACO_SETTINGS_PAGE_SLUG = 'rubymaco-settings';

/**
 * Bouten Styles
 */

/**
 * 傍点を黒丸の点として表示するスタイル。
 */
const RUBYMACO_BOUTEN_STYLE_DOT = 'dot';

/**
 * 傍点をゴマ点として表示するスタイル。
 */
const RUBYMACO_BOUTEN_STYLE_SESAME = 'sesame';

/**
 * 傍点スタイルのデフォルト値。
 */
const RUBYMACO_DEFAULT_BOUTEN_STYLE = RUBYMACO_BOUTEN_STYLE_DOT;

/**
 * 傍点スタイルの定義一覧を返す。
 *
 * 配列キーは保存値として使用する傍点スタイルID。
 *
 * @return array<string, array{
 *     label:string,
 * }>
 */
function rubymaco_get_bouten_style_definitions(): array {
	return array(
		RUBYMACO_BOUTEN_STYLE_DOT    => array(
			'label' => __( 'dot', 'ruby-markup-converter' ), // ja-jp: '点'.
		),
		RUBYMACO_BOUTEN_STYLE_SESAME => array(
			'label' => __( 'sesame', 'ruby-markup-converter' ), // ja-jp: 'ゴマ点'.
		),
	);
}

/**
 * 許可されている傍点スタイルID一覧を返す。
 *
 * @return string[]
 */
function rubymaco_get_allowed_bouten_styles(): array {
	return array_keys( rubymaco_get_bouten_style_definitions() );
}

/**
 * Bouten Renderers
 */

/**
 * 独自HTML構造で傍点を描画する方式。
 */
const RUBYMACO_BOUTEN_RENDERER_CUSTOM = 'custom';

/**
 * CSS text-emphasis で傍点を描画する方式。
 */
const RUBYMACO_BOUTEN_RENDERER_TEXT_EMPHASIS = 'text_emphasis';

/**
 * 傍点描画方式のデフォルト値。
 */
const RUBYMACO_DEFAULT_BOUTEN_RENDERER = RUBYMACO_BOUTEN_RENDERER_CUSTOM;

/**
 * 傍点描画方式の定義一覧を返す。
 *
 * 配列キーは保存値として使用する傍点描画方式ID。
 *
 * @return array<string, array{
 *     label:string,
 *     description:string
 * }>
 */
function rubymaco_get_bouten_renderer_definitions(): array {
	return array(
		RUBYMACO_BOUTEN_RENDERER_CUSTOM        => array(
			'label'       => __( 'Custom Renderer', 'ruby-markup-converter' ), // ja-jp: '独自実装'.
			'description' => __(
				'Renders bouten marks by wrapping each character in separate HTML elements. Recommended when you want finer control over positioning and appearance via CSS.',
				'ruby-markup-converter'
			), // ja-jp: '文字ごとにHTMLを分けて傍点を描画します。CSSによる位置調整や見た目のカスタマイズがしやすい方式です。'.
		),
		RUBYMACO_BOUTEN_RENDERER_TEXT_EMPHASIS => array(
			'label'       => __( 'CSS text-emphasis', 'ruby-markup-converter' ), // ja-jp: 'CSS text-emphasis'.
			'description' => __(
				'Renders bouten marks using the W3C-standard text-emphasis property. The appearance, including mark position and line spacing, depends on each browser’s implementation.',
				'ruby-markup-converter'
			), // ja-jp: 'W3C標準の「text-emphasis」プロパティを使用して傍点を描画します。表示位置や行間などの見た目は、各ブラウザの実装に依存します。'.
		),
	);
}

/**
 * 許可されている傍点描画方式ID一覧を返す。
 *
 * @return string[]
 */
function rubymaco_get_allowed_bouten_renderers(): array {
	return array_keys( rubymaco_get_bouten_renderer_definitions() );
}
