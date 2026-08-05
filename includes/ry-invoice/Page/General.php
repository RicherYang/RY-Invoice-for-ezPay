<?php

namespace RY\Invoice\V20260805\Page;

defined('ABSPATH') or exit;

use RY\General\V20260801\AbstractAdminPage;
use RY\General\V20260801\Utils;

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
        echo '<form method="post" action="' . esc_url(admin_url('admin-post.php')) . '">';
        include __DIR__ . '/html/general.php';
        Utils::the_action_form_button('invoice-general', 'save-option', __('Save Changes', 'ry-invoice-for-ezpay'), 'submit', 'button-primary');
        echo '</form>';
    }

    protected function do_admin_action(string $action, string $real_action): void
    {
        if ('ry-invoice-general' !== $action) {
            return;
        }

        if ($real_action !== '' && is_callable([$this, $real_action])) {
            $this->$real_action();
        }

        wp_safe_redirect(admin_url('admin.php?page=ry-invoice&type=general'));
        exit;
    }

    private function save_option(): void
    {
        check_ajax_referer('save-option', '_ajax_nonce');

        $general_info = [
            'count_precision' => intval($_POST['count_precision'] ?? ''),
            'amount_precision' => intval($_POST['amount_precision'] ?? ''),
        ];

        update_option('RY_Invoice_general', $general_info, false);
        $this->add_notice('success', __('Settings saved.', 'ry-invoice-for-ezpay'));
    }
}
