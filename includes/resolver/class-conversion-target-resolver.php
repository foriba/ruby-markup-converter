<?php
/**
 * 投稿 ID から実効的な変換範囲を取得する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Resolver;

use Foriba\RubyMarkupConverter\Markup\Value\Apply_Mode;
use Foriba\RubyMarkupConverter\Service\Category_Target_Matcher;
use Foriba\RubyMarkupConverter\Service\Conversion_Target_Policy;
use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定取得と共通判定を接続する。投稿 ID の推測や HTML 変換は行わない。
 */
final class Conversion_Target_Resolver {
	/**
	 * 全体設定の取得元。
	 *
	 * @var Apply_Mode_Resolver
	 */
	private Apply_Mode_Resolver $apply_mode_resolver;

	/**
	 * 投稿別設定の取得元。
	 *
	 * @var Post_Conversion_Override_Resolver
	 */
	private Post_Conversion_Override_Resolver $post_conversion_override_resolver;

	/**
	 * カテゴリ判定。
	 *
	 * @var Category_Target_Matcher
	 */
	private Category_Target_Matcher $category_target_matcher;

	/**
	 * 優先順位の判定。
	 *
	 * @var Conversion_Target_Policy
	 */
	private Conversion_Target_Policy $conversion_target_policy;

	/**
	 * 標準の依存オブジェクトを使って生成する。
	 *
	 * @return self 判定の取得元.
	 */
	public static function create_default(): self {
		return new self(
			new Apply_Mode_Resolver(),
			new Post_Conversion_Override_Resolver(),
			new Category_Target_Matcher( new Target_Category_Settings() ),
			new Conversion_Target_Policy()
		);
	}

	/**
	 * 必要な依存を受け取る。
	 *
	 * @param Apply_Mode_Resolver               $apply_mode_resolver 全体設定.
	 * @param Post_Conversion_Override_Resolver $post_conversion_override_resolver 投稿別設定.
	 * @param Category_Target_Matcher           $category_target_matcher カテゴリ判定.
	 * @param Conversion_Target_Policy          $conversion_target_policy 優先順位.
	 */
	public function __construct(
		Apply_Mode_Resolver $apply_mode_resolver,
		Post_Conversion_Override_Resolver $post_conversion_override_resolver,
		Category_Target_Matcher $category_target_matcher,
		Conversion_Target_Policy $conversion_target_policy
	) {
		$this->apply_mode_resolver               = $apply_mode_resolver;
		$this->post_conversion_override_resolver = $post_conversion_override_resolver;
		$this->category_target_matcher           = $category_target_matcher;
		$this->conversion_target_policy          = $conversion_target_policy;
	}

	/**
	 * 投稿別指定を最優先にして、呼び出し時点の変換範囲を返す。
	 *
	 * 優先条件で決まる場合はカテゴリを取得しない。判定結果はキャッシュしない。
	 * 不明な投稿 ID は 0 とし、全体設定と明示範囲の従来動作を維持する。
	 *
	 * @param int $post_id 対象投稿 ID.
	 * @return string Conversion_Target_Policy の判定定数.
	 */
	public function get( int $post_id ): string {
		$override = $this->post_conversion_override_resolver->get( $post_id );
		$mode     = $this->apply_mode_resolver->get();
		$matches  = false;
		if ( null === $override && ! $mode->equals( Apply_Mode::all() ) && 0 < $post_id ) {
			$matches = $this->category_target_matcher->matches( $post_id );
		}
		return $this->conversion_target_policy->resolve( $mode, $override, $matches );
	}
}
