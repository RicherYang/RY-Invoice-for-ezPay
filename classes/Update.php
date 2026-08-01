<?php

namespace RY\Invoice\Ezpay;

defined('ABSPATH') or exit;

use RY\General\V20260801\Logs;

final class Update
{
    public static function update()
    {
        $now_version = Main::get_option('version', '0.0.0');

        if (RY_IFEZPAY_VERSION === $now_version) {
            return;
        }

        if ($now_version === '0.0.0') {
            Main::update_option('version', RY_IFEZPAY_VERSION, true);
            return;
        }

        if (version_compare($now_version, '2026.7.27', '<')) {
            $old_dir = WP_CONTENT_DIR . '/ry-logs';
            if (is_dir($old_dir)) {
                $new_dir = Logs::get_log_directory();
                foreach (new \FilesystemIterator($old_dir, \FilesystemIterator::SKIP_DOTS) as $file) {
                    @rename($file->getPathname(), $new_dir . $file->getFilename());
                }
                @rmdir($old_dir);
            }

            Main::update_option('version', '2026.7.27', true);
        }

        if (version_compare($now_version, '2026.7.31', '<')) {
            $info = Main::get_option('general', []);
            if (!empty($info)) {
                update_option('RY_Invoice_general', $info, false);
            }
            Main::delete_option('general');

            add_action('init', function () {
                as_unschedule_all_actions('RY_log_action');
            });

            Main::update_option('version', '2026.7.31', true);
        }

        if (version_compare($now_version, '2026.8.1', '<')) {
            add_action('init', function () {
                if (class_exists('\RY\General\V20260801\Logs')) {
                    $file_dir = \RY\General\V20260801\Logs::get_log_directory();
                    foreach (new \FilesystemIterator($file_dir, \FilesystemIterator::SKIP_DOTS) as $file) {
                        if ($file->isFile() && $file->isReadable()) {
                            if ($file->getExtension() === 'log') {
                                $file_name = $file->getBasename('.log');
                                $parts = explode('-', $file_name);
                                if (count($parts) > 4) {
                                    $hash_suffix = array_pop($parts);
                                    $date_suffix = implode('-', array_slice($parts, -3));
                                    $handle = implode('-', array_slice($parts, 0, -3));
                                    if (wp_hash($handle) === $hash_suffix) {
                                        $file_name = sanitize_file_name(implode('-', [$handle, $date_suffix, wp_hash($handle . $date_suffix)]) . '.log');
                                        rename($file->getPathname(), $file_dir . '/' . $file_name);
                                    }
                                }
                            }
                        }
                    }

                    Main::update_option('version', '2026.8.1', true);
                }
            });
        }
    }
}
