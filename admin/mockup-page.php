<?php
/**
 * React 管理画面のモックアップ。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Admin;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function rubymaco_mockup_settings_page() {
	add_options_page(
		__( 'Ruby Markup Converter Mockup', 'ruby-markup-converter' ),
		__( 'Ruby Markup Converter Mockup', 'ruby-markup-converter' ),
		'manage_options',
		'rubymaco-mockup',
		__NAMESPACE__ . '\\rubymaco_mockup_settings_page_html'
	);
}

add_action(
	'admin_menu',
	__NAMESPACE__ . '\\rubymaco_mockup_settings_page'
);

function rubymaco_mockup_settings_page_html() {
	printf(
		'<div class="wrap" id="rubymaco-mockup-settings">%s</div>',
		esc_html__( 'Loading...', 'rubymaco-mockup' )
	);
}

function rubymaco_mockup_settings_page_enqueue_style_script( $admin_page ) {
	if ( 'settings_page_rubymaco-mockup' !== $admin_page ) {
		return;
	}

	$asset_file = plugin_dir_path( __FILE__ ) . 'mockup-build/index.asset.php';

	if ( ! file_exists( $asset_file ) ) {
		return;
	}

	$asset = include $asset_file;

	wp_enqueue_script(
		'rubymaco-mockup-script',
		plugins_url( 'mockup-build/index.js', __FILE__ ),
		$asset['dependencies'],
		$asset['version'],
		array(
			'in_footer' => true,
		)
	);

	wp_enqueue_style( 'wp-components' );
	wp_enqueue_style( 'rubymaco-mockup', plugins_url( 'mockup-build/index.css', __FILE__ ), array( 'wp-components' ), $asset['version'] );
}

add_action(
	'admin_enqueue_scripts',
	__NAMESPACE__ . '\\rubymaco_mockup_settings_page_enqueue_style_script'
);
