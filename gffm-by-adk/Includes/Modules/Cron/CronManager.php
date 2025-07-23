<?php
/**
 * Manages all scheduled events (cron jobs) for the plugin.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\Modules\Cron;

use GFFM\Includes\Modules\Notifications\NotificationManager;

class CronManager {

    public function add_cron_schedules($schedules) {
        $schedules['weekly'] = [
            'interval' => 604800,
            'display'  => esc_html__('Once Weekly'),
        ];
        return $schedules;
    }

    public function run_weekly_specials_reset() {
        $args = [
            'post_type'      => 'gffm_vendor',
            'posts_per_page' => -1,
            'fields'         => 'ids',
            'meta_query'     => [
                [
                    'key'     => 'weekly_special_desc',
                    'compare' => 'EXISTS',
                ],
            ],
        ];
        $vendor_ids = get_posts($args);

        foreach ($vendor_ids as $vendor_id) {
            delete_post_meta($vendor_id, 'weekly_special_desc');
            delete_post_meta($vendor_id, 'weekly_special_image');
        }
    }

    public function run_daily_coi_check() {
        $notification_manager = new NotificationManager();
        $notification_manager->send_coi_expiration_reminders();
    }
}
