<?php

defined('ABSPATH') or exit;

use RY\Paid\AbstractLicense;

final class RY_IFEZPAY_License extends AbstractLicense
{
    public static string $main_class = RY_IFEZPAY::class;

    protected static ?self $_instance = null;

    public static function instance(): RY_IFEZPAY_License
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        $this->valid_key();
    }

    public function activate_key()
    {
        return RY_IFEZPAY_LinkServer::instance()->activate_key($this->get_license_key());
    }

    public function get_version_info()
    {
        $version_info = RY_IFEZPAY::get_transient('version_info');
        if (empty($version_info)) {
            $version_info = RY_IFEZPAY_LinkServer::instance()->check_version();
            if ($version_info) {
                RY_IFEZPAY::set_transient('version_info', $version_info, HOUR_IN_SECONDS);
            }
        }

        return $version_info;
    }

    public function check_expire(): void
    {
        $json = RY_IFEZPAY_LinkServer::instance()->expire_data();
        if (is_array($json) && isset($json['data'])) {
            $this->set_license_data($json['data']);
            RY_IFEZPAY::delete_transient('expire_link_error');
        } elseif ($json === false) {
            $link_error = (int) RY_IFEZPAY::get_transient('expire_link_error');
            if ($link_error > 3) {
                $this->delete_license();
            } else {
                if ($link_error <= 0) {
                    $link_error = 0;
                }
                $link_error += 1;
                RY_IFEZPAY::set_transient('expire_link_error', $link_error);
            }
        } else {
            $this->delete_license();
        }
    }
}
