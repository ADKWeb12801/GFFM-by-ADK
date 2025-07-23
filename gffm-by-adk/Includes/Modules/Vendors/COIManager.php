<?php
/**
 * Manages COI columns and tracking logic.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\Modules\Vendors;

class COIManager {

    public function add_coi_column($columns) {
        $columns['coi_expiration'] = __('COI Expiration', 'gffm-plugin');
        return $columns;
    }

    public function render_coi_column($column, $post_id) {
        if ($column === 'coi_expiration') {
            $expiration_date = get_post_meta($post_id, 'coi_expiration_date', true);
            if (empty($expiration_date)) {
                echo '—';
                return;
            }

            try {
                $today = new \DateTime();
                $expire = new \DateTime($expiration_date);
                $diff = $today->diff($expire)->format("%a");

                if ($today > $expire) {
                    echo '<strong style="color: #d63638;">Expired</strong>';
                } elseif ($diff <= 30) {
                    echo '<strong style="color: #f5a623;">Expires in ' . esc_html($diff) . ' days</strong>';
                } else {
                    echo esc_html(date_format($expire, 'M j, Y'));
                }
            } catch (\Exception $e) {
                echo 'Invalid Date';
            }
        }
    }
}
