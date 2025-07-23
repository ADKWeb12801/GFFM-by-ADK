<?php
/**
 * Fired during plugin activation
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */

namespace GFFM\Includes\Setup;

use GFFM\Includes\Modules\Vendors\VendorCPT;

class Installer {

    public static function activate() {
        // Ensure CPT and taxonomies are registered before flushing rules
        $vendor_cpt = new VendorCPT();
        $vendor_cpt->register();
        
        // Add the 'vendor' role
        add_role(
            'gffm_vendor',
            __('Vendor', 'gffm-plugin'),
            [
                'read'         => true,
                'upload_files' => true,
            ]
        );

        // Set up default options
        if (false === get_option('gffm_settings')) {
            $default_settings = [
                'admin_fee_percentage' => '5',
                'product_categories'   => "Fruits\nVegetables\nMeat\nEggs\nDairy/Cheese\nBaked Goods\nPrepared Foods\nCrafts",
            ];
            update_option('gffm_settings', $default_settings);
        }
        
        // Schedule cron jobs
        if (!wp_next_scheduled('gffm_weekly_reset_specials')) {
            wp_schedule_event(strtotime('next sunday 00:00:00'), 'weekly', 'gffm_weekly_reset_specials');
        }
        if (!wp_next_scheduled('gffm_daily_coi_check')) {
            wp_schedule_event(time(), 'daily', 'gffm_daily_coi_check');
        }

        // Flush rewrite rules to ensure CPT URLs work correctly
        flush_rewrite_rules();
    }
}
