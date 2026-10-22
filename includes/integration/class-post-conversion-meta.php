<?php
/**
 * 投稿別の変換設定を WordPress 標準の投稿メタ API に登録する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Integration;

use Foriba\RubyMarkupConverter\Resolver\Post_Conversion_Override_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * REST の認証・nonce・投稿の存在確認・保存処理は WordPress に委譲する。
 */
final class Post_Conversion_Meta {
	/**
	 * 投稿タイプ登録後にメタを登録する。
	 */
	public function register_hooks(): void {
		add_action( 'init', array( $this, 'register_meta' ), 20 );
	}

	/**
	 * 投稿と固定ページに三状態のメタを登録する。
	 *
	 * REST の enum 検証で不正値を拒否する。custom-fields 対応が必要。
	 * 未保存は inherit とし、既存投稿への一括書き込みは行わない。
	 */
	public function register_meta(): void {
		foreach ( array( 'post', 'page' ) as $post_type ) {
			register_post_meta(
				$post_type,
				Post_Conversion_Override_Resolver::META_KEY,
				array(
					'type'              => 'string',
					'single'            => true,
					'default'           => Post_Conversion_Override_Resolver::INHERIT,
					'show_in_rest'      => array(
						'schema' => array(
							'type'    => 'string',
							'enum'    => array(
								Post_Conversion_Override_Resolver::INHERIT,
								Post_Conversion_Override_Resolver::ENABLED,
								Post_Conversion_Override_Resolver::DISABLED,
							),
							'context' => array( 'edit' ),
						),
					),
					'auth_callback'     => array( $this, 'can_edit' ),
					'sanitize_callback' => array( $this, 'sanitize' ),
				)
			);
		}
	}

	/**
	 * 対象投稿を編集できる利用者だけにメタの変更を許可する。
	 *
	 * @param bool   $allowed 元の判定.
	 * @param string $meta_key メタキー.
	 * @param int    $post_id 投稿 ID.
	 * @param int    $user_id 権限判定の対象ユーザー ID.
	 * @return bool 編集可能な場合に true.
	 */
	public function can_edit(
		bool $allowed,
		string $meta_key,
		int $post_id,
		int $user_id
	): bool {
		unset( $allowed );
		return Post_Conversion_Override_Resolver::META_KEY === $meta_key
			&& 0 < $post_id
			&& user_can( $user_id, 'edit_post', $post_id );
	}

	/**
	 * PHP 経由のメタ更新でも、未定義の保存値を残さない。
	 *
	 * @param mixed $value メタ値.
	 * @return string 三状態のいずれか。不正値は継承.
	 */
	public function sanitize( $value ): string {
		if ( in_array(
			$value,
			array(
				Post_Conversion_Override_Resolver::ENABLED,
				Post_Conversion_Override_Resolver::DISABLED,
			),
			true
		) ) {
			return $value;
		}
		return Post_Conversion_Override_Resolver::INHERIT;
	}
}
