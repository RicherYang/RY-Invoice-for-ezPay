<?php

namespace RY\Invoice\V20260827;

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
            // 預設捐贈單位 財團法人台灣兒童暨家庭扶助基金會 ( CCF )
            'donate' => ['024', '035', '1785', '2085', '2100', '3100', '5520', '5584', '5875', '5900', '6782', '7123', '7885', '8300', '8585', '8700', '33085', '68660', '70885', '078585', '176176', '323804', '326139', '378585', '461234', '818585', '2812085', '5678585', '6323200', '6361712', '6361716', '7261651'],
        ], $general_info);
        $general_info['count_precision'] = (int) $general_info['count_precision'];
        $general_info['amount_precision'] = (int) $general_info['amount_precision'];

        return $general_info;
    }
}
