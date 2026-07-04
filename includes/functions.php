<?php

defined('ABSPATH') or exit;

function ry_ifezpay_invoice_type_to_name($invoice_type)
{
    static $type_name = [];
    if (empty($type_name)) {
        $type_name = [
            'personal' => _x('personal', 'invoice type', 'ry-invoice-for-ezpay'),
            'company' => _x('company', 'invoice type', 'ry-invoice-for-ezpay'),
            'donate' => _x('donate', 'invoice type', 'ry-invoice-for-ezpay'),
        ];
    }

    return $type_name[$invoice_type] ?? $invoice_type;
}

function ry_ifezpay_carruer_type_to_name($carruer_type)
{
    static $type_name = [];
    if (empty($type_name)) {
        $type_name = [
            'amego_host' => _x('amego_host', 'carruer type', 'ry-invoice-for-ezpay'),
            'ezpay_host' => _x('ezpay_host', 'carruer type', 'ry-invoice-for-ezpay'),
            'ecpay_host' => _x('ecpay_host', 'carruer type', 'ry-invoice-for-ezpay'),
            'smilepay_host' => _x('smilepay_host', 'carruer type', 'ry-invoice-for-ezpay'),
            'MOICA' => _x('MOICA', 'carruer type', 'ry-invoice-for-ezpay'),
            'phone_barcode' => _x('phone_barcode', 'carruer type', 'ry-invoice-for-ezpay'),
        ];
    }

    return $type_name[$carruer_type] ?? $carruer_type;
}
