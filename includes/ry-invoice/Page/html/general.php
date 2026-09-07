<?php defined('ABSPATH') or exit; ?>

<?php
use RY\Invoice\V20260906\AbstractLinkProvider;

?>

<?php $general_info = AbstractLinkProvider::get_info(); ?>

<h2 class="title"><?php esc_html_e('Buyer options', 'ry-invoice-for-ezpay'); ?></h2>
<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><?php esc_html_e('Buyer name', 'ry-invoice-for-ezpay'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php esc_html_e('Use real name', 'ry-invoice-for-ezpay'); ?></span></legend>
                <label for="buyer_name"><input name="buyer_name" type="checkbox" id="buyer_name" value="yes" <?php checked($general_info['buyer']['name']); ?>>
                    <?php esc_html_e('Use real name', 'ry-invoice-for-ezpay'); ?></label>
                <p class="description">
                    <?php echo esc_html(sprintf(__('If disabled, fallback to "%s" as the name.', 'ry-invoice-for-ezpay'), __('Customer', 'ry-invoice-for-ezpay'))); ?>
                    <?php esc_html_e('Only for personal invoice.', 'ry-invoice-for-ezpay'); ?>
                </p>
            </fieldset>
        </td>
    </tr>
    <tr>
        <th scope="row"><?php esc_html_e('Buyer address', 'ry-invoice-for-ezpay'); ?></th>
        <td>
            <fieldset>
                <legend class="screen-reader-text"><span><?php esc_html_e('Use real address', 'ry-invoice-for-ezpay'); ?></span></legend>
                <label for="buyer_address"><input name="buyer_address" type="checkbox" id="buyer_address" value="yes" <?php checked($general_info['buyer']['address']); ?>>
                    <?php esc_html_e('Use real address', 'ry-invoice-for-ezpay'); ?></label>
                <p class="description">
                    <?php echo esc_html(sprintf(__('If disabled, fallback to "%s" as the address.', 'ry-invoice-for-ezpay'), __('Taiwan', 'ry-invoice-for-ezpay'))); ?>
                </p>
            </fieldset>
        </td>
    </tr>
</table>

<h2 class="title"><?php esc_html_e('General options', 'ry-invoice-for-ezpay'); ?></h2>

<table class="form-table" role="presentation">
    <tr>
        <th scope="row"><label for="count_precision"><?php esc_html_e('Item count precision', 'ry-invoice-for-ezpay'); ?></label></th>
        <td>
            <select name="count_precision" id="count_precision">
                <option value="1" <?php selected($general_info['count_precision'], 1); ?>>1</option>
                <option value="2" <?php selected($general_info['count_precision'], 2); ?>>2</option>
                <option value="3" <?php selected($general_info['count_precision'], 3); ?>>3</option>
                <option value="4" <?php selected($general_info['count_precision'], 4); ?>>4</option>
                <option value="5" <?php selected($general_info['count_precision'], 5); ?>>5</option>
                <option value="6" <?php selected($general_info['count_precision'], 6); ?>>6</option>
                <option value="7" <?php selected($general_info['count_precision'], 7); ?>>7</option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="amount_precision"><?php esc_html_e('Item amount precision', 'ry-invoice-for-ezpay'); ?></label></th>
        <td>
            <select name="amount_precision" id="amount_precision">
                <option value="1" <?php selected($general_info['amount_precision'], 1); ?>>1</option>
                <option value="2" <?php selected($general_info['amount_precision'], 2); ?>>2</option>
                <option value="3" <?php selected($general_info['amount_precision'], 3); ?>>3</option>
                <option value="4" <?php selected($general_info['amount_precision'], 4); ?>>4</option>
                <option value="5" <?php selected($general_info['amount_precision'], 5); ?>>5</option>
                <option value="6" <?php selected($general_info['amount_precision'], 6); ?>>6</option>
                <option value="7" <?php selected($general_info['amount_precision'], 7); ?>>7</option>
            </select>
        </td>
    </tr>
    <tr>
        <th scope="row"><label for="donate"><?php esc_html_e('Default donate number', 'ry-invoice-for-ezpay'); ?></label></th>
        <td>
            <input name="donate" type="text" id="donate" value="<?php echo esc_attr(implode(', ', $general_info['donate'])); ?>" class="large-text">
            <p class="description">
                <?php echo wp_kses(
                    sprintf(
                        /* translators: %s: link to full list of donate numbers */
                        __('Separate donate numbers with commas. View <a href="%s" target="_blank">full list</a>.', 'ry-invoice-for-ezpay'),
                        'https://www.einvoice.nat.gov.tw/portal/btc/btc603w/search'
                    ),
                    ['a' => ['href' => [], 'target' => []]]
                ); ?>
            </p>
        </td>
    </tr>
</table>
