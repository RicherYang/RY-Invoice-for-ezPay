<?php

defined('ABSPATH') or exit;

final class RY_IFEZPAY_WC_Admin_Setting_Invoice
{
    private static ?self $_instance = null;

    public static function instance(): RY_IFEZPAY_WC_Admin_Setting_Invoice
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_filter('woocommerce_get_sections_rytools', [$this, 'add_sections'], 11);
        add_filter('woocommerce_get_settings_rytools', [$this, 'add_setting'], 10, 2);
        add_action('woocommerce_update_options_rytools_ezpay_invoice', [$this, 'check_option']);
    }

    public function add_sections($sections)
    {
        if (isset($sections['tools'])) {
            $add_idx = array_search('tools', array_keys($sections));
            $sections = array_slice($sections, 0, $add_idx) + [
                'ezpay_invoice' => __('ezPay invoice', 'ry-invoice-for-ezpay'),
            ] + array_slice($sections, $add_idx);
        } else {
            $sections['ezpay_invoice'] = __('ezPay invoice', 'ry-invoice-for-ezpay');
        }

        return $sections;
    }

    public function add_setting($settings, $current_section)
    {
        if ($current_section === 'ezpay_invoice') {
            $settings = include RY_IFEZPAY_PLUGIN_DIR . 'woocommerce/admin/settings/settings-invoice.php';
        }
        return $settings;
    }

    public function check_option()
    {
        if (!preg_match('/^[a-z0-9]{0,3}$/i', RY_IFEZPAY::get_option('prefix', ''))) {
            WC_Admin_Settings::add_error(__('Trade no prefix only letters and numbers allowed, and maximum length is 3 characters.', 'ry-invoice-for-ezpay'));
            RY_IFEZPAY::update_option('prefix', '', false);
        }
    }
}
