<?php
/**
 * 実行用オートローダーの回帰テスト。
 *
 * @package RubyMarkupConverter
 */

check_same( false, class_exists( 'Foriba\RubyMarkupConverter\Markup\Rule_Type', false ), 'class loading is deferred' );
$runtime_classmap = require dirname( __DIR__, 2 ) . '/vendor-runtime/composer/autoload_classmap.php';
foreach ( $runtime_classmap as $class => $class_file ) {
	if ( 0 !== strpos( $class, 'Foriba\\RubyMarkupConverter\\' ) ) {
		continue;
	}
	check_same( true, class_exists( $class ), 'plugin class autoloads: ' . $class );
	check_same( realpath( $class_file ), ( new ReflectionClass( $class ) )->getFileName(), 'autoload uses plugin source' );
}
check_same( false, class_exists( 'Foriba\RubyMarkupConverter\Unknown_Class' ), 'unknown class is ignored' );
check_same( false, function_exists( 'get_posts' ), 'runtime does not load development WordPress stubs' );
