<?php
/**
 * 標準カテゴリー画面に変換対象の選択欄を追加する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Settings\Settings_Identifiers;
use Foriba\RubyMarkupConverter\Settings\Target_Category_Settings;
use Foriba\RubyMarkupConverter\Resolver\Apply_Mode_Resolver;
use Foriba\RubyMarkupConverter\Markup\Value\Apply_Mode;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** カテゴリーの追加・編集フォームと保存処理を接続する。 */
final class Admin_Category_Controller {
	/**
	 * 対象カテゴリーの保存先。
	 *
	 * @var Target_Category_Settings
	 */
	private Target_Category_Settings $target_category_settings;

	/**
	 * 保存担当を受け取る。
	 *
	 * @param Target_Category_Settings $target_category_settings カテゴリー設定.
	 */
	public function __construct( Target_Category_Settings $target_category_settings ) {
		$this->target_category_settings = $target_category_settings;
	}

	/** 標準カテゴリー画面のフックを登録する。 */
	public function register_hooks(): void {
		add_action( 'category_add_form_fields', array( $this, 'render_add_field' ) );
		add_action( 'category_edit_form_fields', array( $this, 'render_edit_field' ) );
		add_action( 'created_category', array( $this, 'save_created_category' ) );
		add_action( 'edited_category', array( $this, 'save_edited_category' ) );
	}

	/** 新規追加フォームの選択欄を表示する。 */
	public function render_add_field(): void {
		if ( ! current_user_can( Settings_Identifiers::CATEGORY_CAPABILITY ) ) {
			return;
		}
		echo '<div class="form-field"><label for="rubymaco-category-enabled">Ruby Markup Converter</label>';
		$this->render_field( 0 );
		echo '</div>';
	}

	/**
	 * 編集フォームに標準のテーブル行を追加する。
	 *
	 * @param \WP_Term $term 編集するカテゴリー.
	 */
	public function render_edit_field( \WP_Term $term ): void {
		if ( ! current_user_can( Settings_Identifiers::CATEGORY_CAPABILITY ) ) {
			return;
		}
		echo '<tr class="form-field"><th scope="row">Ruby Markup Converter</th><td>';
		$this->render_field( $term->term_id );
		echo '</td></tr>';
	}

	/**
	 * 明示的な選択と継承による適用を区別して表示する。
	 *
	 * @param int $term_id 既存カテゴリー ID。新規追加は 0.
	 */
	private function render_field( int $term_id ): void {
		wp_nonce_field( 'rubymaco_category_' . $term_id, 'rubymaco_category_nonce', false );
		echo '<input type="hidden" name="rubymaco_category_enabled" value="0">';
		echo '<label for="rubymaco-category-enabled"><input type="checkbox" id="rubymaco-category-enabled" name="rubymaco_category_enabled" value="1" aria-describedby="rubymaco-category-description" ';
		checked( $this->target_category_settings->is_enabled( $term_id ) );
		echo '> ';
		echo esc_html__( 'Convert the full text of posts in this category and its descendants.', 'ruby-markup-converter' );
		echo '</label><p class="description" id="rubymaco-category-description">';
		echo esc_html__( 'Leaving this unchecked does not disable conversion inherited from a parent category or full-content mode. A post-specific override takes precedence.', 'ruby-markup-converter' );
		if ( ( new Apply_Mode_Resolver() )->get()->equals( Apply_Mode::all() ) ) {
			echo ' ' . esc_html__( 'Full-content mode already includes all categories. This selection is retained for other modes.', 'ruby-markup-converter' );
		} elseif ( $this->has_enabled_ancestor( $term_id ) ) {
			echo ' ' . esc_html__( 'This category is already included by a selected parent category.', 'ruby-markup-converter' );
		}
		echo '</p>';
	}

	/**
	 * 祖先のカテゴリーのいずれかが対象として指定されているかを返す。
	 *
	 * @param int $term_id 既存カテゴリー ID。新規追加は 0.
	 * @return bool 指定された祖先がある場合に true.
	 */
	private function has_enabled_ancestor( int $term_id ): bool {
		if ( 0 >= $term_id ) {
			return false;
		}
		foreach ( get_ancestors( $term_id, 'category', 'taxonomy' ) as $ancestor_id ) {
			if ( $this->target_category_settings->is_enabled( $ancestor_id ) ) {
				return true;
			}
		}
		return false;
	}

	/**
	 * 新規追加フォームの送信を保存する。
	 *
	 * @param int $term_id 作成されたカテゴリー ID.
	 */
	public function save_created_category( int $term_id ): void {
		$this->save( $term_id, 0 );
	}

	/**
	 * 編集フォームの送信を保存する。
	 *
	 * @param int $term_id 編集されたカテゴリー ID.
	 */
	public function save_edited_category( int $term_id ): void {
		$this->save( $term_id, $term_id );
	}

	/**
	 * 専用 nonce を持つ送信だけを処理し、他のカテゴリーの設定を維持する。
	 *
	 * クイック編集、REST、インポートなど、項目を送信しない操作では変更しない。
	 *
	 * @param int $term_id 保存対象 ID.
	 * @param int $nonce_id nonce が対象とする ID。新規追加は 0.
	 */
	private function save( int $term_id, int $nonce_id ): void {
		if (
			! current_user_can( Settings_Identifiers::CATEGORY_CAPABILITY )
			|| ! isset( $_POST['rubymaco_category_nonce'], $_POST['rubymaco_category_enabled'] )
			|| ! is_string( $_POST['rubymaco_category_nonce'] )
			|| ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['rubymaco_category_nonce'] ) ), 'rubymaco_category_' . $nonce_id )
			|| ! in_array( $_POST['rubymaco_category_enabled'], array( '0', '1' ), true )
		) {
			return;
		}
		try {
			$this->target_category_settings->save( $term_id, '1' === $_POST['rubymaco_category_enabled'] );
		} catch ( \InvalidArgumentException | \RuntimeException $error ) {
			wp_die( esc_html__( 'The category was saved, but its Ruby Markup Converter setting could not be saved. Please reopen the category and try again.', 'ruby-markup-converter' ) );
		}
	}
}
