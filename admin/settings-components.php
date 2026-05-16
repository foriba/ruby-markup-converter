<?php

declare(strict_types=1);

if (! defined('ABSPATH')) {
    exit;
}

/**
 * 設定画面のセクション枠組みを描画する。
 *
 * @param string   $title    セクションタイトル
 * @param callable $callback コンテンツを描画するコールバック関数
 */
function rubymaco_render_settings_section(string $title, callable $callback): void
{
?>
    <section class="rubymaco-settings-section">
        <h2 class="rubymaco-section-title"><?php echo esc_html($title); ?></h2>
        <div class="rubymaco-section-body">
            <?php $callback(); ?>
        </div>
    </section>
<?php
}

/**
 * 注意事項ボックスを描画する。
 *
 * @param string   $title 注意事項のタイトル
 * @param string[] $notes 注意事項のリスト
 */
function rubymaco_render_settings_note(string $title, array $notes): void
{
    if ($notes === []) {
        return;
    }
?>
    <div class="rubymaco-settings-note">
        <p class="rubymaco-settings-note-title">
            <span class="dashicons dashicons-warning"></span>
            <?php echo esc_html($title); ?>
        </p>
        <ul>
            <?php foreach ($notes as $note) : ?>
                <li><?php echo esc_html($note); ?></li>
            <?php endforeach; ?>
        </ul>
    </div>
<?php
}

/**
 * ラジオボタン形式の選択肢グループを描画する。
 *
 * @param array<int, array{
 *     id:string,
 *     value:string,
 *     label:string,
 *     description:string,
 *     is_selected:bool
 * }> $choices
 * @param string $option_name option 名
 * @param string $aria_label aria-label
 */
function rubymaco_render_choice_group(array $choices, string $option_name, string $aria_label): void
{
?>
    <div class="rubymaco-choice-group" role="radiogroup" aria-label="<?php echo esc_attr($aria_label); ?>">
        <?php foreach ($choices as $choice) : ?>
            <?php rubymaco_render_choice_card($choice, $option_name); ?>
        <?php endforeach; ?>
    </div>
<?php
}

/**
 * ラジオボタン形式の選択肢カードを描画する。
 *
 * @param array{
 *     id:string,
 *     value:string,
 *     label:string,
 *     description:string,
 *     is_selected:bool
 * } $choice
 * @param string $option_name option 名
 */
function rubymaco_render_choice_card(array $choice, string $option_name): void
{
?>
    <label class="rubymaco-choice-card" for="<?php echo esc_attr($choice['id']); ?>">
        <input
            id="<?php echo esc_attr($choice['id']); ?>"
            type="radio"
            name="<?php echo esc_attr($option_name); ?>"
            value="<?php echo esc_attr($choice['value']); ?>"
            <?php checked($choice['is_selected']); ?>>

        <span class="rubymaco-choice-card-body">
            <span class="rubymaco-choice-card-title-row">
                <span class="rubymaco-choice-card-title"><?php echo esc_html($choice['label']); ?></span>
                <span class="rubymaco-choice-card-state">
                    <?php echo $choice['is_selected']
                        ? esc_html__('Selected', 'ruby-markup-converter') // ja-jp: '選択中'
                        : ''; ?>
                </span>
            </span>

            <?php if ($choice['description'] !== '') : ?>
                <span class="rubymaco-choice-card-description">
                    <?php echo esc_html($choice['description']); ?>
                </span>
            <?php endif; ?>
        </span>
    </label>
<?php
}

/**
 * 記法ルールカードのタイトル行を描画する。
 *
 * @param string[] $titles ルールタイトル一覧
 * @param bool     $is_enabled 現在有効かどうか
 */
function rubymaco_render_rule_card_title(array $titles, bool $is_enabled): void
{
?>
    <span class="rubymaco-rule-card-title-row">
        <span class="rubymaco-rule-card-title">
            <?php foreach ($titles as $title) : ?>
                <span class="rubymaco-rule-card-title-item">
                    <?php echo esc_html($title); ?>
                </span>
            <?php endforeach; ?>
        </span>
        <span class="rubymaco-rule-card-state">
            <?php echo $is_enabled
                ? esc_html__('Enabled', 'ruby-markup-converter') // ja-jp: '有効'
                : esc_html__('Disabled', 'ruby-markup-converter'); // ja-jp: '無効'
            ?>
        </span>
    </span>
<?php
}

/**
 * 記法例のメタ情報を描画する。
 *
 * 記法例は通常テキストとして扱い、この関数内でエスケープする。
 *
 * @param string   $label    表示ラベル
 * @param string[] $examples 記法例一覧
 */
function rubymaco_render_rule_card_example_meta(string $label, array $examples): void
{
?>
    <span class="rubymaco-rule-card-meta">
        <span class="rubymaco-rule-card-meta-label"><?php echo esc_html($label); ?></span>
        <span class="rubymaco-rule-card-example-list">
            <?php foreach ($examples as $example) : ?>
                <code class="rubymaco-rule-card-example"><?php echo esc_html($example); ?></code>
            <?php endforeach; ?>
        </span>
    </span>
<?php
}

/**
 * 出力結果プレビューのメタ情報を描画する。
 *
 * プレビューは変換済みHTMLを含むため、wp_kses_post() で許可されたHTMLのみ出力する。
 *
 * @param string   $label 表示ラベル
 * @param string[] $previews プレビューHTML一覧
 */
function rubymaco_render_rule_card_preview_meta(string $label, array $previews): void
{
?>
    <span class="rubymaco-rule-card-meta">
        <span class="rubymaco-rule-card-meta-label"><?php echo esc_html($label); ?></span>
        <span class="rubymaco-rule-card-preview-list">
            <?php foreach ($previews as $preview) : ?>
                <span class="rubymaco-rule-card-preview">
                    <?php echo wp_kses_post($preview); ?>
                </span>
            <?php endforeach; ?>
        </span>
    </span>
<?php
}
