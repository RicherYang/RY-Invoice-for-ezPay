<?php

defined('ABSPATH') or exit;

include_once RY_IFEZPAY_PLUGIN_DIR . 'includes/ry-paid/abstract-link-server.php';

final class RY_IFEZPAY_LinkServer extends RY_Abstract_Link_Server
{
    protected static ?self $_instance = null;

    protected string $plugin_slug = 'ry-invoice-for-ezpay';

    public static function instance(): RY_IFEZPAY_LinkServer
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
        }

        return self::$_instance;
    }

    protected function get_base_info(): array
    {
        return [
            'plugin' => RY_IFEZPAY_VERSION,
            'php' => PHP_VERSION,
            'wp' => get_bloginfo('version'),
        ];
    }

    protected function get_user_agent()
    {
        return sprintf(
            'RY_IFEZPAY %s (WordPress/%s)',
            RY_IFEZPAY_VERSION,
            get_bloginfo('version'),
        );
    }
}
