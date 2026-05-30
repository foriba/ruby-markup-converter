<?php
/**
 * 管理画面の設定ページを描画する。
 *
 * @package RubyMarkupConverter
 */

declare(strict_types=1);

use foriba\rubymarkupconverter\RUBYMACO_Option_Key;
use foriba\rubymarkupconverter\RUBYMACO_Rule_Type;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Page Rendering
 */

/**
 * Ruby Markup Converter の設定画面を描画する。
 */
function rubymaco_render_settings_page(): void {
	if ( ! current_user_can( 'manage_options' ) ) {
		wp_die( esc_html__( 'You do not have permission to access this page.', 'ruby-markup-converter' ) );
		// ja-jp: 'このページにアクセスする権限がありません。'.
	}

	$view_data = rubymaco_get_admin_settings_view_data();
	?>
	<div class="wrap rubymaco-settings-page">
		<h1><?php echo esc_html__( 'Ruby Markup Converter', 'ruby-markup-converter' ); ?></h1>
		<p>
		<?php
		echo esc_html__(
			'Configure the conversion scope and the ruby/bouten markup rules to use.', // ja-jp: '変換を適用する範囲や、使用するルビ・傍点記法を設定します。'.
			'ruby-markup-converter'
		);
		?>
			</p>

		<form method="post" action="options.php" class="rubymaco-settings-form">
			<?php settings_fields( RUBYMACO_SETTINGS_GROUP ); ?>

			<div class="rubymaco-settings-layout">
				<?php
				rubymaco_render_settings_section(
					__( 'Conversion Scope', 'ruby-markup-converter' ), // ja-jp: '適用範囲'.
					fn() => rubymaco_render_apply_mode_field( $view_data['apply_mode_choices'] )
				);

				rubymaco_render_settings_section(
					__( 'Markup Rules', 'ruby-markup-converter' ), // ja-jp: '記法ルール'.
					function () use ( $view_data ) {
						rubymaco_render_settings_note(
							__( 'Notes', 'ruby-markup-converter' ), // ja-jp: '注意事項'.
							array(
								__( 'Nested markup structures are not supported. For example, placing one markup rule inside another, such as "｜BaseText1{{ruby|BaseText2|RubyAnnotation2}}《RubyAnnotation1》", may produce unexpected conversion results.', 'ruby-markup-converter' ),
								// ja-jp: '記法の入れ子構造は想定していません。たとえば「｜親文{{ruby|親文字|ルビ}}字《ルビ》」のように、ある記法の内部に別の記法を含める書き方では、想定と異なる変換結果となる場合があります。'.
								__( 'If the displayed result is not as expected, please review the markup content and rule combinations. Complex structures in particular may lead to unintended conversion results.', 'ruby-markup-converter' ),
								// ja-jp: '表示結果が意図と異なる場合は、記述内容や記法の組み合わせを確認してください。特に複雑な構造の場合、意図しない変換結果となることがあります。'.
							)
						);
						rubymaco_render_markup_rules_field( $view_data['rules'], $view_data );
					}
				);

				rubymaco_render_settings_section(
					__( 'Advanced Settings', 'ruby-markup-converter' ), // ja-jp: '高度な設定'.
					fn() => rubymaco_render_advanced_settings_field( $view_data )
				);
				?>
			</div>

			<?php
			submit_button( __( 'Save Settings', 'ruby-markup-converter' ) ); // ja-jp: '設定を保存'.
			?>
		</form>
	</div>
	<?php
}

/**
 * Field Renderers
 */

/**
 * 「適用範囲」設定フィールドを描画する。
 *
 * @param array<int, array{ id:string, value:string, label:string, description:string, is_selected:bool }> $choices 適用範囲の選択肢.
 */
function rubymaco_render_apply_mode_field( array $choices ): void {
	rubymaco_render_choice_group(
		$choices,
		RUBYMACO_Option_Key::APPLY_MODE,
		__( 'Conversion Scope', 'ruby-markup-converter' ), // ja-jp: '適用範囲'.
	);
}

/**
 * 記法ルール設定フィールドを描画する。
 *
 * @param array<int, array<string, mixed>> $rules     記法ルール一覧.
 * @param array<string, mixed>             $view_data 設定画面の表示データ.
 */
