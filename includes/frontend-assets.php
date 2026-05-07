<?php

declare(strict_types=1);

/**
 * Front-end assets.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_action('wp_enqueue_scripts', 'rbmkup_enqueue_styles');

/**
 * WordPress Hooks
 */

/**
 * フロントエンド用の CSS を読み込む。
 */
function rbmkup_enqueue_styles(): void
{
    wp_enqueue_style(
        'ruby-markup-converter',
        RBMKUP_PLUGIN_URL . '/public/css/ruby-markup-converter.css',
        [],
        RBMKUP_VERSION
    );
}
