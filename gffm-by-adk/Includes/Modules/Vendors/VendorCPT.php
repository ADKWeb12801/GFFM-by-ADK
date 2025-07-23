<?php
/**
 * Registers the Vendor Custom Post Type and associated taxonomies.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\Modules\Vendors;

class VendorCPT {

    public function register() {
        $this->register_post_type();
        $this->register_taxonomies();
    }

    private function register_post_type() {
        $labels = [
            'name'                  => _x('Vendors', 'Post Type General Name', 'gffm-plugin'),
            'singular_name'         => _x('Vendor', 'Post Type Singular Name', 'gffm-plugin'),
            'menu_name'             => __('Vendors', 'gffm-plugin'),
            'name_admin_bar'        => __('Vendor', 'gffm-plugin'),
            'archives'              => __('Vendor Archives', 'gffm-plugin'),
            'attributes'            => __('Vendor Attributes', 'gffm-plugin'),
            'parent_item_colon'     => __('Parent Vendor:', 'gffm-plugin'),
            'all_items'             => __('All Vendors', 'gffm-plugin'),
            'add_new_item'          => __('Add New Vendor', 'gffm-plugin'),
            'add_new'               => __('Add New', 'gffm-plugin'),
            'new_item'              => __('New Vendor', 'gffm-plugin'),
            'edit_item'             => __('Edit Vendor', 'gffm-plugin'),
            'update_item'           => __('Update Vendor', 'gffm-plugin'),
            'view_item'             => __('View Vendor', 'gffm-plugin'),
            'view_items'            => __('View Vendors', 'gffm-plugin'),
            'search_items'          => __('Search Vendor', 'gffm-plugin'),
        ];
        $args = [
            'label'                 => __('Vendor', 'gffm-plugin'),
            'description'           => __('GFFM Vendor Profiles', 'gffm-plugin'),
            'labels'                => $labels,
            'supports'              => ['title', 'editor', 'author', 'thumbnail', 'revisions'],
            'taxonomies'            => ['gffm_vendor_type'],
            'hierarchical'          => false,
            'public'                => true,
            'show_ui'               => true,
            'show_in_menu'          => false, // We will add it under our main admin menu
            'menu_position'         => 5,
            'show_in_admin_bar'     => true,
            'show_in_nav_menus'     => true,
            'can_export'            => true,
            'has_archive'           => 'vendors',
            'exclude_from_search'   => false,
            'publicly_queryable'    => true,
            'capability_type'       => 'post',
            'show_in_rest'          => true,
        ];
        register_post_type('gffm_vendor', $args);
    }

    private function register_taxonomies() {
        $labels = [
            'name'              => _x('Vendor Types', 'taxonomy general name', 'gffm-plugin'),
            'singular_name'     => _x('Vendor Type', 'taxonomy singular name', 'gffm-plugin'),
            'search_items'      => __('Search Vendor Types', 'gffm-plugin'),
            'all_items'         => __('All Vendor Types', 'gffm-plugin'),
            'parent_item'       => __('Parent Vendor Type', 'gffm-plugin'),
            'parent_item_colon' => __('Parent Vendor Type:', 'gffm-plugin'),
            'edit_item'         => __('Edit Vendor Type', 'gffm-plugin'),
            'update_item'       => __('Update Vendor Type', 'gffm-plugin'),
            'add_new_item'      => __('Add New Vendor Type', 'gffm-plugin'),
            'new_item_name'     => __('New Vendor Type Name', 'gffm-plugin'),
            'menu_name'         => __('Vendor Types', 'gffm-plugin'),
        ];
        $args = [
            'hierarchical'      => true,
            'labels'            => $labels,
            'show_ui'           => true,
            'show_admin_column' => true,
            'query_var'         => true,
            'rewrite'           => ['slug' => 'vendor-type'],
            'show_in_rest'      => true,
        ];
        register_taxonomy('gffm_vendor_type', ['gffm_vendor'], $args);
    }
}
