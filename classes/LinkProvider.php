<?php

namespace RY\Invoice\Ezpay;

defined('ABSPATH') or exit;

use RY\General\V20260810\Logs;
use RY\General\V20260810\Utils;
use RY\Invoice\V20260805\AbstractLinkProvider;

final class LinkProvider extends AbstractLinkProvider
{
    private static ?self $_instance = null;

    private array $api_test_url = [
        'get' => 'https://cinv.ezpay.com.tw/Api/invoice_issue',
        'invalid' => 'https://cinv.ezpay.com.tw/Api/invoice_invalid',
        'track' => 'https://cinv.ezpay.com.tw/Api_number_management/searchNumber',
    ];

    private array $api_url = [
        'get' => 'https://inv.ezpay.com.tw/Api/invoice_issue',
        'invalid' => 'https://inv.ezpay.com.tw/Api/invoice_invalid',
        'track' => 'https://inv.ezpay.com.tw/Api_number_management/searchNumber',
    ];

    public static function instance(): LinkProvider
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void {}

    public function get_invoice($invoice_data, $object_ID)
    {
        $general_info = $this::get_info();
        $api_info = $this->get_api_info();

        $now = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $post_args = [
            'RespondType' => 'JSON',
            'Version' => '1.5',
            'TimeStamp' => $now->getTimestamp(),
            'MerchantOrderNo' => $this->generate_trade_no($object_ID, $invoice_data['prefix']),
            'Status' => 1,
            'Category' => 'B2C',
            'BuyerName' => __('Customer', 'ry-invoice-for-ezpay'),
            'BuyerAddress' => __('Taiwan', 'ry-invoice-for-ezpay'),
            'BuyerEmail' => $invoice_data['email'],
            'CarrierType' => '',
            'CarrierNum' => '',
            'PrintFlag' => 'N',
            'TaxType' => 1,
            'TaxRate' => 5,
            'Amt' => 0,
            'AmtSales' => 0,
            'AmtFree' => 0,
            'AmtZero' => 0,
            'TaxAmt' => 0,
            'TotalAmt' => round($invoice_data['total'], 0),
            'ItemName' => [],
            'ItemCount' => [],
            'ItemUnit' => [],
            'ItemPrice' => [],
            'ItemAmt' => [],
            'ItemTaxType' => [],
            'Comment' => '#' . $invoice_data['no'],
        ];

        switch ($invoice_data['type']) {
            case 'host':
                $post_args['CarrierType'] = 2;
                $post_args['CarrierNum'] = hash('md5', $invoice_data['email']);
                break;
            case 'MOICA':
                $post_args['CarrierType'] = 1;
                $post_args['CarrierNum'] = $invoice_data['moica_no'];
                break;
            case 'phone_barcode':
                $post_args['CarrierType'] = 0;
                $post_args['CarrierNum'] = $invoice_data['phone_barcode'];
                break;
            case 'company':
                $post_args['Category'] = 'B2B';
                $post_args['PrintFlag'] = 'Y';
                $post_args['BuyerUBN'] = $invoice_data['tax_no'];
                $post_args['BuyerName'] = $invoice_data['tax_name'];
                if (empty($post_args['BuyerName'])) {
                    $post_args['BuyerName'] = $post_args['BuyerUBN'];
                }
                break;
            case 'donate':
                $post_args['LoveCode'] = $invoice_data['donate_no'];
                break;
        }

        foreach ($invoice_data['item'] as $invoice_item) {
            if ($invoice_item['qty'] == 0 && $invoice_item['total'] == 0) {
                continue;
            }
            if ($invoice_item['qty'] == 0) {
                $invoice_item['qty'] = 1;
            }

            $name = mb_strimwidth($this->clean_string($invoice_item['name']), 0, 80, '');
            $unit = mb_strimwidth($this->clean_string($invoice_item['unit']), 0, 6, '');
            $qty = round($invoice_item['qty'], $general_info['count_precision']);
            $total = $invoice_item['total'];
            if ($post_args['Category'] === 'B2B') {
                $total = $total / 1.05;
            }
            $unit_price = round($total / $qty, $general_info['amount_precision']);
            $total = round($unit_price * $qty, $general_info['amount_precision']);

            match($invoice_item['tax']) {
                1 => $post_args['AmtSales'] += $total,
            };
            $post_args['ItemName'][] = $name;
            $post_args['ItemCount'][] = $qty;
            $post_args['ItemUnit'][] = $unit;
            $post_args['ItemPrice'][] = $unit_price;
            $post_args['ItemAmt'][] = $total;
            $post_args['ItemTaxType'][] = $invoice_item['tax'];
        }

        $post_args['AmtSales'] = round($post_args['AmtSales'], 0);
        $post_args['Amt'] = $post_args['AmtSales'] + $post_args['AmtFree'] + $post_args['AmtZero'];
        $post_args['TaxAmt'] = $post_args['TotalAmt'] - $post_args['Amt'];
        $post_args['Comment'] = apply_filters('ry_invoice-main_remark', $post_args['Comment'], $object_ID);
        $post_args['Comment'] = mb_strimwidth($this->clean_string($post_args['Comment']), 0, 200, '');

        foreach ($post_args as $key => $value) {
            if (is_array($value)) {
                foreach ($value as $sub_key => $sub_value) {
                    if (is_int($sub_value) || is_float($sub_value)) {
                        $post_args[$key][$sub_key] = (string) $sub_value;
                    }
                }
                $post_args[$key] = implode('|', $post_args[$key]);
            }
            if (is_int($value) || is_float($value)) {
                $post_args[$key] = (string) $value;
            }
        }

        if ($api_info['testmode']) {
            $post_url = $this->api_test_url['get'];
        } else {
            $post_url = $this->api_url['get'];
        }

        do_action('ry_invoice_ezpay-pre_get_invoice', $post_args, $object_ID);
        Logs::log('ezpay-invoice', 'info', 'Get LINK #' . $object_ID, $post_args);
        $result = $this->link_server($post_url, $post_args, $api_info['MerchantID'], $api_info['HashKey'], $api_info['HashIV']);
        if ($result) {
            Logs::log('ezpay-invoice', 'info', 'Get response #' . $object_ID, $result);
            do_action('ry_invoice_ezpay-post_get_invoice', $post_args, $result, $object_ID);
        }
    }

