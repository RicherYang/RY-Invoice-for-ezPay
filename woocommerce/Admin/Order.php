<?php

namespace RY\Invoice\Ezpay\WooCommerce\Admin;

defined('ABSPATH') or exit;

use Automattic\WooCommerce\Utilities\OrderUtil;
use RY\Invoice\Ezpay\WooCommerce\Invoice;

final class Order
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
        add_action('woocommerce_admin_order_data_after_billing_address', ['RY\Invoice\Ezpay\WooCommerce\Admin\MetaBoxes\Info', 'output']);

        add_action('woocommerce_update_order', [$this, 'save_order_update']);

        add_action('admin_notices', [$this, 'bulk_action_notices']);
        if (class_exists('Automattic\WooCommerce\Utilities\OrderUtil') && OrderUtil::custom_orders_table_usage_is_enabled()) {
            if ('edit' !== ($_GET['action'] ?? '')) {
                add_filter('manage_woocommerce_page_wc-orders_columns', [$this, 'add_invoice_column'], 11);
                add_action('manage_woocommerce_page_wc-orders_custom_column', [$this, 'show_invoice_column'], 11, 2);

                add_filter('bulk_actions-woocommerce_page_wc-orders', [$this, 'shop_order_list_action']);
                add_filter('handle_bulk_actions-woocommerce_page_wc-orders', [$this, 'do_shop_order_action'], 10, 3);
            }
        } else {
            add_filter('manage_shop_order_posts_columns', [$this, 'add_invoice_column'], 11);
            add_action('manage_shop_order_posts_custom_column', [$this, 'show_invoice_column'], 11, 2);

            add_filter('bulk_actions-edit-shop_order', [$this, 'shop_order_list_action']);
            add_filter('handle_bulk_actions-edit-shop_order', [$this, 'do_shop_order_action'], 10, 3);
        }
    }

    public function save_order_update($order_ID)
    {
        if ($order = wc_get_order($order_ID)) {
            if (isset($_POST['_invoice_type'])) {
                remove_action('woocommerce_update_order', [$this, 'save_order_update']);
                $order->update_meta_data('_invoice_type', sanitize_locale_name($_POST['_invoice_type'] ?? ''));
                $order->update_meta_data('_invoice_carruer_type', sanitize_locale_name($_POST['_invoice_carruer_type'] ?? ''));
                foreach (['_invoice_carruer_no', '_invoice_no', '_invoice_donate_no'] as $key) {
                    $value = sanitize_text_field(wp_unslash($_POST[$key] ?? ''));
                    if (!empty($value)) {
                        $order->update_meta_data($key, $value);
                    } else {
                        $order->delete_meta_data($key);
                    }
                }

                $invoice_number = strtoupper(sanitize_locale_name($_POST['_invoice_number'] ?? ''));
                if (!empty($invoice_number)) {
                    $order->update_meta_data('_invoice_number', $invoice_number);
                    $order->update_meta_data('_invoice_random_number', sanitize_key($_POST['_invoice_random_number'] ?? ''));

                    $date = sanitize_key($_POST['_invoice_date'] ?? '');
                    $hour = intval($_POST['_invoice_date_hour'] ?? '');
                    $minute = intval($_POST['_invoice_date_minute'] ?? '');
                    $second = intval($_POST['_invoice_date_second'] ?? '');
                    $date = gmdate('Y-m-d H:i:s', strtotime($date . ' ' . $hour . ':' . $minute . ':' . $second));
                    $order->update_meta_data('_invoice_date', $date);
                }
                $order->save();
                add_action('woocommerce_update_order', [$this, 'save_order_update']);
            }
        }
    }

    public function bulk_action_notices()
    {
        $bulk_action = wp_unslash($_GET['bulk_action'] ?? '');

        if ($bulk_action === 'ry_get_invoice') {
            $number = intval($_GET['ry_geted'] ?? '');

            /* translators: %s: count */
            $message = sprintf(_n('%s order issue invoice.', '%s orders issue invoice.', $number, 'ry-invoice-for-ezpay'), number_format_i18n($number));
            echo '<div class="updated"><p>' . esc_html($message) . '</p></div>';
        }
    }

    public function add_invoice_column($columns)
    {
        if (!isset($columns['invoice-number'])) {
            $add_columns = [
                'invoice-number' => __('Invoice number', 'ry-invoice-for-ezpay'),
            ];
            $pre_idx = array_search('order_status', array_keys($columns)) + 1;
            $pre_array = array_splice($columns, 0, $pre_idx);
            $columns = array_merge($pre_array, $add_columns, $columns);
        }
        return $columns;
    }

    public function show_invoice_column($column, $order)
    {
        if ($column === 'invoice-number') {
            if (!is_object($order)) {
                global $the_order;
                $order = $the_order;
            }

            $invoice_number = $order->get_meta('_invoice_number');
            if (!empty($invoice_number)) {
                match ($invoice_number) {
                    'wait' => esc_html_e('Wait get invoice', 'ry-invoice-for-ezpay'),
                    'zero' => esc_html_e('Zero no invoice', 'ry-invoice-for-ezpay'),
                    'negative' => esc_html_e('Negative no invoice', 'ry-invoice-for-ezpay'),
                    default => print(esc_html($invoice_number)),
                };
            }
        }
    }

    public function shop_order_list_action($actions)
    {
        $actions['ry_get_invoice'] = __('Issue invoice', 'ry-invoice-for-ezpay');

        return $actions;
    }

    public function do_shop_order_action($redirect_to, $action, $ids)
    {
        if ('ry_get_invoice' === $action) {
            $geted = 0;

            foreach ($ids as $order_ID) {
                $order = wc_get_order($order_ID);
                $invoice_number = $order->get_meta('_invoice_number');
                if (empty($invoice_number) && $order->is_paid()) {
                    $geted += 1;
                    Invoice::instance()->get_invoice($order);
                }
            }

            $redirect_to = add_query_arg([
                'bulk_action' => 'ry_get_invoice',
                'ry_geted' => $geted,
            ], $redirect_to);
        }

        return $redirect_to;
    }
}
