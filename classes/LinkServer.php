<?php

namespace RY\Invoice\Ezpay;

defined('ABSPATH') or exit;

use RY\Paid\V20260727\AbstractLinkServer;

final class LinkServer extends AbstractLinkServer
{
    private static ?self $_instance = null;

    protected string $plugin_slug = 'ry-invoice-for-ezpay';

    public static function instance(): LinkServer
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
        ];
    }
}
