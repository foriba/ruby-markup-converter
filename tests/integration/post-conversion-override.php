<?php
/**
 * 投稿別設定の取得をメモリ上の投稿メタで検証する。
 *
 * @package RubyMarkupConverter
 */

namespace Foriba\RubyMarkupConverter\Resolver;

/**
 * 投稿メタ取得の代替処理。
 *
 * @param int    $post_id 投稿 ID.
 * @param string $key メタキー.
 * @param bool   $single 単一値の取得.
 * @return mixed 保存値または空文字.
 */
function get_post_meta( $post_id, $key, $single ) {
	\check_same( Post_Conversion_Override_Resolver::META_KEY, $key, 'Override meta key' );
	\check_same( true, $single, 'Override reads a single value' );
	$GLOBALS['test_override_reads'][] = $post_id;
	return $GLOBALS['test_override_meta'][ $post_id ] ?? '';
}

$override_resolver              = new Post_Conversion_Override_Resolver();
$GLOBALS['test_override_meta']  = array(
	10 => 'enabled',
	20 => 'disabled',
	30 => 'inherit',
);
$GLOBALS['test_override_reads'] = array();
try {
	\check_same( null, $override_resolver->get( 0 ), 'Unknown ID inherits' );
	\check_same( null, $override_resolver->get( -10 ), 'Negative ID inherits' );
	\check_same( array(), $GLOBALS['test_override_reads'], 'Invalid IDs never query metadata' );
	foreach ( array(
		10 => true,
		20 => false,
		30 => null,
		40 => null,
	) as $test_post_id => $expected ) {
		\check_same( $expected, $override_resolver->get( $test_post_id ), 'Explicit post metadata ' . $test_post_id );
	}
	foreach ( array( '', null, false, true, 0, 1, '0', '1', array(), 'ENABLED', ' enabled ', 'unknown' ) as $invalid_value ) {
		\check_same( null, $override_resolver->normalize( $invalid_value ), 'Invalid override inherits' );
	}
	$GLOBALS['test_override_meta'][10] = 'disabled';
	\check_same( false, $override_resolver->get( 10 ), 'Metadata changes are not cached' );
	\check_same( array( 10, 20, 30, 40, 10 ), $GLOBALS['test_override_reads'], 'Only explicit IDs queried' );
} finally {
	unset( $GLOBALS['test_override_meta'], $GLOBALS['test_override_reads'] );
}
