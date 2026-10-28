<?php
/**
 * カテゴリ一致判定を WordPress の取得 API の代替処理で検証する。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Service;

use Foriba\RubyMarkupConverter\Markup\Value\Apply_Mode;
use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;

/**
 * 所属カテゴリ取得の代替処理。
 *
 * @param int    $post_id 投稿 ID.
 * @param string $taxonomy タクソノミー.
 * @return array|false|\WP_Error カテゴリ一覧または取得失敗.
 */
function get_the_terms( $post_id, $taxonomy ) {
	\check_same( 'category', $taxonomy, 'Only category taxonomy queried' );
	$GLOBALS['test_category_reads'][] = $post_id;
	return $GLOBALS['test_category_terms'][ $post_id ] ?? false;
}

/**
 * 祖先取得 API の返り値を代替する。階層探索自体は WordPress の責務。
 *
 * @param int    $term_id カテゴリ ID.
 * @param string $taxonomy タクソノミー.
 * @param string $resource_type リソース種別.
 * @return int[] 親から順に並ぶ祖先 ID.
 */
function get_ancestors( $term_id, $taxonomy, $resource_type ) {
	\check_same( 'category', $taxonomy, 'Ancestors use category taxonomy' );
	\check_same( 'taxonomy', $resource_type, 'Ancestor resource is explicit' );
	return $GLOBALS['test_category_ancestors'][ $term_id ] ?? array();
}

/**
 * タームメタの一括読み込み要求を記録する。
 *
 * @param int[] $term_ids カテゴリ ID.
 * @return bool 読み込み結果.
 */
function update_termmeta_cache( $term_ids ) {
	$GLOBALS['test_term_meta_primes'][] = $term_ids;
	return true;
}

require_once ABSPATH . WPINC . '/class-wp-error.php';
$category_matcher                   = new Category_Target_Matcher( new Target_Category_Settings() );
$target_policy                      = new Conversion_Target_Policy();
$GLOBALS['test_category_reads']     = array();
$GLOBALS['test_term_meta_primes']   = array();
$GLOBALS['test_category_ancestors'] = array(
	2 => array( 1 ),
	3 => array( 1 ),
	4 => array( 2, 1 ),
);
$GLOBALS['test_category_terms']     = array(
	10 => array( (object) array( 'term_id' => 1 ) ),
	20 => array( (object) array( 'term_id' => 2 ) ),
	30 => array( (object) array( 'term_id' => 3 ) ),
	40 => array( (object) array( 'term_id' => 4 ) ),
	50 => array( (object) array( 'term_id' => 9 ), (object) array( 'term_id' => 3 ) ),
	60 => new \WP_Error( 'term_error', 'Unable to read categories' ),
	80 => array(),
);
try {
	$GLOBALS['test_term_meta'] = array( 1 => Target_Category_Settings::FULL_CONTENT );
	\check_same( false, $category_matcher->matches( 0 ), 'Unknown ID does not match' );
	\check_same( false, $category_matcher->matches( -10 ), 'Negative ID does not match' );
	\check_same( array(), $GLOBALS['test_category_reads'], 'No queries for invalid post IDs' );
	foreach ( array(
		array( 10, array( 1 ), true, 'Selected category itself' ),
		array( 20, array( 1 ), true, 'Child category' ),
		array( 40, array( 1 ), true, 'Grandchild category' ),
		array( 10, array( 2 ), false, 'Child selection excludes parent' ),
		array( 30, array( 2 ), false, 'Child selection excludes sibling' ),
		array( 50, array( 1 ), true, 'Any assigned category may match' ),
		array( 20, array( 9, 1 ), true, 'Any selected category may match' ),
		array( 20, array(), false, 'No selected categories' ),
		array( 20, array( 999 ), false, 'Unrelated selection' ),
		array( 70, array( 1 ), false, 'Missing post' ),
		array( 80, array( 1 ), false, 'No categories' ),
		array( 60, array( 1 ), false, 'Term API error' ),
	) as $category_case ) {
		list( $test_post_id, $selected_ids, $expected_match, $label ) = $category_case;
		$GLOBALS['test_term_meta']                                    = array_fill_keys( $selected_ids, Target_Category_Settings::FULL_CONTENT );
		\check_same( $expected_match, $category_matcher->matches( $test_post_id ), $label );
	}
	// 所属カテゴリと祖先のメタは、照合前に 1 回でまとめて読み込む.
	$GLOBALS['test_term_meta']        = array();
	$GLOBALS['test_term_meta_primes'] = array();
	$category_matcher->matches( 40 );
	\check_same( array( array( 4, 2, 1 ) ), $GLOBALS['test_term_meta_primes'], 'Assigned and ancestor meta primed once' );
	$GLOBALS['test_term_meta_primes'] = array();
	$category_matcher->matches( 50 );
	\check_same( array( array( 9, 3, 1 ) ), $GLOBALS['test_term_meta_primes'], 'Shared ancestors primed without duplicates' );
	$GLOBALS['test_term_meta_primes'] = array();
	$category_matcher->matches( 80 );
	\check_same( array(), $GLOBALS['test_term_meta_primes'], 'No meta query without categories' );
	$GLOBALS['test_term_meta'] = array( 1 => Target_Category_Settings::FULL_CONTENT );
	$category_matches          = $category_matcher->matches( 40 );
	\check_same( Conversion_Target_Policy::FULL_CONTENT, $target_policy->resolve( Apply_Mode::selected_areas(), null, $category_matches ), 'Descendant enables full conversion' );
	\check_same( Conversion_Target_Policy::NONE, $target_policy->resolve( Apply_Mode::all(), Conversion_Target_Policy::NONE, $category_matches ), 'Post disable wins over category and global mode' );
	$GLOBALS['test_category_terms'][40] = array();
	\check_same( false, $category_matcher->matches( 40 ), 'Changed membership is not cached' );
	$GLOBALS['test_category_terms'][40] = array( (object) array( 'term_id' => 4 ) );
	$GLOBALS['test_term_meta']          = array();
	\check_same( false, $category_matcher->matches( 40 ), 'Changed selection is not cached' );
} finally {
	$GLOBALS['test_term_meta'] = array();
	unset( $GLOBALS['test_category_reads'], $GLOBALS['test_category_ancestors'], $GLOBALS['test_category_terms'], $GLOBALS['test_term_meta_primes'] );
}
