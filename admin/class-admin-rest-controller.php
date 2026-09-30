<?php
/**
 * 設定の取得・保存 REST API。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Settings\Settings_Service;
use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 管理者向け設定 API。Cookie の REST nonce 検証は WordPress に委譲する。
 *
 * レスポンスの翻訳言語は WordPress の現在のロケールに従う。
 * 管理画面のクライアントは _locale=user を指定する。
 */
final class Admin_REST_Controller {
	/**
	 * 現在値と保存処理。
	 *
	 * @var Settings_Service
	 */
	private Settings_Service $settings_service;

	/**
	 * 初期データ。
	 *
	 * @var Admin_Page_Data
	 */
	private Admin_Page_Data $admin_page_data;

	/**
	 * 依存オブジェクトを受け取る。登録は行わない。
	 *
	 * @param Settings_Service $settings_service 設定サービス.
	 * @param Admin_Page_Data  $admin_page_data 初期データ.
	 */
	public function __construct(
		Settings_Service $settings_service,
		Admin_Page_Data $admin_page_data
	) {
		$this->settings_service = $settings_service;
		$this->admin_page_data  = $admin_page_data;
	}

	/**
	 * REST 起動時にルート登録を予約する。
	 */
	public function register_hooks(): void {
		add_action(
			'rest_api_init',
			array( $this, 'register_routes' )
		);
	}

	/**
	 * 管理画面かどうかに依存せずルートを登録する。
	 */
	public function register_routes(): void {
		register_rest_route(
			'rubymaco/v1',
			'/settings',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( $this, 'get_settings' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( $this, 'save_settings' ),
					'permission_callback' => array( $this, 'check_permissions' ),
				),
			)
		);
	}

	/**
	 * 両操作に管理権限を要求する。
	 *
	 * @return true|\WP_Error 許可または 401/403 エラー.
	 */
	public function check_permissions() {
		if ( current_user_can( Settings_Identifiers::CAPABILITY ) ) {
			return true;
		}
		return new \WP_Error(
			'rubymaco_forbidden',
			__(
				'You do not have permission to manage these settings.',
				'ruby-markup-converter'
			),
			array( 'status' => rest_authorization_required_code() )
		);
	}

	/**
	 * 設定画面の初期データを返す。
	 *
	 * @return \WP_REST_Response 初期データ.
	 */
	public function get_settings(): \WP_REST_Response {
		return new \WP_REST_Response(
			$this->admin_page_data->get(),
			200,
			array( 'Cache-Control' => 'no-store' )
		);
	}

	/**
	 * JSON 本文の4設定を保存する。クエリやフォーム値とは混合しない。
	 *
	 * JSON オブジェクトと配列を区別し、空オブジェクトを全解除として扱わない。
	 *
	 * @param \WP_REST_Request $request REST リクエスト.
	 * @return \WP_REST_Response|\WP_Error 保存後の settings、またはエラー.
	 */
	public function save_settings( \WP_REST_Request $request ) {
		if ( ! $request->is_json_content_type() ) {
			return new \WP_Error(
				'rubymaco_invalid_content_type',
				__(
					'Send settings as application/json.',
					'ruby-markup-converter'
				),
				array( 'status' => 415 )
			);
		}
		try {
			$body = json_decode( $request->get_body(), false, 512, JSON_THROW_ON_ERROR );
		} catch ( \JsonException $error ) {
			return new \WP_Error(
				'rubymaco_invalid_json',
				__(
					'The request contains invalid JSON.',
					'ruby-markup-converter'
				),
				array( 'status' => 400 )
			);
		}
		if ( ! $body instanceof \stdClass ) {
			return new \WP_Error(
				'rubymaco_invalid_settings',
				__(
					'Settings must be a JSON object.',
					'ruby-markup-converter'
				),
				array( 'status' => 400 )
			);
		}
		try {
			$settings = $this->settings_service->save( get_object_vars( $body ) );
		} catch ( \InvalidArgumentException $error ) {
			return new \WP_Error(
				'rubymaco_invalid_settings',
				__(
					'Check that all four settings contain valid values.',
					'ruby-markup-converter'
				),
				array( 'status' => 400 )
			);
		} catch ( \RuntimeException $error ) {
			return new \WP_Error(
				'rubymaco_save_failed',
				__(
					'Unable to save settings. Some settings may already have been saved. Please try again.',
					'ruby-markup-converter'
				),
				array(
					'status'                => 500,
					'partial_save_possible' => true,
				)
			);
		}
		return new \WP_REST_Response(
			array( 'settings' => $settings ),
			200,
			array( 'Cache-Control' => 'no-store' )
		);
	}
}
