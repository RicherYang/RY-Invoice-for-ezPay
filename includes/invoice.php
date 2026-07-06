<?php

defined('ABSPATH') or exit;

final class RY_IFEZPAY_Invoice extends RY_IFEZPAY_Abstract_Invoice
{
    protected static ?self $_instance = null;

    private array $api_test_url = [
        'get' => 'https://cinv.ezpay.com.tw/Api/invoice_issue',
        'invalid' => 'https://cinv.ezpay.com.tw/Api/invoice_invalid',
    ];

    private array $api_url = [
        'get' => 'https://inv.ezpay.com.tw/Api/invoice_issue',
        'invalid' => 'https://inv.ezpay.com.tw/Api/invoice_invalid',
    ];

    public static function instance(): RY_IFEZPAY_Invoice
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
        $general_info = $this->get_info();
        $api_info = $this->get_api_info();

        $now = new DateTime('now', new DateTimeZone('Asia/Taipei'));
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

            $name = mb_strimwidth(str_replace('|', '', wp_strip_all_tags($invoice_item['name'])), 0, 80, '');
            $unit = mb_strimwidth(str_replace('|', '', wp_strip_all_tags($invoice_item['unit'])), 0, 6, '');
            $qty = round($invoice_item['qty'], $general_info['count_precision']);
            $total = $invoice_item['total'];
            if ($post_args['Category'] === 'B2B') {
                $total = round($total / 1.05, 0);
                $unit_price = round($total / $qty, $general_info['count_precision']);
                $total = round($unit_price * $qty, $general_info['count_precision']);
            } else {
                $unit_price = round($total / $qty, $general_info['count_precision']);
                $total = round($unit_price * $qty, $general_info['count_precision']);
            }

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
        $post_args['Amt'] = round($post_args['AmtSales'] + $post_args['AmtFree'] + $post_args['AmtZero'], 0);
        $post_args['TaxAmt'] = $post_args['TotalAmt'] - $post_args['Amt'];
        $post_args['Comment'] = apply_filters('ry_invoice-main_remark', $post_args['Comment'], $object_ID);
        $post_args['Comment'] = mb_strimwidth(wp_strip_all_tags($post_args['Comment']), 0, 200, '');

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
        RY_Logs::log('ezpay-invoice', 'info', 'Get LINK #' . $object_ID, $post_args);
        $result = $this->link_server($post_url, $post_args, $api_info['MerchantID'], $api_info['HashKey'], $api_info['HashIV']);
        if ($result) {
            RY_Logs::log('ezpay-invoice', 'info', 'Get response #' . $object_ID, $result);
            do_action('ry_invoice_ezpay-post_get_invoice', $post_args, $result, $object_ID);
        }
    }

    public function invalid_invoice($invoice_data, $object_ID = null)
    {
        $api_info = $this->get_api_info();

        $now = new DateTime('now', new DateTimeZone('Asia/Taipei'));
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
        RY_Logs::log('ezpay-invoice', 'info', 'Invalid LINK #' . $object_ID, $post_args);
        $result = $this->link_server($post_url, $post_args, $api_info['MerchantID'], $api_info['HashKey'], $api_info['HashIV']);
        if ($result) {
            RY_Logs::log('ezpay-invoice', 'info', 'Invalid response #' . $object_ID, $result);
            do_action('ry_invoice_ezpay-post_invalid_invoice', $post_args, $result, $object_ID);
        }
    }

    public function get_info()
    {
        $general_info = RY_IFEZPAY::get_option('general', []);
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

    public function get_api_info()
    {
        $api_info = RY_IFEZPAY::get_option('apiinfo', []);
        if (!is_array($api_info)) {
            $api_info = [];
        }
        $api_info = array_merge([
            'testmode' => 'no',
            'MerchantID' => '',
            'HashKey' => '',
            'HashIV' => '',
        ], $api_info);
        $api_info['testmode'] = $api_info['testmode'] === 'yes';

        return $api_info;
    }
}
