<?php
/**
 * Renders the 'Business Information' fields for the vendor dashboard.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */

// $vendor_id and $product_categories are inherited from the main dashboard file
$business_name = get_post_meta($vendor_id, 'business_name', true);
$public_email  = get_post_meta($vendor_id, 'public_email', true);
$website_url   = get_post_meta($vendor_id, 'website_url', true);
$facebook_url  = get_post_meta($vendor_id, 'facebook_url', true);
$instagram_url = get_post_meta($vendor_id, 'instagram_url', true);
$business_logo_id = get_post_meta($vendor_id, 'business_logo', true);
$business_desc = get_post_meta($vendor_id, 'business_description', true);
$products_offered = get_post_meta($vendor_id, 'products_offered', true) ?: [];
$cdphp_participant = get_post_meta($vendor_id, 'cdphp_participant', true);

?>
<div class="gffm-form-row">
    <label for="business_name"><?php esc_html_e('Business Name', 'gffm-plugin'); ?></label>
    <input type="text" id="business_name" name="business_name" value="<?php echo esc_attr($business_name); ?>">
</div>

<div class="gffm-form-row">
    <label for="business_description"><?php esc_html_e('Public Description', 'gffm-plugin'); ?></label>
    <textarea id="business_description" name="business_description" rows="5"><?php echo esc_textarea($business_desc); ?></textarea>
</div>

<div class="gffm-form-row">
    <label for="business_logo"><?php esc_html_e('Business Logo', 'gffm-plugin'); ?></label>
    <div class="gffm-image-uploader">
        <?php if ($business_logo_id) : ?>
            <div class="gffm-image-preview">
                <?php echo wp_get_attachment_image($business_logo_id, 'thumbnail'); ?>
            </div>
        <?php endif; ?>
        <input type="file" name="business_logo" id="business_logo" accept="image/*">
    </div>
</div>

<div class="gffm-form-row gffm-form-grid">
    <div>
        <label for="public_email"><?php esc_html_e('Public Email', 'gffm-plugin'); ?></label>
        <input type="email" id="public_email" name="public_email" value="<?php echo esc_attr($public_email); ?>">
    </div>
    <div>
        <label for="website_url"><?php esc_html_e('Website URL', 'gffm-plugin'); ?></label>
        <input type="url" id="website_url" name="website_url" value="<?php echo esc_attr($website_url); ?>">
    </div>
    <div>
        <label for="facebook_url"><?php esc_html_e('Facebook URL', 'gffm-plugin'); ?></label>
        <input type="url" id="facebook_url" name="facebook_url" value="<?php echo esc_attr($facebook_url); ?>">
    </div>
    <div>
        <label for="instagram_url"><?php esc_html_e('Instagram URL', 'gffm-plugin'); ?></label>
        <input type="url" id="instagram_url" name="instagram_url" value="<?php echo esc_attr($instagram_url); ?>">
    </div>
</div>

<div class="gffm-form-row">
    <fieldset>
        <legend><?php esc_html_e('Products Offered', 'gffm-plugin'); ?></legend>
        <div class="gffm-checkbox-group">
            <?php foreach ($product_categories as $category) : $category = trim($category); ?>
                <label>
                    <input type="checkbox" name="products_offered[]" value="<?php echo esc_attr($category); ?>" <?php checked(in_array($category, $products_offered)); ?>>
                    <?php echo esc_html($category); ?>
                </label>
            <?php endforeach; ?>
        </div>
    </fieldset>
</div>

<div class="gffm-form-row">
    <fieldset>
        <legend><?php esc_html_e('CDPHP Participant?', 'gffm-plugin'); ?></legend>
        <div class="gffm-radio-group">
            <label>
                <input type="radio" name="cdphp_participant" value="Yes" <?php checked($cdphp_participant, 'Yes'); ?>>
                <?php esc_html_e('Yes', 'gffm-plugin'); ?>
            </label>
            <label>
                <input type="radio" name="cdphp_participant" value="No" <?php checked($cdphp_participant, 'No'); ?>>
                 <?php esc_html_e('No', 'gffm-plugin'); ?>
            </label>
        </div>
    </fieldset>
</div>
