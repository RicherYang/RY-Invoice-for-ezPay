<?php

namespace RY\Invoice\Ezpay;

defined('ABSPATH') or exit;

final class Update
{
    public static function update()
    {
        $now_version = \RY_IFEZPAY::get_option('version', '0.0.0');

        if (RY_IFEZPAY_VERSION === $now_version) {
            return;
        }

        if ($now_version === '0.0.0') {
            \RY_IFEZPAY::update_option('version', RY_IFEZPAY_VERSION, true);
            return;
        }

        if (version_compare($now_version, '2026.7.19', '<')) {
            \RY_IFEZPAY::update_option('version', '2026.7.19', true);
        }
    }
}
