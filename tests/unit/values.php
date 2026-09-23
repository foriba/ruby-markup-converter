<?php
/**
 * 値オブジェクトの回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;

use Foriba\RubyMarkupConverter\Markup\Value\Apply_Mode;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;
use Foriba\RubyMarkupConverter\Markup\Value\Rule_Type;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;

check_same( 'rubymaco_enabled_markup_rules', Option_Keys::ENABLED_MARKUP_RULES, 'enabled rules storage key' );
check_same( 'rubymaco_bouten_style', Option_Keys::BOUTEN_STYLE, 'bouten style storage key' );
check_same( 'rubymaco_apply_mode', Option_Keys::APPLY_MODE, 'apply mode storage key' );
check_same( 'rubymaco_bouten_renderer', Option_Keys::BOUTEN_RENDERING_METHOD, 'rendering method storage key' );
check_same(
	array( 'rubymaco_enabled_markup_rules', 'rubymaco_bouten_style', 'rubymaco_apply_mode', 'rubymaco_bouten_renderer' ),
	Option_Keys::values(),
	'option key list preserves compatibility'
);

foreach ( array(
	Apply_Mode::class              => array( 'shortcode', 'all' ),
	Bouten_Style::class            => array( 'dot', 'sesame' ),
	Bouten_Rendering_Method::class => array( 'text_emphasis', 'custom' ),
	Rule_Type::class               => array( 'ruby', 'bouten' ),
) as $class => $values ) {
	check_same( $values, $class::values(), $class . ' values' );
	foreach ( $values as $i => $value ) {
		check_same( $value, $class::from( $value )->get_value(), 'round trip' );
		check_same( $class::from( $value ), $class::cases()[ $i ], 'shared instance' );
	}
	foreach ( array( '', 'unknown', strtoupper( $values[0] ), ' ' . $values[0] ) as $value ) {
		check_same( null, $class::try_from( $value ), 'invalid value' );
		$thrown = false;
		try {
			$class::from( $value );
		} catch ( InvalidArgumentException $e ) {
			$thrown = true; }
		check_same( true, $thrown, 'invalid value throws' );
	}
}

check_same( Apply_Mode::from( 'shortcode' ), Apply_Mode::selected_areas(), 'selected areas factory preserves saved value' );
check_same( Apply_Mode::from( 'all' ), Apply_Mode::all(), 'all factory preserves saved value' );
check_same( true, Apply_Mode::selected_areas()->equals( Apply_Mode::from( 'shortcode' ) ), 'equal apply modes' );
check_same( false, Apply_Mode::selected_areas()->equals( Apply_Mode::all() ), 'different apply modes' );

check_same( 'rubymaco_settings', Settings_Identifiers::GROUP, 'settings group compatibility' );
check_same( 'rubymaco-settings', Settings_Identifiers::PAGE_SLUG, 'settings page slug compatibility' );
check_same( 'manage_options', Settings_Identifiers::CAPABILITY, 'settings capability compatibility' );
