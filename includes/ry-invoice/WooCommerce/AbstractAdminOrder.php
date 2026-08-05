<?php

namespace RY\Invoice\V20260805\WooCommerce;

defined('ABSPATH') or exit;

use Automattic\WooCommerce\Utilities\OrderUtil;
use RY\Invoice\V20260805\Utils;

abstract class AbstractAdminOrder
{
    protected string $type = '';

    public function __construct()
    {
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

    abstract protected function do_get_invoice($order);

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
                    $this->do_get_invoice($order);
                }
            }

            $redirect_to = add_query_arg([
                'bulk_action' => 'ry_get_invoice',
                'ry_geted' => $geted,
            ], $redirect_to);
        }

        return $redirect_to;
    }

    protected function get_fields($order)
    {
        $host_type = $this->type . '_host';

        $fields = [
            'type' => [
                'label' => __('Invoice type', 'ry-invoice-for-ezpay'),
                'show' => false,
                'class' => 'select short',
                'type' => 'select',
                'options' => [
                    'personal' => Utils::invoice_type_to_name('personal'),
                    'company' => Utils::invoice_type_to_name('company'),
                    'donate' => Utils::invoice_type_to_name('donate'),
                ],
            ],
            'carruer_type' => [
                'label' => __('Carruer type', 'ry-invoice-for-ezpay'),
                'show' => false,
                'class' => 'select short',
                'type' => 'select',
                'options' => [
                    $host_type => Utils::carruer_type_to_name($host_type),
                    'MOICA' => Utils::carruer_type_to_name('MOICA'),
                    'phone_barcode' => Utils::carruer_type_to_name('phone_barcode'),
                ],
            ],
            'carruer_no' => [
                'label' => __('Carruer number', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'text',
            ],
            'no' => [
                'label' => __('Tax ID number', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'text',
            ],
            'donate_no' => [
                'label' => __('Donate number', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'text',
            ],
        ];
        $carruer_type = $order->get_meta('_invoice_carruer_type');
        if (!isset($fields['carruer_type']['options'][$carruer_type])) {
            $fields['carruer_type']['options'][$carruer_type] = Utils::carruer_type_to_name($carruer_type);
        }

        if ($order->is_paid()) {
            $fields['number'] = [
                'label' => __('Invoice number', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'text',
            ];
            $fields['random_number'] = [
                'label' => __('Invoice random number', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'text',
                'pattern' => '[0-9]{4}',
            ];
            $fields['date'] = [
                'label' => __('Invoice date', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'date',
            ];
        }

        return  $fields;
    }

    protected function _show_invoice_info($order, $scheduled_time)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        $invoice_type = $order->get_meta('_invoice_type');
        $carruer_type = $order->get_meta('_invoice_carruer_type'); ?>

<h3 style="clear:both">
    <?php esc_html_e('Invoice info', 'ry-invoice-for-ezpay'); ?>
</h3>
<?php if (!empty($invoice_type) || !empty($invoice_number)) { ?>
<div class="ivoice <?php echo($invoice_number ? '' : 'address'); ?>">
    <div class="ivoice_data_column">
        <p>
            <?php if (!empty($invoice_number)) { ?>
            <?php switch ($invoice_number) {
                case 'wait': ?>
            <strong><?php esc_html_e('Invoice number', 'ry-invoice-for-ezpay'); ?>:</strong> <?php esc_html_e('Wait get invoice', 'ry-invoice-for-ezpay'); ?><br>
            <?php if ($scheduled_time > 0) {
                $scheduled_time = as_get_datetime_object($scheduled_time)->setTimezone(wp_timezone()); ?>
            <strong><?php esc_html_e('Expected get time', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html($scheduled_time->format('Y-m-d H:i')); ?><br>
            <?php } ?>
            <?php
                    break;
                case 'zero': ?>
            <strong><?php esc_html_e('Invoice number', 'ry-invoice-for-ezpay'); ?>:</strong> <?php esc_html_e('Zero no invoice', 'ry-invoice-for-ezpay'); ?><br>
            <?php
                    break;
                case 'negative': ?>
            <strong><?php esc_html_e('Invoice number', 'ry-invoice-for-ezpay'); ?>:</strong> <?php esc_html_e('Negative no invoice', 'ry-invoice-for-ezpay'); ?><br>
            <?php
                    break;
                default: ?>
            <strong><?php esc_html_e('Invoice number', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html($invoice_number); ?><br>
            <strong><?php esc_html_e('Invoice random number', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html($order->get_meta('_invoice_random_number')); ?><br>
            <strong><?php esc_html_e('Invoice date', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html($order->get_meta('_invoice_date')); ?><br>
            <?php
                    break;
            } ?>
            <?php } ?>

            <strong><?php esc_html_e('Invoice type', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html(Utils::invoice_type_to_name($invoice_type)); ?><br>

            <?php if ($invoice_type === 'personal') { ?>
            <strong><?php esc_html_e('Carruer type', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html(Utils::carruer_type_to_name($carruer_type)); ?><br>

            <?php if (in_array($carruer_type, ['MOICA', 'phone_barcode'])) { ?>
            <strong><?php esc_html_e('Carruer number', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html($order->get_meta('_invoice_carruer_no')); ?><br>
            <?php } ?>
            <?php } ?>

            <?php if ($invoice_type === 'company') { ?>
            <strong><?php esc_html_e('Tax ID number', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html($order->get_meta('_invoice_no')); ?><br>
            <?php } ?>

            <?php if ($invoice_type === 'donate') { ?>
            <strong><?php esc_html_e('Donate number', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html($order->get_meta('_invoice_donate_no')); ?><br>
            <?php } ?>
        </p>
    </div>
    <div class="ivoice_action_column">
        <?php
        if (preg_match('/^[A-Z]{2}[0-9]{8}$/', $invoice_number)) {
            echo '<button type="button" class="button ajax-' . esc_attr($this->type) . '-invoice" data-action="invalid" data-orderid="' . esc_attr($order->get_id()) . '">'
                . esc_html__('Invalid invoice', 'ry-invoice-for-ezpay')
                . '</button>';
        } elseif ($invoice_number === 'wait') {
            echo '<button type="button" class="button ajax-' . esc_attr($this->type) . '-invoice" data-action="cancel" data-orderid="' . esc_attr($order->get_id()) . '">'
                . esc_html__('Cancel get', 'ry-invoice-for-ezpay')
                . '</button>';
        } elseif ($order->is_paid()) {
            echo '<button type="button" class="button ajax-' . esc_attr($this->type) . '-invoice" data-action="get" data-orderid="' . esc_attr($order->get_id()) . '">'
                . esc_html__('Issue invoice', 'ry-invoice-for-ezpay')
                . '</button>';
        }
    ?>
    </div>
</div>
<?php } ?>

<div class="edit_address">
    <?php
    if (!$invoice_number) {
        $fields = $this->get_fields($order);

        foreach ($fields as $key => $field) {
            $field['id'] = '_invoice_' . $key;
            $field['value'] = $order->get_meta($field['id']);

            switch ($field['type']) {
                case 'select':
                    woocommerce_wp_select($field);
                    break;
                case 'date':
                    ?>
    <p class="form-field form-field-wide <?php echo esc_attr($field['id']); ?>_field">
        <label for="<?php echo esc_attr($field['id']); ?>"><?php echo esc_html($field['label']); ?></label>
        <input type="text" class="date-picker" id="<?php echo esc_attr($field['id']); ?>" name="<?php echo esc_attr($field['id']); ?>" maxlength="10" value="" pattern="<?php echo esc_attr(apply_filters('woocommerce_date_input_html_pattern', '[0-9]{4}-(0[1-9]|1[012])-(0[1-9]|1[0-9]|2[0-9]|3[01])')); ?>" />@
        &lrm;
        <input type="number" class="hour" placeholder="<?php esc_attr_e('h', 'ry-invoice-for-ezpay'); ?>" name="<?php echo esc_attr($field['id']); ?>_hour" min="0" max="23" step="1" value="" pattern="([01]?[0-9]{1}|2[0-3]{1})" />:
        <input type="number" class="minute" placeholder="<?php esc_attr_e('m', 'ry-invoice-for-ezpay'); ?>" name="<?php echo esc_attr($field['id']); ?>_minute" min="0" max="59" step="1" value="" pattern="[0-5]{1}[0-9]{1}" />:
        <input type="number" class="second" placeholder="<?php esc_attr_e('s', 'ry-invoice-for-ezpay'); ?>" name="<?php echo esc_attr($field['id']); ?>_second" min="0" max="59" step="1" value="" pattern="[0-5]{1}[0-9]{1}" />
    </p>
    <?php
                    break;
                default:
                    woocommerce_wp_text_input($field);
                    break;
            }
        }
    } ?>
</div>
<?php
    }
}
