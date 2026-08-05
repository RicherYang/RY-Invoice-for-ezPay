<?php

namespace RY\Invoice\Ezpay;

defined('ABSPATH') or exit;

final class Utils
{
    public static function track_term_to_name($value = '')
    {
        static $list = [];
        if (empty($list)) {
            $list = [
                '1' => _x('Jan - Feb', 'track term', 'ry-invoice-for-ezpay'),
                '2' => _x('Mar - Apr', 'track term', 'ry-invoice-for-ezpay'),
                '3' => _x('May - Jun', 'track term', 'ry-invoice-for-ezpay'),
                '4' => _x('Jul - Aug', 'track term', 'ry-invoice-for-ezpay'),
                '5' => _x('Sep - Oct', 'track term', 'ry-invoice-for-ezpay'),
                '6' => _x('Nov - Dec', 'track term', 'ry-invoice-for-ezpay'),
            ];
        }

        return $list[$value] ?? $value;
    }

    public static function track_status_to_name($value = '')
    {
        static $list = [];
        if (empty($list)) {
            $list = [
                '0' => _x('Pending', 'track status', 'ry-invoice-for-ezpay'),
                '1' => _x('Use', 'track status', 'ry-invoice-for-ezpay'),
                '2' => _x('Disable', 'track status', 'ry-invoice-for-ezpay'),
            ];
        }

        return $list[$value] ?? $value;
    }
}
