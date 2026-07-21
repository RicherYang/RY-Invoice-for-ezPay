<?php

use RY\Invoice\Ezpay\WooCommerce\Invoice;

defined('ABSPATH') or exit;

final class RY_IFEZPAY_Cron
{
    public static function add_action(): void
    {
        add_action(RY_IFEZPAY::OPTION_PREFIX . 'check_expire', [__CLASS__, 'check_expire']);

        add_action(RY_IFEZPAY::OPTION_PREFIX . 'auto_get_invoice', [__CLASS__, 'get_invoice']);
        add_action(RY_IFEZPAY::OPTION_PREFIX . 'auto_invalid_invoice', [__CLASS__, 'invalid_invoice']);
    }

    public static function check_expire(): void
    {
        RY_IFEZPAY_License::instance()->check_expire();
    }

    public static function get_invoice($object_ID): void
    {
        if (function_exists('wc_get_order')) {
            $order = wc_get_order($object_ID);
            if ($order) {
                Invoice::instance()->get_invoice($order);
            }
        }
    }

    public static function invalid_invoice($object_ID): void
    {
        if (function_exists('wc_get_order')) {
            $order = wc_get_order($object_ID);
            if ($order) {
                Invoice::instance()->invalid_invoice($order);
            }
        }
    }
}
