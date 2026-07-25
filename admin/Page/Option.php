<?php

namespace RY\Invoice\Ezpay\Admin\Page;

defined('ABSPATH') or exit;

use RY\General\V20260724\AbstractAdminPage;

final class Option extends AbstractAdminPage
{
    public static function init_menu(): void
    {
        add_filter('ry_invoice-navs', [__CLASS__, 'add_nav']);
        add_action('ry_invoice-show_page-ezpay-option', [__CLASS__, 'pre_show_page']);
        add_action('admin_post_ry-invoice-ezpay-option', [__CLASS__, 'admin_action']);
    }

    public static function add_nav(array $navs): array
    {
        $navs[] = [
            'name' => __('ezPay options', 'ry-invoice-for-ezpay'),
            'type' => 'ezpay-option',
        ];

        return $navs;
    }

    protected function do_init(): void {}

    public function output_page(): void
    {
        echo '<form method="post" action="admin-post.php">';
        echo '<input type="hidden" name="action" value="ry-invoice-ezpay-option">';
        wp_nonce_field('ry-invoice-ezpay-option');
        include __DIR__ . '/html/option.php';
        submit_button();
        echo '</form>';
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
        \RY_IFEZPAY::update_option('log', $log);
        $api_info = [
            'testmode' => sanitize_locale_name($_POST['testmode'] ?? '') === 'yes' ? 'yes' : 'no',
            'MerchantID' => sanitize_locale_name($_POST['MerchantID'] ?? ''),
            'HashKey' => sanitize_locale_name($_POST['HashKey'] ?? ''),
            'HashIV' => sanitize_locale_name($_POST['HashIV'] ?? ''),
        ];
        \RY_IFEZPAY::update_option('apiinfo', $api_info, false);
        $this->add_notice('success', __('Settings saved.', 'ry-invoice-for-ezpay'));

        wp_safe_redirect(admin_url('admin.php?page=ry-invoice&type=ezpay-option'));
    }
}
