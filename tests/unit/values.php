<?php
/**
 * 値オブジェクトの回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;

use Foriba\RubyMarkupConverter\Markup\Value\Conversion_Mode;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;
use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Rendering_Method;
use Foriba\RubyMarkupConverter\Markup\Value\Rule_Type;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;
use Foriba\RubyMarkupConverter\Markup\Rules\Transform_Rule;
use Foriba\RubyMarkupConverter\Markup\Rules\Markup_Rule;

foreach ( Rule_Type::cases() as $rule_type ) {
	$pattern        = '/(example)/u';
	$transform_rule = new Transform_Rule( $rule_type, $pattern );
	check_same( $rule_type, $transform_rule->get_type(), 'transform rule preserves type' );
	check_same( $pattern, $transform_rule->get_pattern(), 'transform rule preserves pattern' );
	check_same(
		array(),
		get_object_vars( $transform_rule ),
		'transform rule exposes no public properties'
	);
	$thrown = false;
	try {
		new Transform_Rule( $rule_type, '' );
	} catch ( InvalidArgumentException $e ) {
		$thrown = true;
	}
	check_same( true, $thrown, 'empty transform pattern throws' );
}

foreach ( Rule_Type::cases() as $rule_type ) {
	$child  = new Transform_Rule( $rule_type, '/(example)/u' );
	$input  = array( $child, $child );
	$parent = new Markup_Rule( 'test', array( 'title' ), array( 'example' ), 'description', true, $input );
	check_same( $rule_type, $parent->get_type(), 'parent derives child type' );
	check_same( 'test', $parent->get_id(), 'parent preserves ID' );
	check_same( array( 'title' ), $parent->get_titles(), 'parent preserves titles' );
	check_same( array( 'example' ), $parent->get_examples(), 'parent preserves examples' );
	check_same( 'description', $parent->get_description(), 'parent preserves description' );
	check_same( true, $parent->is_enabled_by_default(), 'parent preserves default state' );
	check_same( array(), get_object_vars( $parent ), 'markup rule exposes no public properties' );
	check_same( $input, $parent->get_transform_rules(), 'child order is preserved' );
	$input[0]  = new Transform_Rule( Rule_Type::ruby(), '/(other)/u' );
	$output    = $parent->get_transform_rules();
	$output[0] = $input[0];
	check_same( array( $child, $child ), $parent->get_transform_rules(), 'input and returned arrays cannot replace stored children' );
	foreach ( array(
		array( $parent, 'id', 'changed' ),
		array( $parent, 'type', Rule_Type::bouten() ),
		array( $parent, 'titles', array() ),
		array( $parent, 'examples', array() ),
		array( $parent, 'description', '' ),
		array( $parent, 'enabled_by_default', false ),
		array( $parent, 'transform_rules', array() ),
		array( $child, 'type', Rule_Type::bouten() ),
		array( $child, 'pattern', '' ),
	) as $mutation ) {
		$thrown = false;
		try {
			$mutation[0]->{$mutation[1]} = $mutation[2];
		} catch ( Error $e ) {
			$thrown = true;
		}
		check_same( true, $thrown, 'rule property cannot be overwritten: ' . $mutation[1] );
	}
}
$ruby_child   = new Transform_Rule( Rule_Type::ruby(), '/(base)(ruby)/u' );
$bouten_child = new Transform_Rule( Rule_Type::bouten(), '/(text)/u' );
foreach ( array( array(), array( null ), array( 'invalid' ), array( new stdClass() ), array( $ruby_child, null ), array( $ruby_child, $bouten_child ), array( $bouten_child, $ruby_child ) ) as $invalid_children ) {
	$thrown = false;
	try {
		new Markup_Rule( 'test', array(), array(), '', false, $invalid_children );
	} catch ( InvalidArgumentException $e ) {
		$thrown = true;
	}
	check_same( true, $thrown, 'invalid children rejected' );
}
$listed_rule = new Markup_Rule(
	'test',
	array(
		'b' => 'title',
		5   => 'second',
	),
	array( 3 => 'example' ),
	'',
	false,
	array( $ruby_child )
);
check_same( array( 'title', 'second' ), $listed_rule->get_titles(), 'titles are stored as a list' );
check_same( array( 'example' ), $listed_rule->get_examples(), 'examples are stored as a list' );
check_same( false, $listed_rule->is_enabled_by_default(), 'disabled default state preserved' );
foreach ( array(
	'titles'   => array( array( 'title', null ), array( 'example' ) ),
	'examples' => array( array( 'title' ), array( 'example', 1 ) ),
	'nested'   => array( array( array( 'title' ) ), array( 'example' ) ),
) as $invalid_label => $invalid_texts ) {
	$thrown = false;
	try {
		new Markup_Rule( 'test', $invalid_texts[0], $invalid_texts[1], '', false, array( $ruby_child ) );
	} catch ( InvalidArgumentException $e ) {
		$thrown = true;
	}
	check_same( true, $thrown, 'non-string display text rejected: ' . $invalid_label );
}

check_same( 'rubymaco_enabled_markup_rules', Option_Keys::ENABLED_MARKUP_RULES, 'enabled rules storage key' );
check_same( 'rubymaco_bouten_style', Option_Keys::BOUTEN_STYLE, 'bouten style storage key' );
check_same( 'rubymaco_apply_mode', Option_Keys::CONVERSION_MODE, 'apply mode storage key' );
check_same( 'rubymaco_bouten_renderer', Option_Keys::BOUTEN_RENDERING_METHOD, 'rendering method storage key' );
check_same(
	array( 'rubymaco_enabled_markup_rules', 'rubymaco_bouten_style', 'rubymaco_apply_mode', 'rubymaco_bouten_renderer' ),
	Option_Keys::values(),
	'option key list preserves compatibility'
);

foreach ( array(
	Conversion_Mode::class         => array( 'shortcode', 'all' ),
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

check_same( Conversion_Mode::from( 'shortcode' ), Conversion_Mode::selected(), 'selected areas factory preserves saved value' );
check_same( Conversion_Mode::from( 'all' ), Conversion_Mode::all(), 'all factory preserves saved value' );
check_same( true, Conversion_Mode::selected()->equals( Conversion_Mode::from( 'shortcode' ) ), 'equal apply modes' );
check_same( false, Conversion_Mode::selected()->equals( Conversion_Mode::all() ), 'different apply modes' );

check_same( 'rubymaco-settings', Settings_Identifiers::PAGE_SLUG, 'settings page slug compatibility' );
check_same( 'manage_options', Settings_Identifiers::CAPABILITY, 'settings capability compatibility' );
