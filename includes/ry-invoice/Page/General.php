<?php

namespace RY\Invoice\V20260906\Page;

defined('ABSPATH') or exit;

use RY\General\V20260810\AbstractAdminPage;
use RY\General\V20260810\Utils;

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
            'donate' => sanitize_text_field($_POST['donate'] ?? ''),
            'buyer' => [
                'name' => Utils::bool_to_string($_POST['buyer_name'] ?? 'no'),
                'address' => Utils::bool_to_string($_POST['buyer_address'] ?? 'no'),
            ],
        ];

        if ($general_info['count_precision'] < 1 || $general_info['count_precision'] > 7) {
            $general_info['count_precision'] = 3;
        }

        if ($general_info['amount_precision'] < 1 || $general_info['amount_precision'] > 7) {
            $general_info['amount_precision'] = 7;
        }

        $general_info['donate'] = explode(',', $general_info['donate']);
        foreach ($general_info['donate'] as &$donate) {
            $donate = preg_replace('/[^0-9]/', '', $donate);
        }
        unset($donate);
        $general_info['donate'] = array_filter($general_info['donate']);
        sort($general_info['donate']);
        if (empty($general_info['donate'])) {
            // 預設捐贈單位 財團法人台灣兒童暨家庭扶助基金會 ( CCF )
            $general_info['donate'] = ['024', '035', '1785', '2085', '2100', '3100', '5520', '5584', '5875', '5900', '6782', '7123', '7885', '8300', '8585', '8700', '33085', '68660', '70885', '078585', '176176', '323804', '326139', '378585', '461234', '818585', '2812085', '5678585', '6323200', '6361712', '6361716', '7261651'];
        }

        update_option('RY_Invoice_general', $general_info, false);
        $this->add_notice('success', __('Settings saved.', 'ry-invoice-for-ezpay'));
    }
}
