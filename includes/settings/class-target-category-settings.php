<?php
/**
 * 対象カテゴリ設定の取得・検証・保存。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * カテゴリ ID を重複のない昇順の整数配列として保存する。
 *
 * HTTP 層の nonce 確認は呼び出し側で行う。既存の4項目の保存とは独立する。
 */
final class Target_Category_Settings {
	/**
	 * 現在存在するカテゴリだけを返す。補正保存は行わない。
	 *
	 * @return int[] 未保存・不正値は空配列.
	 */
	public function get(): array {
		$value = get_option( Option_Keys::TARGET_CATEGORY_IDS, array() );
		if ( ! is_array( $value ) ) {
			return array();
		}
		$ids = array();
		foreach ( $value as $id ) {
			if ( is_int( $id ) && 0 < $id && $this->exists( $id ) ) {
				$ids[] = $id;
			}
		}
		return $this->canonicalize( $ids );
	}

	/**
	 * 保存要求を厳密に検証する。
	 *
	 * @param mixed $value 正の整数のリスト。空配列は全解除.
	 * @return int[] 検証済みのカテゴリ ID.
	 * @throws \InvalidArgumentException 型違い、非リスト、存在しないカテゴリの場合.
	 */
	public function validate( $value ): array {
		if ( ! is_array( $value ) || array_values( $value ) !== $value ) {
			throw new \InvalidArgumentException( 'Category IDs must be a list.' );
		}
		foreach ( $value as $id ) {
			if ( ! is_int( $id ) || 0 >= $id || ! $this->exists( $id ) ) {
				throw new \InvalidArgumentException( 'Unknown or invalid category ID.' );
			}
		}
		return $this->canonicalize( $value );
	}

	/**
	 * 管理権限を確認し、全入力を検証してから保存する。
	 *
	 * 不正な入力の場合は validate() の InvalidArgumentException をそのまま返す。
	 *
	 * @param mixed $value カテゴリ ID リスト.
	 * @return int[] 保存した値.
	 * @throws \RuntimeException 権限不足または保存失敗の場合.
	 */
	public function save( $value ): array {
		if ( ! current_user_can( Settings_Identifiers::CAPABILITY ) ) {
			throw new \RuntimeException( 'Not allowed to update conversion targets.' );
		}
		$ids = $this->validate( $value );
		update_option( Option_Keys::TARGET_CATEGORY_IDS, $ids );
		if ( get_option( Option_Keys::TARGET_CATEGORY_IDS ) !== $ids ) {
			throw new \RuntimeException( 'Unable to save conversion targets.' );
		}
		return $ids;
	}

	/**
	 * カテゴリの存在を category タクソノミー内で確認する。
	 *
	 * @param int $id カテゴリ ID.
	 * @return bool 存在する場合に true.
	 */
	private function exists( int $id ): bool {
		$term = get_term( $id, 'category' );
		return $term instanceof \WP_Term;
	}

	/**
	 * 重複と順序の違いを取り除く。
	 *
	 * @param int[] $ids 検証済み ID.
	 * @return int[] 重複のない昇順リスト.
	 */
	private function canonicalize( array $ids ): array {
		$ids = array_values( array_unique( $ids ) );
		sort( $ids, SORT_NUMERIC );
		return $ids;
	}
}
