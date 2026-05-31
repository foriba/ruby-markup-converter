<?php
/**
 * Ruby Markup Converterで使用する共通定数と設定定義を管理する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
/**
 * Settings API
 */

/**
 * WordPress Settings API で使用する設定グループ名。
 */
const RUBYMACO_SETTINGS_GROUP = 'rubymaco_settings';

/**
 * 設定ページのスラッグ。
 */
const RUBYMACO_SETTINGS_PAGE_SLUG = 'rubymaco-settings';
