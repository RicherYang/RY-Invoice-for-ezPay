<?php

namespace RY\Invoice\V20260805\Page;

defined('ABSPATH') or exit;

use RY\General\V20260810\AbstractAdminPage;

final class Status extends AbstractAdminPage
{
    public static function init_menu(): void
    {
        if (!has_action('ry_invoice-show_page-track')) {
            add_filter('ry_invoice-navs', [__CLASS__, 'add_nav'], 11);
            add_action('ry_invoice-show_page-track', [__CLASS__, 'pre_show_page']);
        }
    }

    public static function add_nav(array $navs): array
    {
        $navs[] = [
            'name' => __('Track status', 'ry-invoice-for-ezpay'),
            'type' => 'track',
        ];

        return $navs;
    }

    protected function do_init(): void
    {
        wp_enqueue_script('ry-invoice-admin-invoice');

        wp_localize_script('ry-invoice-admin-invoice', 'RyAdminInvoiceParams', [
            '_nonce' => [
                'track' => wp_create_nonce('track-status'),
            ],
        ]);
    }

    public function output_page(): void
    {
        echo '<p id="invoice-track-status">' . esc_html__('Loading data...', 'ry-invoice-for-ezpay') . '</p>';
        echo '<p class="description">' . esc_html__('Only get this and next term status.', 'ry-invoice-for-ezpay') . '</p>';
    }
}
