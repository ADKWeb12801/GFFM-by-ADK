<?php
/**
 * Fired during plugin deactivation
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */

namespace GFFM\Includes\Setup;

class Deactivator {

    public static function deactivate() {
        // Unschedule cron jobs
        wp_clear_scheduled_hook('gffm_weekly_reset_specials');
        wp_clear_scheduled_hook('gffm_daily_coi_check');
        
        // Note: We do not remove the custom role or CPT data on deactivation
        // to prevent data loss. This should be handled by an uninstaller if desired.

        // Flush rewrite rules
        flush_rewrite_rules();
    }
}
