<?php

declare(strict_types=1);

/**
 * Shortcode entry point.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_shortcode('rubymarkup', 'rbmkup_shortcode');

/**
 * WordPress Hooks
 */

/**
 * ショートコード内の本文を変換する。
 *
 * @param array<string, mixed> $atts    ショートコード属性
 * @param string|null          $content ショートコード本文
 * @return string 変換後の本文
 */
function rbmkup_shortcode(array $atts, ?string $content = null): string
{
    return rbmkup_transform_content_markup((string) $content);
}
