<?php

namespace RY\Invoice\Ezpay\WooCommerce;

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\Utils;

final class Fields
{
    private static ?self $_instance = null;

    public static function instance(): Fields
    {
        if (null === self::$_instance) {
            self::$_instance = new self();
            self::$_instance->do_init();
        }

        return self::$_instance;
    }

    protected function do_init(): void
    {
        add_filter('woocommerce_checkout_fields', [$this, 'add_invoice_info'], 9999);

        add_action('woocommerce_after_checkout_billing_form', [$this, 'show_invoice_form']);
        add_action('woocommerce_after_checkout_validation', [$this, 'invoice_checkout_validation'], 10, 2);
        add_action('woocommerce_checkout_create_order', [$this, 'save_order_invoice'], 10, 2);

        add_action('woocommerce_order_details_after_customer_details', [$this, 'show_invoice_info']);
    }

    public function add_invoice_info($fields)
    {
        $fields['invoice'] = [
            'invoice_type' => [
                'type' => 'select',
                'label' => __('Invoice type', 'ry-invoice-for-ezpay'),
                'options' => [
                    'personal' => Utils::invoice_type_to_name('personal'),
                    'company' => Utils::invoice_type_to_name('company'),
                    'donate' => Utils::invoice_type_to_name('donate'),
                ],
                'default' => 'personal',
                'required' => true,
                'priority' => 10,
            ],
            'invoice_carruer_type' => [
                'type' => 'select',
                'label' => __('Carruer type', 'ry-invoice-for-ezpay'),
                'options' => [
                    'ezpay_host' => Utils::carruer_type_to_name('ezpay_host') . __(' (send paper when win)', 'ry-invoice-for-ezpay'),
                    'MOICA' => Utils::carruer_type_to_name('MOICA'),
                    'phone_barcode' => Utils::carruer_type_to_name('phone_barcode'),
                ],
                'default' => 'ezpay_host',
                'required' => true,
                'priority' => 10,
            ],
            'invoice_carruer_no' => [
                'label' => __('Carruer number', 'ry-invoice-for-ezpay'),
                'required' => true,
                'priority' => 20,
            ],
            'invoice_no' => [
                'label' => __('Tax ID number', 'ry-invoice-for-ezpay'),
                'required' => true,
                'priority' => 30,
            ],
            'invoice_donate_no' => [
                'label' => __('Donate number', 'ry-invoice-for-ezpay'),
                'required' => true,
                'priority' => 40,
            ],
        ];

        if (\RY_IFEZPAY::get_option('move_billing_company', 'no') === 'yes') {
            unset($fields['billing']['billing_company']);
            $fields['invoice']['invoice_company_name'] = [
                'label' => __('Company name', 'ry-invoice-for-ezpay'),
                'required' => true,
                'priority' => 30,
            ];
        }

        // default donate no - 財團法人台灣兒童暨家庭扶助基金會 ( CCF )
        $donate_no = apply_filters('ry_invoice-default_donate_no', ['7261651', '5900', '8585', '7885', '035', '378585', '2085', '024', '326139', '5875', '5520', '68660', '2100', '323804', '078585', '5584', '70885', '8300', '5678585', '2812085', '6323200', '6361712', '6361716', '8700', '7123', '1785', '3100', '6782', '461234', '818585', '33085', '176176'], '');
        if (is_array($donate_no)) {
            $donate_no = $donate_no[intval(time() / 86400) % count($donate_no)];
        }
        $fields['invoice']['invoice_donate_no']['default'] = $donate_no;

        if (did_action('woocommerce_checkout_process')) {
            $invoice_type = sanitize_locale_name($_POST['invoice_type'] ?? '');
            $invoice_carruer_type = sanitize_locale_name($_POST['invoice_carruer_type'] ?? '');

            switch ($invoice_type) {
                case 'personal':
                    switch ($invoice_carruer_type) {
                        case 'ezpay_host':
                            $fields['invoice']['invoice_carruer_no']['required'] = false;
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                        case 'MOICA':
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                        case 'phone_barcode':
                            $fields['invoice']['invoice_no']['required'] = false;
                            $fields['invoice']['invoice_company_name']['required'] = false;
                            $fields['invoice']['invoice_donate_no']['required'] = false;
                            break;
                    }
                    break;
                case 'company':
                    $fields['invoice']['invoice_carruer_no']['required'] = false;
                    $fields['invoice']['invoice_donate_no']['required'] = false;
                    break;
                case 'donate':
                    $fields['invoice']['invoice_carruer_no']['required'] = false;
                    $fields['invoice']['invoice_no']['required'] = false;
                    $fields['invoice']['invoice_company_name']['required'] = false;
                    break;
            }
        }
        return $fields;
    }

    public function show_invoice_form($checkout)
    {
        $asset_info = include RY_IFEZPAY_PLUGIN_DIR . 'assets/wc-checkout.asset.php';
        wp_enqueue_script('ry-invoice-wc-checkout', RY_IFEZPAY_PLUGIN_URL . 'assets/wc-checkout.js', $asset_info['dependencies'], $asset_info['version'], true);

        $args = [
            'checkout' => $checkout,
        ];
        wc_get_template('checkout/form-invoice.php', $args, '', RY_IFEZPAY_PLUGIN_DIR . 'woocommerce/templates/');
    }

