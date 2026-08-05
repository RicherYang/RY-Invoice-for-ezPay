<?php

namespace RY\Invoice\V20260805;

defined('ABSPATH') or exit;

final class Utils
{
    public static function invoice_type_to_name($value = '')
    {
        static $list = [];
        if (empty($list)) {
            $list = [
                'personal' => _x('personal', 'invoice type', 'ry-invoice-for-ezpay'),
                'company' => _x('company', 'invoice type', 'ry-invoice-for-ezpay'),
                'donate' => _x('donate', 'invoice type', 'ry-invoice-for-ezpay'),
            ];
        }

        return $list[$value] ?? $value;
    }

    public static function carruer_type_to_name($value = '')
    {
        static $list = [];
        if (empty($list)) {
            $list = [
                'amego_host' => _x('amego_host', 'carruer type', 'ry-invoice-for-ezpay'),
                'ezpay_host' => _x('ezpay_host', 'carruer type', 'ry-invoice-for-ezpay'),
                'ecpay_host' => _x('ecpay_host', 'carruer type', 'ry-invoice-for-ezpay'),
                'smilepay_host' => _x('smilepay_host', 'carruer type', 'ry-invoice-for-ezpay'),
                'MOICA' => _x('MOICA', 'carruer type', 'ry-invoice-for-ezpay'),
                'phone_barcode' => _x('phone_barcode', 'carruer type', 'ry-invoice-for-ezpay'),
            ];
        }

        return $list[$value] ?? $value;
    }
}
