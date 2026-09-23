<?php
/**
 * 設定画面の選択肢と表示文言を定義する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Markup\Value\Apply_Mode;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;


if ( ! defined( 'ABSPATH' ) ) {
	exit;
}


/**
 * Apply Modes
 */

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
function rubymaco_get_apply_mode_definitions(): array {
	return array(
		Apply_Mode::SELECTED_AREAS => array(
			'label'       => __( 'Apply Only Within Selected Areas', 'ruby-markup-converter' ), // ja-jp: '指定範囲内のみ適用'.
			'description' => __(
				'Converts markup within Ruby Markup Converter blocks. Markup inside manually written [rubymaco]...[/rubymaco] shortcodes is also converted.',
				// ja-jp: 'Ruby Markup Converter ブロック内の記法を変換します。手書きの [rubymaco]〜[/rubymaco] ショートコード内の記法も変換されます。'.
				'ruby-markup-converter'
			),
		),
		Apply_Mode::ALL            => array(
			'label'       => __( 'Apply to Entire Post Content', 'ruby-markup-converter' ), // ja-jp: '投稿本文全体に適用'.
			'description' => __(
				'Automatically converts markup throughout the post content without placing it inside Ruby Markup Converter blocks. This is more convenient, but unintended text may also be converted and long posts may take more processing.',
				// ja-jp: 'Ruby Markup Converter ブロック内に配置しなくても、投稿本文内の記法を自動的に変換します。手軽に使用できますが、意図しない箇所まで変換されたり、長い投稿では処理が増えたりする場合があります。'.
				'ruby-markup-converter'
			),
		),
	);
}

/**
 * Bouten Styles
 */

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
		Bouten_Style::DOT    => array(
			'label' => __( 'dot', 'ruby-markup-converter' ), // ja-jp: '点'.
		),
		Bouten_Style::SESAME => array(
			'label' => __( 'sesame', 'ruby-markup-converter' ), // ja-jp: 'ゴマ点'.
		),
	);
}

/**
 * Bouten Renderers
 */

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
		Bouten_Rendering_Method::TEXT_EMPHASIS => array(
			'label'       => __( 'CSS text-emphasis', 'ruby-markup-converter' ), // ja-jp: 'CSS text-emphasis'.
			'description' => __(
				'Renders bouten marks using the CSS text-emphasis property. This is the standard rendering method, but the appearance may vary slightly between browsers.',
				'ruby-markup-converter'
			), // ja-jp: 'CSS の text-emphasis プロパティを使用して傍点を描画します。標準的な描画方式ですが、表示はブラウザによってわずかに異なる場合があります。'.
		),
		Bouten_Rendering_Method::CUSTOM        => array(
			'label'       => __( 'Custom Renderer', 'ruby-markup-converter' ), // ja-jp: '独自実装'.
			'description' => __(
				'Renders bouten marks by wrapping each character in separate HTML elements. Use this when you want finer control over positioning and appearance via CSS.',
				'ruby-markup-converter'
			), // ja-jp: '文字ごとにHTMLを分けて傍点を描画します。CSSによる位置調整や見た目を細かく調整したい場合に使用します。'.
		),
	);
}
