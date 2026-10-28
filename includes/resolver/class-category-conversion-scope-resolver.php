<?php
/**
 * 投稿の所属カテゴリから、カテゴリ別に指定された変換範囲を取得する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Resolver;

use Foriba\RubyMarkupConverter\Settings\Category_Conversion_Scope_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 所属カテゴリ自身とその祖先に指定された変換範囲を、投稿ごとにまとめて返す。
 *
 * Post_Conversion_Scope_Resolver と対になり、どちらも投稿 ID を受け取って変換範囲を返す。
 * カテゴリ 1 件の指定の読み書きは Category_Conversion_Scope_Settings が担当する。
 * 全体設定や投稿別指定の優先順位は判定しない。結果は保持せず、
 * 投稿・カテゴリの変更を次回呼び出しに反映する。
 */
final class Category_Conversion_Scope_Resolver {
	/**
	 * カテゴリごとの指定の取得元。
	 *
	 * @var Category_Conversion_Scope_Settings
	 */
	private Category_Conversion_Scope_Settings $category_conversion_scope_settings;

	/**
	 * 指定の取得元を受け取る。
	 *
	 * @param Category_Conversion_Scope_Settings $category_conversion_scope_settings カテゴリ設定.
	 */
	public function __construct( Category_Conversion_Scope_Settings $category_conversion_scope_settings ) {
		$this->category_conversion_scope_settings = $category_conversion_scope_settings;
	}

	/**
	 * 所属カテゴリの一つでも、自身または祖先に本文全体の変換が指定されていれば FULL_CONTENT を返す。
	 *
	 * 現在カテゴリで指定できるのは FULL_CONTENT だけ。
	 * 不明な投稿 ID、カテゴリなし、取得エラー、指定なしは null。
	 *
	 * @param int $post_id 対象の投稿 ID.
	 * @return string|null カテゴリから決まる変換範囲。指定がなければ null.
	 */
	public function get( int $post_id ): ?string {
		if ( 0 >= $post_id ) {
			return null;
		}

		$terms = get_the_terms( $post_id, 'category' );
		if ( ! is_array( $terms ) ) {
			return null;
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
			return null;
		}

		// 所属カテゴリと祖先のメタを、未取得の分だけまとめて読み込む.
		update_termmeta_cache( $term_ids );
		foreach ( $term_ids as $term_id ) {
			if ( $this->category_conversion_scope_settings->is_enabled( $term_id ) ) {
				return Category_Conversion_Scope_Settings::FULL_CONTENT;
			}
		}

		return null;
	}
}
