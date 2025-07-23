<?php
/**
 * Renders the public vendor directory and filters.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */

$settings = get_option('gffm_settings');
$product_categories = isset($settings['product_categories']) ? explode("\n", $settings['product_categories']) : [];
$vendor_types = get_terms(['taxonomy' => 'gffm_vendor_type', 'hide_empty' => true]);

?>
<div class="gffm-container gffm-directory-wrapper">
    <h2><?php esc_html_e('Vendor Directory', 'gffm-plugin'); ?></h2>

    <form id="gffm-directory-filters" class="gffm-directory-filters">
        <div>
            <label for="gffm-filter-product"><?php esc_html_e('Product Type', 'gffm-plugin'); ?></label>
            <select id="gffm-filter-product" name="product_type">
                <option value=""><?php esc_html_e('All Products', 'gffm-plugin'); ?></option>
                <?php foreach ($product_categories as $category) : $category = trim($category); ?>
                    <option value="<?php echo esc_attr($category); ?>"><?php echo esc_html($category); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label for="gffm-filter-vendor-type"><?php esc_html_e('Vendor Type', 'gffm-plugin'); ?></label>
            <select id="gffm-filter-vendor-type" name="vendor_type">
                <option value=""><?php esc_html_e('All Types', 'gffm-plugin'); ?></option>
                <?php foreach ($vendor_types as $type) : ?>
                    <option value="<?php echo esc_attr($type->slug); ?>"><?php echo esc_html($type->name); ?></option>
                <?php endforeach; ?>
            </select>
        </div>
        <div>
            <label class="gffm-checkbox-label">
                <input type="checkbox" id="gffm-filter-cdphp" name="cdphp" value="yes">
                <?php esc_html_e('CDPHP Participant', 'gffm-plugin'); ?>
            </label>
        </div>
    </form>

    <div id="gffm-directory-grid-loader" class="gffm-loader" style="display: none;"></div>
    <div id="gffm-directory-grid">
        <?php
            $initial_args = [
                'post_type' => 'gffm_vendor',
                'post_status' => 'publish',
                'posts_per_page' => -1,
            ];
            $initial_query = new WP_Query($initial_args);
            if ($initial_query->have_posts()) {
                while ($initial_query->have_posts()) {
                    $initial_query->the_post();
                    include 'vendor-card.php';
                }
            } else {
                echo '<p class="gffm-no-results">' . esc_html__('No vendors found.', 'gffm-plugin') . '</p>';
            }
            wp_reset_postdata();
        ?>
    </div>
</div>
