<?php
if (PHP_SAPI !== 'cli') { exit; }
// PHP 7.4-compatible, dependency-free regression runner.
error_reporting(E_ALL);
set_error_handler(static function ($severity, $message, $file, $line) {
    if (error_reporting() & $severity) { throw new ErrorException($message, 0, $severity, $file, $line); }
    return false;
});
$count = 0;
function check_same($expected, $actual, $label) {
    global $count;
    ++$count;
    if ($expected !== $actual) {
        throw new RuntimeException($label . "\nExpected: " . var_export($expected, true) . "\nActual: " . var_export($actual, true));
    }
}
try {
    require __DIR__ . '/bootstrap.php';
    foreach (['unit/values.php', 'integration/conversion.php', 'integration/settings.php'] as $file) {
        require __DIR__ . '/' . $file;
    }
    echo "PASS: $count assertions (PHP " . PHP_VERSION . ", WordPress $wp_version)\n";
} catch (Throwable $error) {
    fwrite(STDERR, "FAIL: " . $error->getMessage() . "\n" . $error->getTraceAsString() . "\n");
    exit(1);
}
