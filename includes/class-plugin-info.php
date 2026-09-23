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
	private string $file;

	/**
	 * 末尾にスラッシュを含むディレクトリ。
	 *
	 * @var string
	 */
	private string $directory;

	/**
	 * 末尾にスラッシュを含む公開 URL。
	 *
	 * @var string
	 */
	private string $url;

	/**
	 * ヘッダーから取得したバージョン。
	 *
	 * @var string
	 */
	private string $version;

	/**
	 * メインファイルからプラグイン情報を取得する。
	 *
	 * パスは realpath() で解決せず、呼び出し元の __FILE__ をそのまま保持する。
	 *
	 * @param string $file メインファイルの絶対パス。__FILE__ を渡す.
	 * @throws InvalidArgumentException ファイルを読み取れない場合.
	 * @throws RuntimeException バージョンのヘッダーが空の場合.
	 */
	public function __construct( string $file ) {
		if ( ! is_file( $file ) || ! is_readable( $file ) ) {
			throw new InvalidArgumentException( 'The plugin main file must be readable.' );
		}

		$headers = get_file_data( $file, array( 'version' => 'Version' ) );
		if ( '' === $headers['version'] ) {
			throw new RuntimeException( 'The plugin Version header must not be empty.' );
		}

		$this->file      = $file;
		$this->directory = plugin_dir_path( $file );
		$this->url       = plugin_dir_url( $file );
		$this->version   = $headers['version'];
	}

	/**
	 * メインファイルのパスを返す。
	 *
	 * @return string メインファイルのパス.
	 */
	public function get_file(): string {
		return $this->file;
	}

	/**
	 * プラグインのディレクトリを返す。
	 *
	 * @return string 末尾にスラッシュを含むディレクトリ.
	 */
	public function get_directory(): string {
		return $this->directory;
	}

	/**
	 * プラグインの公開 URL を返す。
	 *
	 * @return string 末尾にスラッシュを含む URL.
	 */
	public function get_url(): string {
		return $this->url;
	}

	/**
	 * プラグインのバージョンを返す。
	 *
	 * @return string メインファイルのヘッダーに記載されたバージョン.
	 */
	public function get_version(): string {
		return $this->version;
	}
}
