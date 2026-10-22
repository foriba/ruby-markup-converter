<?php
/**
 * 変換対象判定の優先順位を検証する。
 *
 * @package RubyMarkupConverter
 */

use Foriba\RubyMarkupConverter\Markup\Value\Apply_Mode;
use Foriba\RubyMarkupConverter\Service\Conversion_Target_Policy;

$conversion_target_policy = new Conversion_Target_Policy();

// 投稿別指定 × 全体設定 × カテゴリ一致の全12通りを固定の期待値で検証する.
$conversion_target_cases = array(
	array( false, Apply_Mode::selected_areas(), false, Conversion_Target_Policy::NONE ),
	array( false, Apply_Mode::selected_areas(), true, Conversion_Target_Policy::NONE ),
	array( false, Apply_Mode::all(), false, Conversion_Target_Policy::NONE ),
	array( false, Apply_Mode::all(), true, Conversion_Target_Policy::NONE ),
	array( true, Apply_Mode::selected_areas(), false, Conversion_Target_Policy::FULL_CONTENT ),
	array( true, Apply_Mode::selected_areas(), true, Conversion_Target_Policy::FULL_CONTENT ),
	array( true, Apply_Mode::all(), false, Conversion_Target_Policy::FULL_CONTENT ),
	array( true, Apply_Mode::all(), true, Conversion_Target_Policy::FULL_CONTENT ),
	array( null, Apply_Mode::selected_areas(), false, Conversion_Target_Policy::SELECTED_AREAS ),
	array( null, Apply_Mode::selected_areas(), true, Conversion_Target_Policy::FULL_CONTENT ),
	array( null, Apply_Mode::all(), false, Conversion_Target_Policy::FULL_CONTENT ),
	array( null, Apply_Mode::all(), true, Conversion_Target_Policy::FULL_CONTENT ),
);

foreach ( $conversion_target_cases as $case_index => $conversion_target_case ) {
	list( $post_override, $apply_mode, $category_matches, $expected_scope ) = $conversion_target_case;
	check_same(
		$expected_scope,
		$conversion_target_policy->resolve( $apply_mode, $post_override, $category_matches ),
		'Conversion target priority case ' . $case_index
	);
}

// 同一インスタンスを再利用しても、前の投稿の無効指定を持ち越さない.
check_same(
	Conversion_Target_Policy::NONE,
	$conversion_target_policy->resolve( Apply_Mode::all(), false, true ),
	'Explicit disable excludes blocks and shortcodes as well'
);
check_same(
	Conversion_Target_Policy::SELECTED_AREAS,
	$conversion_target_policy->resolve( Apply_Mode::selected_areas(), null, false ),
	'Missing post context or initial settings preserve explicit areas'
);
check_same(
	Conversion_Target_Policy::FULL_CONTENT,
	$conversion_target_policy->resolve( Apply_Mode::all(), null, false ),
	'Missing post context preserves global full conversion'
);
