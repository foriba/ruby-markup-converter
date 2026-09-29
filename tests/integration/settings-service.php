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
} finally {
	$GLOBALS['test_options'] = $settings_options_before;
}
