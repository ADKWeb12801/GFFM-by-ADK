<?php
/**
 * Renders the 'Weekly Special' fields for the vendor dashboard.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */

// $vendor_id is inherited from the main dashboard file
$special_desc  = get_post_meta($vendor_id, 'weekly_special_desc', true);
$special_image_id = get_post_meta($vendor_id, 'weekly_special_image', true);
?>

<div class="gffm-form-row">
    <label for="weekly_special_desc"><?php esc_html_e('Special Description', 'gffm-plugin'); ?></label>
    <p class="gffm-form-description"><?php esc_html_e('Describe your weekly special. This will be shown on the public directory and resets every Sunday.', 'gffm-plugin'); ?></p>
    <?php
    wp_editor($special_desc, 'weekly_special_desc', [
        'textarea_name' => 'weekly_special_desc',
        'media_buttons' => false,
        'textarea_rows' => 5,
    ]);
    ?>
</div>

<div class="gffm-form-row">
    <label for="weekly_special_image"><?php esc_html_e('Special Image', 'gffm-plugin'); ?></label>
    <div class="gffm-image-uploader">
        <?php if ($special_image_id) : ?>
            <div class="gffm-image-preview">
                <?php echo wp_get_attachment_image($special_image_id, 'thumbnail'); ?>
            </div>
        <?php endif; ?>
        <input type="file" name="weekly_special_image" id="weekly_special_image" accept="image/*">
    </div>
</div>
