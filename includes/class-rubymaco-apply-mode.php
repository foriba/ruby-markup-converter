<?php
/**
 * 適用モードを定義する enum 風クラス。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace foriba\rubymarkupconverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 適用モードを表す enum 風クラス。
 */
final class RUBYMACO_Apply_Mode {
	/**
	 * ショートコード内のみを変換対象にする適用モード。
	 */
	public const SHORTCODE = 'shortcode';

	/**
	 * 投稿本文全体を変換対象にする適用モード。
	 */
	public const ALL = 'all';

	/**
	 * デフォルトの適用モードを返す。
	 *
	 * @return string デフォルトの適用モードID。
	 */
	public static function default(): string {
		return self::SHORTCODE;
	}

	/**
	 * 適用モードの定義一覧を返す。
	 *
	 * 配列キーは保存値として使用する適用モードID。
	 *
	 * @return array<string, array{label:string, description:string}> 適用モードの定義一覧。
	 */
	public static function definitions(): array {
		return array(
			self::SHORTCODE => array(
				'label'       => __( 'Apply Only Within Shortcodes', 'ruby-markup-converter' ),
				'description' => __(
					'Converts markup only within [rubymaco]...[/rubymaco] blocks. This helps prevent unintended conversions and reduces processing overhead.',
					'ruby-markup-converter'
				),
			),
			self::ALL       => array(
				'label'       => __( 'Apply to Entire Post Content', 'ruby-markup-converter' ),
				'description' => __(
					'Automatically converts markup throughout the post content without requiring shortcodes. This is more convenient, but unintended text may also be converted.',
					'ruby-markup-converter'
				),
			),
		);
	}

	/**
	 * 許可されている適用モードID一覧を返す。
	 *
	 * @return string[] 適用モードID一覧。
	 */
	public static function values(): array {
		return array(
			self::SHORTCODE,
			self::ALL,
		);
	}

	/**
	 * 適用モードIDを正規化する。
	 *
	 * 未定義の適用モードIDが渡された場合はデフォルト値を返す。
	 *
	 * @param string $apply_mode 適用モードID。.
	 * @return string 正規化済みの適用モードID。
	 */
	public static function normalize( string $apply_mode ): string {
		return in_array( $apply_mode, self::values(), true )
			? $apply_mode
			: self::default();
	}

	/**
	 * 投稿本文全体に適用するモードかどうかを判定する。
	 *
	 * @param string $apply_mode 適用モードID。.
	 * @return bool 投稿本文全体に適用する場合は true。
	 */
	public static function is_all( string $apply_mode ): bool {
		return self::normalize( $apply_mode ) === self::ALL;
	}
}
