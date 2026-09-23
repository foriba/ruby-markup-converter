<?php
/**
 * 傍点スタイルの設定取得と正規化。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Resolver;

use Foriba\RubyMarkupConverter\Settings\Option_Keys;

use Foriba\RubyMarkupConverter\Markup\Value\Bouten_Style;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 保存済みの傍点スタイルを検証済みの値オブジェクトとして提供する。
 *
 * 保存値は従来どおり文字列とし、読み取り時に型を変換する。
 * 設定の登録・保存、表示用の翻訳、HTML エスケープは行わない。
 * 使用前に WordPress、設定キーの定義、Bouten_Style を読み込むこと。
 */
final class Bouten_Style_Resolver {
	/**
	 * 保存済みの設定を取得する。
	 *
	 * 未保存時や不正値の場合は初期値を返す。保存済みデータは変更しない。
	 * 結果はキャッシュせず、呼び出しごとに WordPress から取得する。
	 *
	 * @return Bouten_Style 検証済みの傍点スタイル.
	 */
	public function get(): Bouten_Style {
		$value = get_option( Option_Keys::BOUTEN_STYLE, $this->default_value()->get_value() );

		return is_string( $value ) ? $this->normalize( $value ) : $this->default_value();
	}

	/**
	 * 未保存時や不正値時に使用する初期値を返す。
	 *
	 * @return Bouten_Style 黒丸の点を表すスタイル.
	 */
	public function default_value(): Bouten_Style {
		return Bouten_Style::dot();
	}

	/**
	 * 文字列を検証し、不正値の場合は初期値に戻す。
	 *
	 * 大文字・小文字や前後の空白は補正しない。
	 * スラッシュ除去など、リクエスト固有の前処理は呼び出し側で行う。
	 *
	 * @param string $value 傍点スタイルの保存値または入力値.
	 * @return Bouten_Style 検証済みの傍点スタイル.
	 */
	public function normalize( string $value ): Bouten_Style {
		return Bouten_Style::try_from( $value ) ?? $this->default_value();
	}
}
