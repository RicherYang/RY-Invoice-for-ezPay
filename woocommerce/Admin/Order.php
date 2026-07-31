<?php

namespace RY\Invoice\Ezpay\WooCommerce\Admin;

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\Main;
use RY\Invoice\Ezpay\WooCommerce\Invoice;
use RY\Invoice\V20260729\WooCommerce\AbstractAdminOrder;

final class Order extends AbstractAdminOrder
{
    private static ?self $_instance = null;

    public static function instance(): Order
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_action('woocommerce_admin_order_data_after_billing_address', [$this, 'show_invoice_info']);
    }

    public function show_invoice_info($order)
    {
        $scheduled_time = as_next_scheduled_action(Main::get_prefix_name('auto_get_invoice'), [$order->get_id()], 'ry-invoice');
        $this->_show_invoice_info($order, $scheduled_time);
    }

    protected function do_get_invoice($order)
    {
        Invoice::instance()->get_invoice($order);
    }
}
