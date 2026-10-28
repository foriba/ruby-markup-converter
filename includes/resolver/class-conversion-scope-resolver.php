<?php
/**
 * 投稿 ID から実効的な変換範囲を取得する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Resolver;

use Foriba\RubyMarkupConverter\Markup\Value\Conversion_Mode;
use Foriba\RubyMarkupConverter\Service\Conversion_Scope_Policy;
use Foriba\RubyMarkupConverter\Settings\Category_Conversion_Scope_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 設定取得と共通判定を接続する。投稿 ID の推測や HTML 変換は行わない。
 */
final class Conversion_Scope_Resolver {
	/**
	 * 全体設定の取得元。
	 *
	 * @var Conversion_Mode_Resolver
	 */
	private Conversion_Mode_Resolver $conversion_mode_resolver;

	/**
	 * 投稿別設定の取得元。
	 *
	 * @var Post_Conversion_Scope_Resolver
	 */
	private Post_Conversion_Scope_Resolver $post_conversion_scope_resolver;

	/**
	 * カテゴリ別設定の取得元。
	 *
	 * @var Category_Conversion_Scope_Resolver
	 */
	private Category_Conversion_Scope_Resolver $category_conversion_scope_resolver;

	/**
	 * 優先順位の判定。
	 *
	 * @var Conversion_Scope_Policy
	 */
	private Conversion_Scope_Policy $conversion_scope_policy;

	/**
	 * 標準の依存オブジェクトを使って生成する。
	 *
	 * @return self 判定の取得元.
	 */
	public static function create_default(): self {
		return new self(
			new Conversion_Mode_Resolver(),
			new Post_Conversion_Scope_Resolver(),
			new Category_Conversion_Scope_Resolver( new Category_Conversion_Scope_Settings() ),
			new Conversion_Scope_Policy()
		);
	}

	/**
	 * 必要な依存を受け取る。
	 *
	 * @param Conversion_Mode_Resolver           $conversion_mode_resolver 全体設定.
	 * @param Post_Conversion_Scope_Resolver     $post_conversion_scope_resolver 投稿別設定.
	 * @param Category_Conversion_Scope_Resolver $category_conversion_scope_resolver カテゴリ別設定.
	 * @param Conversion_Scope_Policy            $conversion_scope_policy 優先順位.
	 */
	public function __construct(
		Conversion_Mode_Resolver $conversion_mode_resolver,
		Post_Conversion_Scope_Resolver $post_conversion_scope_resolver,
		Category_Conversion_Scope_Resolver $category_conversion_scope_resolver,
		Conversion_Scope_Policy $conversion_scope_policy
	) {
		$this->conversion_mode_resolver           = $conversion_mode_resolver;
		$this->post_conversion_scope_resolver     = $post_conversion_scope_resolver;
		$this->category_conversion_scope_resolver = $category_conversion_scope_resolver;
		$this->conversion_scope_policy            = $conversion_scope_policy;
	}

	/**
	 * 投稿別指定を最優先にして、呼び出し時点の変換範囲を返す。
	 *
	 * 優先条件で決まる場合はカテゴリを取得しない。判定結果はキャッシュしない。
	 * 不明な投稿 ID は 0 とし、全体設定と明示範囲の従来動作を維持する。
	 *
	 * @param int $post_id 対象投稿 ID.
	 * @return string Conversion_Scope_Policy の判定定数.
	 */
	public function get( int $post_id ): string {
		$post_scope     = $this->post_conversion_scope_resolver->get( $post_id );
		$mode           = $this->conversion_mode_resolver->get();
		$category_scope = null;
		if ( null === $post_scope && ! $mode->equals( Conversion_Mode::all() ) && 0 < $post_id ) {
			$category_scope = $this->category_conversion_scope_resolver->get( $post_id );
		}
		return $this->conversion_scope_policy->resolve( $mode, $post_scope, $category_scope );
	}
}
