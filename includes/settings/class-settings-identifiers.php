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
 * 設定画面と REST API で使用する識別子を管理する。
 */
final class Settings_Identifiers {
	/**
	 * 設定ページのスラッグ。
	 */
	public const PAGE_SLUG = 'rubymaco-settings';

	/**
	 * 設定画面へのアクセスに必要な権限。
	 *
	 * 変更する場合は、REST API の保存処理側の権限も確認すること。
	 */
	public const CAPABILITY = 'manage_options';

	/** 対象カテゴリを管理するための権限。 */
	public const CATEGORY_CAPABILITY = 'manage_categories';

	/**
	 * 定数のみを使用するため、インスタンス化を禁止する。
	 */
	private function __construct() {}
}
