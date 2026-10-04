<?php

namespace RY\Invoice\Ezpay;

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\WooCommerce\Invoice;

final class Cron
{
    public static function add_action(): void
    {
        add_action(Main::get_prefix_name('check_expire'), [__CLASS__, 'check_expire']);

        add_action(Main::get_prefix_name('auto_get_invoice'), [__CLASS__, 'get_invoice'], 10, 2);
        add_action(Main::get_prefix_name('auto_invalid_invoice'), [__CLASS__, 'invalid_invoice'], 10, 2);
    }

    public static function check_expire(): void
    {
        License::instance()->check_expire();
    }

    /**
     * @param int $object_ID
     */
    public static function get_invoice($object_ID, $type = 'woocommerce'): void
    {
        switch ($type) {
            case 'woocommerce':
                if (function_exists('wc_get_order')) {
                    $order = wc_get_order($object_ID);
                    if ($order) {
                        Invoice::instance()->get_invoice($order);
                    }
                }
                break;
        }
    }

    /**
     * @param int $object_ID
     */
    public static function invalid_invoice($object_ID, $type = 'woocommerce'): void
    {
        switch ($type) {
            case 'woocommerce':
                if (function_exists('wc_get_order')) {
                    $order = wc_get_order($object_ID);
                    if ($order) {
                        Invoice::instance()->invalid_invoice($order);
                    }
                }
                break;
        }
    }
}
