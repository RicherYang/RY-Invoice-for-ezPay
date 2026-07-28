<?php

namespace RY\Invoice\Ezpay\Admin;

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\Admin\Page\General as PageGeneral;
use RY\Invoice\Ezpay\Admin\Page\Option as PageOption;
use RY\Invoice\Ezpay\License;
use RY\Paid\V20260727\AbstractAdmin;

final class Admin extends AbstractAdmin
{
    private static ?self $_instance = null;

    protected License $license;

    public static function instance(): Admin
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        parent::do_init();

        $this->license = License::instance();
        add_filter('ry-plugin/license_list', [$this, 'add_license']);
        add_filter('enable_ry_invoice', [$this, 'add_enable_ry_invoice']);
        add_action('admin_notices', [$this, 'show_invoice_check']);

        if ($this->license->is_activated()) {
            $this->license->check_expire_cron();

            PageGeneral::init_menu();
            PageOption::init_menu();

            Ajax::instance();

            add_filter('ry-plugin/menu_list', [$this, 'add_menu']);
            add_action('admin_enqueue_scripts', [$this, 'enqueue_scripts']);
        }
    }

    public function add_license(array $license_list): array
    {
        $license_list[RY_IFEZPAY_PLUGIN_BASENAME] = [
            'name' => $this->license::$main_class::PLUGIN_NAME,
            'license' => $this->license,
            'version' => RY_IFEZPAY_VERSION,
            'basename' => RY_IFEZPAY_PLUGIN_BASENAME,
        ];

        return $license_list;
    }

    public function add_enable_ry_invoice(array $enable): array
    {
        $enable[] = 'ezpay';

        return $enable;
    }

    public function show_invoice_check()
    {
        $enable_list = apply_filters('enable_ry_invoice', []);
        if (count($enable_list) > 1) {
            echo '<div class="notice notice-info is-dismissible">';
            echo '<p>' . esc_html__('Not recommended enable two invoice plugins at the same time!', 'ry-invoice-for-ezpay') . '</p>';
            echo '</div>';
        }
    }

    public function add_menu(array $menu_list): array
    {
        $menu_list[] = [
            'name' => __('E-Invoice', 'ry-invoice-for-ezpay'),
            'slug' => 'ry-invoice',
            'capability' => 'manage_options',
            'function' => [$this, 'show_page'],
        ];

        return $menu_list;
    }

    public function show_page(): void
    {
        $navs = apply_filters('ry_invoice-navs', []);
        $show_type = wp_unslash($_GET['type'] ?? 'general');
        if ($show_type !== sanitize_key($show_type)) {
            $show_type = '';
        }

        echo '<div class="wrap">';

        echo '<nav class="nav-tab-wrapper wp-clearfix">';
        foreach ($navs as $nav) {
            printf(
                '<a href="%1$s" class="nav-tab %2$s">%3$s</a>',
                esc_url(add_query_arg([
                    'page' => 'ry-invoice',
                    'type' => $nav['type'],
                ], admin_url('admin.php'))),
                $show_type === $nav['type'] ? 'nav-tab-active' : '',
                esc_html($nav['name'])
            );
        }
        echo '</nav>';

        do_action('ry_invoice-show_page-' . $show_type);

        echo '</div>';
    }

    public function enqueue_scripts()
    {
        $asset_info = include RY_IFEZPAY_PLUGIN_DIR . 'assets/admin/invoice.asset.php';
        wp_register_script('ry-invoice-admin-invoice', RY_IFEZPAY_PLUGIN_URL . 'assets/admin/invoice.js', $asset_info['dependencies'], $asset_info['version'], true);
        wp_register_style('ry-invoice-admin-invoice', RY_IFEZPAY_PLUGIN_URL . 'assets/admin/invoice.css', [], $asset_info['version']);
    }
}
