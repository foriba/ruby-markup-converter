<?php
/**
 * プラグインの配置情報とバージョンを保持する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter;

use InvalidArgumentException;
use RuntimeException;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * メインファイルを基に取得した情報を、変更用メソッドを持たずに公開する。
 *
 * WordPress の読み込み後に生成する。ヘッダーは生成時に一度だけ読み取り、
 * フック登録、定数の定義、設定の読み書きは行わない。
 */
final class Plugin_Info {
	/**
	 * メインファイルのパス。
	 *
	 * @var string
	 */
	private string $plugin_file_path;

	/**
	 * 末尾にスラッシュを含むディレクトリ。
	 *
	 * @var string
	 */
	private string $plugin_directory_path;

	/**
	 * 末尾にスラッシュを含む公開 URL。
	 *
	 * @var string
	 */
	private string $plugin_directory_url;

	/**
	 * ヘッダーから取得したバージョン。
	 *
	 * @var string
	 */
	private string $plugin_version;

	/**
	 * メインファイルからプラグイン情報を取得する。
	 *
	 * パスは realpath() で解決せず、呼び出し元の __FILE__ をそのまま保持する。
	 *
	 * @param string $plugin_file_path メインファイルの絶対パス。__FILE__ を渡す.
	 * @throws InvalidArgumentException ファイルを読み取れない場合.
	 * @throws RuntimeException プラグインの version ヘッダーが空の場合.
	 */
	public function __construct( string $plugin_file_path ) {
		if ( ! is_file( $plugin_file_path ) || ! is_readable( $plugin_file_path ) ) {
			throw new InvalidArgumentException( 'The plugin main file must be readable.' );
		}

		$headers = get_file_data( $plugin_file_path, array( 'version' => 'Version' ) );
		if ( '' === $headers['version'] ) {
			throw new RuntimeException( 'The plugin Version header must not be empty.' );
		}

		$this->plugin_file_path      = $plugin_file_path;
		$this->plugin_directory_path = plugin_dir_path( $plugin_file_path );
		$this->plugin_directory_url  = plugin_dir_url( $plugin_file_path );
		$this->plugin_version        = $headers['version'];
	}

	/**
	 * メインファイルのパスを返す。
	 *
	 * @return string メインファイルのパス.
	 */
	public function get_file_path(): string {
		return $this->plugin_file_path;
	}

	/**
	 * プラグインのディレクトリを返す。
	 *
	 * @return string 末尾にスラッシュを含むディレクトリ.
	 */
	public function get_directory_path(): string {
		return $this->plugin_directory_path;
	}

	/**
	 * プラグインの公開 URL を返す。
	 *
	 * @return string 末尾にスラッシュを含む URL.
	 */
	public function get_directory_url(): string {
		return $this->plugin_directory_url;
	}

	/**
	 * プラグインのバージョンを返す。
	 *
	 * @return string メインファイルのヘッダーに記載されたバージョン.
	 */
	public function get_version(): string {
		return $this->plugin_version;
	}
}
