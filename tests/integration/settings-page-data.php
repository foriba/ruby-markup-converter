<?php
/**
 * 管理画面の初期データの回帰テスト。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Admin\Admin_Page_Data;
use Foriba\RubyMarkupConverter\Settings\Settings_Service;
use Foriba\RubyMarkupConverter\Settings\Settings_Definitions;
use Foriba\RubyMarkupConverter\Settings\Option_Keys;

$page_options_before = $GLOBALS['test_options'];
$page_hooks_before   = array_keys( $GLOBALS['wp_filter'] );
try {
	$GLOBALS['test_options'] = array();
	$page_data_provider      = Admin_Page_Data::create_default();
	$page_data               = $page_data_provider->get();
	check_same( array( 'settings', 'definitions', 'links', 'messages' ), array_keys( $page_data ), 'page data sections' );
	check_same( Settings_Service::create_default()->get(), $page_data['settings'], 'settings delegation' );
	check_same( Settings_Definitions::create_default()->get(), $page_data['definitions'], 'definitions delegation' );
	check_same( array( 'user_guide' => 'https://github.com/foriba/ruby-markup-converter/blob/main/docs/en-US/user-guide.md' ), $page_data['links'], 'guide URL' );
	foreach ( array( 'page_title', 'basic_tab', 'compatibility_tab', 'conversion_scope', 'markup_rules', 'ruby', 'bouten', 'bouten_style', 'bouten_rendering_method', 'markup_example', 'output_result', 'user_guide', 'opens_in_new_tab', 'save', 'saving', 'saved', 'save_failed', 'loading', 'load_failed', 'retry', 'unsaved_changes', 'preview_loading', 'preview_failed' ) as $message_key ) {
		check_same( true, isset( $page_data['messages'][ $message_key ] ) && is_string( $page_data['messages'][ $message_key ] ) && '' !== $page_data['messages'][ $message_key ], 'message: ' . $message_key );
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.json_encode_json_encode -- Check the transport shape without loading WordPress runtime helpers.
	check_same( $page_data, json_decode( json_encode( $page_data, JSON_THROW_ON_ERROR ), true ), 'page data JSON round trip' );
	check_same( array(), $GLOBALS['test_options'], 'page data does not save defaults' );
	$GLOBALS['test_options'] = array(
		Option_Keys::APPLY_MODE           => 'all',
		Option_Keys::ENABLED_MARKUP_RULES => array(),
	);
	$page_changed            = $page_data_provider->get();
	check_same( 'all', $page_changed['settings']['apply_mode'], 'page does not cache settings' );
	check_same( array(), $page_changed['settings']['enabled_rule_ids'], 'all disabled preserved' );
	check_same( $page_data['definitions'], $page_changed['definitions'], 'definitions do not include selection state' );
	check_same( $page_hooks_before, array_keys( $GLOBALS['wp_filter'] ), 'page provider registers no hooks' );
} finally {
	$GLOBALS['test_options'] = $page_options_before;
}
