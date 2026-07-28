<?php

namespace RY\Invoice\Ezpay;

defined('ABSPATH') or exit;

use RY\General\V20260727\AbstractBasic;
use RY\General\V20260727\Logs;
use RY\Invoice\Ezpay\Admin\Admin;
use RY\Invoice\Ezpay\WooCommerce\Fields;
use RY\Invoice\Ezpay\WooCommerce\Invoice;

final class Main extends AbstractBasic
{
    public const OPTION_PREFIX = 'RY_IFEZPAY_';

    public const PLUGIN_NAME = 'RY Invoice for ezPay';

    private static ?self $_instance = null;

    public static function instance(): Main
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        load_plugin_textdomain('ry-invoice-for-ezpay', false, plugin_basename(dirname(__DIR__)) . '/languages');

        Logs::set_log(Main::get_option('log', 'no') === 'yes', 'ezpay-invoice');

        if (is_admin()) {
            Update::update();
        }

        add_action('init', [$this, 'do_wp_init'], 9);
    }

    public function do_wp_init(): void
    {
        Updater::instance();

        if (is_admin()) {
            Admin::instance();
        }

        if (License::instance()->is_activated()) {
            Cron::add_action();
        }

        if (did_action('woocommerce_init')) {
            Fields::instance();

            if (License::instance()->is_activated()) {
                Invoice::instance();
            }
        }
    }

    public static function usage_tracking(): void
    {
        if (get_option('RY_General_tracking', 'yes') !== 'yes') {
            return;
        }

        LinkServer::instance()->send_tracking();
    }

    public static function plugin_activation(): void {}

    public static function plugin_deactivation(): void
    {
        wp_unschedule_hook(self::OPTION_PREFIX . 'check_expire');
    }
}
