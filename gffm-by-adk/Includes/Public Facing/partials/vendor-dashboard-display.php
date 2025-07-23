<?php
/**
 * Renders the vendor dashboard form.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */

// The $vendor_query object is passed from the shortcode class
if (!isset($vendor_query) || !$vendor_query->have_posts()) {
    return;
}

$vendor_query->the_post();
$vendor_id = get_the_ID();
$settings = get_option('gffm_settings');
$product_categories = isset($settings['product_categories']) ? explode("\n", $settings['product_categories']) : [];

?>
<div class="gffm-container gffm-dashboard-wrapper">
    <div id="gffm-ajax-response" class="gffm-notice" style="display: none;"></div>

    <form id="gffm-vendor-form" method="POST" enctype="multipart/form-data">
        <input type="hidden" name="action" value="gffm_update_vendor_profile">
        <input type="hidden" name="vendor_id" value="<?php echo esc_attr($vendor_id); ?>">
        <?php wp_nonce_field('gffm_update_nonce', 'security'); ?>

        <h2><?php esc_html_e('Vendor Dashboard', 'gffm-plugin'); ?></h2>
        <p><?php esc_html_e('Update your public profile information below. Click on a section to expand it.', 'gffm-plugin'); ?></p>

        <!-- Accordion Sections -->
        <div class="gffm-accordion">
            <div class="gffm-accordion-item">
                <h3 class="gffm-accordion-header"><?php esc_html_e('Weekly Special', 'gffm-plugin'); ?></h3>
                <div class="gffm-accordion-content">
                    <?php include 'form-fields/special.php'; ?>
                </div>
            </div>
            <div class="gffm-accordion-item">
                <h3 class="gffm-accordion-header"><?php esc_html_e('Business Information', 'gffm-plugin'); ?></h3>
                <div class="gffm-accordion-content">
                    <?php include 'form-fields/business-info.php'; ?>
                </div>
            </div>
            <div class="gffm-accordion-item">
                <h3 class="gffm-accordion-header"><?php esc_html_e('Insurance (COI)', 'gffm-plugin'); ?></h3>
                <div class="gffm-accordion-content">
                    <?php include 'form-fields/coi.php'; ?>
                </div>
            </div>
        </div>

        <button type="submit" class="gffm-button-primary"><?php esc_html_e('Save Changes', 'gffm-plugin'); ?></button>
    </form>
</div>
<?php
wp_reset_postdata();
