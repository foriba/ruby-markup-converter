<?php
/**
 * 適用モードの設定取得と正規化。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Resolver;

use Foriba\RubyMarkupConverter\Markup\Apply_Mode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 保存済みの適用モードを検証済みの値オブジェクトとして提供する。
 *
 * 保存値は従来どおり文字列とし、読み取り時に型を変換する。
 * 設定の登録・保存、表示用の翻訳、HTML エスケープは行わない。
 * 使用前に WordPress、設定キーの定義、Apply_Mode を読み込むこと。
 */
final class Apply_Mode_Resolver {
	/**
	 * 保存済みの設定を取得する。
	 *
	 * 未保存時や不正値の場合は初期値を返す。保存済みデータは変更しない。
	 * 結果はキャッシュせず、呼び出しごとに WordPress から取得する。
	 *
	 * @return Apply_Mode 検証済みの適用モード.
	 */
	public function get(): Apply_Mode {
		$value = get_option( RUBYMACO_OPTION_APPLY_MODE, $this->default_value()->get_value() );

		return is_string( $value ) ? $this->normalize( $value ) : $this->default_value();
	}

	/**
	 * 未保存時や不正値時に使用する初期値を返す。
	 *
	 * @return Apply_Mode ブロックまたはショートコードの指定範囲を変換するモード.
	 */
	public function default_value(): Apply_Mode {
		return Apply_Mode::selected_areas();
	}

	/**
	 * 文字列を検証し、不正値の場合は初期値に戻す。
	 *
	 * 大文字・小文字や前後の空白は補正しない。
	 * スラッシュ除去など、リクエスト固有の前処理は呼び出し側で行う。
	 *
	 * @param string $value 適用モードの保存値または入力値.
	 * @return Apply_Mode 検証済みの適用モード.
	 */
	public function normalize( string $value ): Apply_Mode {
		return Apply_Mode::try_from( $value ) ?? $this->default_value();
	}
}