function rubymaco_render_markup_rules_field( array $rules, array $view_data ): void {
	$preview_settings = array(
		'style'    => $view_data['current_bouten_style'],
		'renderer' => $view_data['current_bouten_renderer'],
	);

	rubymaco_render_markup_rule_group(
		RUBYMACO_Rule_Type::RUBY,
		__( 'Ruby', 'ruby-markup-converter' ), // ja-jp: 'ルビ'.
		array_values(
			array_filter(
				$rules,
				static fn( array $r ): bool => RUBYMACO_Rule_Type::RUBY === $r['type']
			)
		),
		$preview_settings
	);

	rubymaco_render_markup_rule_group(
		RUBYMACO_Rule_Type::BOUTEN,
		__( 'Bouten', 'ruby-markup-converter' ), // ja-jp: '傍点'.
		array_values(
			array_filter(
				$rules,
				static fn( array $r ): bool => RUBYMACO_Rule_Type::BOUTEN === $r['type']
			)
		),
		$preview_settings,
		$view_data['bouten_style_choices']
	);
}

/**
 * 記法ルールのグループ（ルビまたは傍点）を描画する。
 *
 * @param string                                                                                           $type             ルール種別（ruby / bouten）.
 * @param string                                                                                           $label            グループ表示名.
 * @param array<int, array<string, mixed>>                                                                 $rules            このグループに属する整形済みのルール配列.
 * @param array{style:string, renderer:string}                                                             $preview_settings プレビュー用の設定値.
 * @param array<int, array{ id:string, value:string, label:string, description:string, is_selected:bool }> $bouten_choices   傍点セクションの場合のみ使用する選択肢データ.
 */
function rubymaco_render_markup_rule_group(
	string $type,
	string $label,
	array $rules,
	array $preview_settings,
	array $bouten_choices = array()
): void {
	if ( array() === $rules ) {
		return;
	}

	?>
	<section class="rubymaco-rule-group rubymaco-rule-group-<?php echo esc_attr( $type ); ?>">
		<h3 class="rubymaco-rule-group-title"><?php echo esc_html( $label ); ?></h3>

		<div class="rubymaco-rule-card-list">
			<?php foreach ( $rules as $rule ) : ?>
				<?php
				rubymaco_render_markup_rule_card(
					$rule,
					$preview_settings['style'],
					$preview_settings['renderer']
				);
				?>
			<?php endforeach; ?>
		</div>

		<?php
		if ( RUBYMACO_Rule_Type::BOUTEN === $type && array() !== $bouten_choices ) :
			?>
			<?php rubymaco_render_bouten_style_group_field( $bouten_choices ); ?>
		<?php endif; ?>
	</section>
	<?php
}

/**
 * 記法ルールカードを描画する。
 *
 * @param array<string, mixed> $rule                    記法ルール.
 * @param string               $current_bouten_style    現在の傍点種類.
 * @param string               $current_bouten_renderer 現在の傍点描画方式.
 */
function rubymaco_render_markup_rule_card( array $rule, string $current_bouten_style, string $current_bouten_renderer ): void {
	$rule_id     = $rule['id'];
	$is_enabled  = $rule['is_enabled'];
	$titles      = $rule['titles'];
	$examples    = $rule['examples'];
	$description = $rule['description'];
	?>
	<article class="rubymaco-rule-card<?php echo $is_enabled ? ' is-enabled' : ' is-disabled'; ?>">
		<label class="rubymaco-rule-card-selector" for="rubymaco-rule-<?php echo esc_attr( $rule_id ); ?>">
			<input
				id="rubymaco-rule-<?php echo esc_attr( $rule_id ); ?>"
				class="rubymaco-rule-card-checkbox"
				type="checkbox"
				name="<?php echo esc_attr( RUBYMACO_Option_Key::ENABLED_MARKUP_RULES ); ?>[]"
				value="<?php echo esc_attr( $rule_id ); ?>"
				<?php checked( $is_enabled ); ?>>
			<span class="rubymaco-rule-card-checkmark" aria-hidden="true"></span>
			<span class="rubymaco-rule-card-content">
				<?php rubymaco_render_rule_card_title( $titles, $is_enabled ); ?>
				<?php if ( array() !== $examples ) : ?>
					<?php
					rubymaco_render_rule_card_example_meta(
						__( 'Markup Example', 'ruby-markup-converter' ), // ja-jp: '記法例'.
						$examples
					);
					?>
					<?php
					$previews = array_map(
						static fn( string $example ): string => rubymaco_render_admin_rule_preview(
							$example,
							$rule,
							$current_bouten_style,
							$current_bouten_renderer
						),
						$examples
					);
					rubymaco_render_rule_card_preview_meta(
						__( 'Output Result', 'ruby-markup-converter' ), // ja-jp: '出力結果'.
						$previews
					);
					?>
				<?php endif; ?>
				<?php if ( '' !== $description ) : ?>
					<span class="rubymaco-rule-card-description">
						<?php echo esc_html( $description ); ?>
					</span>
				<?php endif; ?>
			</span>
		</label>
	</article>
	<?php
}

