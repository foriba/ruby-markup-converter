<?php
/**
 * 設定とルールを組み合わせる記法変換サービス。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Service;

use Foriba\RubyMarkupConverter\Settings\Option_Keys;

use Foriba\RubyMarkupConverter\Markup\Rules\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Markup\Markup_Transformer;
use Foriba\RubyMarkupConverter\Markup\Markup_Renderer;
use Foriba\RubyMarkupConverter\Markup\Rules\Transform_Rule;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Style_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Rendering_Method_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 保存設定から変換条件を揃え、変換本体へ渡す。
 *
 * HTML 走査やエスケープ、フック登録、適用範囲の判定は担当しない。
 * 使用前に WordPress、設定キー、依存クラスを読み込むこと。
 */
final class Markup_Conversion_Service {
	/**
	 * プラグイン標準の依存オブジェクトでサービスを生成する。
	 *
	 * 保存設定はここでは取得せず、convert() の呼び出し時に取得する。
	 *
	 * @return self 標準の変換サービス.
	 */
	public static function create_default(): self {
		return new self(
			Markup_Rule_Registry::instance(),
			new Markup_Transformer( new Markup_Renderer() ),
			new Bouten_Style_Resolver(),
			new Bouten_Rendering_Method_Resolver()
		);
	}

	/**
	 * ルールの取得元。
	 *
	 * @var Markup_Rule_Registry
	 */
	private Markup_Rule_Registry $registry;

	/**
	 * 変換本体。
	 *
	 * @var Markup_Transformer
	 */
	private Markup_Transformer $transformer;

	/**
	 * スタイル設定の取得元。
	 *
	 * @var Bouten_Style_Resolver
	 */
	private Bouten_Style_Resolver $style_resolver;

	/**
	 * 描画方式設定の取得元。
	 *
	 * @var Bouten_Rendering_Method_Resolver
	 */
	private Bouten_Rendering_Method_Resolver $method_resolver;

	/**
	 * 変換に必要な依存オブジェクトを受け取る。
	 *
	 * @param Markup_Rule_Registry             $registry        ルールの取得元.
	 * @param Markup_Transformer               $transformer     変換本体.
	 * @param Bouten_Style_Resolver            $style_resolver  スタイル設定の取得元.
	 * @param Bouten_Rendering_Method_Resolver $method_resolver 描画方式設定の取得元.
	 */
	public function __construct(
		Markup_Rule_Registry $registry,
		Markup_Transformer $transformer,
		Bouten_Style_Resolver $style_resolver,
		Bouten_Rendering_Method_Resolver $method_resolver
	) {
		$this->registry        = $registry;
		$this->transformer     = $transformer;
		$this->style_resolver  = $style_resolver;
		$this->method_resolver = $method_resolver;
	}

	/**
	 * 保存済みの有効ルールと描画設定で本文を変換する。
	 *
	 * 未保存・配列以外の設定には初期ルールを使う。空配列は全解除として保持する。
	 * 配列内の文字列以外の要素と未登録 ID は無視する。保存値は変更しない。
	 *
	 * @param string $content HTML を含む本文.
	 * @return string 変換後の本文.
	 */
	public function convert( string $content ): string {
		$ids = get_option( Option_Keys::ENABLED_MARKUP_RULES, $this->registry->default_enabled_ids() );
		if ( ! is_array( $ids ) ) {
			$ids = $this->registry->default_enabled_ids();
		}
		$ids   = array_values( array_filter( $ids, 'is_string' ) );
		$rules = $this->registry->transform_rules_for( $ids );
		if ( array() === $rules ) {
			return $content;
		}

		return $this->convert_with_rules(
			$content,
			$rules,
			$this->style_resolver->get(),
			$this->method_resolver->get()
		);
	}

	/**
	 * 明示された条件で変換する。プレビューなどに使用し、保存設定は取得しない。
	 *
	 * @param string                  $content                 HTML を含む本文.
	 * @param Transform_Rule[]        $rules                   適用順の変換ルール一覧.
	 * @param Bouten_Style            $bouten_style            傍点スタイル.
	 * @param Bouten_Rendering_Method $bouten_rendering_method 傍点描画方式.
	 * @return string 変換後の本文.
	 */
	public function convert_with_rules(
		string $content,
		array $rules,
		Bouten_Style $bouten_style,
		Bouten_Rendering_Method $bouten_rendering_method
	): string {
		return $this->transformer->transform( $content, $rules, $bouten_style, $bouten_rendering_method );
	}
}
