<?php
/**
 * プラグイン情報の取得と副作用の有無を検証する。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Plugin_Info;

$plugin_main_file  = dirname( __DIR__, 2 ) . '/ruby-markup-converter.php';
$info_hooks_before = array();
foreach ( $GLOBALS['wp_filter'] as $info_hook => $info_hook_object ) {
	$info_hooks_before[ $info_hook ] = $info_hook_object->callbacks;
}
$info_options_before = $GLOBALS['test_options'];
$plugin_info         = new Plugin_Info( $plugin_main_file );
check_same( $plugin_main_file, $plugin_info->get_file(), 'plugin main file' );
check_same( dirname( $plugin_main_file ) . '/', $plugin_info->get_directory(), 'plugin directory with trailing slash' );
check_same( plugin_dir_url( $plugin_main_file ), $plugin_info->get_url(), 'plugin URL uses WordPress API' );
check_same( '/', substr( $plugin_info->get_url(), -1 ), 'plugin URL has trailing slash' );
check_same( get_file_data( $plugin_main_file, array( 'version' => 'Version' ) )['version'], $plugin_info->get_version(), 'version comes from header' );
check_same( RUBYMACO_PLUGIN_FILE, $plugin_info->get_file(), 'file matches existing constant' );
check_same( RUBYMACO_PLUGIN_DIR, $plugin_info->get_directory(), 'directory matches existing constant' );
check_same( RUBYMACO_PLUGIN_URL, $plugin_info->get_url(), 'URL matches existing constant' );
check_same( RUBYMACO_VERSION, $plugin_info->get_version(), 'version matches existing constant' );
$info_hooks_after = array();
foreach ( $GLOBALS['wp_filter'] as $info_hook => $info_hook_object ) {
	$info_hooks_after[ $info_hook ] = $info_hook_object->callbacks;
}
check_same( $info_hooks_before, $info_hooks_after, 'plugin info does not change hooks' );
check_same( $info_options_before, $GLOBALS['test_options'], 'plugin info does not change options' );
foreach ( array( 'file', 'directory', 'url', 'version' ) as $info_property ) {
	check_same( true, ( new ReflectionProperty( Plugin_Info::class, $info_property ) )->isPrivate(), 'plugin info property is private' );
}
foreach ( array( '', __DIR__ . '/missing-plugin.php', __DIR__ ) as $invalid_plugin_file ) {
	$info_exception = false;
	try {
		new Plugin_Info( $invalid_plugin_file );
	} catch ( InvalidArgumentException $error ) {
		$info_exception = true;
	}
	check_same( true, $info_exception, 'invalid plugin file is rejected' );
}
$info_exception = false;
try {
	new Plugin_Info( __FILE__ );
} catch ( RuntimeException $error ) {
	$info_exception = true;
}
check_same( true, $info_exception, 'missing Version header is rejected' );
