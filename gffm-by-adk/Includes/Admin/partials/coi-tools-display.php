<?php
/**
 * Renders the COI Tools admin page.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
?>
<div class="wrap gffm-admin-wrapper">
    <h1><?php esc_html_e('COI Management Tools', 'gffm-plugin'); ?></h1>
    <p><?php esc_html_e('Review Certificate of Insurance (COI) compliance status for all vendors.', 'gffm-plugin'); ?></p>
    
    <div class="postbox">
        <h2 class="hndle"><?php esc_html_e('COI Compliance Report', 'gffm-plugin'); ?></h2>
        <div class="inside">
            <p><?php esc_html_e('A full, filterable list of vendors with their COI expiration dates will be available here in a future update. For now, please refer to the "COI Expiration" column on the main Vendors list page.', 'gffm-plugin'); ?></p>
            <a href="<?php echo esc_url(admin_url('edit.php?post_type=gffm_vendor')); ?>" class="button button-primary"><?php esc_html_e('View All Vendors', 'gffm-plugin'); ?></a>
        </div>
    </div>
</div>
