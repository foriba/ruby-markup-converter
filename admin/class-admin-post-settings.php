<?php
/**
 * 投稿・固定ページのブロックエディタに個別設定を読み込む。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

namespace Foriba\RubyMarkupConverter\Admin;

use Foriba\RubyMarkupConverter\Plugin_Info;
use Foriba\RubyMarkupConverter\Resolver\Post_Conversion_Scope_Resolver;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** 保存はエディタと登録済みの投稿メタ REST API に委譲する。 */
final class Admin_Post_Settings {
	/**
	 * 配置情報。
	 *
	 * @var Plugin_Info
	 */
	private Plugin_Info $plugin_info;

	/**
	 * 配置情報を受け取る。
	 *
	 * @param Plugin_Info $plugin_info プラグイン情報.
	 */
	public function __construct( Plugin_Info $plugin_info ) {
		$this->plugin_info = $plugin_info;
	}

	/** エディタ用アセットのフックを登録する。 */
	public function register_hooks(): void {
		add_action( 'enqueue_block_editor_assets', array( $this, 'enqueue_assets' ) );
	}

	/** 編集できる投稿・固定ページにだけ設定 UI を読み込む。 */
	public function enqueue_assets(): void {
		$screen = get_current_screen();
		$post   = get_post();
		if (
			null === $screen || 'post' !== $screen->base
			|| ! $post instanceof \WP_Post
			|| ! in_array( $post->post_type, array( 'post', 'page' ), true )
			|| ! post_type_supports( $post->post_type, 'custom-fields' )
			|| ! current_user_can( 'edit_post', $post->ID )
		) {
			return;
		}
		$directory = $this->plugin_info->get_directory_path() . 'editor/settings/build/';
		if ( ! is_file( $directory . 'index.asset.php' ) || ! is_file( $directory . 'index.js' ) ) {
			return;
		}
		$asset = require $directory . 'index.asset.php';
		wp_enqueue_script(
			'rubymaco-post-settings',
			$this->plugin_info->get_directory_url() . 'editor/settings/build/index.js',
			array_values( array_unique( array_merge( $asset['dependencies'], array( 'wp-editor', 'wp-data', 'wp-plugins' ) ) ) ),
			$asset['version'],
			true
		);
		wp_add_inline_script(
			'rubymaco-post-settings',
			'window.rubymacoPostSettings = ' . wp_json_encode(
				array(
					'metaKey' => Post_Conversion_Scope_Resolver::META_KEY,
					'options' => array(
						array(
							'value' => Post_Conversion_Scope_Resolver::INHERIT,
							'label' => __( 'Follow global and category settings', 'ruby-markup-converter' ),
						),
						array(
							'value' => Post_Conversion_Scope_Resolver::FULL_CONTENT,
							'label' => 'page' === $post->post_type
								? __( 'Convert the full text of this page', 'ruby-markup-converter' )
								: __( 'Convert the full text of this post', 'ruby-markup-converter' ),
						),
						array(
							'value' => Post_Conversion_Scope_Resolver::NONE,
							'label' => 'page' === $post->post_type
								? __( 'Do not convert this page', 'ruby-markup-converter' )
								: __( 'Do not convert this post', 'ruby-markup-converter' ),
						),
					),
					'label'   => __( 'Markup conversion', 'ruby-markup-converter' ),
					'help'    => __( 'The default follows global and category settings; blocks and shortcodes still work outside those targets. Disabling conversion here also disables blocks and shortcodes. Save or update the post to apply changes.', 'ruby-markup-converter' ),
				),
				JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT
			) . ';',
			'before'
		);
	}
}
