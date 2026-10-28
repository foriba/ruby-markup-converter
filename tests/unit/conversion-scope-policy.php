<?php
/**
 * 変換対象判定の優先順位を検証する。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Markup\Value\Conversion_Mode;
use Foriba\RubyMarkupConverter\Service\Conversion_Scope_Policy;

$conversion_scope_policy = new Conversion_Scope_Policy();

// 投稿別指定 × 全体設定 × カテゴリ一致の全12通りを固定の期待値で検証する.
$conversion_scope_cases = array(
	array( Conversion_Scope_Policy::NONE, Conversion_Mode::selected(), null, Conversion_Scope_Policy::NONE ),
	array( Conversion_Scope_Policy::NONE, Conversion_Mode::selected(), Conversion_Scope_Policy::FULL_CONTENT, Conversion_Scope_Policy::NONE ),
	array( Conversion_Scope_Policy::NONE, Conversion_Mode::all(), null, Conversion_Scope_Policy::NONE ),
	array( Conversion_Scope_Policy::NONE, Conversion_Mode::all(), Conversion_Scope_Policy::FULL_CONTENT, Conversion_Scope_Policy::NONE ),
	array( Conversion_Scope_Policy::FULL_CONTENT, Conversion_Mode::selected(), null, Conversion_Scope_Policy::FULL_CONTENT ),
	array( Conversion_Scope_Policy::FULL_CONTENT, Conversion_Mode::selected(), Conversion_Scope_Policy::FULL_CONTENT, Conversion_Scope_Policy::FULL_CONTENT ),
	array( Conversion_Scope_Policy::FULL_CONTENT, Conversion_Mode::all(), null, Conversion_Scope_Policy::FULL_CONTENT ),
	array( Conversion_Scope_Policy::FULL_CONTENT, Conversion_Mode::all(), Conversion_Scope_Policy::FULL_CONTENT, Conversion_Scope_Policy::FULL_CONTENT ),
	array( null, Conversion_Mode::selected(), null, Conversion_Scope_Policy::BLOCKS_AND_SHORTCODES ),
	array( null, Conversion_Mode::selected(), Conversion_Scope_Policy::FULL_CONTENT, Conversion_Scope_Policy::FULL_CONTENT ),
	array( null, Conversion_Mode::all(), null, Conversion_Scope_Policy::FULL_CONTENT ),
	array( null, Conversion_Mode::all(), Conversion_Scope_Policy::FULL_CONTENT, Conversion_Scope_Policy::FULL_CONTENT ),
);

foreach ( $conversion_scope_cases as $case_index => $conversion_scope_case ) {
	list( $post_scope, $conversion_mode, $category_scope, $expected_scope ) = $conversion_scope_case;
	check_same(
		$expected_scope,
		$conversion_scope_policy->resolve( $conversion_mode, $post_scope, $category_scope ),
		'Conversion target priority case ' . $case_index
	);
}

// 同一インスタンスを再利用しても、前の投稿の無効指定を持ち越さない.
check_same(
	Conversion_Scope_Policy::NONE,
	$conversion_scope_policy->resolve( Conversion_Mode::all(), Conversion_Scope_Policy::NONE, Conversion_Scope_Policy::FULL_CONTENT ),
	'Explicit disable excludes blocks and shortcodes as well'
);
check_same(
	Conversion_Scope_Policy::BLOCKS_AND_SHORTCODES,
	$conversion_scope_policy->resolve( Conversion_Mode::selected(), null, null ),
	'Missing post context or initial settings preserve explicit areas'
);
check_same(
	Conversion_Scope_Policy::FULL_CONTENT,
	$conversion_scope_policy->resolve( Conversion_Mode::all(), null, null ),
	'Missing post context preserves global full conversion'
);
// 投稿別に指定できるのは FULL_CONTENT と NONE だけ。判定結果専用の値や旧値は継承として扱う.
foreach ( array( Conversion_Scope_Policy::BLOCKS_AND_SHORTCODES, 'selected_areas', 'inherit', 'enabled', 'disabled', '', 'FULL_CONTENT' ) as $unknown_scope ) {
	check_same(
		Conversion_Scope_Policy::BLOCKS_AND_SHORTCODES,
		$conversion_scope_policy->resolve( Conversion_Mode::selected(), $unknown_scope, null ),
		'Unknown post scope is treated as inherited: ' . $unknown_scope
	);
}
check_same(
	Conversion_Scope_Policy::FULL_CONTENT,
	$conversion_scope_policy->resolve( Conversion_Mode::all(), Conversion_Scope_Policy::BLOCKS_AND_SHORTCODES, null ),
	'Result-only scope cannot narrow global full conversion'
);
// カテゴリ別に指定できるのは FULL_CONTENT だけ。それ以外の値は指定なしとして扱う.
foreach ( array( Conversion_Scope_Policy::NONE, Conversion_Scope_Policy::BLOCKS_AND_SHORTCODES, '1', 'inherit', '' ) as $unknown_category_scope ) {
	check_same(
		Conversion_Scope_Policy::BLOCKS_AND_SHORTCODES,
		$conversion_scope_policy->resolve( Conversion_Mode::selected(), null, $unknown_category_scope ),
		'Unknown category scope is treated as unspecified: ' . $unknown_category_scope
	);
}
