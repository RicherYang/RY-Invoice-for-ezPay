<?php

namespace RY\Invoice\Ezpay\Admin\ListTable;

defined('ABSPATH') or exit;

use RY\Invoice\Ezpay\LinkProvider;
use RY\Invoice\Ezpay\Utils;
use RY\Invoice\V20260805\ListTable\Track as BaseTrack;

final class Track extends BaseTrack
{
    public function prepare_items()
    {
        $time = new \DateTime('now', new \DateTimeZone('Asia/Taipei'));
        $get_list[] = [$time->format('Y'), ceil($time->format('n') / 2)];
        $time->add(new \DateInterval('P2M'));
        $get_list[] = [$time->format('Y'), ceil($time->format('n') / 2)];

        foreach ($get_list as $get) {
            $result = LinkProvider::instance()->track_status($get[0], $get[1]);
            if ($result->Status == 'SUCCESS') {
                foreach ($result->Result as $status) {
                    $this->items[] = [
                        'year' => $status->Year,
                        'term' => $status->Term,
                        'code' => $status->AphabeticLetter,
                        'start_no' => $status->StartNumber,
                        'end_no' => $status->EndNumber,
                        'now_no' => $status->UsedNumber,
                        'status' => $status->Flag,
                    ];
                }
            }
        }
    }

    public function get_columns()
    {
        $columns = parent::get_columns();
        unset($columns['trackcode']);
        return $columns;
    }

    protected function column_term($item)
    {
        return Utils::track_term_to_name($item['term']);
    }

    protected function column_status($item)
    {
        $info = '';
        if ($item['status'] == '1') {
            $info = '<span class="dashicons dashicons-saved"></span>';
        }
        return $info . Utils::track_status_to_name($item['status']);
    }
}
