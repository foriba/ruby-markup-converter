<?php
/**
 * 投稿に対する変換範囲の優先順位を判定する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Service;

use Foriba\RubyMarkupConverter\Markup\Value\Apply_Mode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 正規化済みの条件から変換範囲を決める、副作用のない判定クラス。
 *
 * 投稿 ID の特定、DB 参照、カテゴリ階層の探索、HTML 変換は行わない。
 * 戻り値は判定結果であり、設定として保存する文字列ではない。
 */
final class Conversion_Target_Policy {
	/**
	 * ブロック・ショートコードを含め、変換しない。
	 */
	public const NONE = 'none';

	/**
	 * ブロック・ショートコードで指定した範囲だけ変換する。
	 */
	public const SELECTED_AREAS = 'selected_areas';

	/**
	 * 本文全体を変換する。
	 */
	public const FULL_CONTENT = 'full_content';

	/**
	 * 投稿別指定、全体設定、カテゴリの順に判定する。
	 *
	 * 投稿別指定が未保存の場合は null を渡す。指定がある場合は、その範囲をそのまま返す。
	 * 定義外の値は未保存として扱う。
	 * カテゴリ一致は、選択カテゴリまたはその子孫に一つでも所属する場合に true。
	 * 投稿 ID を特定できない呼び出し元は null と false を渡し、
	 * 全体設定と明示された範囲による従来の動作を維持する。
	 *
	 * @param Apply_Mode  $apply_mode 全体の適用モード.
	 * @param string|null $post_scope 投稿別に指定された NONE、SELECTED_AREAS、FULL_CONTENT。null は継承.
	 * @param bool        $category_matches 対象カテゴリに一致するか.
	 * @return string NONE、SELECTED_AREAS、FULL_CONTENT のいずれか.
	 */
	public function resolve(
		Apply_Mode $apply_mode,
		?string $post_scope,
		bool $category_matches
	): string {
		if ( in_array( $post_scope, array( self::NONE, self::SELECTED_AREAS, self::FULL_CONTENT ), true ) ) {
			return $post_scope;
		}

		if ( $apply_mode->equals( Apply_Mode::all() ) || $category_matches ) {
			return self::FULL_CONTENT;
		}

		return self::SELECTED_AREAS;
	}
}