    public function invalid_invoice($invoice_data, $object_ID = null)
    {
        $api_info = $this->get_api_info();

        $now = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $post_args = [
            'RespondType' => 'JSON',
            'Version' => '1.0',
            'TimeStamp' => $now->getTimestamp(),
            'InvoiceNumber' => $invoice_data['no'],
            'InvalidReason' => __('Order cancel', 'ry-invoice-for-smilepay'),
        ];

        if ($api_info['testmode']) {
            $post_url = $this->api_test_url['invalid'];
        } else {
            $post_url = $this->api_url['invalid'];
        }

        do_action('ry_invoice_ezpay-pre_invalid_invoice', $post_args, $object_ID);
        Logs::log('ezpay-invoice', 'info', 'Invalid LINK #' . $object_ID, $post_args);
        $result = $this->link_server($post_url, $post_args, $api_info['MerchantID'], $api_info['HashKey'], $api_info['HashIV']);
        if ($result) {
            Logs::log('ezpay-invoice', 'info', 'Invalid response #' . $object_ID, $result);
            do_action('ry_invoice_ezpay-post_invalid_invoice', $post_args, $result, $object_ID);
        }
    }

    public function track_status($year, $term)
    {
        $api_info = $this->get_api_info();

        $now = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $post_args = [
            'RespondType' => 'JSON',
            'Version' => '1.0',
            'TimeStamp' => $now->getTimestamp(),
            'Year' => $year - 1911,
            'Term' => $term,
        ];

        if ($api_info['testmode']) {
            $post_url = $this->api_test_url['track'];
        } else {
            $post_url = $this->api_url['track'];
        }

        $result = $this->link_server_number($post_url, $post_args, $api_info['CompanyID'], $api_info['C_HashKey'], $api_info['C_HashIV']);
        Logs::log('ezpay-invoice', 'info', 'Track LINK #' . $year . '-' . $term, $result);
        return $result;
    }

    public function get_api_info()
    {
        $api_info = Main::get_option('apiinfo', []);
        if (!is_array($api_info)) {
            $api_info = [];
        }
        $api_info = array_merge([
            'testmode' => 'no',
            'MerchantID' => '',
            'HashKey' => '',
            'HashIV' => '',
            'CompanyID' => '',
            'C_HashKey' => '',
            'C_HashIV' => '',
        ], $api_info);
        $api_info['testmode'] = Utils::string_to_bool($api_info['testmode']);

        return $api_info;
    }

    protected function generate_trade_no($object_ID, $order_prefix = '')
    {
        $trade_no = parent::generate_trade_no($object_ID, $order_prefix);
        $trade_no = apply_filters('ry_invoice_ezpay-trade_no', $trade_no, $object_ID, $order_prefix);

        return substr($trade_no, 0, 18);
    }

    protected function link_server(string $url, array $args, string $MerchantID, string $HashKey, string $HashIV, int $timeout = 30)
    {
        @set_time_limit(40);

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

    protected function link_server_number(string $url, array $args, string $MerchantID, string $HashKey, string $HashIV, int $timeout = 30)
    {
        @set_time_limit(40);

        ksort($args);
        $args_string = http_build_query($args);
        $encrypt_string = @openssl_encrypt($args_string, 'aes-256-cbc', $HashKey, OPENSSL_RAW_DATA, $HashIV);

        $post_data = [
            'CompanyID_' => 'C1467281175',
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

        return $result;
    }
}
