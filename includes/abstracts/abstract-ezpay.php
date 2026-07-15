<?php

use RY\General\Logs;

abstract class RY_IFEZPAY_Abstract_Invoice
{
    protected function generate_trade_no($object_ID, $order_prefix = '')
    {
        $trade_no = $order_prefix . $object_ID . 'T' . random_int(0, 9) . strrev((string) time());
        $trade_no = apply_filters('ry_invoice_ezpay-trade_no', $trade_no, $object_ID, $order_prefix);

        return substr($trade_no, 0, 18);
    }

    protected function link_server(string $url, array $args, string $MerchantID, string $HashKey, string $HashIV, int $timeout = 30)
    {
        wc_set_time_limit(40);

        ksort($args);
        $args_string = http_build_query($args);
        $encrypt_string = @openssl_encrypt($args_string, 'aes-256-cbc', $HashKey, OPENSSL_RAW_DATA, $HashIV);

        $post_data = [
            'MerchantID_' => $MerchantID,
            'PostData_' => bin2hex($encrypt_string),
        ];
        $response = wp_remote_post($url, [
            'timeout' => $timeout,
            'body' => $post_data,
            'user-agent' => apply_filters('http_headers_useragent', 'WordPress/' . get_bloginfo('version')),
        ]);

        if (is_wp_error($response)) {
            Logs::log('ezpay-invoice', 'error', 'Link failed', $response->get_error_messages());
            return;
        }

        if (wp_remote_retrieve_response_code($response) != 200) {
            Logs::log('ezpay-invoice', 'error', 'Link HTTP status error', ['status' => wp_remote_retrieve_response_code($response)]);
            return;
        }

        $result = json_decode(wp_remote_retrieve_body($response));

        if (!is_object($result)) {
            Logs::log('ezpay-invoice', 'error', 'Link response parse failed', ['response' => wp_remote_retrieve_body($response)]);
            return;
        }

        if ($result->Status === 'SUCCESS') {
            $result->Result = json_decode($result->Result);
        }

        return $result;
    }
}
