<?php

declare(strict_types=1);

if (! defined('WP_UNINSTALL_PLUGIN')) {
    exit;
}

$rbmkup_option_keys = [
    'rbmkup_enabled_markup_rules',
    'rbmkup_bouten_style',
    'rbmkup_bouten_renderer',
    'rbmkup_apply_mode',
];

foreach ($rbmkup_option_keys as $rbmkup_option_key) {
    delete_option($rbmkup_option_key);
}
