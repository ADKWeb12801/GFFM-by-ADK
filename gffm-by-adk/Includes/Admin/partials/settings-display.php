<?php
/**
 * Renders the Settings admin page.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
?>
<div class="wrap gffm-admin-wrapper">
    <h1><?php esc_html_e('GFFM Settings', 'gffm-plugin'); ?></h1>
    
    <form method="post" action="options.php">
        <?php
            settings_fields('gffm_settings_group');
            $settings = get_option('gffm_settings');
            $product_categories = isset($settings['product_categories']) ? $settings['product_categories'] : '';
            $admin_fee = isset($settings['admin_fee_percentage']) ? $settings['admin_fee_percentage'] : '5';
        ?>
        
        <table class="form-table">
            <tr valign="top">
                <th scope="row">
                    <label for="gffm-product-categories"><?php esc_html_e('Product Categories', 'gffm-plugin'); ?></label>
                </th>
                <td>
                    <textarea id="gffm-product-categories" name="gffm_settings[product_categories]" rows="10" class="large-text"><?php echo esc_textarea($product_categories); ?></textarea>
                    <p class="description"><?php esc_html_e('Enter one product category per line. These will be available as checkboxes for vendors on their dashboard.', 'gffm-plugin'); ?></p>
                </td>
            </tr>
            <tr valign="top">
                <th scope="row">
                    <label for="gffm-admin-fee"><?php esc_html_e('Admin Fee Percentage', 'gffm-plugin'); ?></label>
                </th>
                <td>
                    <input type="number" id="gffm-admin-fee" name="gffm_settings[admin_fee_percentage]" value="<?php echo esc_attr($admin_fee); ?>" min="0" max="100" step="0.1" />%
                    <p class="description"><?php esc_html_e('The percentage to be taken as an admin fee from Stripe transactions (future feature).', 'gffm-plugin'); ?></p>
                </td>
            </tr>
        </table>
        
        <?php submit_button(); ?>
    </form>
</div>
