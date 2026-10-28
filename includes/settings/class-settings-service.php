<?php
/**
 * 設定値の取得・検証・保存。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

use Foriba\RubyMarkupConverter\Markup\Rules\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Resolver\Conversion_Mode_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Style_Resolver;
use Foriba\RubyMarkupConverter\Resolver\Bouten_Rendering_Method_Resolver;
use Foriba\RubyMarkupConverter\Markup\Value\Conversion_Mode;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 保存値を表示・編集用の配列として提供する。
 *
 * フック登録・通信・権限確認は呼び出し側が担当する。
 * 読み取り時の不正値は補正するが、保存要求の不正値は拒否する。
 *
 * @phpstan-type Settings array{apply_mode:string, enabled_rule_ids:string[], bouten_style:string, bouten_rendering_method:string}
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
	 * @var Conversion_Mode_Resolver
	 */
	private Conversion_Mode_Resolver $conversion_mode_resolver;

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
			new Conversion_Mode_Resolver(),
			new Bouten_Style_Resolver(),
			new Bouten_Rendering_Method_Resolver()
		);
	}

	/**
	 * 設定の取得に使用する依存オブジェクトを受け取る。
	 *
	 * @param Markup_Rule_Registry             $markup_rule_registry 記法の取得元.
	 * @param Conversion_Mode_Resolver         $conversion_mode_resolver 適用範囲の取得元.
	 * @param Bouten_Style_Resolver            $bouten_style_resolver 傍点スタイルの取得元.
	 * @param Bouten_Rendering_Method_Resolver $bouten_rendering_method_resolver 傍点描画方法の取得元.
	 */
	public function __construct(
		Markup_Rule_Registry $markup_rule_registry,
		Conversion_Mode_Resolver $conversion_mode_resolver,
		Bouten_Style_Resolver $bouten_style_resolver,
		Bouten_Rendering_Method_Resolver $bouten_rendering_method_resolver
	) {
		$this->markup_rule_registry             = $markup_rule_registry;
		$this->conversion_mode_resolver         = $conversion_mode_resolver;
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
			'apply_mode'              => $this->conversion_mode_resolver->get()->get_value(),
			'enabled_rule_ids'        => array_values( array_intersect( $this->markup_rule_registry->ids(), $ids ) ),
			'bouten_style'            => $this->bouten_style_resolver->get()->get_value(),
			'bouten_rendering_method' => $this->bouten_rendering_method_resolver->get()->get_value(),
		);
	}

	/**
	 * 全設定の保存要求を検証する。保存や初期値への補正は行わない。
	 *
	 * JSON デコード済みの配列を要求する。スラッシュ除去は行わない。
	 * 記法 ID は重複を除去し、定義順に揃える。空配列は全解除を表す。
	 *
	 * @param array<string, mixed> $settings 保存要求.
	 * @return array<string, mixed> 検証済み設定.
	 * @phpstan-return Settings
	 * @throws \InvalidArgumentException 欠落・未知の項目、型違い、不正値の場合.
	 */
	public function validate( array $settings ): array {
		$fields = array( 'apply_mode', 'enabled_rule_ids', 'bouten_style', 'bouten_rendering_method' );
		if ( array_diff( $fields, array_keys( $settings ) ) || array_diff( array_keys( $settings ), $fields ) ) {
			throw new \InvalidArgumentException( 'Settings must contain exactly the four supported fields.' );
		}
		foreach ( array(
			'apply_mode'              => Conversion_Mode::values(),
			'bouten_style'            => Bouten_Style::values(),
			'bouten_rendering_method' => Bouten_Rendering_Method::values(),
		) as $field => $allowed ) {
			if ( ! is_string( $settings[ $field ] ) || ! in_array( $settings[ $field ], $allowed, true ) ) {
				throw new \InvalidArgumentException( 'Invalid setting value.' );
			}
		}
		$ids = $settings['enabled_rule_ids'];
		if ( ! is_array( $ids ) || array_values( $ids ) !== $ids ) {
			throw new \InvalidArgumentException( 'Enabled rule IDs must be a list.' );
		}
		$allowed_ids = $this->markup_rule_registry->ids();
		foreach ( $ids as $id ) {
			if ( ! is_string( $id ) || ! in_array( $id, $allowed_ids, true ) ) {
				throw new \InvalidArgumentException( 'Unknown or invalid rule ID.' );
			}
		}
		return array(
			'apply_mode'              => $settings['apply_mode'],
			'enabled_rule_ids'        => array_values( array_intersect( $allowed_ids, $ids ) ),
			'bouten_style'            => $settings['bouten_style'],
			'bouten_rendering_method' => $settings['bouten_rendering_method'],
		);
	}

	/**
	 * 全項目を検証してから既存の保存キーへ書き込む。
	 *
	 * 呼び出し側で権限・nonce を確認すること。未変更も成功として扱う。
	 * 書き込み後の値を確認し、不一致なら中断する。複数キーの保存は非原子的で、
	 * 途中失敗時には先行項目が保存済みの場合がある。ロールバックは行わない。
	 *
	 * @param array<string, mixed> $settings 保存要求.
	 * @return array<string, mixed> 保存後の設定.
	 * @phpstan-return Settings
	 * @throws \InvalidArgumentException|\RuntimeException 検証失敗（書き込みなし）、または保存失敗の場合.
	 */
	public function save( array $settings ): array {
		$validated = $this->validate( $settings );
		$keys      = array(
			'apply_mode'              => Option_Keys::CONVERSION_MODE,
			'enabled_rule_ids'        => Option_Keys::ENABLED_MARKUP_RULES,
			'bouten_style'            => Option_Keys::BOUTEN_STYLE,
			'bouten_rendering_method' => Option_Keys::BOUTEN_RENDERING_METHOD,
		);
		foreach ( $keys as $field => $key ) {
			update_option( $key, $validated[ $field ] );
			if ( get_option( $key ) !== $validated[ $field ] ) {
				throw new \RuntimeException( 'Unable to save settings. Some settings may already have been saved.' );
			}
		}
		return $this->get();
	}
}
