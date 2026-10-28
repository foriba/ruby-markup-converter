<?php
/**
 * 設定の選択肢と記法の表示情報。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

use Foriba\RubyMarkupConverter\Markup\Rules\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Markup\Value\Conversion_Mode;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定表示に必要な定義を JSON 化できる配列で提供する。
 *
 * 現在値・保存処理・HTML・正規表現は含めない。
 * 選択肢の表示文言をここで定義し、HTML エスケープは表示側に委ねる。
 * このクラスでは翻訳結果をキャッシュしない。レジストリの翻訳済み文言は
 * レジストリの寿命に従うため、ロケール変更時はレジストリも再生成すること。
 */
final class Settings_Definitions {
	/**
	 * 記法の取得元。
	 *
	 * @var Markup_Rule_Registry
	 */
	private Markup_Rule_Registry $markup_rule_registry;

	/**
	 * 標準のレジストリで生成する。
	 *
	 * @return self 表示定義の取得担当.
	 */
	public static function create_default(): self {
		return new self( Markup_Rule_Registry::instance() );
	}

	/**
	 * 記法の取得元を受け取る。
	 *
	 * @param Markup_Rule_Registry $markup_rule_registry 記法の取得元.
	 */
	public function __construct( Markup_Rule_Registry $markup_rule_registry ) {
		$this->markup_rule_registry = $markup_rule_registry;
	}

	/**
	 * 選択肢と記法を定義順の一覧で返す。
	 *
	 * 管理画面の起動処理に依存せず、REST リクエストでも呼び出せる。
	 *
	 * @return array{
	 *     apply_modes:array<int, array{value:string, label:string, description:string}>,
	 *     markup_rules:array<int, array{id:string, type:string, titles:string[], description:string, examples:string[]}>,
	 *     bouten_styles:array<int, array{value:string, label:string, description:string}>,
	 *     bouten_rendering_methods:array<int, array{value:string, label:string, description:string}>
	 * } definitions の中身。文言は取得時の言語に従う.
	 */
	public function get(): array {
		$markup_rules = array();
		foreach ( $this->markup_rule_registry->all() as $rule ) {
			$markup_rules[] = array(
				'id'          => $rule->get_id(),
				'type'        => $rule->get_type()->get_value(),
				'titles'      => $rule->get_titles(),
				'description' => $rule->get_description(),
				'examples'    => $rule->get_examples(),
			);
		}

		$rendering_methods = $this->prepare_choices( $this->get_bouten_rendering_methods() );
		foreach ( $rendering_methods as $index => $method ) {
			if ( Bouten_Rendering_Method::CUSTOM === $method['value'] ) {
				$rendering_methods[ $index ]['description'] .= ' ' . __(
					'This compatibility option may be removed in a future version.',
					'ruby-markup-converter'
				);
			}
		}

		return array(
			'apply_modes'              => $this->prepare_choices( $this->get_apply_modes() ),
			'markup_rules'             => $markup_rules,
			'bouten_styles'            => $this->prepare_choices( $this->get_bouten_styles() ),
			'bouten_rendering_methods' => $rendering_methods,
		);
	}

	/**
	 * 変換モードの定義一覧を返す。
	 *
	 * 配列キーは保存値として使用する変換モードID。
	 *
	 * @return array<string, array{
	 *     label:string,
	 *     description:string
	 * }>
	 */
	public function get_apply_modes(): array {
		return array(
			Conversion_Mode::SELECTED => array(
				'label'       => __( 'Apply Only Within Selected Areas', 'ruby-markup-converter' ), // ja-jp: '指定範囲内のみ適用'.
				'description' => __(
					'Converts the full text of posts in categories selected on the category screen, and of posts and pages set to convert their full text in the editor. Markup in Ruby Markup Converter blocks and [rubymaco] shortcodes is also converted. See the user guide for details.',
					// ja-jp: 'カテゴリー画面で選択したカテゴリーの投稿と、編集画面で本文全体の変換を選んだ投稿・固定ページは、本文全体を変換します。Ruby Markup Converter ブロックと [rubymaco] ショートコード内の記法も変換されます。詳しくはユーザーガイドをご覧ください。'.
					'ruby-markup-converter'
				),
			),
			Conversion_Mode::ALL      => array(
				'label'       => __( 'Apply to Entire Post Content', 'ruby-markup-converter' ), // ja-jp: '投稿本文全体に適用'.
				'description' => __(
					'Converts markup throughout all posts and pages, except those set not to be converted. This is convenient, but unintended text may also be converted and long posts may take more processing.',
					// ja-jp: '変換しないを選んだものを除き、すべての投稿・固定ページで本文全体の記法を変換します。手軽ですが、意図しない箇所まで変換されたり、長い投稿では処理が増えたりする場合があります。'.
					'ruby-markup-converter'
				),
			),
		);
	}

	/**
	 * 傍点スタイルの定義一覧を返す。
	 *
	 * 配列キーは保存値として使用する傍点スタイルID。
	 *
	 * @return array<string, array{
	 *     label:string,
	 * }>
	 */
	public function get_bouten_styles(): array {
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
	 * 傍点描画方式の定義一覧を返す。
	 *
	 * 配列キーは保存値として使用する傍点描画方式ID。
	 *
	 * @return array<string, array{
	 *     label:string,
	 *     description:string
	 * }>
	 */
	public function get_bouten_rendering_methods(): array {
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

	/**
	 * 保存値をキーとした定義を、統一した項目を持つ連番配列にする。
	 *
	 * @param array<string, array{label:string, description?:string}> $definitions 表示定義.
	 * @return array<int, array{value:string, label:string, description:string}> 表示順の選択肢.
	 */
	private function prepare_choices( array $definitions ): array {
		$choices = array();
		foreach ( $definitions as $value => $definition ) {
			$choices[] = array(
				'value'       => $value,
				'label'       => $definition['label'],
				'description' => $definition['description'] ?? '',
			);
		}
		return $choices;
	}
}
