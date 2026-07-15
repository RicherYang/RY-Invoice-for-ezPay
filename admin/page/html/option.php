<?php defined('ABSPATH') or exit; ?>

<?php
use RY\General\Logs;

?>

<?php $api_info = RY_IFEZPAY_Invoice::instance()->get_api_info(); ?>

<h2 class="title"><?php esc_html_e('API credentials', 'ry-invoice-for-ezpay'); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e('Debug log', 'ry-invoice-for-ezpay'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php esc_html_e('Debug log', 'ry-invoice-for-ezpay'); ?></span></legend>
                <label for="log"><input name="log" type="checkbox" id="log" value="yes" <?php checked(RY_IFEZPAY::get_option('log', 'no') === 'yes'); ?>>
                    <?php esc_html_e('Enable log', 'ry-invoice-for-ezpay'); ?></label>
                <p class="description">
                    <?php echo wp_kses(sprintf(
                        /* translators: %s: Path of log file */
                        __('Log file %s', 'ry-invoice-for-ezpay'),
                        '<code>' . esc_html(Logs::get_log_path('ezpay-invoice')) . '</code>'
                    ), ['code' => []]); ?>
                    <br><?php echo wp_kses(
                        __('<strong>Note:</strong> The log may contain personal information.', 'ry-invoice-for-ezpay'),
                        ['strong' => []]
                    ); ?>
                </p>
            </fieldset>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Sandbox', 'ry-invoice-for-ezpay'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php esc_html_e('Sandbox', 'ry-invoice-for-ezpay'); ?></span></legend>
                <label for="testmode"><input name="testmode" type="checkbox" id="testmode" value="yes" <?php checked($api_info['testmode']); ?>>
                    <?php esc_html_e('Enable sandbox', 'ry-invoice-for-ezpay'); ?></label>
                <p class="description"><?php esc_html_e('Note: For developers use ONLY.', 'ry-invoice-for-ezpay'); ?></p>
            </fieldset>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="MerchantID"><?php esc_html_e('MerchantID', 'ry-invoice-for-ezpay'); ?></label></th>
        <td><input name="MerchantID" type="text" id="MerchantID" value="<?php echo esc_attr($api_info['MerchantID']); ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th scope="row"><label for="HashKey"><?php esc_html_e('HashKey', 'ry-invoice-for-ezpay'); ?></label></th>
        <td><input name="HashKey" type="text" id="HashKey" value="<?php echo esc_attr($api_info['HashKey']); ?>" class="regular-text"></td>
    </tr>
    <tr>
        <th scope="row"><label for="HashIV"><?php esc_html_e('HashIV', 'ry-invoice-for-ezpay'); ?></label></th>
        <td><input name="HashIV" type="text" id="HashIV" value="<?php echo esc_attr($api_info['HashIV']); ?>" class="regular-text"></td>
    </tr>
</table>
