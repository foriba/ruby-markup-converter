<?php
/**
 * 設定の表示定義の回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Settings\Settings_Definitions;
use Foriba\RubyMarkupConverter\Markup\Rules\Markup_Rule_Registry;
use Foriba\RubyMarkupConverter\Markup\Value\Conversion_Mode;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;

$definitions_options_before = $GLOBALS['test_options'];
$settings_definitions       = Settings_Definitions::create_default();
$definitions_data           = $settings_definitions->get();
check_same( array( 'apply_modes', 'markup_rules', 'bouten_styles', 'bouten_rendering_methods' ), array_keys( $definitions_data ), 'definition response keys' );
foreach ( array(
	'apply_modes'              => Conversion_Mode::values(),
	'bouten_styles'            => Bouten_Style::values(),
	'bouten_rendering_methods' => Bouten_Rendering_Method::values(),
) as $definition_key => $definition_values ) {
	check_same( $definition_values, array_column( $definitions_data[ $definition_key ], 'value' ), 'choices match allowed values' );
	foreach ( $definitions_data[ $definition_key ] as $definition_choice ) {
		check_same( array( 'value', 'label', 'description' ), array_keys( $definition_choice ), 'choice has no selected state' );
		check_same( true, is_string( $definition_choice['label'] ) && '' !== $definition_choice['label'], 'choice has a label' );
		check_same( true, is_string( $definition_choice['description'] ), 'choice has a text description' );
	}
}
check_same( array( '', '' ), array_column( $definitions_data['bouten_styles'], 'description' ), 'optional descriptions are empty strings' );
check_same( Markup_Rule_Registry::instance()->ids(), array_column( $definitions_data['markup_rules'], 'id' ), 'rule order and IDs preserved' );
foreach ( Markup_Rule_Registry::instance()->all() as $definition_index => $definition_rule ) {
	check_same(
		array(
			'id'          => $definition_rule->get_id(),
			'type'        => $definition_rule->get_type()->get_value(),
			'titles'      => $definition_rule->get_titles(),
			'description' => $definition_rule->get_description(),
			'examples'    => $definition_rule->get_examples(),
		),
		$definitions_data['markup_rules'][ $definition_index ],
		'rule contains only display metadata'
	);
}
$legacy_methods = $settings_definitions->get_bouten_rendering_methods();
check_same( $legacy_methods['text_emphasis']['description'], $definitions_data['bouten_rendering_methods'][0]['description'], 'standard method text unchanged' );
check_same( $legacy_methods['custom']['description'] . ' This compatibility option may be removed in a future version.', $definitions_data['bouten_rendering_methods'][1]['description'], 'custom method includes future compatibility notice' );
check_same( $legacy_methods, $settings_definitions->get_bouten_rendering_methods(), 'keyed definitions unchanged by REST formatting' );
foreach ( array( 'rubymaco_get_apply_mode_definitions', 'rubymaco_get_bouten_style_definitions', 'rubymaco_get_bouten_renderer_definitions' ) as $removed_function ) {
	check_same( false, function_exists( $removed_function ), 'legacy definition functions removed' );
}
check_same( $definitions_options_before, $GLOBALS['test_options'], 'definition lookup does not save settings' );
// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Verify plain data serialization without WordPress runtime helpers.
check_same( $definitions_data, json_decode( json_encode( $definitions_data, JSON_THROW_ON_ERROR ), true ), 'definitions JSON round trip' );
