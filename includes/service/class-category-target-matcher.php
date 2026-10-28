<?php
/**
 * 投稿の所属カテゴリと対象カテゴリの一致を判定する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Service;

use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 対象として指定されたカテゴリ自身とその子孫を対象とする。
 *
 * 全体設定や投稿別指定の優先順位は判定しない。結果は保持せず、
 * 投稿・カテゴリの変更を次回呼び出しに反映する。
 */
final class Category_Target_Matcher {
	/**
	 * カテゴリごとの指定の取得元。
	 *
	 * @var Target_Category_Settings
	 */
	private Target_Category_Settings $target_category_settings;

	/**
	 * 指定の取得元を受け取る。
	 *
	 * @param Target_Category_Settings $target_category_settings カテゴリ設定.
	 */
	public function __construct( Target_Category_Settings $target_category_settings ) {
		$this->target_category_settings = $target_category_settings;
	}

	/**
	 * 所属カテゴリの一つでも、自身または祖先が対象として指定されていれば一致とする。
	 *
	 * 不明な投稿 ID、カテゴリなし、取得エラーは不一致。
	 *
	 * @param int $post_id 対象の投稿 ID.
	 * @return bool 自身または祖先のカテゴリが指定されている場合に true.
	 */
	public function matches( int $post_id ): bool {
		if ( 0 >= $post_id ) {
			return false;
		}

		$terms = get_the_terms( $post_id, 'category' );
		if ( ! is_array( $terms ) ) {
			return false;
		}

		$term_ids = array();
		foreach ( $terms as $term ) {
			$term_ids[] = $term->term_id;
			foreach ( get_ancestors( $term->term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
				$term_ids[] = $ancestor_id;
			}
		}
		$term_ids = array_values( array_unique( $term_ids ) );
		if ( array() === $term_ids ) {
			return false;
		}

		// 所属カテゴリと祖先のメタを、未取得の分だけまとめて読み込む.
		update_termmeta_cache( $term_ids );
		foreach ( $term_ids as $term_id ) {
			if ( $this->target_category_settings->is_enabled( $term_id ) ) {
				return true;
			}
		}

		return false;
	}
}
