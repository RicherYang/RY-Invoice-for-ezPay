<?php

defined('ABSPATH') or exit;

use RY\General\AbstractAdminPage;

final class RY_IFEZPAY_Admin_Page_Option extends AbstractAdminPage
{
    public static function init_menu(): void
    {
        add_filter('ry-invoice-navs', [__CLASS__, 'add_nav']);
        add_submenu_page('', __('ezPay options', 'ry-invoice-for-ezpay'), '', 'manage_options', 'ry-invoice-ezpay-option', [__CLASS__, 'pre_show_page']);
        add_action('load-admin_page_ry-invoice-ezpay-option', [__CLASS__, 'instance']);
        add_action('admin_post_ry-invoice-ezpay-option', [__CLASS__, 'admin_action']);
    }

    public static function add_nav(array $navs): array
    {
        $navs[] = [
            'name' => __('ezPay options', 'ry-invoice-for-ezpay'),
            'slug' => 'ry-invoice-ezpay-option',
        ];

        return $navs;
    }

    protected function do_init(): void
    {
        global $_wp_menu_nopriv, $_wp_real_parent_file, $submenu_file;

        if ($_wp_menu_nopriv) {
            $_wp_menu_nopriv['ry-invoice-ezpay-option'] = true;
            $_wp_real_parent_file['ry-invoice-ezpay-option'] = RY_IFEZPAY_Admin::instance()->main_slug;
            $submenu_file = 'ry-invoice';
        }
    }

    public function output_page(): void
    {
        echo '<div class="wrap">';

        $show_type = 'ry-invoice-ezpay-option';
        include RY_IFEZPAY_PLUGIN_DIR . 'admin/page/html/nav.php';

        echo '<form method="post" action="admin-post.php">';
        echo '<input type="hidden" name="action" value="ry-invoice-ezpay-option">';
        wp_nonce_field('ry-invoice-ezpay-option');
        include RY_IFEZPAY_PLUGIN_DIR . 'admin/page/html/option.php';
        submit_button();
        echo '</form>';

        echo '</div>';
    }

    public function do_admin_action(string $action): void
    {
        if ('ry-invoice-ezpay-option' !== $action) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'ry-invoice-ezpay-option')) {
            wp_die('Invalid nonce');
        }

        $log = sanitize_locale_name($_POST['log'] ?? '') === 'yes' ? 'yes' : 'no';
        RY_IFEZPAY::update_option('log', $log);
        $api_info = [
            'testmode' => sanitize_locale_name($_POST['testmode'] ?? '') === 'yes' ? 'yes' : 'no',
            'MerchantID' => sanitize_locale_name($_POST['MerchantID'] ?? ''),
            'HashKey' => sanitize_locale_name($_POST['HashKey'] ?? ''),
            'HashIV' => sanitize_locale_name($_POST['HashIV'] ?? ''),
        ];
        RY_IFEZPAY::update_option('apiinfo', $api_info, false);
        $this->add_notice('success', __('Settings saved.', 'ry-invoice-for-ezpay'));

        wp_safe_redirect(admin_url('admin.php?page=ry-invoice-ezpay-option'));
    }
}

RY_IFEZPAY_Admin_Page_Option::init_menu();
