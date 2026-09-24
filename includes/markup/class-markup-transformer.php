<?php
/**
 * HTML 構造を保つ記法変換。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Markup;

use Foriba\RubyMarkupConverter\Markup\Rules\Transform_Rule;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;
use Foriba\RubyMarkupConverter\Markup\Value\Rule_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 呼び出し側が選択したルールを順番に適用する。
 *
 * 設定取得は行わず、HTML 出力のエスケープはレンダラーに委譲する。
 * WordPress HTML API と関連する値クラスは使用前に読み込むこと。
 */
final class Markup_Transformer {
	/**
	 * 記法変換から除外する要素の集合。
	 *
	 * @var array<string, bool>
	 */
	private const EXCLUDED_TAGS = array(
		'SCRIPT'   => true,
		'STYLE'    => true,
		'TEXTAREA' => true,
		'TITLE'    => true,
		'CODE'     => true,
		'PRE'      => true,
		'RUBY'     => true,
		'NOSCRIPT' => true,
		'TEMPLATE' => true,
	);

	/**
	 * HTML の生成担当。
	 *
	 * @var Markup_Renderer
	 */
	private Markup_Renderer $renderer;

	/**
	 * 描画担当を受け取る。
	 *
	 * @param Markup_Renderer $renderer HTML の生成担当.
	 */
	public function __construct( Markup_Renderer $renderer ) {
		$this->renderer = $renderer;
	}

	/**
	 * ルール一覧を与えられた順序で適用する。
	 *
	 * 正規表現とキャプチャ構造は検証済みの内部定義を前提とする。
	 *
	 * @param string                  $content HTML を含む本文.
	 * @param Transform_Rule[]        $rules 適用するルール一覧.
	 * @param Bouten_Style            $bouten_style 傍点スタイル.
	 * @param Bouten_Rendering_Method $bouten_rendering_method 傍点描画方式.
	 * @return string 変換後の本文.
	 */
	public function transform(
		string $content,
		array $rules,
		Bouten_Style $bouten_style,
		Bouten_Rendering_Method $bouten_rendering_method
	): string {
		foreach ( $rules as $rule ) {
			if ( '' === $rule->get_pattern() ) {
				continue;
			}
			$content = $this->transform_html( $content, $rule, $bouten_style, $bouten_rendering_method );
		}

		return $content;
	}

	/**
	 * HTML の保護対象を除外し、単一ルールを適用する。
	 *
	 * HTML API が処理を完了できない場合は、このルール適用前の本文を返す。
	 *
	 * @param string                  $content HTML を含む本文.
	 * @param Transform_Rule          $rule 適用するルール.
	 * @param Bouten_Style            $bouten_style 傍点スタイル.
	 * @param Bouten_Rendering_Method $bouten_rendering_method 傍点描画方式.
	 * @return string 変換後の本文.
	 */
	private function transform_html(
		string $content,
		Transform_Rule $rule,
		Bouten_Style $bouten_style,
		Bouten_Rendering_Method $bouten_rendering_method
	): string {
		$processor = \WP_HTML_Processor::create_fragment( $content );
		if ( null === $processor ) {
			return $content;
		}

		$protected_depth = null;
		$replacements    = array();
		$prefix          = 'rubymaco-html-token-';
		while ( str_contains( $content, $prefix ) ) {
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
				|| $this->is_excluded( $processor->get_breadcrumbs() ) ) {
				continue;
			}

			$html = $this->transform_text( $processor->get_modifiable_text(), $rule, $bouten_style, $bouten_rendering_method );
			if ( null === $html ) {
				continue;
			}

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

	/**
	 * 現在位置の要素階層に除外対象が含まれるか判定する。
	 *
	 * @param string[] $breadcrumbs HTML API が返す要素階層.
	 * @return bool 除外対象が含まれる場合に true.
	 */
	private function is_excluded( array $breadcrumbs ): bool {
		foreach ( $breadcrumbs as $tag ) {
			if ( isset( self::EXCLUDED_TAGS[ $tag ] ) ) {
				return true;
			}
		}

		return false;
	}

	/**
	 * デコード済みテキストの記法を HTML に変換する。
	 *
	 * @param string                  $text デコード済みテキスト.
	 * @param Transform_Rule          $rule 適用するルール.
	 * @param Bouten_Style            $bouten_style 傍点スタイル.
	 * @param Bouten_Rendering_Method $bouten_rendering_method 傍点描画方式.
	 * @return string|null 生成した HTML。未一致または正規表現エラー時は null.
	 */
	private function transform_text(
		string $text,
		Transform_Rule $rule,
		Bouten_Style $bouten_style,
		Bouten_Rendering_Method $bouten_rendering_method
	): ?string {
		if ( ! preg_match_all( $rule->get_pattern(), $text, $matches, PREG_SET_ORDER | PREG_OFFSET_CAPTURE ) ) {
			return null;
		}
		$is_ruby = $rule->get_type()->equals( Rule_Type::ruby() );
		$html    = '';
		$offset  = 0;
		foreach ( $matches as $match ) {
			// HTML API がデコードしたテキストを再エスケープする際、文字参照そのものの表示を保つ.
			$html .= $this->renderer->render_text( substr( $text, $offset, $match[0][1] - $offset ) );
			if ( $is_ruby ) {
				$html .= $this->renderer->render_ruby( $match[1][0], $match[2][0] );
			} else {
				$html .= $this->renderer->render_bouten( $match[1][0], $bouten_style, $bouten_rendering_method );
			}
			$offset = $match[0][1] + strlen( $match[0][0] );
		}
		$html .= $this->renderer->render_text( substr( $text, $offset ) );

		return $html;
	}
}
