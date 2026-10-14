<?php
/**
 * 設定値取得サービスの回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Settings\Settings_Service;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;

$settings_options_before = $GLOBALS['test_options'];
try {
	$GLOBALS['test_options'] = array();
	$settings_service        = Settings_Service::create_default();
	$settings_defaults       = array(
		'apply_mode'              => 'shortcode',
		'enabled_rule_ids'        => array( 'ruby_double_angle', 'bouten_double_bracket' ),
		'bouten_style'            => 'dot',
		'bouten_rendering_method' => 'text_emphasis',
	);
	check_same( $settings_defaults, $settings_service->get(), 'settings defaults and response shape' );
	check_same( array(), $GLOBALS['test_options'], 'reading defaults does not save them' );

	$GLOBALS['test_options'] = array(
		Option_Keys::APPLY_MODE              => 'all',
		Option_Keys::ENABLED_MARKUP_RULES    => array( 'ruby_rb' ),
		Option_Keys::BOUTEN_STYLE            => 'sesame',
		Option_Keys::BOUTEN_RENDERING_METHOD => 'custom',
	);
	$settings_expected       = array(
		'apply_mode'              => 'all',
		'enabled_rule_ids'        => array( 'ruby_rb' ),
		'bouten_style'            => 'sesame',
		'bouten_rendering_method' => 'custom',
	);
	check_same( $settings_expected, $settings_service->get(), 'same service reads changed saved values' );
	foreach ( array( null, false, 1, '', 'unknown', array(), new stdClass() ) as $invalid_setting ) {
		$GLOBALS['test_options'] = array(
			Option_Keys::APPLY_MODE              => $invalid_setting,
			Option_Keys::BOUTEN_STYLE            => $invalid_setting,
			Option_Keys::BOUTEN_RENDERING_METHOD => $invalid_setting,
		);
		check_same( $settings_defaults, $settings_service->get(), 'invalid scalar settings fall back' );
	}

	foreach ( array(
		array( null, $settings_defaults['enabled_rule_ids'] ),
		array( false, $settings_defaults['enabled_rule_ids'] ),
		array( 'ruby_rb', $settings_defaults['enabled_rule_ids'] ),
		array( 1, $settings_defaults['enabled_rule_ids'] ),
		array( array(), array() ),
		array( array( 'unknown', null, false, 1, array(), new stdClass() ), array() ),
		array(
			array(
				9       => 'bouten_double_bracket',
				2       => 'ruby_rb',
				5       => 'ruby_rb',
				'extra' => 'unknown',
			),
			array( 'ruby_rb', 'bouten_double_bracket' ),
		),
	) as $settings_case ) {
		$GLOBALS['test_options']               = array( Option_Keys::ENABLED_MARKUP_RULES => $settings_case[0] );
		$settings_stored                       = $GLOBALS['test_options'];
		$settings_expected                     = $settings_defaults;
		$settings_expected['enabled_rule_ids'] = $settings_case[1];
		$settings_actual                       = $settings_service->get();
		check_same( $settings_expected, $settings_actual, 'enabled rules normalization' );
		check_same( $settings_stored, $GLOBALS['test_options'], 'normalization does not write options' );
		// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Verify plain data serialization without loading WordPress runtime helpers.
		check_same( $settings_actual, json_decode( json_encode( $settings_actual, JSON_THROW_ON_ERROR ), true ), 'settings JSON round trip' );
	}
	$settings_writes               = array();
	$settings_failure_key          = null;
	$GLOBALS['test_option_writer'] = static function ( $key, $value ) use ( &$settings_writes, &$settings_failure_key ) {
		$settings_writes[] = $key;
		if ( $key === $settings_failure_key ) {
			return false;
		}
		if ( array_key_exists( $key, $GLOBALS['test_options'] ) && $GLOBALS['test_options'][ $key ] === $value ) {
			return false;
		}
		$GLOBALS['test_options'][ $key ] = $value;
		return true;
	};
	$settings_invalid_requests     = array();
	foreach ( array_keys( $settings_defaults ) as $field ) {
		$request = $settings_defaults;
		unset( $request[ $field ] );
		$settings_invalid_requests[] = $request;
		foreach ( array( null, false, 1, new stdClass() ) as $invalid_value ) {
			$request                     = $settings_defaults;
			$request[ $field ]           = $invalid_value;
			$settings_invalid_requests[] = $request;
		}
	}
	foreach ( array( 'apply_mode', 'bouten_style', 'bouten_rendering_method' ) as $field ) {
		foreach ( array( '', 'unknown', 'ALL', ' dot ', array() ) as $invalid_value ) {
			$request                     = $settings_defaults;
			$request[ $field ]           = $invalid_value;
			$settings_invalid_requests[] = $request;
		}
	}
	foreach ( array( array( 'unknown' ), array( 'ruby_rb', null ), array( array() ), array( 2 => 'ruby_rb' ), array( 'id' => 'ruby_rb' ) ) as $invalid_ids ) {
		$request                     = $settings_defaults;
		$request['enabled_rule_ids'] = $invalid_ids;
		$settings_invalid_requests[] = $request;
	}
	$request                     = $settings_defaults;
	$request['extra']            = 'unknown';
	$settings_invalid_requests[] = $request;
	foreach ( $settings_invalid_requests as $request ) {
		$GLOBALS['test_options'] = array();
		$settings_writes         = array();
		$thrown                  = false;
		try {
			$settings_service->save( $request );
		} catch ( InvalidArgumentException $error ) {
			$thrown = true;
		}
		check_same( true, $thrown, 'invalid save rejected' );
		check_same( array(), $settings_writes, 'all validation precedes writes' );
	}
	$request                      = array(
		'apply_mode'              => 'all',
		'enabled_rule_ids'        => array( 'bouten_double_bracket', 'ruby_rb', 'ruby_rb' ),
		'bouten_style'            => 'sesame',
		'bouten_rendering_method' => 'custom',
	);
	$expected                     = $request;
	$expected['enabled_rule_ids'] = array( 'ruby_rb', 'bouten_double_bracket' );
	$settings_writes              = array();
	check_same( $expected, $settings_service->validate( $request ), 'validation canonicalizes rule IDs' );
	check_same( array(), $settings_writes, 'validation does not write' );
	check_same( $expected, $settings_service->save( $request ), 'saved response' );
	check_same(
		array(
			Option_Keys::APPLY_MODE              => 'all',
			Option_Keys::ENABLED_MARKUP_RULES    => array( 'ruby_rb', 'bouten_double_bracket' ),
			Option_Keys::BOUTEN_STYLE            => 'sesame',
			Option_Keys::BOUTEN_RENDERING_METHOD => 'custom',
		),
		$GLOBALS['test_options'],
		'existing storage keys and values preserved'
	);
	check_same( $expected, $settings_service->save( $request ), 'unchanged update returning false is successful' );
	$request['enabled_rule_ids'] = array();
	check_same( array(), $settings_service->save( $request )['enabled_rule_ids'], 'save all disabled' );
	check_same( array(), $settings_service->get()['enabled_rule_ids'], 'all disabled persists' );
	foreach ( array( Option_Keys::APPLY_MODE, Option_Keys::ENABLED_MARKUP_RULES, Option_Keys::BOUTEN_STYLE, Option_Keys::BOUTEN_RENDERING_METHOD ) as $index => $failure_key ) {
		$GLOBALS['test_options'] = array();
		$settings_failure_key    = $failure_key;
		$settings_writes         = array();
		$thrown                  = false;
		try {
			$settings_service->save( $settings_defaults );
		} catch ( RuntimeException $error ) {
			$thrown = true;
		}
		check_same( true, $thrown, 'write failure throws even for default values' );
		check_same( $index + 1, count( $settings_writes ), 'write stops at failed field' );
		check_same( $index, count( $GLOBALS['test_options'] ), 'earlier writes remain on failure' );
		$settings_failure_key = null;
		check_same( $settings_defaults, $settings_service->save( $settings_defaults ), 'retry completes partial save' );
	}
} finally {
	unset( $GLOBALS['test_option_writer'] );
	$GLOBALS['test_options'] = $settings_options_before;
}
