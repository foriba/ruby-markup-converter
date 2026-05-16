<?php

declare(strict_types=1);

/**
 * Shortcode entry point.
 */

if (! defined('ABSPATH')) {
    exit;
}

add_shortcode('rubymarkup', 'rubymaco_shortcode');

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
function rubymaco_shortcode(array $atts, ?string $content = null): string
{
    $content = wp_kses_post((string) $content);

    return wp_kses_post(rubymaco_transform_content_markup($content));
}
