<?php
/**
 * カテゴリ別の変換設定を WordPress 標準のタームメタ API に登録する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;
use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * メタの型・初期値・保存値の補正・変更権限を宣言する。
 *
 * 保存はカテゴリー画面の送信を受けて Target_Category_Settings が行う。REST API には公開しない。
 */
final class Category_Conversion_Meta {
	/**
	 * タクソノミー登録後にメタを登録する。
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ), 20 );
	}

	/**
	 * カテゴリに全文変換の指定を登録する。未保存は未指定とする。
	 */
	public function register_meta(): void {
		register_term_meta(
			'category',
			Target_Category_Settings::META_KEY,
			array(
				'type'              => 'string',
				'single'            => true,
				'default'           => '',
				'show_in_rest'      => false,
				'auth_callback'     => array( $this, 'can_edit' ),
				'sanitize_callback' => array( $this, 'sanitize' ),
			)
		);
	}

	/**
	 * カテゴリを管理できる利用者だけにメタの変更を許可する。
	 *
	 * @param bool   $allowed 元の判定.
	 * @param string $meta_key メタキー.
	 * @param int    $term_id カテゴリ ID.
	 * @param int    $user_id 権限判定の対象ユーザー ID.
	 * @return bool 変更可能な場合に true.
	 */
	public function can_edit(
		bool $allowed,
		string $meta_key,
		int $term_id,
		int $user_id
	): bool {
		unset( $allowed );
		return Target_Category_Settings::META_KEY === $meta_key
			&& 0 < $term_id
			&& user_can( $user_id, Settings_Identifiers::CATEGORY_CAPABILITY );
	}

	/**
	 * PHP 経由のメタ更新でも、定義外の保存値を残さない。
	 *
	 * @param mixed $value メタ値.
	 * @return string 指定値、またはそれ以外の場合は空文字.
	 */
	public function sanitize( $value ): string {
		return Target_Category_Settings::FULL_CONTENT === $value ? Target_Category_Settings::FULL_CONTENT : '';
	}
}
