<?php

namespace RY\Invoice\V20260729;

defined('ABSPATH') or exit;

abstract class AbstractLinkProvider
{
    protected function generate_trade_no($object_ID, $order_prefix = '')
    {
        return $order_prefix . $object_ID . 'T' . random_int(0, 9) . strrev((string) time());
    }

    protected function clean_string(string $string)
    {
        $string = wp_strip_all_tags($string);
        $string = trim(str_replace(["\r", "\n", "\t"], '', $string));
        return str_replace(['|', '<', '>', '&', ':', '\'', '"', '`'], '', $string);
    }

    public static function get_info()
    {
        $general_info = get_option('RY_Invoice_general', []);
        if (!is_array($general_info)) {
            $general_info = [];
        }

        $general_info = array_merge([
            'count_precision' => 3,
            'amount_precision' => 7,
        ], $general_info);
        $general_info['count_precision'] = (int) $general_info['count_precision'];
        $general_info['amount_precision'] = (int) $general_info['amount_precision'];

        return $general_info;
    }
}
