<?php

namespace RY\Invoice\Ezpay\WooCommerce\Admin;

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\WooCommerce\Admin\Order;
use RY\Invoice\Ezpay\WooCommerce\Admin\Settings;
use RY\Invoice\Ezpay\WooCommerce\Admin\Settings\Invoice;

final class Admin
{
    private static ?self $_instance = null;

    public static function instance(): Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        Order::instance();
        Invoice::instance();

        add_filter('woocommerce_get_settings_pages', [$this, 'get_settings_page']);

        add_action('admin_enqueue_scripts', [$this, 'add_scripts'], 11);
    }

    public function get_settings_page($settings = [])
    {
        if (!has_action('woocommerce_sections_rytools')) {
            $settings[] = new Settings();
        }

        return $settings;
    }

    public function add_scripts()
    {
        $screen = get_current_screen();
        $screen_id = $screen ? $screen->id : '';

        if (in_array($screen_id, ['shop_order', 'edit-shop_order', 'woocommerce_page_wc-settings', 'woocommerce_page_wc-orders'])) {
            wp_enqueue_script('ry-invoice-admin-invoice');
            wp_enqueue_style('ry-invoice-admin-invoice');

            wp_localize_script('ry-invoice-admin-invoice', 'RyWaiAdminInvoiceParams', [
                'i18n' => [
                    'get' => __('Issue invoice.<br>Please wait.', 'ry-invoice-for-ezpay'),
                    'cancel' => __('Cancel get invoice.<br>Please wait.', 'ry-invoice-for-ezpay'),
                    'invalid' => __('Invalid invoice.<br>Please wait.', 'ry-invoice-for-ezpay'),
                ],
                '_nonce' => [
                    'get' => wp_create_nonce('get-invoice'),
                    'cancel' => wp_create_nonce('cancel-invoice'),
                    'invalid' => wp_create_nonce('invalid-invoice'),
                ],
            ]);
        }
    }
}
