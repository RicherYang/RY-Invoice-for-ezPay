<?php

defined('ABSPATH') or exit;

include_once RY_IFEZPAY_PLUGIN_DIR . 'includes/ry-general/abstract-admin.php';

final class RY_IFEZPAY_Admin extends RY_Abstract_Admin
{
    protected static ?self $_instance = null;

    public static function instance(): RY_IFEZPAY_Admin
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

        $this->license = RY_IFEZPAY_License::instance();
        add_filter('ry-plugin/license_list', [$this, 'add_license']);
        add_filter('enable_ry_invoice', [$this, 'add_enable_ry_invoice']);
        add_action('admin_notices', [$this, 'show_invoice_check']);

        if ($this->license->is_activated()) {
            $this->license->check_expire_cron();

            include_once RY_IFEZPAY_PLUGIN_DIR . 'admin/page/general.php';
            include_once RY_IFEZPAY_PLUGIN_DIR . 'admin/page/option.php';

            include_once RY_IFEZPAY_PLUGIN_DIR . 'admin/ajax.php';
            RY_IFEZPAY_Admin_Ajax::instance();

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

    public function add_enable_ry_invoice($enable)
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
            'function' => [$this, 'goto_page'],
        ];

        return $menu_list;
    }

    public function goto_page()
    {
        echo '<script>location.href="' . esc_url(admin_url('admin.php?page=ry-invoice-general')) . '";</script>';
        exit;
    }

    public function enqueue_scripts()
    {
        $asset_info = include RY_IFEZPAY_PLUGIN_DIR . 'assets/admin/invoice.asset.php';
        wp_register_script('ry-invoice-admin-invoice', RY_IFEZPAY_PLUGIN_URL . 'assets/admin/invoice.js', $asset_info['dependencies'], $asset_info['version'], true);
        wp_register_style('ry-invoice-admin-invoice', RY_IFEZPAY_PLUGIN_URL . 'assets/admin/invoice.css', [], $asset_info['version']);
    }
}
