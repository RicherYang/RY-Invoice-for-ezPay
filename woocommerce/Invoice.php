<?php

namespace RY\Invoice\Ezpay\WooCommerce;

defined('ABSPATH') or exit;

use RY\General\Logs;
use RY\Invoice\Ezpay\WooCommerce\Admin\Admin;

final class Invoice
{
    private static ?self $_instance = null;

    public static function instance(): Invoice
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        $auto_get_statuses = match (\RY_IFEZPAY::get_option('get_mode')) {
            'auto_paid' => wc_get_is_paid_statuses(),
            'auto_completed' => ['completed'],
            default => [],
        };
        foreach ($auto_get_statuses as $status) {
            add_action('woocommerce_order_status_' . $status, [$this, 'auto_get_invoice']);
        }

        if (\RY_IFEZPAY::get_option('invalid_mode') === 'auto_cancel') {
            add_action('woocommerce_order_status_cancelled', [$this, 'auto_delete_invoice']);
            add_action('woocommerce_order_status_refunded', [$this, 'auto_delete_invoice']);
        }

        add_action('ry_invoice_ezpay-post_get_invoice', [$this, 'do_get_invoice'], 0, 3);
        add_action('ry_invoice_ezpay-post_invalid_invoice', [$this, 'do_delete_invoice'], 0, 3);

