<?php
/**
 * データベースを使わずに WordPress API とプラグインを読み込む。
 *
 * @package RubyMarkupConverter
 */

$root = getenv( 'WP_ROOT' );
if ( ! $root || ! is_file( rtrim( $root, '/\\' ) . '/wp-includes/version.php' ) ) {
	throw new RuntimeException( 'Set WP_ROOT to a WordPress installation.' );
}
define( 'ABSPATH', rtrim( $root, '/\\' ) . '/' );
define( 'WPINC', 'wp-includes' );
define( 'WP_DEBUG', false );
$test_wp_version     = ( static function (): string {
	$wp_version = '';
	require ABSPATH . WPINC . '/version.php';
	return $wp_version;
} )();
$test_plugin_headers = get_file_data(
	dirname( __DIR__ ) . '/ruby-markup-converter.php',
	array(
		'version'  => 'Version',
		'requires' => 'Requires at least',
	)
);
foreach ( $test_plugin_headers as $test_header_value ) {
	if ( '' === $test_header_value || ! preg_match( '/^\d+(?:\.\d+)+(?:[-+][a-zA-Z0-9.-]+)?$/D', $test_header_value ) ) {
		throw new RuntimeException( 'Missing or invalid Version / Requires at least plugin header.' );
	}
}
if ( '' === $test_wp_version || version_compare( $test_wp_version, $test_plugin_headers['requires'], '<' ) ) {
	// phpcs:ignore WordPress.Security.EscapeOutput.ExceptionNotEscaped -- CLI diagnostic, not HTML.
	throw new RuntimeException( 'WordPress ' . $test_plugin_headers['requires'] . ' or later is required by the plugin header.' );
}
foreach ( array( 'compat-utf8.php', 'utf8.php' ) as $optional ) {
	if ( is_file( ABSPATH . WPINC . '/' . $optional ) ) {
		require_once ABSPATH . WPINC . '/' . $optional;
	}
}
foreach ( array( 'compat.php', 'plugin.php', 'formatting.php', 'kses.php', 'shortcodes.php', 'class-wp-token-map.php', 'html-api/html5-named-character-references.php', 'html-api/class-wp-html-tag-processor.php' ) as $file ) {
	require_once ABSPATH . WPINC . '/' . $file;
}
foreach ( glob( ABSPATH . WPINC . '/html-api/class-*.php' ) as $file ) {
	require_once $file;
}
$GLOBALS['test_options'] = array();
/**
 * テスト用に先頭 8 KB のファイルヘッダーを読み取る。
 *
 * WordPress の部分読み込み用の代替実装。コンテキスト拡張は扱わない。
 * 本体と同様に行単位でヘッダーを検索し、コメント終端を除去する。
 *
 * @param string               $file 読み込むファイル.
 * @param array<string,string> $default_headers キーとヘッダー名.
 * @param string               $context 空文字のみ対応.
 * @return array<string,string> 読み取った値。欠落したヘッダーは空文字.
 * @throws RuntimeException 読み取り失敗、または非対応のコンテキストの場合.
 */
function get_file_data( $file, $default_headers, $context = '' ) {
	if ( '' !== $context ) {
		throw new RuntimeException( 'Header contexts are not supported by the test bootstrap.' );
	}
	// phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- Read-only CLI fixture access without WordPress filesystem initialization.
	$data = file_get_contents( $file, false, null, 0, 8192 );
	if ( false === $data ) {
		throw new RuntimeException( 'Unable to read plugin headers.' );
	}
	$data = str_replace( "\r", "\n", $data );
	foreach ( $default_headers as $key => $header ) {
		$default_headers[ $key ] = preg_match( '/^(?:[ \t]*<\?(?:php)?)?[ \t\/*#@]*' . preg_quote( $header, '/' ) . ':(.*)$/mi', $data, $matches )
			? trim( preg_replace( '/\s*(?:\*\/|\?>).*/', '', $matches[1] ) )
			: '';
	}
	return $default_headers;
}
/**
 * メモリ上の設定値を取得する。
 *
 * @param string $key 設定キー.
 * @param mixed  $default_value 未保存時の値.
 * @return mixed 保存値または初期値.
 */
function get_option( $key, $default_value = false ) {
	if ( 'blog_charset' === $key ) {
		return 'UTF-8';
	}
	return array_key_exists( $key, $GLOBALS['test_options'] ) ? $GLOBALS['test_options'][ $key ] : $default_value;
}
/**
 * 設定の更新を禁止する。
 *
 * @param string $key 設定キー.
 * @param mixed  $value 保存値.
 * @param mixed  $autoload 自動読み込み指定.
 * @return void
 * @throws RuntimeException データベース操作が呼ばれた場合.
 */
function update_option( $key, $value, $autoload = null ) {
	unset( $key, $value, $autoload );
	throw new RuntimeException( 'Unexpected database write' );
}
/**
 * 設定の追加を禁止する。
 *
 * @param string $key 設定キー.
 * @param mixed  $value 保存値.
 * @param mixed  $deprecated 未使用の互換引数.
 * @param mixed  $autoload 自動読み込み指定.
 * @return void
 * @throws RuntimeException データベース操作が呼ばれた場合.
 */
function add_option( $key, $value = '', $deprecated = '', $autoload = null ) {
	unset( $key, $value, $deprecated, $autoload );
	throw new RuntimeException( 'Unexpected database write' );
}
/**
 * 設定の削除を禁止する。
 *
 * @param string $key 設定キー.
 * @return void
 * @throws RuntimeException データベース操作が呼ばれた場合.
 */
function delete_option( $key ) {
	unset( $key );
	throw new RuntimeException( 'Unexpected database write' );
}
/**
 * 翻訳せずに元の文字列を返す。
 *
 * @param string $text 元の文字列.
 * @param string $domain 翻訳ドメイン.
 * @return string 元の文字列.
 */
function __( $text, $domain = '' ) {
	unset( $domain );
	return $text;
}
/**
 * テストで許可するプロトコルを返す。
 *
 * @return string[] プロトコル一覧.
 */
function wp_allowed_protocols() {
	return array( 'http', 'https', 'mailto' );
}
/**
 * テスト用の文字コード名を正規化する。
 *
 * @param string $charset 文字コード名.
 * @return string 正規化した文字コード名.
 */
function _canonical_charset( $charset ) {
	return is_utf8_charset( $charset ) ? 'UTF-8' : $charset;
}
/**
 * テスト用の UTF-8 判定を行う。
 *
 * @param string|null $charset 文字コード名.
 * @return bool UTF-8 として扱う場合に true.
 */
function is_utf8_charset( $charset = null ) {
	return null === $charset || in_array( strtolower( $charset ), array( 'utf8', 'utf-8' ), true );
}
/**
 * 管理画面の読み込み経路を有効にする。
 *
 * @return bool 常に true.
 */
function is_admin() {
	return true;
}
/**
 * テスト用の固定 URL を返す。
 *
 * @param string $path 相対パス.
 * @param string $plugin プラグインファイル.
 * @return string テスト用 URL.
 */
function plugins_url( $path = '', $plugin = '' ) {
	unset( $plugin );
	return 'https://example.invalid/plugin/' . $path;
}
require dirname( __DIR__ ) . '/ruby-markup-converter.php';

if ( RUBYMACO_VERSION !== $test_plugin_headers['version'] ) {
	throw new RuntimeException( 'Plugin version does not match its header.' );
}

return $test_wp_version;
