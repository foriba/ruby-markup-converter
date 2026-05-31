<?php
/**
 * ルビ・傍点記法の変換処理を行う。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use foriba\rubymarkupconverter\RUBYMACO_Bouten_Renderer;
use foriba\rubymarkupconverter\RUBYMACO_Bouten_Style;
use foriba\rubymarkupconverter\RUBYMACO_Option_Key;
use foriba\rubymarkupconverter\RUBYMACO_Rule_Type;

/**
 * Markup transformation pipeline.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Public API
 */

/**
 * 本文に有効な変換ルールを適用する。
 *
 * @param string $content 変換対象本文.
 * @return string 変換後の本文
 */
function rubymaco_transform_content_markup( string $content ): string {
	return rubymaco_apply_markup_rules(
		$content,
		rubymaco_get_enabled_rules()
	);
}

/**
 * Settings / State
 */

/**
 * 保存済み設定から有効な変換ルール一覧を返す。
 *
 * 保存されている値は管理画面用の親ルールID一覧。
 * rubymaco_get_transform_rules_for_rule_ids() 側で、
 * 親ルールIDに対応する transform_rules を展開して返す。
 *
 * @return array<int, array{
 *     id:string,
 *     type:string,
 *     pattern:string
 * }>
 */
function rubymaco_get_enabled_rules(): array {
	$enabled_rule_ids = get_option(
		RUBYMACO_Option_Key::ENABLED_MARKUP_RULES,
		rubymaco_get_default_enabled_rule_ids()
	);

	if ( ! is_array( $enabled_rule_ids ) ) {
		$enabled_rule_ids = rubymaco_get_default_enabled_rule_ids();
	}

	$enabled_rule_ids = array_values(
		array_filter(
			array_map( 'strval', $enabled_rule_ids ),
			static fn( string $rule_id ): bool => '' !== $rule_id
		)
	);

	return rubymaco_get_transform_rules_for_rule_ids( $enabled_rule_ids );
}

/**
 * Core Transform
 */

/**
 * ルールを定義順に本文へ適用する。
 *
 * @param string                                                      $content         変換対象本文.
 * @param array<int, array{ id:string, type:string, pattern:string }> $rules           適用する変換ルール一覧.
 * @param string|null                                                 $bouten_style    傍点スタイル。null の場合は保存済み設定を使う.
 * @param string|null                                                 $bouten_renderer 傍点描画方式。null の場合は保存済み設定を使う.
 * @return string 変換後の本文.
 */
function rubymaco_apply_markup_rules(
	string $content,
	array $rules,
	?string $bouten_style = null,
	?string $bouten_renderer = null
): string {
	if ( empty( $rules ) ) {
		return $content;
	}

	$bouten_style = RUBYMACO_Bouten_Style::normalize(
		$bouten_style ?? rubymaco_get_bouten_style()
	);

	$bouten_renderer = RUBYMACO_Bouten_Renderer::normalize(
		$bouten_renderer ?? rubymaco_get_bouten_renderer()
	);

	foreach ( $rules as $rule ) {
		$pattern = $rule['pattern'];
		if ( '' === $pattern ) {
			continue;
		}

		$type = $rule['type'];

		switch ( $type ) {
			case RUBYMACO_Rule_Type::RUBY:
				$content = preg_replace_callback(
					$pattern,
					fn( $matches ) => rubymaco_render_ruby(
						$matches[1] ?? '',
						$matches[2] ?? ''
					),
					$content
				) ?? $content;
				break;

			case RUBYMACO_Rule_Type::BOUTEN:
				$content = preg_replace_callback(
					$pattern,
					fn( $matches ) => rubymaco_render_bouten(
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
 * @param string $base_text 親文字.
 * @param string $ruby_text ルビ文字列.
 * @return string ルビ HTML
 */
function rubymaco_render_ruby( string $base_text, string $ruby_text ): string {
	$html = '<ruby class="rubymaco-ruby" data-rt="' .
		esc_attr( $ruby_text ) .
		'">' .
		esc_html( $base_text ) .
		'<rp>（</rp><rt>' .
		esc_html( $ruby_text ) .
		'</rt><rp>）</rp></ruby>';

		return wp_kses( $html, rubymaco_get_allowed_generated_html() );
}

/**
 * 傍点用 HTML を生成する。
 *
 * @param string $text 対象文字列.
 * @param string $bouten_style 傍点スタイル.
 * @param string $bouten_renderer 傍点描画方式.
 * @return string 傍点 HTML
 */
function rubymaco_render_bouten(
	string $text,
	string $bouten_style,
	?string $bouten_renderer = null
): string {
	$bouten_renderer ??= RUBYMACO_Bouten_Renderer::default();

	$bouten_style    = RUBYMACO_Bouten_Style::normalize( $bouten_style );
	$bouten_renderer = RUBYMACO_Bouten_Renderer::normalize( $bouten_renderer );

	return RUBYMACO_Bouten_Renderer::TEXT_EMPHASIS === $bouten_renderer
		? rubymaco_render_text_emphasis_bouten( $text, $bouten_style )
		: rubymaco_render_custom_bouten( $text, $bouten_style );
}

/**
 * 独自実装による傍点 HTML を生成する。
 *
 * @param string $text 対象文字列.
 * @param string $bouten_style 傍点スタイル.
 * @return string 傍点 HTML
 */
function rubymaco_render_custom_bouten( string $text, string $bouten_style ): string {
	$chars = rubymaco_split_chars( $text );
	$html  = '';

	foreach ( $chars as $char ) {
		$html .= '<span class="rubymaco-bouten__char">' .
			esc_html( $char ) .
			'</span>';
	}

	$html = '<span class="rubymaco-bouten rubymaco-bouten--custom rubymaco-bouten--' .
		esc_attr( $bouten_style ) .
		'">' .
		$html .
		'</span>';

		return wp_kses( $html, rubymaco_get_allowed_generated_html() );
}

/**
 * CSS text-emphasis による傍点 HTML を生成する。
 *
 * @param string $text 対象文字列.
 * @param string $bouten_style 傍点スタイル.
 * @return string 傍点 HTML
 */
function rubymaco_render_text_emphasis_bouten( string $text, string $bouten_style ): string {
	$html = '<span class="rubymaco-bouten rubymaco-bouten--text-emphasis rubymaco-bouten--' .
		esc_attr( $bouten_style ) .
		'">' .
		esc_html( $text ) .
		'</span>';

		return wp_kses( $html, rubymaco_get_allowed_generated_html() );
}

/**
 * プラグインが生成する HTML 断片で許可するタグと属性を返す。
 *
 * ルビ変換および傍点変換で組み立てた HTML を wp_kses() に通すための
 * 許可リストとして使用する。
 *
 * @return array<string, array<string, bool>>
 */
function rubymaco_get_allowed_generated_html(): array {
	return array(
		'ruby' => array(
			'class'   => true,
			'data-rt' => true,
		),
		'rt'   => array(),
		'rp'   => array(),
		'span' => array(
			'class' => true,
		),
	);
}

/**
 * 文字列を Unicode 文字単位で分割する。
 *
 * @param string $text 対象文字列.
 * @return array<int, string>
 */
function rubymaco_split_chars( string $text ): array {
	$chars = preg_split( '//u', $text, -1, PREG_SPLIT_NO_EMPTY );

	return false === $chars ? array( $text ) : $chars;
}
