<?php
/**
 * 投稿別の変換指定を取得・正規化する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Resolver;

use Foriba\RubyMarkupConverter\Service\Conversion_Target_Policy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 投稿メタから、投稿別に指定された変換範囲を取得する。
 *
 * 保存値は Conversion_Target_Policy の判定結果と同じ名前を使う。未指定はメタを保存しない。
 * 投稿 ID をグローバル変数から推測せず、保存や権限判定も行わない。
 * メタの登録と保存時の権限確認は Post_Conversion_Meta が担当する。
 */
final class Post_Conversion_Override_Resolver {
	/** 投稿別の変換範囲のメタキー。カテゴリ別設定のタームメタと同じ名前を使う。 */
	public const META_KEY = '_rubymaco_conversion_scope';

	/** 全体設定とカテゴリ設定に従う状態。保存せず、REST API とエディタでの未指定の表現に使う。 */
	public const INHERIT = 'inherit';

	/** 本文全体を変換する保存値。 */
	public const FULL_CONTENT = Conversion_Target_Policy::FULL_CONTENT;

	/** ブロック・ショートコードを含めて変換しない保存値。 */
	public const NONE = Conversion_Target_Policy::NONE;

	/**
	 * 指定投稿の設定を取得する。
	 *
	 * 固定ページも同じ投稿メタで扱う。投稿 ID が不明なら 0 を渡す。
	 * 存在しない投稿、未保存、不正値は継承とする。読み取り時に補正保存しない。
	 *
	 * @param int $post_id 対象の投稿 ID.
	 * @return string|null 指定された変換範囲。継承なら null.
	 */
	public function get( int $post_id ): ?string {
		if ( 0 >= $post_id ) {
			return null;
		}

		return $this->normalize( get_post_meta( $post_id, self::META_KEY, true ) );
	}

	/**
	 * 保存値を判定用の値へ変換する。
	 *
	 * @param mixed $value 保存値。真偽値や数値を暗黙に変換しない.
	 * @return string|null FULL_CONTENT または NONE。それ以外は継承として null.
	 */
	public function normalize( $value ): ?string {
		return in_array( $value, array( self::FULL_CONTENT, self::NONE ), true ) ? $value : null;
	}
}
