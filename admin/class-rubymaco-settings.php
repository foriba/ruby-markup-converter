<?php
/**
 * 設定関連の識別子。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace foriba\rubymarkupconverter;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Settings API 関連の識別子を管理するクラス。
 */
final class RUBYMACO_Settings {

	/**
	 * WordPress Settings API で使用する設定グループ名。
	 */
	public const GROUP = 'rubymaco_settings';

	/**
	 * 設定ページのスラッグ。
	 */
	public const PAGE_SLUG = 'rubymaco-settings';

	/**
	 * インスタンス化を防ぐ。
	 */
	private function __construct() {}
}
