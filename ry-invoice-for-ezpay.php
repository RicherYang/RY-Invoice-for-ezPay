<?php

/**
 * Plugin Name: RY Invoice for ezPay
 * Plugin URI: https://ry-plugin.com/ry-invoice-for-ezpay
 * Description: ezPay E-invoice, support WooCommerce.
 * Version: 2026.8.12
 * Requires at least: 6.8
 * Requires PHP: 8.2
 * Author: Richer Yang
 * Author URI: https://richer.tw/
 * License: GPLv3
 * Update URI: https://ry-plugin.com/ry-invoice-for-ezpay
 *
 * Text Domain: ry-invoice-for-ezpay
 * Domain Path: /languages
 */

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\Main;

define('RY_IFEZPAY_VERSION', '2026.8.12');
define('RY_IFEZPAY_PLUGIN_URL', plugin_dir_url(__FILE__));
define('RY_IFEZPAY_PLUGIN_DIR', plugin_dir_path(__FILE__));
define('RY_IFEZPAY_PLUGIN_BASENAME', plugin_basename(__FILE__));
define('RY_IFEZPAY_PLUGIN_LANGUAGES_DIR', plugin_dir_path(__FILE__) . '/languages');

require_once RY_IFEZPAY_PLUGIN_DIR . 'includes/vendor/autoload.php';

register_activation_hook(__FILE__, [Main::class, 'plugin_activation']);
register_deactivation_hook(__FILE__, [Main::class, 'plugin_deactivation']);

Main::instance();
