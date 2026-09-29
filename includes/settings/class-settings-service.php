<?php
/**
 * 設定画面に提供する現在値の取得。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

use Foriba\RubyMarkupConverter\Markup\Rules\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Style_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Rendering_Method_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 保存値を表示・編集用の配列として提供する。
 *
 * 保存、フック登録、翻訳、通信は行わない。
 * 不正な保存値は読み取り時だけ補正し、データベースは変更しない。
 */
final class Settings_Service {
	/**
	 * 記法の取得元。
	 *
	 * @var Markup_Rule_Registry
	 */
	private Markup_Rule_Registry $markup_rule_registry;

	/**
	 * 適用範囲の取得元。
	 *
	 * @var Apply_Mode_Resolver
	 */
	private Apply_Mode_Resolver $apply_mode_resolver;

	/**
	 * 傍点スタイルの取得元。
	 *
	 * @var Bouten_Style_Resolver
	 */
	private Bouten_Style_Resolver $bouten_style_resolver;

	/**
	 * 傍点描画方法の取得元。
	 *
	 * @var Bouten_Rendering_Method_Resolver
	 */
	private Bouten_Rendering_Method_Resolver $bouten_rendering_method_resolver;

	/**
	 * 標準の依存オブジェクトで生成する。
	 *
	 * @return self 設定サービス.
	 */
	public static function create_default(): self {
		return new self(
			Markup_Rule_Registry::instance(),
			new Apply_Mode_Resolver(),
			new Bouten_Style_Resolver(),
			new Bouten_Rendering_Method_Resolver()
		);
	}

	/**
	 * 設定の取得に使用する依存オブジェクトを受け取る。
	 *
	 * @param Markup_Rule_Registry             $markup_rule_registry 記法の取得元.
	 * @param Apply_Mode_Resolver              $apply_mode_resolver 適用範囲の取得元.
	 * @param Bouten_Style_Resolver            $bouten_style_resolver 傍点スタイルの取得元.
	 * @param Bouten_Rendering_Method_Resolver $bouten_rendering_method_resolver 傍点描画方法の取得元.
	 */
	public function __construct(
		Markup_Rule_Registry $markup_rule_registry,
		Apply_Mode_Resolver $apply_mode_resolver,
		Bouten_Style_Resolver $bouten_style_resolver,
		Bouten_Rendering_Method_Resolver $bouten_rendering_method_resolver
	) {
		$this->markup_rule_registry             = $markup_rule_registry;
		$this->apply_mode_resolver              = $apply_mode_resolver;
		$this->bouten_style_resolver            = $bouten_style_resolver;
		$this->bouten_rendering_method_resolver = $bouten_rendering_method_resolver;
	}

	/**
	 * 現在の設定を取得する。
	 *
	 * 結果はキャッシュしない。返すキーは受け渡し用であり、保存キーではない。
	 * 記法の未保存・非配列は初期値、空配列は全解除として扱う。
	 * 非文字列・未登録 ID・重複を除き、レジストリ順の連番配列にする。
	 *
	 * @return array{
	 *     apply_mode:string,
	 *     enabled_rule_ids:string[],
	 *     bouten_style:string,
	 *     bouten_rendering_method:string
	 * } JSON 化できる設定値。settings の外側の包装は含まない.
	 */
	public function get(): array {
		$ids = get_option( Option_Keys::ENABLED_MARKUP_RULES, $this->markup_rule_registry->default_enabled_ids() );
		if ( ! is_array( $ids ) ) {
			$ids = $this->markup_rule_registry->default_enabled_ids();
		}
		$ids = array_filter( $ids, 'is_string' );

		return array(
			'apply_mode'              => $this->apply_mode_resolver->get()->get_value(),
			'enabled_rule_ids'        => array_values( array_intersect( $this->markup_rule_registry->ids(), $ids ) ),
			'bouten_style'            => $this->bouten_style_resolver->get()->get_value(),
			'bouten_rendering_method' => $this->bouten_rendering_method_resolver->get()->get_value(),
		);
	}
}
