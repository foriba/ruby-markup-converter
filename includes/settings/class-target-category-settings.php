<?php
/**
 * 対象カテゴリ設定の取得・保存。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * カテゴリごとの全文変換の指定を、そのカテゴリのタームメタとして保存する。
 *
 * カテゴリの削除時はメタも WordPress が削除し、エクスポート・インポートではカテゴリと一緒に移る。
 * HTTP 層の nonce 確認は呼び出し側で行う。既存の4項目の保存とは独立する。
 */
final class Target_Category_Settings {
	/**
	 * 全文変換の対象であることを示すタームメタのキー。
	 *
	 * @var string
	 */
	public const META_KEY = '_rubymaco_category_full_content';

	/**
	 * 対象として指定されている場合の保存値。未指定の場合はメタを削除する。
	 *
	 * @var string
	 */
	public const ENABLED = '1';

	/**
	 * カテゴリ自身が明示的に対象として指定されているかを返す。
	 *
	 * 親カテゴリからの継承は含めない。補正保存は行わない。
	 *
	 * @param int $term_id カテゴリ ID。0 以下は未指定として扱う.
	 * @return bool 指定されている場合に true.
	 */
	public function is_enabled( int $term_id ): bool {
		return 0 < $term_id && self::ENABLED === get_term_meta( $term_id, self::META_KEY, true );
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
			update_term_meta( $term_id, self::META_KEY, self::ENABLED );
		} else {
			delete_term_meta( $term_id, self::META_KEY );
		}
		if ( $this->is_enabled( $term_id ) !== $enabled ) {
			throw new \RuntimeException( 'Unable to save conversion targets.' );
		}
	}
}
