<?php
/**
 * ルビ・傍点記法の変換処理を行う。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

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
		RUBYMACO_OPTION_ENABLED_MARKUP_RULES,
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

	$bouten_style = rubymaco_normalize_bouten_style(
		$bouten_style ?? rubymaco_get_bouten_style()
	);

	$bouten_renderer = rubymaco_normalize_bouten_renderer(
		$bouten_renderer ?? rubymaco_get_bouten_renderer()
	);

	foreach ( $rules as $rule ) {
		if ( '' === $rule['pattern'] ) {
			continue;
		}

		$content = rubymaco_transform_html_text( $content, $rule, $bouten_style, $bouten_renderer );
	}

	return $content;
}

/**
 * Apply one rule to HTML text nodes while preserving surrounding HTML.
 *
 * @param string                                      $content HTML content.
 * @param array{id:string,type:string,pattern:string} $rule Markup rule.
 * @param string                                      $bouten_style Bouten style.
 * @param string                                      $bouten_renderer Bouten renderer.
 * @return string Updated HTML.
 */
function rubymaco_transform_html_text( string $content, array $rule, string $bouten_style, string $bouten_renderer ): string {
	$processor = WP_HTML_Processor::create_fragment( $content );
	if ( null === $processor ) {
		return $content;
	}

	$excluded_tags   = array( 'SCRIPT', 'STYLE', 'TEXTAREA', 'TITLE', 'CODE', 'PRE', 'RUBY', 'NOSCRIPT', 'TEMPLATE' );
	$protected_depth = null;
	$replacements    = array();
	$prefix          = 'rubymaco-html-token-';
	while ( false !== strpos( $content, $prefix ) ) {
		$prefix .= '_';
	}

	while ( $processor->next_token() ) {
		$depth = $processor->get_current_depth();
		if ( null !== $protected_depth ) {
			if ( $depth < $protected_depth || ( $processor->is_tag_closer() && $depth === $protected_depth ) ) {
				$protected_depth = null;
			} else {
				continue;
			}
		}
		if ( '#tag' === $processor->get_token_type() && ! $processor->is_tag_closer() && $processor->has_class( 'rubymaco-bouten' ) ) {
			$protected_depth = $depth;
			continue;
		}
		if ( '#text' !== $processor->get_token_type() || 'html' !== $processor->get_namespace()
			|| array_intersect( $excluded_tags, $processor->get_breadcrumbs() ) ) {
			continue;
		}

		$text = $processor->get_modifiable_text();
		if ( ! preg_match_all( $rule['pattern'], $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			continue;
		}
		$html   = '';
		$offset = 0;
		foreach ( $matches as $match ) {
			// HTML API text is decoded; preserve literal character references when encoding it again.
			$html  .= esc_html( str_replace( '&', '&amp;', substr( $text, $offset, $match[0][1] - $offset ) ) );
			$html  .= RUBYMACO_RULE_TYPE_RUBY === $rule['type']
				? rubymaco_render_ruby( str_replace( '&', '&amp;', $match[1][0] ), str_replace( '&', '&amp;', $match[2][0] ) )
				: rubymaco_render_bouten( str_replace( '&', '&amp;', $match[1][0] ), $bouten_style, $bouten_renderer );
			$offset = $match[0][1] + strlen( $match[0][0] );
		}
		$html .= esc_html( str_replace( '&', '&amp;', substr( $text, $offset ) ) );

		// The HTML API escapes replacements as text; restore only our generated HTML afterward.
		$token = $prefix . count( $replacements ) . '-end';
		if ( $processor->set_modifiable_text( $token ) ) {
			$replacements[ $token ] = $html;
		}
	}

	if ( null !== $processor->get_last_error() ) {
		return $content;
	}
	return strtr( $processor->get_updated_html(), $replacements );
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
	string $bouten_renderer = RUBYMACO_DEFAULT_BOUTEN_RENDERER
): string {
	$bouten_style    = rubymaco_normalize_bouten_style( $bouten_style );
	$bouten_renderer = rubymaco_normalize_bouten_renderer( $bouten_renderer );

	return RUBYMACO_BOUTEN_RENDERER_TEXT_EMPHASIS === $bouten_renderer
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
