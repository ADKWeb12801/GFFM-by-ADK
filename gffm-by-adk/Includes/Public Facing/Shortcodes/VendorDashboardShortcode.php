<?php
/**
 * Handles the rendering of the [vendor_dashboard] shortcode.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\PublicFacing\Shortcodes;

class VendorDashboardShortcode {

    public function render($atts, $content = null) {
        if (!is_user_logged_in()) {
            return '<div class="gffm-container"><p>' . __('Please log in to access your dashboard.', 'gffm-plugin') . '</p></div>';
        }

        $user_id = get_current_user_id();
        $args = [
            'post_type'   => 'gffm_vendor',
            'author'      => $user_id,
            'post_status' => ['publish', 'pending', 'draft'],
            'posts_per_page' => 1,
        ];
        $vendor_query = new \WP_Query($args);

        if (!$vendor_query->have_posts()) {
            return '<div class="gffm-container"><p>' . __('No vendor profile is associated with your account. Please contact the market administrator.', 'gffm-plugin') . '</p></div>';
        }

        ob_start();
        // Pass the query object to the partial to avoid global post issues
        include GFFM_PLUGIN_DIR . 'includes/PublicFacing/partials/vendor-dashboard-display.php';
        return ob_get_clean();
    }
}
