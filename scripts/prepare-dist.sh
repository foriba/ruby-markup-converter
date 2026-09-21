#!/usr/bin/env bash
set -euo pipefail

root="$(cd "$(dirname "${BASH_SOURCE[0]}")/.." && pwd)"
stage=""

on_error() {
    printf 'Distribution preparation failed. Do not publish this output.\n' >&2
    if [[ -n "$stage" ]]; then
        printf 'Incomplete files were retained at: %s\n' "$stage" >&2
    fi
}
trap on_error EXIT

for command in rsync composer php mktemp; do
    if ! command -v "$command" >/dev/null 2>&1; then
        printf 'Required command not found: %s\n' "$command" >&2
        exit 1
    fi
done

for file in .distignore composer.json composer.lock ruby-markup-converter.php editor/build/blocks-manifest.php; do
    if [[ ! -f "$root/$file" ]]; then
        printf 'Required file not found: %s\n' "$root/$file" >&2
        exit 1
    fi
done

# Always build outside the checkout; never modify its installed dependencies.
temp_root="${TMPDIR:-/tmp}"
stage_parent="$(mktemp -d "${temp_root%/}/rubymaco-dist.XXXXXX")"
stage="$stage_parent/ruby-markup-converter-dist"
mkdir -p "$stage"
rsync -a --exclude-from="$root/.distignore" "$root/" "$stage/"
cp "$root/composer.json" "$root/composer.lock" "$stage/"

(
    cd "$stage"
    unset COMPOSER
    export COMPOSER_VENDOR_DIR=vendor-runtime
    composer install --no-dev --optimize-autoloader --no-plugins --no-scripts --no-interaction

    # Check generated class paths without booting WordPress or accessing a database.
    php -r '
        define("ABSPATH", getcwd() . "/");
        require "vendor-runtime/autoload.php";
        $map = require "vendor-runtime/composer/autoload_classmap.php";
        $count = 0;
        foreach ($map as $class => $file) {
            if (strpos($class, "Foriba\\RubyMarkupConverter\\") === 0) {
                if (!is_file($file) || !class_exists($class)) {
                    fwrite(STDERR, "Autoload check failed: " . $class . PHP_EOL);
                    exit(1);
                }
                ++$count;
            }
        }
        if ($count === 0) {
            fwrite(STDERR, "No plugin classes found." . PHP_EOL);
            exit(1);
        }
        echo "Autoload check passed: " . $count . " classes." . PHP_EOL;
    '
)

rm "$stage/composer.json" "$stage/composer.lock"
trap - EXIT
printf '\nDistribution files prepared at:\n%s\n' "$stage"
printf 'No ZIP was created and nothing was uploaded.\n'
