<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$rubymaco_option_keys = [
    'rubymaco_enabled_markup_rules',
    'rubymaco_bouten_style',
    'rubymaco_bouten_renderer',
    'rubymaco_apply_mode',
];

foreach ($rubymaco_option_keys as $rubymaco_option_key) {
    delete_option($rubymaco_option_key);
}
