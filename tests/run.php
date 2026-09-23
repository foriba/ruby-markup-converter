<?php
/**
 * PHP 7.4 対応の CLI 回帰テストランナー。
 *
 * @package RubyMarkupConverter
 */

if ( 'cli' !== PHP_SAPI ) {
	exit;
}
// PHP 7.4-compatible, dependency-free regression runner.
// phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions -- CLI tests must detect all warnings.
error_reporting( E_ALL );
// phpcs:ignore WordPress.PHP.DevelopmentFunctions -- Convert PHP warnings into test failures.
set_error_handler(
	static function ( $severity, $message, $file, $line ) {
		// phpcs:ignore WordPress.PHP.DevelopmentFunctions, WordPress.PHP.DiscouragedPHPFunctions -- Respect the active error mask.
		if ( error_reporting() & $severity ) {
			// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- Preserve original diagnostics for CLI, not HTML.
			throw new ErrorException( $message, 0, $severity, $file, $line );
		}
		return false;
	}
);
$count = 0;
/**
 * 期待値と実際の値を厳密比較する。
 *
 * @param mixed  $expected 期待値.
 * @param mixed  $actual 実際の値.
 * @param string $label 検証名.
 * @return void
 * @throws RuntimeException 値が一致しない場合.
 */
function check_same( $expected, $actual, $label ) {
	global $count;
	++$count;
	if ( $expected !== $actual ) {
		// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped, WordPress.PHP.DevelopmentFunctions -- Raw values are needed for CLI failure diagnostics.
		throw new RuntimeException( $label . "\nExpected: " . var_export( $expected, true ) . "\nActual: " . var_export( $actual, true ) );
	}
}
try {
	$test_wp_version = require __DIR__ . '/bootstrap.php';
	foreach ( array( 'unit/autoload.php', 'integration/bootstrap.php', 'unit/values.php', 'integration/conversion.php', 'integration/settings.php', 'integration/post-content-filter.php', 'integration/blocks.php' ) as $file ) {
		require __DIR__ . '/' . $file;
	}
	// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Plain CLI output, not HTML.
	echo "PASS: $count assertions (PHP " . PHP_VERSION . ", WordPress $test_wp_version)\n";
} catch ( Throwable $error ) {
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_fwrite -- Report CLI failures to STDERR without WordPress filesystem access.
	fwrite( STDERR, 'FAIL: ' . $error->getMessage() . "\n" . $error->getTraceAsString() . "\n" );
	exit( 1 );
}
