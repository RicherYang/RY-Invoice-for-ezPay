<?php

namespace RY\Invoice\Ezpay\Admin\Page;

defined('ABSPATH') or exit;

use RY\General\V20260729\AbstractAdminPage;
use RY\General\V20260729\Utils;
use RY\Invoice\Ezpay\Main;

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
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        include __DIR__ . '/html/option.php';
        Utils::the_action_form_button('invoice-ezpay-option', 'save-option', __('Save Changes', 'ry-invoice-for-ezpay'), 'submit', 'button-primary');
        echo '</form>';
    }

    protected function do_admin_action(string $action, string $real_action): void
    {
        if ('ry-invoice-ezpay-option' !== $action) {
            return;
        }

        if ($real_action !== '' && is_callable([$this, $real_action])) {
            $this->$real_action();
        }

        wp_safe_redirect(admin_url('admin.php?page=ry-invoice&type=ezpay-option'));
        exit;
    }

    private function save_option(): void
    {
        check_ajax_referer('save-option', '_ajax_nonce');

        Main::update_option('log', Utils::bool_to_string($_POST['log'] ?? ''));
        $api_info = [
            'testmode' => Utils::bool_to_string($_POST['testmode'] ?? ''),
            'MerchantID' => sanitize_locale_name($_POST['MerchantID'] ?? ''),
            'HashKey' => sanitize_locale_name($_POST['HashKey'] ?? ''),
            'HashIV' => sanitize_locale_name($_POST['HashIV'] ?? ''),
        ];
        Main::update_option('apiinfo', $api_info, false);
        $this->add_notice('success', __('Settings saved.', 'ry-invoice-for-ezpay'));
    }
}
