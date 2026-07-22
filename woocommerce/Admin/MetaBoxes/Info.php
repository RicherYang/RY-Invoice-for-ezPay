<?php

namespace RY\Invoice\Ezpay\WooCommerce\Admin\MetaBoxes;

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\Utils;

final class Info
{
    private static array $fields;

    protected static function init_fields($order)
    {
        self::$fields = [
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
                    'ezpay_host' => Utils::carruer_type_to_name('ezpay_host'),
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
        if (!isset(self::$fields['carruer_type']['options'][$carruer_type])) {
            self::$fields['carruer_type']['options'][$carruer_type] = Utils::carruer_type_to_name($carruer_type);
        }

        if ($order->is_paid()) {
            self::$fields['number'] = [
                'label' => __('Invoice number', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'text',
            ];
            self::$fields['random_number'] = [
                'label' => __('Invoice random number', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'text',
                'pattern' => '[0-9]{4}',
            ];
            self::$fields['date'] = [
                'label' => __('Invoice date', 'ry-invoice-for-ezpay'),
                'show' => false,
                'type' => 'date',
            ];
        }
    }

    public static function output($order)
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
            <?php $next_time = as_next_scheduled_action(\RY_IFEZPAY::OPTION_PREFIX . 'auto_get_invoice', [$order->get_id()], 'ry-invoice'); ?>
            <?php if ($next_time > 0) {
                $next_time = as_get_datetime_object($next_time)->setTimezone(wp_timezone()); ?>
            <strong><?php esc_html_e('Expected get time', 'ry-invoice-for-ezpay'); ?>:</strong> <?php echo esc_html($next_time->format('Y-m-d H:i')); ?><br>
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
            echo '<button type="button" class="button ajax-ezpay-invoice" data-action="invalid" data-orderid="' . esc_attr($order->get_id()) . '">'
                . esc_html__('Invalid invoice', 'ry-invoice-for-ezpay')
                . '</button>';
        } elseif ($invoice_number === 'wait') {
            echo '<button type="button" class="button ajax-ezpay-invoice" data-action="cancel" data-orderid="' . esc_attr($order->get_id()) . '">'
                . esc_html__('Cancel get', 'ry-invoice-for-ezpay')
                . '</button>';
        } elseif ($order->is_paid()) {
            echo '<button type="button" class="button ajax-ezpay-invoice" data-action="get" data-orderid="' . esc_attr($order->get_id()) . '">'
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
        self::init_fields($order);

        foreach (self::$fields as $key => $field) {
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
?>