        if (is_admin()) {
            Admin::instance();
        } else {
            add_filter('default_checkout_invoice_company_name', [$this, 'set_default_invoice_company_name']);
            if (\RY_IFEZPAY::get_option('show_invoice_number', 'no') === 'yes') {
                add_filter('woocommerce_account_orders_columns', [$this, 'add_invoice_column']);
                add_action('woocommerce_my_account_my_orders_column_invoice-number', [$this, 'show_invoice_column']);
            }
        }
    }

    public function auto_get_invoice($order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        $skip_shipping = apply_filters('ry_invoice-wc_skip_autoget_shipping', []);
        if (!empty($skip_shipping)) {
            foreach ($order->get_items('shipping') as $item) {
                if (in_array($item->get_method_id(), $skip_shipping)) {
                    return;
                }
            }
        }

        if (\RY_IFEZPAY::get_option('skip_foreign_order', 'no') === 'yes') {
            if ('TW' !== $order->get_billing_country()) {
                if ($order->needs_shipping_address()) {
                    if ('TW' !== $order->get_shipping_country()) {
                        return;
                    }
                } else {
                    return;
                }
            }
        }

        if (!empty($order->get_meta('_invoice_number'))) {
            return;
        }

        if (!as_has_scheduled_action(\RY_IFEZPAY::OPTION_PREFIX . 'auto_get_invoice', [$order->get_id()], 'ry-invoice')) {
            $delay_time = (int) \RY_IFEZPAY::get_option('get_delay_time', '0');
            if ($delay_time < 0) {
                $delay_time = 0;
            }
            if ($delay_time > 336) {
                $delay_time = 336;
            }
            $order->update_meta_data('_invoice_number', 'wait');
            $order->save();
            as_schedule_single_action(time() + MINUTE_IN_SECONDS * 2 + HOUR_IN_SECONDS * $delay_time, \RY_IFEZPAY::OPTION_PREFIX . 'auto_get_invoice', [$order->get_id()], 'ry-invoice');
        }
    }

    public function auto_delete_invoice($order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return false;
        }

        if (empty($order->get_meta('_invoice_number'))) {
            return;
        }

        switch ($order->get_meta('_invoice_number')) {
            case 'wait':
            case 'zero':
            case 'negative':
                as_unschedule_action(\RY_IFEZPAY::OPTION_PREFIX . 'auto_get_invoice', [$order->get_id()], 'ry-invoice');
                $order->delete_meta_data('_invoice_number');
                $order->save();
                break;
            default:
                as_schedule_single_action(time() + MINUTE_IN_SECONDS * 2, \RY_IFEZPAY::OPTION_PREFIX . 'auto_invalid_invoice', [$order->get_id()], 'ry-invoice');
                break;
        }
    }

    public function do_get_invoice($post_args, $result, $order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        if ($result->Status != 'SUCCESS') {
            if ($order->get_meta('_invoice_number') === 'wait') {
                $order->delete_meta_data('_invoice_number');
                $order->save();
            }

            $order->add_order_note(sprintf(
                /* translators: %1$s Error messade, %2$s Status code */
                __('Issue invoice error: %1$s (%2$s)', 'ry-invoice-for-ezpay'),
                $result->Message,
                $result->Status,
            ));
            return;
        }

        $invoice_date = new \DateTime($result->Result->CreateTime, new \DateTimeZone('Asia/Taipei'));
        $invoice_date->setTimezone(wp_timezone());

        if (apply_filters('ry_invoice-wc_success_notice', true)) {
            $order->add_order_note(
                __('Invoice number', 'ry-invoice-for-ezpay') . ': ' . $result->Result->InvoiceNumber . "\n"
                . __('Invoice random number', 'ry-invoice-for-ezpay') . ': ' . $result->Result->RandomNum . "\n"
                . __('Invoice create time', 'ry-invoice-for-ezpay') . ': ' . $invoice_date->format('Y-m-d H:i:s'),
            );
        }

        $order->update_meta_data('_invoice_number', $result->Result->InvoiceNumber);
        $order->update_meta_data('_invoice_random_number', $result->Result->RandomNum);
        $order->update_meta_data('_invoice_date', $invoice_date->format('Y-m-d H:i:s'));
        $order->save();
    }

    public function do_delete_invoice($post_args, $result, $order_ID)
    {
        $order = wc_get_order($order_ID);
        if (!$order) {
            return;
        }

        if ($result->Status != 'SUCCESS') {
            $order->add_order_note(sprintf(
                /* translators: %1$s Error messade, %2$s Status code */
                __('Invalid invoice error: %1$s (%2$s)', 'ry-invoice-for-ezpay'),
                $result->Message,
                $result->Status,
            ));
            return;
        }

        if (apply_filters('ry_invoice-wc_success_notice', true)) {
            $order->add_order_note(
                __('Invalid invoice', 'ry-invoice-for-ezpay') . ': ' . $order->get_meta('_invoice_number'),
            );
        }

        $order->delete_meta_data('_invoice_number');
        $order->delete_meta_data('_invoice_random_number');
        $order->delete_meta_data('_invoice_date');
        $order->save();
    }

    public function set_default_invoice_company_name()
    {
        if (is_user_logged_in()) {
            $customer = new \WC_Customer(get_current_user_id(), true);

            return $customer->get_billing_company();
        }

        return '';
    }

    public function add_invoice_column($columns)
    {
        if (!isset($columns['invoice-number'])) {
            $add_columns = [
                'invoice-number' => __('Invoice number', 'ry-invoice-for-ezpay'),
            ];
            $pre_idx = array_search('order-total', array_keys($columns)) + 1;
            $pre_array = array_splice($columns, 0, $pre_idx);
            $columns = array_merge($pre_array, $add_columns, $columns);
        }
        return $columns;
    }

    public function show_invoice_column($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        if (preg_match('/^[A-Z]{2}[0-9]{8}$/', $invoice_number)) {
            echo esc_html($invoice_number);
        }
    }

    public function get_invoice($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        if (preg_match('/^[A-Z]{2}[0-9]{8}$/', $invoice_number)) {
            return;
        }
        if (in_array($invoice_number, ['zero', 'negative'])) {
            return;
        }
        if (empty($order->get_meta('_invoice_type'))) {
            $order->delete_meta_data('_invoice_number');
            $order->save();
            return;
        }

        $invoice_data = [
            'no' => $order->get_order_number(),
            'prefix' => \RY_IFEZPAY::get_option('prefix', ''),
            'email' => $order->get_billing_email(),
            'total' => $order->get_total() - $order->get_total_refunded(),
        ];

        if ($invoice_data['total'] == 0) {
            $order->update_meta_data('_invoice_number', 'zero');
            $order->save();
            $order->add_order_note(__('Zero total fee without invoice', 'ry-invoice-for-ezpay'));
            return;
        }
        if ($invoice_data['total'] < 0) {
            $order->update_meta_data('_invoice_number', 'negative');
            $order->save();
            $order->add_order_note(__('Negative total fee can\'t invoice', 'ry-invoice-for-ezpay'));
            return;
        }

        switch ($order->get_meta('_invoice_type')) {
            case 'personal':
                switch ($order->get_meta('_invoice_carruer_type')) {
                    case 'amego_host':
                        $order->update_meta_data('_invoice_carruer_type', 'amego_host');
                        $order->save();
                        $order = wc_get_order($order->get_id());
                        $invoice_data['type'] = 'host';
                        break;
                    case 'ecpay_host':
                        $order->update_meta_data('_invoice_carruer_type', 'ezpay_host');
                        $order->save();
                        $order = wc_get_order($order->get_id());
                        $invoice_data['type'] = 'host';
                        break;
                    case 'ezpay_host':
                        $invoice_data['type'] = 'host';
                        break;
                    case 'smilepay_host':
                        $order->update_meta_data('_invoice_carruer_type', 'ezpay_host');
                        $order->save();
                        $order = wc_get_order($order->get_id());
                        $invoice_data['type'] = 'host';
                        break;
                    case 'MOICA':
                        $invoice_data['type'] = 'MOICA';
                        $invoice_data['moica_no'] = $order->get_meta('_invoice_carruer_no');
                        break;
                    case 'phone_barcode':
                        $invoice_data['type'] = 'phone_barcode';
                        $invoice_data['phone_barcode'] = $order->get_meta('_invoice_carruer_no');
                        break;
                }
                break;
            case 'company':
                $invoice_data['type'] = 'company';
                $invoice_data['tax_no'] = $order->get_meta('_invoice_no');
                $invoice_data['tax_name'] = $order->get_billing_company();
                break;
            case 'donate':
                $invoice_data['type'] = 'donate';
                $invoice_data['donate_no'] = $order->get_meta('_invoice_donate_no');
                break;
        }

        $invoice_data['item'] = [];
        $total_refunded = $order->get_total_refunded();
        $order_items = $order->get_items(['line_item']);
        if (count($order_items)) {
            foreach ($order_items as $order_item) {
                $item_total = $order_item->get_total();
                $item_refunded = $order->get_total_refunded_for_item($order_item->get_id(), $order_item->get_type());
                $total_refunded -= $item_refunded;

                $item_name = $order_item->get_name();

                $invoice_data['item'][] = [
                    'name' => $item_name,
                    'qty' => $order_item->get_quantity() + $order->get_qty_refunded_for_item($order_item->get_id(), $order_item->get_type()),
                    'total' => $item_total - $item_refunded,
                    'unit' => apply_filters('ry_invoice-item_unit_name', __('parcel', 'ry-invoice-for-ezpay'), $order->get_id(), $order_item->get_id()),
                    'tax' => 1,
                ];
            }
        }

        $fee_items = $order->get_items(['fee']);
        if (count($fee_items)) {
            foreach ($fee_items as $fee_item) {
                $invoice_data['item'][] = [
                    'name' => $fee_item->get_name(),
                    'qty' => $fee_item->get_quantity(),
                    'total' => $fee_item->get_total(),
                    'unit' => apply_filters('ry_invoice-item_unit_name', __('parcel', 'ry-invoice-for-ezpay'), $order->get_id(), $fee_item->get_id()),
                    'tax' => 1,
                ];
            }
        }

        $shipping_fee = $order->get_shipping_total() - $order->get_total_shipping_refunded();
        $total_refunded -= $order->get_total_shipping_refunded();
        if ($shipping_fee != 0) {
            $invoice_data['item'][] = [
                'name' => __('shipping fee', 'ry-invoice-for-ezpay'),
                'qty' => 1,
                'total' => $shipping_fee,
                'unit' => apply_filters('ry_invoice-item_unit_name', __('parcel', 'ry-invoice-for-ezpay'), $order->get_id(), 'shipping'),
                'tax' => 1,
            ];
        }

        if ($total_refunded != 0) {
            $invoice_data['item'][] = [
                'name' => __('return fee', 'ry-invoice-for-ezpay'),
                'qty' => 1,
                'total' => -$total_refunded,
                'unit' => apply_filters('ry_invoice-item_unit_name', __('parcel', 'ry-invoice-for-ezpay'), $order->get_id(), 'return'),
                'tax' => 1,
            ];
        }

        Logs::log('ezpay-invoice', 'info', 'Get WooCommerce #' . $order->get_id(), $invoice_data);
        \RY_IFEZPAY_Invoice::instance()->get_invoice($invoice_data, $order->get_id());
    }

    public function cancel_invoice($order)
    {
        $order->delete_meta_data('_invoice_number');
        $order->save();
    }

    public function invalid_invoice($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');

        if (empty($invoice_number)) {
            return;
        }
        if (in_array($invoice_number, ['wait', 'zero', 'negative'])) {
            $order->delete_meta_data('_invoice_number');
            $order->save();
            return;
        }

        $invoice_data = [
            'no' => $invoice_number,
            'date' => $order->get_meta('_invoice_date'),
        ];
        Logs::log('ezpay-invoice', 'info', 'Invalid WooCommerce #' . $order->get_id(), $invoice_data);
        \RY_IFEZPAY_Invoice::instance()->invalid_invoice($invoice_data, $order->get_id());
    }
}
