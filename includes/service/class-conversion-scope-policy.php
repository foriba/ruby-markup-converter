<?php
/**
 * 投稿に対する変換範囲の優先順位を判定する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Service;

use Foriba\RubyMarkupConverter\Markup\Value\Conversion_Mode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 全体の変換モード・投稿別指定・カテゴリ指定から、その投稿の変換範囲を決める副作用のない判定クラス。
 *
 * 投稿 ID の特定、DB 参照、カテゴリ階層の探索、HTML 変換は行わない。
 * 戻り値は投稿ごとの判定結果。FULL_CONTENT と NONE は投稿別・カテゴリ別のメタの保存値にも使う。
 */
final class Conversion_Scope_Policy {
	/**
	 * ブロック・ショートコードを含め、変換しない。投稿別のメタの保存値にも使う。
	 *
	 * 互換性維持のため、値（メタの保存値）は変更不可。
	 */
	public const NONE = 'none';

	/**
	 * ブロックとショートコードの中だけ変換する。
	 *
	 * 変換モードが SELECTED で、投稿別・カテゴリ別の指定がない場合の結果。
	 * 保存しないため、値も変更可。
	 */
	public const BLOCKS_AND_SHORTCODES = 'blocks_and_shortcodes';

	/**
	 * 本文全体を変換する。投稿別・カテゴリ別のメタの保存値にも使う。
	 *
	 * 互換性維持のため、値（メタの保存値）は変更不可。
	 */
	public const FULL_CONTENT = 'full_content';

	/**
	 * 投稿別指定、全体の変換モード、カテゴリの順に判定する。
	 *
	 * 投稿別指定が未保存の場合は null を渡す。FULL_CONTENT または NONE の指定がある場合は、それを返す。
	 * それ以外の値は未保存として扱う。
	 * カテゴリ一致は、選択カテゴリまたはその子孫に一つでも所属する場合に true。
	 * 投稿 ID を特定できない呼び出し元は null と false を渡し、
	 * 全体の変換モードと、ブロック・ショートコードによる従来の動作を維持する。
	 *
	 * @param Conversion_Mode $conversion_mode 全体の変換モード.
	 * @param string|null     $post_scope 投稿別に指定された FULL_CONTENT または NONE。null は継承.
	 * @param bool            $category_matches 対象カテゴリに一致するか.
	 * @return string NONE、BLOCKS_AND_SHORTCODES、FULL_CONTENT のいずれか.
	 */
	public function resolve(
		Conversion_Mode $conversion_mode,
		?string $post_scope,
		bool $category_matches
	): string {
		if ( in_array( $post_scope, array( self::NONE, self::FULL_CONTENT ), true ) ) {
			return $post_scope;
		}

		if ( $conversion_mode->equals( Conversion_Mode::all() ) || $category_matches ) {
			return self::FULL_CONTENT;
		}

		return self::BLOCKS_AND_SHORTCODES;
	}
}
