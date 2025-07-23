<?php
/**
 * Registers and enqueues plugin styles and scripts.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\PublicFacing;

class AssetManager {

    private $plugin_name;
    private $version;

    public function __construct($plugin_name, $version) {
        $this->plugin_name = $plugin_name;
        $this->version = $version;
    }

    public function enqueue_styles() {
        wp_enqueue_style(
            $this->plugin_name,
            GFFM_PLUGIN_URL . 'public/css/gffm-public.css',
            [],
            $this->version,
            'all'
        );
    }

    public function enqueue_scripts() {
        wp_enqueue_script(
            $this->plugin_name,
            GFFM_PLUGIN_URL . 'public/js/gffm-public.js',
            ['jquery'],
            $this->version,
            true
        );
        
        // Localize script for AJAX
        wp_localize_script($this->plugin_name, 'gffm_ajax', [
            'ajax_url' => admin_url('admin-ajax.php'),
            'nonce'    => wp_create_nonce('gffm_filter_nonce'),
        ]);
    }

    public function enqueue_admin_styles($hook) {
        // Only load on our plugin pages
        if (strpos($hook, 'gffm-plugin') === false) {
            return;
        }
        wp_enqueue_style(
            $this->plugin_name . '-admin',
            GFFM_PLUGIN_URL . 'public/css/gffm-admin.css',
            [],
            $this->version,
            'all'
        );
    }
}