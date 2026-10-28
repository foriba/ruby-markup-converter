<?php
/**
 * 変換入口で処理中の投稿 ID を取得する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 検索・カテゴリ画面の queried object ID を投稿 ID として流用しない。
 */
final class Post_Context {
	/**
	 * 現在のループで処理中の投稿 ID を返す。
	 *
	 * 本文・ショートコードには投稿 ID 引数がないため、WordPress の投稿文脈を使う。
	 * 外部コードが任意の本文を渡す場合は、その投稿文脈の設定も必要となる。
	 *
	 * @return int 投稿文脈がない場合は 0.
	 */
	public static function current_id(): int {
		global $post;
		return $post instanceof \WP_Post && 0 < $post->ID ? $post->ID : 0;
	}

	/**
	 * ブロック自身の文脈を優先して投稿 ID を返す。
	 *
	 * @param \WP_Block|null $instance ブロックのインスタンス.
	 * @return int 投稿 ID。不正な明示文脈はグローバルに置き換えず 0.
	 */
	public static function block_id( ?\WP_Block $instance ): int {
		if ( null !== $instance && array_key_exists( 'postId', $instance->context ) ) {
			$id = $instance->context['postId'];
			return is_int( $id ) && 0 < $id ? $id : 0;
		}
		return self::current_id();
	}
}
