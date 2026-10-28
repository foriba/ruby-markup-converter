<?php
/**
 * カテゴリ別の変換範囲の取得・保存。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

use Foriba\RubyMarkupConverter\Service\Conversion_Scope_Policy;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * カテゴリごとの変換範囲の指定を、そのカテゴリのタームメタとして保存する。
 *
 * 投稿別の指定（Post_Conversion_Scope_Resolver）と同じメタキー・保存値を使う。
 *
 * カテゴリの削除時はメタも WordPress が削除し、エクスポート・インポートではカテゴリと一緒に移る。
 * HTTP 層の nonce 確認は呼び出し側で行う。既存の4項目の保存とは独立する。
 */
final class Category_Conversion_Scope_Settings {
	/**
	 * カテゴリ別の変換範囲のタームメタのキー。投稿別設定の投稿メタと同じ名前を使う。
	 *
	 * 互換性維持のため、メタキーは変更不可。
	 *
	 * @var string
	 */
	public const META_KEY = '_rubymaco_conversion_scope';

	/**
	 * 本文全体を変換する保存値。現在カテゴリで指定できるのはこの値だけで、未指定の場合はメタを削除する。
	 *
	 * 互換性維持のため、保存値は変更不可。
	 *
	 * @var string
	 */
	public const FULL_CONTENT = Conversion_Scope_Policy::FULL_CONTENT;

	/**
	 * カテゴリ自身が明示的に対象として指定されているかを返す。
	 *
	 * 親カテゴリからの継承は含めない。補正保存は行わない。
	 *
	 * @param int $term_id カテゴリ ID。0 以下は未指定として扱う.
	 * @return bool 指定されている場合に true.
	 */
	public function is_enabled( int $term_id ): bool {
		return 0 < $term_id && self::FULL_CONTENT === get_term_meta( $term_id, self::META_KEY, true );
	}

	/**
	 * カテゴリ管理権限とカテゴリの存在を確認してから、指定を保存する。
	 *
	 * 指定を外す場合はメタを削除し、他のカテゴリの指定は変更しない。
	 *
	 * @param int  $term_id 保存対象のカテゴリ ID.
	 * @param bool $enabled 対象として指定する場合に true.
	 * @throws \InvalidArgumentException カテゴリ ID が不正、または存在しない場合.
	 * @throws \RuntimeException 権限不足または保存失敗の場合.
	 * @phpstan-throws \InvalidArgumentException|\RuntimeException
	 */
	public function save( int $term_id, bool $enabled ): void {
		if ( ! current_user_can( Settings_Identifiers::CATEGORY_CAPABILITY ) ) {
			throw new \RuntimeException( 'Not allowed to update conversion targets.' );
		}
		if ( 0 >= $term_id || ! get_term( $term_id, 'category' ) instanceof \WP_Term ) {
			throw new \InvalidArgumentException( 'Unknown or invalid category ID.' );
		}
		if ( $enabled ) {
			update_term_meta( $term_id, self::META_KEY, self::FULL_CONTENT );
		} else {
			delete_term_meta( $term_id, self::META_KEY );
		}
		if ( $this->is_enabled( $term_id ) !== $enabled ) {
			throw new \RuntimeException( 'Unable to save conversion targets.' );
		}
	}
}
