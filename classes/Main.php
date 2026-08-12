<?php

namespace RY\Invoice\Ezpay;

defined('ABSPATH') or exit;

use RY\General\V20260810\AbstractBasic;
use RY\General\V20260810\Utils;
use RY\Invoice\Ezpay\Admin\Admin;
use RY\Invoice\Ezpay\WooCommerce\Fields;
use RY\Invoice\Ezpay\WooCommerce\Invoice;

final class Main extends AbstractBasic
{
    public const PREFIX = 'RY_IFEZPAY_';

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

        if (is_admin()) {
            Update::update();
        }

        add_filter('ry-plugin/log_enabled', [$this, 'set_log_enabled'], 10, 2);
        add_action('init', [$this, 'do_wp_init'], 9);
    }

    public function set_log_enabled(bool $enabled, string $handle): bool
    {
        if ($handle === 'ezpay-invoice') {
            return Utils::string_to_bool(self::get_option('log', ''));
        }

        return $enabled;
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
        wp_unschedule_hook(self::get_prefix_name('check_expire'));
    }
}
