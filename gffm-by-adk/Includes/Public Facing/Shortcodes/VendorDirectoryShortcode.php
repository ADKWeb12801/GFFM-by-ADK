<?php
/**
 * Handles the rendering of the [vendor_directory] shortcode.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\PublicFacing\Shortcodes;

class VendorDirectoryShortcode {

    public function render($atts, $content = null) {
        ob_start();
        include GFFM_PLUGIN_DIR . 'includes/PublicFacing/partials/vendor-directory-display.php';
        return ob_get_clean();
    }

    public function handle_ajax_filter() {
        check_ajax_referer('gffm_filter_nonce', 'nonce');

        $args = [
            'post_type'   => 'gffm_vendor',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query'  => ['relation' => 'AND'],
            'tax_query'   => ['relation' => 'AND'],
        ];

        if (!empty($_POST['product_type'])) {
            $args['meta_query'][] = [
                'key' => 'products_offered',
                'value' => sanitize_text_field($_POST['product_type']),
                'compare' => 'LIKE',
            ];
        }
        
        if (!empty($_POST['vendor_type'])) {
            $args['tax_query'][] = [
                'taxonomy' => 'gffm_vendor_type',
                'field'    => 'slug',
                'terms'    => sanitize_text_field($_POST['vendor_type']),
            ];
        }
        
        if (!empty($_POST['cdphp'])) {
            $args['meta_query'][] = [
                'key' => 'cdphp_participant',
                'value' => 'Yes',
                'compare' => '=',
            ];
        }

        $query = new \WP_Query($args);

        if ($query->have_posts()) {
            while ($query->have_posts()) {
                $query->the_post();
                include GFFM_PLUGIN_DIR . 'includes/PublicFacing/partials/vendor-card.php';
            }
        } else {
            echo '<p class="gffm-no-results">' . esc_html__('No vendors found matching your criteria.', 'gffm-plugin') . '</p>';
        }
        wp_reset_postdata();
        
        wp_die();
    }
}
