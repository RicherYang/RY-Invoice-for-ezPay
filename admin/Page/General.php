<?php

namespace RY\Invoice\Ezpay\Admin\Page;

defined('ABSPATH') or exit;

use RY\General\V20260727\AbstractAdminPage;

final class General extends AbstractAdminPage
{
    public static function init_menu(): void
    {
        if (!has_action('ry_invoice-show_page-general')) {
            add_filter('ry_invoice-navs', [__CLASS__, 'add_nav']);
            add_action('ry_invoice-show_page-general', [__CLASS__, 'pre_show_page']);
        }

        add_action('admin_post_ry-invoice-general', [__CLASS__, 'admin_action']);
    }

    public static function add_nav(array $navs): array
    {
        $navs[] = [
            'name' => __('Options', 'ry-invoice-for-ezpay'),
            'type' => 'general',
        ];

        return $navs;
    }

    protected function do_init(): void {}

    public function output_page(): void
    {
        echo '<form method="post" action="admin-post.php">';
        echo '<input type="hidden" name="action" value="ry-invoice-general">';
        wp_nonce_field('ry-invoice-general');
        include __DIR__ . '/html/general.php';
        submit_button();
        echo '</form>';
    }

    public function do_admin_action(string $action): void
    {
        if ('ry-invoice-general' !== $action) {
            return;
        }

        if (!wp_verify_nonce($_POST['_wpnonce'] ?? '', 'ry-invoice-general')) {
            wp_die('Invalid nonce');
        }

        $general_info = [
            'count_precision' => intval($_POST['count_precision'] ?? ''),
            'amount_precision' => intval($_POST['amount_precision'] ?? ''),
        ];

        \RY_IFEZPAY::update_option('general', $general_info, false);
        $this->add_notice('success', __('Settings saved.', 'ry-invoice-for-ezpay'));

        wp_safe_redirect(admin_url('admin.php?page=ry-invoice&type=general'));
    }
}
