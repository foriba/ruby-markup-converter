<?php
/**
 * 設定 REST コントローラーの回帰テスト。登録と権限は代替実装。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Settings\Settings_Service;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;

/**
 * 登録内容を記録する。
 *
 * @param string $rest_namespace 名前空間.
 * @param string $route パス.
 * @param array  $args エンドポイント.
 */
function register_rest_route( $rest_namespace, $route, $args ) {
	$GLOBALS['test_rest_routes'][ $rest_namespace . $route ] = $args;
}

/**
 * 権限確認の代替処理。
 *
 * @param string $capability 権限名.
 * @return bool 許可状態.
 */
function current_user_can( $capability ) {
	\check_same( 'manage_options', $capability, 'REST requires management capability' );
	return $GLOBALS['test_rest_allowed'];
}

/**
 * 未ログイン・権限不足を区別する代替処理。
 *
 * @return int HTTP ステータス.
 */
function rest_authorization_required_code() {
	return $GLOBALS['test_rest_logged_in'] ? 403 : 401;
}

foreach ( array( 'class-wp-error.php', 'class-wp-http-response.php', 'rest-api/class-wp-rest-response.php', 'rest-api/class-wp-rest-request.php', 'rest-api.php' ) as $rest_file ) {
	require_once ABSPATH . WPINC . '/' . $rest_file;
}
$rest_options_before = $GLOBALS['test_options'];
try {
	$GLOBALS['test_rest_routes'] = array();
	$rest_callbacks              = array_values(
		array_filter(
			$GLOBALS['wp_filter']['rest_api_init']->callbacks[10],
			static fn( array $entry ): bool => is_array( $entry['function'] ) && $entry['function'][0] instanceof Admin_REST_Controller
		)
	);
	\check_same( true, count( $rest_callbacks ) >= 1, 'bootstrap connects REST initialization' );
	$rest_controller = $rest_callbacks[0]['function'][0];
	$rest_hooks      = $GLOBALS['wp_filter']['rest_api_init']->callbacks;
	$rest_controller->register_hooks();
	\check_same( $rest_hooks, $GLOBALS['wp_filter']['rest_api_init']->callbacks, 'hook registration is idempotent' );
	$rest_controller->register_routes();
	$rest_routes = $GLOBALS['test_rest_routes']['rubymaco/v1/settings'];
	\check_same( array( 'GET', 'POST' ), array_column( $rest_routes, 'methods' ), 'GET and POST only' );
	foreach ( $rest_routes as $rest_route ) {
		\check_same( array( $rest_controller, 'check_permissions' ), $rest_route['permission_callback'], 'all routes protected' );
		foreach ( array( false, true ) as $logged_in ) {
			$GLOBALS['test_rest_allowed']   = false;
			$GLOBALS['test_rest_logged_in'] = $logged_in;
			$denied                         = call_user_func( $rest_route['permission_callback'] );
			\check_same( $logged_in ? 403 : 401, $denied->get_error_data()['status'], 'permission denied' );
		}
		$GLOBALS['test_rest_allowed'] = true;
		\check_same( true, call_user_func( $rest_route['permission_callback'] ), 'admin allowed' );
	}
	$GLOBALS['test_options'] = array();
	$response                = call_user_func( $rest_routes[0]['callback'] );
	\check_same( 200, $response->get_status(), 'GET status' );
	\check_same( array( 'settings', 'definitions', 'links', 'messages' ), array_keys( $response->get_data() ), 'GET initial data' );
	\check_same( 'no-store', $response->get_headers()['Cache-Control'], 'no cached settings' );
	\check_same( array(), $GLOBALS['test_options'], 'GET does not write' );

	$rest_writes                   = 0;
	$rest_fail                     = false;
	$GLOBALS['test_option_writer'] = static function ( $key, $value ) use ( &$rest_writes, &$rest_fail ) {
		++$rest_writes;
		if ( $rest_fail && Option_Keys::BOUTEN_STYLE === $key ) {
			return false;
		}
		$changed                         = ! array_key_exists( $key, $GLOBALS['test_options'] ) || $GLOBALS['test_options'][ $key ] !== $value;
		$GLOBALS['test_options'][ $key ] = $value;
		return $changed;
	};
	$rest_valid                    = '{"apply_mode":"all","enabled_rule_ids":[],"bouten_style":"sesame","bouten_rendering_method":"custom"}';
	foreach ( array(
		array( 'text/plain', $rest_valid, 415 ),
		array( 'application/json', '{', 400 ),
		array( 'application/json', '', 400 ),
		array( 'application/json', 'null', 400 ),
		array( 'application/json', '[]', 400 ),
		array( 'application/json', '{}', 400 ),
		array( 'application/json', str_replace( '[]', '{}', $rest_valid ), 400 ),
		array( 'application/json', str_replace( '[]', '["unknown"]', $rest_valid ), 400 ),
		array( 'application/json', str_replace( '"all"', 'true', $rest_valid ), 400 ),
		array( 'application/json', str_replace( '"all"', '"invalid"', $rest_valid ), 400 ),
		array( 'application/json', str_replace( '"apply_mode":"all",', '', $rest_valid ), 400 ),
		array( 'application/json', str_replace( '{"apply_mode"', '{"extra":1,"apply_mode"', $rest_valid ), 400 ),
	) as $rest_case ) {
		$request = new \WP_REST_Request( 'POST', '/rubymaco/v1/settings' );
		$request->set_header( 'Content-Type', $rest_case[0] );
		$request->set_body( $rest_case[1] );
		$request->set_query_params( array( 'apply_mode' => 'all' ) );
		$rest_error = call_user_func( $rest_routes[1]['callback'], $request );
		\check_same( $rest_case[2], $rest_error->get_error_data()['status'], 'invalid request status' );
		\check_same( 0, $rest_writes, 'invalid body cannot write or use query fallback' );
	}
	$request = new \WP_REST_Request( 'POST', '/rubymaco/v1/settings' );
	$request->set_header( 'Content-Type', 'application/json; charset=UTF-8' );
	$request->set_body( $rest_valid );
	$request->set_query_params( array( 'apply_mode' => 'shortcode' ) );
	foreach ( array( 1, 2 ) as $attempt ) {
		$response = call_user_func( $rest_routes[1]['callback'], $request );
		\check_same( 200, $response->get_status(), 'save and unchanged save' );
		\check_same( array( 'settings' => Settings_Service::create_default()->get() ), $response->get_data(), 'saved response shape' );
		\check_same( 'all', $response->get_data()['settings']['apply_mode'], 'JSON takes precedence over query' );
		\check_same( array(), $response->get_data()['settings']['enabled_rule_ids'], 'empty array persists' );
	}
	$GLOBALS['test_options'] = array();
	$rest_fail               = true;
	$rest_error              = call_user_func( $rest_routes[1]['callback'], $request );
	\check_same( 'rubymaco_save_failed', $rest_error->get_error_code(), 'save failure code' );
	\check_same(
		array(
			'status'                => 500,
			'partial_save_possible' => true,
		),
		$rest_error->get_error_data(),
		'partial save warning'
	);
	\check_same( false, isset( $GLOBALS['test_options'][ Option_Keys::BOUTEN_RENDERING_METHOD ] ), 'failure stops subsequent writes' );
} finally {
	$GLOBALS['test_options'] = $rest_options_before;
	unset( $GLOBALS['test_option_writer'], $GLOBALS['test_rest_routes'], $GLOBALS['test_rest_allowed'], $GLOBALS['test_rest_logged_in'] );
}
