<?php
/**
 * 設定画面の初期データの組み立て。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Settings\Settings_Service;
use Foriba\RubyMarkupConverter\Settings\Settings_Definitions;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定・表示定義・リンク・共通文言をまとめて提供する。
 *
 * 保存、フック登録、HTML 出力、REST 応答、権限確認は行わない。
 * 認可とユーザー言語の準備は呼び出し側が担当する。
 * 文字列は表示側でエスケープする。定義の翻訳キャッシュは取得元に従う。
 */
final class Settings_Page_Data {
	/**
	 * 設定の取得元。
	 *
	 * @var Settings_Service
	 */
	private Settings_Service $settings_service;

	/**
	 * 表示定義の取得元。
	 *
	 * @var Settings_Definitions
	 */
	private Settings_Definitions $settings_definitions;

	/**
	 * 標準の依存オブジェクトで生成する。
	 *
	 * @return self 初期データの取得担当.
	 */
	public static function create_default(): self {
		return new self( Settings_Service::create_default(), Settings_Definitions::create_default() );
	}

	/**
	 * 設定値と定義の取得元を受け取る。
	 *
	 * @param Settings_Service     $settings_service 設定の取得元.
	 * @param Settings_Definitions $settings_definitions 表示定義の取得元.
	 */
	public function __construct( Settings_Service $settings_service, Settings_Definitions $settings_definitions ) {
		$this->settings_service     = $settings_service;
		$this->settings_definitions = $settings_definitions;
	}

	/**
	 * JSON 化できる初期データを返す。取得結果をキャッシュしない。
	 *
	 * @return array{
	 *     settings:array<string, mixed>,
	 *     definitions:array<string, mixed>,
	 *     links:array{user_guide:string},
	 *     messages:array<string, string>
	 * } 設定画面の初期データ.
	 */
	public function get(): array {
		return array(
			'settings'    => $this->settings_service->get(),
			'definitions' => $this->settings_definitions->get(),
			'links'       => array(
				// translators: URL of the user guide for the current language.
				'user_guide' => _x( 'https://github.com/foriba/ruby-markup-converter/blob/main/docs/en-US/user-guide.md', 'user guide URL', 'ruby-markup-converter' ),
			),
			'messages'    => $this->get_messages(),
		);
	}

	/**
	 * 画面共通の文言を取得時の言語で返す。
	 *
	 * 選択肢の説明は definitions、個別のエラー内容は将来の API 応答で扱う。
	 *
	 * @return array<string, string> HTML を含まない画面文言.
	 */
	private function get_messages(): array {
		return array(
			'page_title'              => __( 'Ruby Markup Converter', 'ruby-markup-converter' ),
			'basic_tab'               => __( 'Basic Settings', 'ruby-markup-converter' ),
			'compatibility_tab'       => __( 'Compatibility Settings', 'ruby-markup-converter' ),
			'conversion_scope'        => __( 'Conversion Scope', 'ruby-markup-converter' ),
			'markup_rules'            => __( 'Markup Rules', 'ruby-markup-converter' ),
			'ruby'                    => __( 'Ruby', 'ruby-markup-converter' ),
			'bouten'                  => __( 'Bouten', 'ruby-markup-converter' ),
			'bouten_style'            => __( 'Bouten Style', 'ruby-markup-converter' ),
			'bouten_rendering_method' => __( 'Bouten Rendering Method', 'ruby-markup-converter' ),
			'markup_example'          => __( 'Markup Example', 'ruby-markup-converter' ),
			'output_result'           => __( 'Output Result', 'ruby-markup-converter' ),
			'user_guide'              => __( 'Ruby Markup Converter User Guide', 'ruby-markup-converter' ),
			'opens_in_new_tab'        => __( '(opens in a new tab)', 'ruby-markup-converter' ),
			'save'                    => __( 'Save Settings', 'ruby-markup-converter' ),
			'saving'                  => __( 'Saving settings…', 'ruby-markup-converter' ),
			'saved'                   => __( 'Settings saved.', 'ruby-markup-converter' ),
			'save_failed'             => __( 'Unable to save settings. Some settings may already have been saved. Please try again.', 'ruby-markup-converter' ),
			'loading'                 => __( 'Loading settings…', 'ruby-markup-converter' ),
			'load_failed'             => __( 'Unable to load settings.', 'ruby-markup-converter' ),
			'retry'                   => __( 'Try Again', 'ruby-markup-converter' ),
			'unsaved_changes'         => __( 'You have unsaved changes.', 'ruby-markup-converter' ),
			'preview_loading'         => __( 'Loading preview…', 'ruby-markup-converter' ),
			'preview_failed'          => __( 'Unable to load preview.', 'ruby-markup-converter' ),
		);
	}
}
