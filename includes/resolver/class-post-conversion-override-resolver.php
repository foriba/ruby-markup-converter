<?php
/**
 * 投稿別の変換指定を取得・正規化する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 投稿メタから共通判定用の三状態を取得する。
 *
 * 投稿 ID をグローバル変数から推測せず、保存や権限判定も行わない。
 * メタの登録と保存時の権限確認は Post_Conversion_Meta が担当する。
 */
final class Post_Conversion_Override_Resolver {
	/** 投稿別設定のメタキー。 */
	public const META_KEY = '_rubymaco_conversion_override';

	/** 全体設定とカテゴリ設定を継承する保存値。 */
	public const INHERIT = 'inherit';

	/** 本文全体を変換する保存値。 */
	public const ENABLED = 'enabled';

	/** ブロック・ショートコードを含めて変換しない保存値。 */
	public const DISABLED = 'disabled';

	/**
	 * 指定投稿の設定を取得する。
	 *
	 * 固定ページも同じ投稿メタで扱う。投稿 ID が不明なら 0 を渡す。
	 * 存在しない投稿、未保存、不正値は継承とする。読み取り時に補正保存しない。
	 *
	 * @param int $post_id 対象の投稿 ID.
	 * @return bool|null 有効なら true、無効なら false、継承なら null.
	 */
	public function get( int $post_id ): ?bool {
		if ( 0 >= $post_id ) {
			return null;
		}

		return $this->normalize( get_post_meta( $post_id, self::META_KEY, true ) );
	}

	/**
	 * 保存値を判定用の値へ変換する。
	 *
	 * @param mixed $value 保存値。真偽値や数値を暗黙に変換しない.
	 * @return bool|null 有効なら true、無効なら false、それ以外は継承.
	 */
	public function normalize( $value ): ?bool {
		if ( self::ENABLED === $value ) {
			return true;
		}
		if ( self::DISABLED === $value ) {
			return false;
		}

		return null;
	}
}
