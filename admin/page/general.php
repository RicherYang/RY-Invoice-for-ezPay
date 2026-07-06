<?php

defined('ABSPATH') or exit;

final class RY_IFEZPAY_Admin_Page_General extends RY_Abstract_Admin_Page
{
    protected static $_instance = null;

    public static function init_menu(): void
    {
        if (!has_action('load-admin_page_ry-invoice-general')) {
            add_filter('ry-invoice-navs', [__CLASS__, 'add_nav']);
            add_submenu_page('', __('General', 'ry-invoice-for-ezpay'), '', 'manage_options', 'ry-invoice-general', [__CLASS__, 'pre_show_page']);
            add_action('load-admin_page_ry-invoice-general', [__CLASS__, 'instance']);
        }

        add_action('admin_post_ry-invoice-general', [__CLASS__, 'admin_action']);
    }

    public static function add_nav(array $navs): array
    {
        $navs[] = [
            'name' => __('General', 'ry-invoice-for-ezpay'),
            'slug' => 'ry-invoice-general',
        ];

        return $navs;
    }

    protected function do_init(): void
    {
        global $_wp_menu_nopriv, $_wp_real_parent_file, $submenu_file;

        if ($_wp_menu_nopriv) {
            $_wp_menu_nopriv['ry-invoice-general'] = true;
            $_wp_real_parent_file['ry-invoice-general'] = RY_IFEZPAY_Admin::instance()->main_slug;
            $submenu_file = 'ry-invoice';
        }

        add_action('admin_enqueue_scripts', [$this, 'add_scripts'], 11);
    }

    public function add_scripts()
    {
        wp_enqueue_script('ry-invoice-admin-invoice');
    }

    public function output_page(): void
    {
        echo '<div class="wrap">';

        $show_type = 'ry-invoice-general';
        include RY_IFEZPAY_PLUGIN_DIR . 'admin/page/html/nav.php';

        echo '<form method="post" action="admin-post.php">';
        echo '<input type="hidden" name="action" value="ry-invoice-general">';
        wp_nonce_field('ry-invoice-general');
        include RY_IFEZPAY_PLUGIN_DIR . 'admin/page/html/general.php';
        submit_button();
        echo '</form>';

        echo '</div>';
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

        RY_IFEZPAY::update_option('general', $general_info, false);
        $this->add_notice('success', __('Settings saved.', 'ry-invoice-for-ezpay'));

        wp_safe_redirect(admin_url('admin.php?page=ry-invoice-general'));
    }
}

RY_IFEZPAY_Admin_Page_General::init_menu();
