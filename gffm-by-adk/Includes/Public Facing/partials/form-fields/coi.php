<?php
/**
 * Renders the 'Insurance (COI)' fields for the vendor dashboard.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */

// $vendor_id is inherited from the main dashboard file
$coi_exp_date = get_post_meta($vendor_id, 'coi_expiration_date', true);
$coi_doc_id   = get_post_meta($vendor_id, 'coi_document', true);
?>
<div class="gffm-form-row">
    <label for="coi_expiration_date"><?php esc_html_e('COI Expiration Date', 'gffm-plugin'); ?></label>
    <p class="gffm-form-description"><?php esc_html_e('Your Certificate of Insurance must be valid for the entire market season.', 'gffm-plugin'); ?></p>
    <input type="date" id="coi_expiration_date" name="coi_expiration_date" value="<?php echo esc_attr($coi_exp_date); ?>">
</div>

<div class="gffm-form-row">
    <label for="coi_document"><?php esc_html_e('Upload COI Document (PDF Only)', 'gffm-plugin'); ?></label>
    <div class="gffm-file-uploader">
        <?php if ($coi_doc_id) : ?>
            <div class="gffm-file-preview">
                <a href="<?php echo esc_url(wp_get_attachment_url($coi_doc_id)); ?>" target="_blank">
                    <?php esc_html_e('View Current COI', 'gffm-plugin'); ?>
                </a>
            </div>
        <?php endif; ?>
        <input type="file" name="coi_document" id="coi_document" accept=".pdf">
    </div>
</div>
