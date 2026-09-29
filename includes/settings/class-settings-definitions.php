<?php
/**
 * 設定の選択肢と記法の表示情報。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

use Foriba\RubyMarkupConverter\Markup\Rules\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定表示に必要な定義を JSON 化できる配列で提供する。
 *
 * 現在値・保存処理・HTML・正規表現は含めない。
 * 表示文言は既存の定義を利用し、HTML エスケープは表示側に委ねる。
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
	 * 既存画面との移行期間は definitions.php を表示文言の共通の取得元とする。
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
		require_once __DIR__ . '/definitions.php';

		$markup_rules = array();
		foreach ( $this->markup_rule_registry->all() as $rule ) {
			$markup_rules[] = array(
				'id'          => $rule->id,
				'type'        => $rule->get_type()->get_value(),
				'titles'      => array_values( $rule->titles ),
				'description' => $rule->description,
				'examples'    => array_values( $rule->examples ),
			);
		}

		$rendering_methods = $this->prepare_choices( \rubymaco_get_bouten_renderer_definitions() );
		foreach ( $rendering_methods as $index => $method ) {
			if ( Bouten_Rendering_Method::CUSTOM === $method['value'] ) {
				$rendering_methods[ $index ]['description'] .= ' ' . __(
					'This compatibility option may be removed in a future version.',
					'ruby-markup-converter'
				);
			}
		}

		return array(
			'apply_modes'              => $this->prepare_choices( \rubymaco_get_apply_mode_definitions() ),
			'markup_rules'             => $markup_rules,
			'bouten_styles'            => $this->prepare_choices( \rubymaco_get_bouten_style_definitions() ),
			'bouten_rendering_methods' => $rendering_methods,
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
