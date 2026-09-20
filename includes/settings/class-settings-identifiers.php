<?php
/**
 * 設定画面の識別子と必要権限。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定登録と設定画面で共有する識別子を管理する。
 */
final class Settings_Identifiers {
	/**
	 * WordPress Settings API の設定グループ名。
	 */
	public const GROUP = 'rubymaco_settings';

	/**
	 * 設定ページのスラッグ。
	 */
	public const PAGE_SLUG = 'rubymaco-settings';

	/**
	 * 設定画面へのアクセスに必要な権限。
	 *
	 * 変更する場合は、Settings API の保存処理側の権限も確認すること。
	 */
	public const CAPABILITY = 'manage_options';

	/**
	 * 定数のみを使用するため、インスタンス化を禁止する。
	 */
	private function __construct() {}
}
