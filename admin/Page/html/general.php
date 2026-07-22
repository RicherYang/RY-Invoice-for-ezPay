<?php defined('ABSPATH') or exit; ?>

<?php
use RY\Invoice\Ezpay\LinkProvider;

?>

<?php $general_info = LinkProvider::instance()->get_info(); ?>

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
</table>
