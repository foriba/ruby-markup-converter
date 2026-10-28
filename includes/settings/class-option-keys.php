<?php
/**
 * 設定の保存キー一覧。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * WordPress の option 名を一元管理する。
 *
 * 設定値の取得・保存・検証や、初期値の定義は行わない。
 */
final class Option_Keys {
	/**
	 * 有効な記法ルール ID 一覧の保存キー。
	 *
	 * 互換性維持のため、option 名 'rubymaco_enabled_markup_rules' は変更不可。
	 */
	public const ENABLED_MARKUP_RULES = 'rubymaco_enabled_markup_rules';

	/**
	 * 傍点スタイルの保存キー。
	 *
	 * 互換性維持のため、option 名 'rubymaco_bouten_style' は変更不可。
	 */
	public const BOUTEN_STYLE = 'rubymaco_bouten_style';

	/**
	 * 変換モード（Conversion_Mode）の保存キー。
	 *
	 * 互換性維持のため、option 名 'rubymaco_apply_mode' は変更不可。
	 */
	public const CONVERSION_MODE = 'rubymaco_apply_mode';

	/**
	 * 傍点描画方式の保存キー。
	 *
	 * 互換性維持のため、option 名 'rubymaco_bouten_renderer' は変更不可。
	 */
	public const BOUTEN_RENDERING_METHOD = 'rubymaco_bouten_renderer';

	/**
	 * 定数と静的メソッドのみを使用するため、インスタンス化を禁止する。
	 */
	private function __construct() {}

	/**
	 * アンインストール時などに使用する保存キー一覧を返す。
	 *
	 * @return string[] 保存キーの一覧.
	 */
	public static function values(): array {
		return array(
			self::ENABLED_MARKUP_RULES,
			self::BOUTEN_STYLE,
			self::CONVERSION_MODE,
			self::BOUTEN_RENDERING_METHOD,
		);
	}
}