    public function invoice_checkout_validation($data, $errors)
    {
        switch ($data['invoice_type']) {
            case 'personal': // 個人
                switch ($data['invoice_carruer_type']) {
                    case 'MOICA':// 自然人憑證
                        if (!empty($data['invoice_carruer_no'])) {
                            if (!preg_match('/^[A-Z]{2}\d{14}$/', $data['invoice_carruer_no'])) {
                                $errors->add('validation', __('Invalid carruer number', 'ry-invoice-for-ezpay'));
                            }
                        }
                        break;
                    case 'phone_barcode':// 手機載具
                        if (!empty($data['invoice_carruer_no'])) {
                            if (!preg_match('/^\/{1}[0-9A-Z+-.]{7}$/', $data['invoice_carruer_no'])) {
                                $errors->add('validation', __('Invalid carruer number', 'ry-invoice-for-ezpay'));
                            }
                        }
                        break;
                }
                break;
            case 'donate': // 愛心碼
                if (!empty($data['invoice_donate_no'])) {
                    if (!preg_match('/^[0-9]{3,7}$/', $data['invoice_donate_no'])) {
                        $errors->add('validation', __('Invalid donate number', 'ry-invoice-for-ezpay'));
                    }
                }
                break;
            case 'company':// 公司
                if (!empty($data['invoice_no'])) {
                    $valid = preg_match('/^[0-9]{8}$/', $data['invoice_no']);
                    if ($valid) {
                        $sum = 0;
                        $weights = [1, 2, 1, 2, 1, 2, 4, 1];
                        for ($i = 0; $i < 8; ++$i) {
                            $product = ((int) $data['invoice_no'][$i]) * $weights[$i];
                            $sum += intdiv($product, 10) + ($product % 10);
                        }
                        if ($sum % 5 === 0) {
                            $valid = true;
                        } elseif ($data['invoice_no'][6] === '7' && ($sum - 9) % 5 === 0) {
                            $valid = true;
                        } else {
                            $valid = false;
                        }
                    }
                    if (!$valid) {
                        $errors->add('validation', __('Invalid tax ID number', 'ry-invoice-for-ezpay'));
                    }
                }
                break;
        }
    }

    public function save_order_invoice($order, $data)
    {
        $order->update_meta_data('_invoice_type', $data['invoice_type'] ?? 'personal');
        $order->update_meta_data('_invoice_carruer_type', $data['invoice_carruer_type'] ?? 'ezpay_host');
        foreach (['invoice_carruer_no', 'invoice_no', 'invoice_donate_no'] as $key) {
            if (isset($data[$key]) && !empty($data[$key])) {
                $order->update_meta_data('_' . $key, $data[$key]);
            } else {
                $order->delete_meta_data('_' . $key);
            }
        }
        if (\RY_IFEZPAY::get_option('move_billing_company', 'no') === 'yes') {
            $order->set_billing_company($data['invoice_company_name'] ?? '');
        }
    }

    public function show_invoice_info($order)
    {
        $invoice_number = $order->get_meta('_invoice_number');
        $invoice_type = $order->get_meta('_invoice_type');
        $carruer_type = $order->get_meta('_invoice_carruer_type');

        if (!$invoice_type) {
            return;
        }

        $invoice_info = [];
        if ($invoice_number) {
            if (preg_match('/^[A-Z]{2}[0-9]{8}$/', $invoice_number)) {
                $invoice_info[] = [
                    'key' => 'invoice-number',
                    'name' => __('Invoice number', 'ry-invoice-for-ezpay'),
                    'value' => $invoice_number,
                ];
                $invoice_info[] = [
                    'key' => 'invoice-random-number',
                    'name' => __('Invoice random number', 'ry-invoice-for-ezpay'),
                    'value' => $order->get_meta('_invoice_random_number'),
                ];
                $invoice_info[] = [
                    'key' => 'invoice-date',
                    'name' => __('Invoice date', 'ry-invoice-for-ezpay'),
                    'value' => $order->get_meta('_invoice_date'),
                ];
            } else {
                switch ($invoice_number) {
                    case 'wait':
                        break;
                    case 'zero':
                        $invoice_info[] = [
                            'key' => 'zero-info',
                            'name' => __('Zero total fee without invoice', 'ry-invoice-for-ezpay'),
                            'value' => '',
                        ];
                        break;
                    case 'negative':
                        $invoice_info[] = [
                            'key' => 'negative-info',
                            'name' => __('Negative total fee can\'t invoice', 'ry-invoice-for-ezpay'),
                            'value' => '',
                        ];
                        break;
                }
            }
        }

        $invoice_info[] = [
            'key' => 'invoice-type',
            'name' => __('Invoice type', 'ry-invoice-for-ezpay'),
            'value' => Utils::invoice_type_to_name($invoice_type),
        ];

        switch ($invoice_type) {
            case 'personal':
                $key = count($invoice_info) - 1;
                $invoice_info[$key]['value'] .= ' (' . Utils::carruer_type_to_name($carruer_type) . ')';
                if (in_array($carruer_type, ['MOICA', 'phone_barcode'])) {
                    $invoice_info[] = [
                        'key' => 'carruer-number',
                        'name' => __('Carruer number', 'ry-invoice-for-ezpay'),
                        'value' => $order->get_meta('_invoice_carruer_no'),
                    ];
                }
                break;
            case 'company':
                $invoice_info[] = [
                    'key' => 'tax-id-number',
                    'name' => __('Tax ID number', 'ry-invoice-for-ezpay'),
                    'value' => $order->get_meta('_invoice_no'),
                ];
                break;
            case 'donate':
                $invoice_info[] = [
                    'key' => 'donate-number',
                    'name' => __('Donate number', 'ry-invoice-for-ezpay'),
                    'value' => $order->get_meta('_invoice_donate_no'),
                ];
                break;
        }

        $args = [
            'order' => $order,
            'invoice_info' => apply_filters('ry_invoice-wc_order_invoice_info', $invoice_info, $order),
        ];
        wc_get_template('order/order-invoice-info.php', $args, '', RY_IFEZPAY_PLUGIN_DIR . 'woocommerce/templates/');
    }
}
