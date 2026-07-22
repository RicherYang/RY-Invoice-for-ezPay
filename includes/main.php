<?php

defined('ABSPATH') or exit;

use RY\General\AbstractBasic;
use RY\General\ActionScheduler;
use RY\General\Logs;
use RY\Invoice\Ezpay\Admin\Admin;
use RY\Invoice\Ezpay\Cron;
use RY\Invoice\Ezpay\License;
use RY\Invoice\Ezpay\Update;
use RY\Invoice\Ezpay\Updater;
use RY\Invoice\Ezpay\WooCommerce\Fields;
use RY\Invoice\Ezpay\WooCommerce\Invoice;

final class RY_IFEZPAY extends AbstractBasic
{
    public const OPTION_PREFIX = 'RY_IFEZPAY_';

    public const PLUGIN_NAME = 'RY Invoice for ezPay';

    private static ?self $_instance = null;

    public static function instance(): RY_IFEZPAY
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
        include_once RY_IFEZPAY_PLUGIN_DIR . 'includes/vendor/woocommerce/action-scheduler/action-scheduler.php';
        ActionScheduler::instance();

        Logs::set_log(RY_IFEZPAY::get_option('log', 'no') === 'yes', 'ezpay-invoice');

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

        if (has_action('woocommerce_init')) {
            Fields::instance();

            if (License::instance()->is_activated()) {
                Invoice::instance();
            }
        }
    }

    public static function plugin_activation() {}

    public static function plugin_deactivation()
    {
        wp_unschedule_hook(self::OPTION_PREFIX . 'check_expire');
    }
}
