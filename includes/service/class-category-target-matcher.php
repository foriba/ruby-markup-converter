<?php
/**
 * 投稿の所属カテゴリと選択カテゴリの一致を判定する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Service;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 選択カテゴリ自身とその子孫を対象とする。
 *
 * 全体設定や投稿別指定の優先順位は判定しない。結果は保持せず、
 * 投稿・カテゴリの変更を次回呼び出しに反映する。
 */
final class Category_Target_Matcher {
	/**
	 * 所属カテゴリの一つでも対象カテゴリまたはその子孫なら一致とする。
	 *
	 * 不明な投稿 ID、空の対象一覧、カテゴリなし、取得エラーは不一致。
	 * ID 一覧は保存・入力境界で正規化した正の整数を渡す。
	 * 念のため不正な要素は無視し、文字列や負数を別の ID に変換しない。
	 *
	 * @param int     $post_id 対象の投稿 ID.
	 * @param mixed[] $selected_category_ids 選択カテゴリの ID 一覧。正の整数以外は無視する.
	 * @return bool 自身または祖先のカテゴリが選択されている場合に true.
	 */
	public function matches( int $post_id, array $selected_category_ids ): bool {
		if ( 0 >= $post_id ) {
			return false;
		}

		$selected = array();
		foreach ( $selected_category_ids as $category_id ) {
			if ( is_int( $category_id ) && 0 < $category_id ) {
				$selected[ $category_id ] = true;
			}
		}
		if ( array() === $selected ) {
			return false;
		}

		$terms = get_the_terms( $post_id, 'category' );
		if ( ! is_array( $terms ) ) {
			return false;
		}

		foreach ( $terms as $term ) {
			if ( isset( $selected[ $term->term_id ] ) ) {
				return true;
			}

			foreach ( get_ancestors( $term->term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
				if ( isset( $selected[ $ancestor_id ] ) ) {
					return true;
				}
			}
		}

		return false;
	}
}
