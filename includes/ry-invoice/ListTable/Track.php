<?php

namespace RY\Invoice\V20260805\ListTable;

defined('ABSPATH') or exit;

include_once ABSPATH . 'wp-admin/includes/class-wp-list-table.php';

class Track extends \WP_List_Table
{
    public function get_columns()
    {
        return [
            'year' => _x('Year', 'Track table header', 'ry-invoice-for-ezpay'),
            'term' => _x('Term', 'Track table header', 'ry-invoice-for-ezpay'),
            'code' => _x('Code', 'Track table header', 'ry-invoice-for-ezpay'),
            'start_no' => _x('Start number', 'Track table header', 'ry-invoice-for-ezpay'),
            'end_no' => _x('End number', 'Track table header', 'ry-invoice-for-ezpay'),
            'now_no' => _x('Current number', 'Track table header', 'ry-invoice-for-ezpay'),
            'unused' => _x('Unused quantity', 'Track table header', 'ry-invoice-for-ezpay'),
            'trackcode' => _x('Track code', 'Track table header', 'ry-invoice-for-ezpay'),
            'status' => _x('Status', 'Track table header', 'ry-invoice-for-ezpay'),
        ];
    }

    protected function column_unused($item)
    {
        if (empty($item['now_no'])) {
            return '';
        }

        $total = (int) $item['end_no'] - (int) $item['start_no'] + 1;
        $used = (int) $item['now_no'] - (int) $item['start_no'] + 1;
        $unused = $total - $used;
        return sprintf('%d ( %.1f%% )', $unused, $unused / $total * 100);
    }

    protected function column_default($item, $column_name)
    {
        return $item[$column_name] ?? '';
    }

    public function display()
    {
        echo '<table class="wp-list-table ' . implode(' ', ['widefat', 'striped', 'table-view-list']) . '">';
        $this->print_table_description();
        echo '<thead><tr>';
        $this->print_column_headers();
        echo '</tr></thead>';
        echo '<tbody id="the-list">';
        $this->display_rows_or_placeholder();
        echo '</tbody></table>';
    }
}
