<?php
/**
 * ルビ・傍点記法の変換処理を行う。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use Foriba\RubyMarkupConverter\Settings\Bouten_Style_Setting;
use Foriba\RubyMarkupConverter\Markup\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Bouten_Rendering_Method;
use Foriba\RubyMarkupConverter\Markup\Markup_Renderer;
use Foriba\RubyMarkupConverter\Settings\Bouten_Style_Resolver;

/**
 * Markup transformation pipeline.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require_once __DIR__ . '/settings/class-bouten-style-setting.php';
require_once __DIR__ . '/markup/class-markup-renderer.php';

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
 * @param string|null                                                 $bouten_rendering_method 傍点描画方式。null の場合は保存済み設定を使う.
 * @return string 変換後の本文.
 */
function rubymaco_apply_markup_rules(
	string $content,
	array $rules,
	?string $bouten_style = null,
	?string $bouten_rendering_method = null
): string {
	if ( empty( $rules ) ) {
		return $content;
	}

	$style_setting = new Bouten_Style_Resolver();
	$bouten_style  = null === $bouten_style
		? $style_setting->get()
		: $style_setting->normalize( $bouten_style );

	$bouten_rendering_method = null === $bouten_rendering_method
		? rubymaco_get_bouten_renderer()
		: rubymaco_normalize_bouten_renderer( $bouten_rendering_method );

	foreach ( $rules as $rule ) {
		if ( '' === $rule['pattern'] ) {
			continue;
		}

		$content = rubymaco_transform_html_text( $content, $rule, $bouten_style, $bouten_rendering_method );
	}

	return $content;
}

/**
 * 周囲の HTML 構造を保ちながら、テキストノードに単一の変換ルールを適用する。
 *
 * @param string                                      $content                 HTML を含む本文.
 * @param array{id:string,type:string,pattern:string} $rule                    変換ルール.
 * @param Bouten_Style                                $bouten_style            傍点スタイル.
 * @param Bouten_Rendering_Method                     $bouten_rendering_method 傍点描画方式.
 * @return string 変換後の HTML.
 */
function rubymaco_transform_html_text(
	string $content,
	array $rule,
	Bouten_Style $bouten_style,
	Bouten_Rendering_Method $bouten_rendering_method
): string {
	$processor = WP_HTML_Processor::create_fragment( $content );
	if ( null === $processor ) {
		return $content;
	}

	$renderer = new Markup_Renderer();

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
			// HTML API がデコードしたテキストを再エスケープする際、文字参照そのものの表示を保つ.
			$html .= esc_html( str_replace( '&', '&amp;', substr( $text, $offset, $match[0][1] - $offset ) ) );
			if ( RUBYMACO_RULE_TYPE_RUBY === $rule['type'] ) {
				$html .= $renderer->render_ruby( $match[1][0], $match[2][0] );
			} else {
				$html .= $renderer->render_bouten( $match[1][0], $bouten_style, $bouten_rendering_method );
			}
			$offset = $match[0][1] + strlen( $match[0][0] );
		}
		$html .= esc_html( str_replace( '&', '&amp;', substr( $text, $offset ) ) );

		// HTML API は置換内容をテキストとしてエスケープするため、一時トークンを使い、後で生成した HTML に戻す.
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