/**
 * 傍点グループ共通の「傍点の種類」設定フィールドを描画する。
 *
 * @param array<int, array{ id:string, value:string, label:string, description:string, is_selected:bool }> $choices 傍点種類の選択肢.
 */
function rubymaco_render_bouten_style_group_field( array $choices ): void {
	?>
	<div class="rubymaco-subfield rubymaco-bouten-style-group-field">
		<div class="rubymaco-subfield-title">
			<?php
			echo esc_html__( 'Bouten Style', 'ruby-markup-converter' );
			// ja-jp: '傍点の種類'.
			?>
			</div>
		<p class="rubymaco-subfield-description">
			<?php
			echo esc_html__( 'Select the type of mark used for bouten markup.', 'ruby-markup-converter' );
			// ja-jp '傍点記法で使用するマークの種類を選択します。'.
			?>
		</p>
		<?php rubymaco_render_bouten_style_field( $choices ); ?>
	</div>
	<?php
}

/**
 * 傍点の種類設定フィールドを描画する。
 *
 * @param array<int, array{ id:string, value:string, label:string, description:string, is_selected:bool }> $choices 傍点種類の選択肢.
 */
function rubymaco_render_bouten_style_field( array $choices ): void {
	rubymaco_render_choice_group(
		$choices,
		RUBYMACO_Option_Key::BOUTEN_STYLE,
		__( 'Bouten Style', 'ruby-markup-converter' ) // ja-jp: '傍点の種類'.
	);
}

/**
 * 高度な設定フィールドを描画する。
 *
 * @param array<string, mixed> $view_data 設定画面の表示データ.
 */
function rubymaco_render_advanced_settings_field( array $view_data ): void {
	?>
	<div class="rubymaco-advanced-settings">
		<section class="rubymaco-advanced-setting-item">
			<h3 class="rubymaco-advanced-setting-title">
				<?php
				echo esc_html__( 'Bouten Rendering Method', 'ruby-markup-converter' );
				// ja-jp: '傍点の描画方式'.
				?>
				</h3>
			<p class="rubymaco-advanced-setting-description">
				<?php
				echo esc_html__( 'Select how bouten marks are rendered. In most cases, the custom renderer is recommended.', 'ruby-markup-converter' )
				// ja-jp: '傍点をどの方式で表示するかを選択します。通常は「独自実装」のままで問題ありません。'.
				?>
			</p>

			<?php rubymaco_render_bouten_renderer_field( $view_data['bouten_renderer_choices'] ); ?>
		</section>
	</div>
	<?php
}

/**
 * 傍点の描画方式設定フィールドを描画する。
 *
 * @param array<int, array{ id:string, value:string, label:string, description:string, is_selected:bool }> $choices 傍点描画方式の選択.
 */
function rubymaco_render_bouten_renderer_field( array $choices ): void {
	rubymaco_render_choice_group(
		$choices,
		RUBYMACO_Option_Key::BOUTEN_RENDERER,
		__( 'Bouten Rendering Method', 'ruby-markup-converter' ) // ja-jp: '傍点の描画方式'.
	);
}

/**
 * Preview Helpers
 */

/**
 * 管理画面用のプレビューを生成する。
 *
 * @param string               $example                 使用例.
 * @param array<string, mixed> $rule                    記法ルール.
 * @param string               $current_bouten_style    現在の傍点種類.
 * @param string               $current_bouten_renderer 現在の傍点描画方式.
 * @return string プレビューHTML.
 */
function rubymaco_render_admin_rule_preview(
	string $example,
	array $rule,
	string $current_bouten_style,
	string $current_bouten_renderer
): string {
	if ( array() === $rule['transform_rules'] ) {
		return esc_html( $example );
	}

	return rubymaco_apply_markup_rules(
		$example,
		$rule['transform_rules'],
		$current_bouten_style,
		$current_bouten_renderer
	);
}
