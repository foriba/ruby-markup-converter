<?php
/**
 * タームメタ API の代替処理。カテゴリ設定を使う各テストで共有する。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Settings;

/**
 * タームメタ取得の代替処理。
 *
 * @param int    $term_id カテゴリ ID.
 * @param string $key メタキー.
 * @param bool   $single 単一値の取得.
 * @return mixed 保存値。未保存は登録済みの初期値と同じ空文字.
 */
function get_term_meta( $term_id, $key, $single ) {
	\check_same( Target_Category_Settings::META_KEY, $key, 'Category meta key' );
	\check_same( true, $single, 'Category meta reads a single value' );
	$GLOBALS['test_term_meta_reads'][] = $term_id;
	return $GLOBALS['test_term_meta'][ $term_id ] ?? '';
}

/**
 * タームメタ更新の代替処理。失敗の再現には test_term_meta_writer を使う.
 *
 * @param int    $term_id カテゴリ ID.
 * @param string $key メタキー.
 * @param mixed  $value 保存値.
 * @return bool 更新結果.
 */
function update_term_meta( $term_id, $key, $value ) {
	\check_same( Target_Category_Settings::META_KEY, $key, 'Only category meta written' );
	if ( isset( $GLOBALS['test_term_meta_writer'] ) ) {
		return ( $GLOBALS['test_term_meta_writer'] )( $term_id, $value );
	}
	$GLOBALS['test_term_meta'][ $term_id ] = $value;
	return true;
}

/**
 * タームメタ削除の代替処理。失敗の再現には test_term_meta_writer を使う.
 *
 * @param int    $term_id カテゴリ ID.
 * @param string $key メタキー.
 * @return bool 削除結果.
 */
function delete_term_meta( $term_id, $key ) {
	\check_same( Target_Category_Settings::META_KEY, $key, 'Only category meta deleted' );
	if ( isset( $GLOBALS['test_term_meta_writer'] ) ) {
		return ( $GLOBALS['test_term_meta_writer'] )( $term_id, null );
	}
	unset( $GLOBALS['test_term_meta'][ $term_id ] );
	return true;
}

/**
 * カテゴリ存在確認の代替処理。
 *
 * @param int    $id カテゴリ ID.
 * @param string $taxonomy タクソノミー.
 * @return \WP_Term|null 存在するカテゴリ.
 */
function get_term( $id, $taxonomy ) {
	\check_same( 'category', $taxonomy, 'Category taxonomy validation' );
	return in_array( $id, $GLOBALS['test_existing_categories'] ?? array(), true )
		? new \WP_Term(
			(object) array(
				'term_id'  => $id,
				'taxonomy' => 'category',
			)
		)
		: null;
}

require_once ABSPATH . WPINC . '/class-wp-term.php';
$GLOBALS['test_term_meta']       = array();
$GLOBALS['test_term_meta_reads'] = array();
