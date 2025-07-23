<?php
/**
 * Renders the main admin dashboard page for GFFM Tools.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
?>
<div class="wrap gffm-admin-wrapper">
    <h1><?php esc_html_e('GFFM Dashboard', 'gffm-plugin'); ?></h1>
    <p><?php esc_html_e('Welcome to the Glens Falls Farmers\' Market management dashboard. Here is a snapshot of your market activity.', 'gffm-plugin'); ?></p>

    <?php
        // Ensure the class exists before instantiating
        if (class_exists('GFFM\Includes\Modules\Reports\ReportingManager')) {
            $reporting_manager = new \GFFM\Includes\Modules\Reports\ReportingManager();
            $counts = $reporting_manager->get_dashboard_kpis();
        } else {
            // Fallback if class not found
            $counts = [
                'active_vendors' => 'N/A',
                'pending_vendors' => 'N/A',
                'draft_vendors' => 'N/A',
                'expired_cois' => 'N/A',
                'expiring_soon_cois' => 'N/A',
                'weekly_specials' => 'N/A',
            ];
            echo '<div class="notice notice-error"><p>Reporting module not found.</p></div>';
        }
    ?>

    <div id="dashboard-widgets-wrap">
        <div id="dashboard-widgets" class="metabox-holder">
            <div class="postbox-container">
                <div class="dashboard-widget">
                    <h3><?php esc_html_e('Vendor Status', 'gffm-plugin'); ?></h3>
                    <ul>
                        <li><strong><?php esc_html_e('Active Vendors:', 'gffm-plugin'); ?></strong> <?php echo esc_html($counts['active_vendors']); ?></li>
                        <li><strong><?php esc_html_e('Pending Applications:', 'gffm-plugin'); ?></strong> <?php echo esc_html($counts['pending_vendors']); ?></li>
                        <li><strong><?php esc_html_e('Draft Applications:', 'gffm-plugin'); ?></strong> <?php echo esc_html($counts['draft_vendors']); ?></li>
                    </ul>
                </div>
            </div>
            <div class="postbox-container">
                 <div class="dashboard-widget">
                    <h3><?php esc_html_e('COI Compliance', 'gffm-plugin'); ?></h3>
                     <ul>
                        <li><strong><?php esc_html_e('Vendors with Expired COI:', 'gffm-plugin'); ?></strong> <span class="gffm-red-text"><?php echo esc_html($counts['expired_cois']); ?></span></li>
                        <li><strong><?php esc_html_e('COIs Expiring in 30 Days:', 'gffm-plugin'); ?></strong> <?php echo esc_html($counts['expiring_soon_cois']); ?></span></li>
                    </ul>
                </div>
            </div>
            <div class="postbox-container">
                 <div class="dashboard-widget">
                    <h3><?php esc_html_e('Market Activity', 'gffm-plugin'); ?></h3>
                     <ul>
                        <li><strong><?php esc_html_e('Vendors with Weekly Specials:', 'gffm-plugin'); ?></strong> <?php echo esc_html($counts['weekly_specials']); ?></li>
                    </ul>
                </div>
            </div>
        </div>
    </div>
</div>
