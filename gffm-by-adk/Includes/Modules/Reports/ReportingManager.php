<?php
/**
 * Manages data aggregation and reporting for the admin dashboard.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\Modules\Reports;

class ReportingManager {

    /**
     * Gathers all Key Performance Indicators (KPIs) for the main admin dashboard.
     *
     * @return array An array of KPI counts.
     */
    public function get_dashboard_kpis() {
        $today = new \DateTime();
        $thirty_days = new \DateTime('+30 days');

        $active_vendors = wp_count_posts('gffm_vendor')->publish;
        $pending_vendors = wp_count_posts('gffm_vendor')->pending;
        $draft_vendors = wp_count_posts('gffm_vendor')->draft;

        // Count vendors with an expired COI
        $expired_cois_query = new \WP_Query([
            'post_type' => 'gffm_vendor',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'coi_expiration_date',
                    'value' => $today->format('Y-m-d'),
                    'compare' => '<',
                    'type' => 'DATE'
                ]
            ]
        ]);
        $expired_cois = $expired_cois_query->post_count;

        // Count vendors with COI expiring in the next 30 days
        $expiring_soon_cois_query = new \WP_Query([
            'post_type' => 'gffm_vendor',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                'relation' => 'AND',
                [
                    'key' => 'coi_expiration_date',
                    'value' => $today->format('Y-m-d'),
                    'compare' => '>=',
                    'type' => 'DATE'
                ],
                [
                    'key' => 'coi_expiration_date',
                    'value' => $thirty_days->format('Y-m-d'),
                    'compare' => '<=',
                    'type' => 'DATE'
                ]
            ]
        ]);
        $expiring_soon_cois = $expiring_soon_cois_query->post_count;
        
        // Count vendors with a weekly special
        $weekly_specials_query = new \WP_Query([
            'post_type' => 'gffm_vendor',
            'post_status' => 'publish',
            'posts_per_page' => -1,
            'meta_query' => [
                [
                    'key' => 'weekly_special_desc',
                    'value'   => '',
                    'compare' => '!='
                ]
            ]
        ]);
        $weekly_specials = $weekly_specials_query->post_count;

        return [
            'active_vendors'     => $active_vendors,
            'pending_vendors'    => $pending_vendors,
            'draft_vendors'      => $draft_vendors,
            'expired_cois'       => $expired_cois,
            'expiring_soon_cois' => $expiring_soon_cois,
            'weekly_specials'    => $weekly_specials,
        ];
    }
}
