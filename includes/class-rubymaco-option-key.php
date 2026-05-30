<?php
/**
 * Option Key を定義する enum 風クラス。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace foriba\rubymarkupconverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Option Key を定義する enum 風クラス。
 */
final class RUBYMACO_Option_Key {
	/**
	 * 有効化されている記法ルールID一覧を保存する option 名。
	 */
	public const ENABLED_MARKUP_RULES = 'rubymaco_enabled_markup_rules';

	/**
	 * 傍点スタイルを保存する option 名。
	 */
	public const BOUTEN_STYLE = 'rubymaco_bouten_style';

	/**
	 * 傍点描画方式を保存する option 名。
	 */
	public const BOUTEN_RENDERER = 'rubymaco_bouten_renderer';

	/**
	 * 適用モードを保存する option 名。
	 */
	public const APPLY_MODE = 'rubymaco_apply_mode';

	/**
	 * Option Key 一覧を返す。
	 *
	 * @return string[]
	 */
	public static function values(): array {
		return array(
			self::ENABLED_MARKUP_RULES,
			self::BOUTEN_STYLE,
			self::BOUTEN_RENDERER,
			self::APPLY_MODE,
		);
	}
}
