<?php

declare(strict_types=1);

/**
 * Apply automatic markup conversion to post content.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_filter('the_content', 'rbmkup_filter_the_content', 9);

/**
 * WordPress Hooks
 */

/**
 * 投稿本文にルビ・傍点変換を適用する。
 *
 * 適用モードが投稿本文全体に設定されている場合のみ、
 * 本文を変換して返す。その他の場合は元の本文をそのまま返す。
 *
 * @param string $content 投稿本文
 * @return string 変換後、または未変換の投稿本文
 */
function rbmkup_filter_the_content(string $content): string
{
    $content = wp_kses_post($content);

    return rbmkup_get_apply_mode() === RBMKUP_APPLY_MODE_ALL
        ? rbmkup_transform_content_markup($content)
        : $content;
}
