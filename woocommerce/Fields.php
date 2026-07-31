<?php

namespace RY\Invoice\Ezpay\WooCommerce;

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\Main;
use RY\Invoice\V20260729\WooCommerce\AbstractFields;

final class Fields extends AbstractFields
{
    private static ?self $_instance = null;

    protected string $host_type = 'ezpay_host';

    public static function instance(): Fields
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('woocommerce_after_checkout_billing_form', [$this, 'show_invoice_form']);

        add_action('woocommerce_order_details_after_customer_details', [$this, 'show_invoice_info']);
    }

    public function show_invoice_form($checkout)
    {
        $asset_info = include RY_IFEZPAY_PLUGIN_DIR . 'assets/wc-checkout.asset.php';
        wp_enqueue_script('ry-invoice-wc-checkout', RY_IFEZPAY_PLUGIN_URL . 'assets/wc-checkout.js', $asset_info['dependencies'], $asset_info['version'], true);

        $args = [
            'checkout' => $checkout,
        ];
        wc_get_template('checkout/form-invoice.php', $args, '', RY_IFEZPAY_PLUGIN_DIR . 'woocommerce/templates/');
    }

    public function show_invoice_info($order)
    {
        if (!$order->get_meta('_invoice_type')) {
            return;
        }

        $invoice_info = $this->get_invoice_info($order);

        $args = [
            'order' => $order,
            'invoice_info' => apply_filters('ry_invoice-wc_order_invoice_info', $invoice_info, $order),
        ];
        wc_get_template('order/order-invoice-info.php', $args, '', RY_IFEZPAY_PLUGIN_DIR . 'woocommerce/templates/');
    }

    protected function is_move_billing_company(): bool
    {
        return Main::get_option('move_billing_company', 'no') === 'yes';
    }
}
