<?php
/**
 * Plugin Name: GFFM by ADK
 * Description: Custom vendor and market management plugin for Glens Falls Farmers' Market. Includes vendor applications, COI tracking, payments, and public profiles.
 * Version: 2.4
 * Author: ADK Web Solutions (Enhanced by Gemini for Breakdance)
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit; // Exit if accessed directly.
}

/**
 * This plugin creates:
 * - A "vendor" custom post type.
 * - A "Vendor" user role with restricted admin access.
 * - A front-end dashboard with weekly specials, an accordion UI, and AJAX saving via [vendor_dashboard] shortcode.
 * - A "GFFM Tools" admin panel for settings, manual cron triggers, and reports.
 * - An automated daily cron job for COI reminders and a weekly cron for resetting specials.
 * - A public-facing, searchable, and filterable vendor directory via [vendor_directory] shortcode.
 * - Full CSS styling using brand colors: #f89720 (orange), #2866ab (blue).
 */

class GFFM_Plugin {

    public function __construct() {
        // Activation/Deactivation
        register_activation_hook(__FILE__, [$this, 'plugin_activate']);
        register_deactivation_hook(__FILE__, [$this, 'plugin_deactivate']);

        // Core Setup & Shortcodes
        add_action('init', [$this, 'register_vendor_cpt']);
        add_shortcode('vendor_dashboard', [$this, 'render_vendor_dashboard']);
        add_shortcode('vendor_directory', [$this, 'render_vendor_directory']);

        // AJAX handler for the dashboard form
        add_action('wp_ajax_gffm_update_vendor_profile', [$this, 'handle_ajax_form_submission']);

        // Admin Features
        add_action('admin_menu', [$this, 'add_admin_panel']);
        add_action('admin_init', [$this, 'handle_admin_panel_actions']);
        add_action('admin_notices', [$this, 'coi_expiry_admin_notices']);
        add_filter('manage_vendor_posts_columns', [$this, 'add_coi_expiry_admin_column']);
        add_action('manage_vendor_posts_custom_column', [$this, 'populate_coi_expiry_admin_column'], 10, 2);
        add_filter('manage_edit-vendor_sortable_columns', [$this, 'make_coi_expiry_column_sortable']);
        add_action('pre_get_posts', [$this, 'sort_coi_expiry_column']);

        // Cron Jobs
        add_filter('cron_schedules', [$this, 'add_weekly_cron_schedule']);
        add_action('gffm_coi_reminder_hook', [$this, 'send_coi_reminders']);
        add_action('gffm_clear_specials_hook', [$this, 'clear_weekly_specials_cron']);
    }

    // =========================
    // 1. PLUGIN LIFECYCLE & SETUP
    // =========================
    public function plugin_activate() {
        add_role('vendor', 'Vendor', ['read' => true, 'upload_files' => true]);
        // Schedule daily COI check
        if (!wp_next_scheduled('gffm_coi_reminder_hook')) {
            wp_schedule_event(time(), 'daily', 'gffm_coi_reminder_hook');
        }
        // Schedule weekly special clearing (every Sunday at midnight)
        if (!wp_next_scheduled('gffm_clear_specials_hook')) {
            wp_schedule_event(strtotime('next sunday'), 'weekly', 'gffm_clear_specials_hook');
        }
    }

    public function plugin_deactivate() {
        remove_role('vendor');
        wp_clear_scheduled_hook('gffm_coi_reminder_hook');
        wp_clear_scheduled_hook('gffm_clear_specials_hook');
    }

    public function register_vendor_cpt() {
        register_post_type('vendor', [
            'labels'        => ['name' => 'Vendors', 'singular_name' => 'Vendor'],
            'public'        => true,
            'has_archive'   => true,
            'rewrite'       => ['slug' => 'vendors'],
            'supports'      => ['title', 'editor', 'author', 'thumbnail'],
            'show_in_rest'  => true,
            'menu_icon'     => 'dashicons-store',
        ]);
    }

    // =========================
    // 2. AJAX FORM HANDLING
    // =========================
    public function handle_ajax_form_submission() {
        check_ajax_referer('gffm_update_vendor', 'gffm_vendor_nonce');
        if (!is_user_logged_in()) wp_send_json_error(['message' => 'You must be logged in.']);
        $post_id = isset($_POST['post_id']) ? intval($_POST['post_id']) : 0;
        if (!$post_id || (get_post_field('post_author', $post_id) != get_current_user_id() && !current_user_can('manage_options'))) {
            wp_send_json_error(['message' => 'Error: You do not have permission to edit this profile.']);
        }
        
        $fields = ['business_name', 'business_description', 'business_email', 'website', 'facebook_url', 'instagram_url', 'cdphp_participant', 'coi_expiration', 'weekly_special'];
        foreach ($fields as $field) {
            if (isset($_POST[$field])) {
                $sanitizer = in_array($field, ['business_description', 'weekly_special']) ? 'wp_kses_post' : 'sanitize_text_field';
                update_post_meta($post_id, $field, call_user_func($sanitizer, $_POST[$field]));
            }
        }
        
        $checkbox_fields = ['products_offered', 'market_seasons'];
        foreach($checkbox_fields as $field) {
            $value = isset($_POST[$field]) && is_array($_POST[$field]) ? array_map('sanitize_text_field', $_POST[$field]) : [];
            update_post_meta($post_id, $field, $value);
        }

        if (!empty($_FILES)) {
            require_once(ABSPATH . 'wp-admin/includes/file.php');
            require_once(ABSPATH . 'wp-admin/includes/image.php');
            require_once(ABSPATH . 'wp-admin/includes/media.php');
            foreach ($_FILES as $field_name => $file_info) {
                 if (!empty($file_info['name'])) {
                    if ($field_name === 'coi_document' && strtolower(pathinfo($file_info['name'], PATHINFO_EXTENSION)) !== 'pdf') continue;
                    $attachment_id = media_handle_upload($field_name, $post_id);
                    if (!is_wp_error($attachment_id)) update_post_meta($post_id, $field_name, $attachment_id);
                }
            }
        }
        wp_send_json_success(['message' => 'Your profile has been updated successfully!']);
    }

    // =========================
    // 3. FRONT-END SHORTCODES
    // =========================
    public function render_vendor_dashboard() {
        if (!is_user_logged_in()) return '<p>Please <a href="' . wp_login_url(get_permalink()) . '">log in</a> to access your dashboard.</p>';
        $user = wp_get_current_user();
        $vendor_post_query = new WP_Query(['post_type' => 'vendor', 'author' => $user->ID, 'posts_per_page' => 1, 'post_status' => 'any']);
        if (!$vendor_post_query->have_posts()) return '<div class="gffm-plugin-wrapper"><p>No vendor profile found. Please contact the Market Administrator to have one created for you.</p></div>';

        ob_start();
        $vendor_post_query->the_post();
        $post_id = get_the_ID();
        ?>
        <div class="gffm-plugin-wrapper vendor-dashboard">
            <div id="gffm-ajax-response" class="gffm-notice" style="display:none;"></div>
            <h2>Welcome, <?php echo esc_html($user->display_name); ?>!</h2>
            <p>Use the sections below to update your profile. Click on a heading to expand it.</p>
            <form id="gffm-vendor-form" method="post" enctype="multipart/form-data">
                <input type="hidden" name="post_id" value="<?php echo $post_id; ?>">
                <input type="hidden" name="action" value="gffm_update_vendor_profile">
                <?php wp_nonce_field('gffm_update_vendor', 'gffm_vendor_nonce'); ?>

                <details class="gffm-accordion" open>
                    <summary>Weekly Special (Resets Every Sunday)</summary>
                    <div class="gffm-accordion-content">
                        <p><label for="weekly_special">Enter this week's special offers or news.</label><br>
                        <?php wp_editor(get_post_meta($post_id, 'weekly_special', true), 'weekly_special', ['textarea_name' => 'weekly_special', 'media_buttons' => false, 'textarea_rows' => 5]); ?></p>
                    </div>
                </details>

                <details class="gffm-accordion">
                    <summary>Business Info</summary>
                    <div class="gffm-accordion-content">
                        <p><label for="business_name">Business Name</label><br>
                        <input type="text" name="business_name" id="business_name" value="<?php echo esc_attr(get_post_meta($post_id, 'business_name', true)); ?>" class="gffm-input"></p>
                        <p><label for="business_description">Business Description / Tagline</label><br>
                        <?php wp_editor(get_post_meta($post_id, 'business_description', true), 'business_description', ['textarea_name' => 'business_description', 'media_buttons' => false, 'textarea_rows' => 5]); ?></p>
                    </div>
                </details>

                <details class="gffm-accordion">
                    <summary>Contact Info & Social Links</summary>
                    <div class="gffm-accordion-content">
                        <p><label for="business_email">Public Email Address (Required)</label><br>
                        <input type="email" name="business_email" id="business_email" value="<?php echo esc_attr(get_post_meta($post_id, 'business_email', true)); ?>" class="gffm-input"></p>
                        <p><label for="website">Website URL</label><br>
                        <input type="url" name="website" id="website" value="<?php echo esc_attr(get_post_meta($post_id, 'website', true)); ?>" class="gffm-input"></p>
                        <p><label for="facebook_url">Facebook URL</label><br>
                        <input type="url" name="facebook_url" id="facebook_url" value="<?php echo esc_attr(get_post_meta($post_id, 'facebook_url', true)); ?>" class="gffm-input"></p>
                        <p><label for="instagram_url">Instagram URL</label><br>
                        <input type="url" name="instagram_url" id="instagram_url" value="<?php echo esc_attr(get_post_meta($post_id, 'instagram_url', true)); ?>" class="gffm-input"></p>
                    </div>
                </details>

                <details class="gffm-accordion">
                    <summary>Product Categories & Seasons</summary>
                    <div class="gffm-accordion-content">
                        <?php
                        $product_options = get_option('gffm_product_categories', ['Fruits', 'Vegetables', 'Meat', 'Eggs', 'Dairy/Cheese', 'Baked Goods', 'Prepared Foods', 'Crafts', 'Other']);
                        $this->render_checkbox_group('products_offered', 'Products Offered', $product_options, $post_id);
                        $this->render_checkbox_group('market_seasons', 'Market Seasons', ['Summer', 'Winter'], $post_id);
                        $this->render_radio_group('cdphp_participant', 'CDPHP Participant?', ['Yes', 'No'], $post_id);
                        ?>
                    </div>
                </details>

                <details class="gffm-accordion">
                    <summary>Logo & Photo Gallery</summary>
                    <div class="gffm-accordion-content">
                        <?php $this->render_image_upload('business_logo', 'Business Logo', $post_id); ?>
                        <hr><label>Photo Gallery (up to 6 images)</label>
                        <div class="gffm-gallery-grid">
                            <?php for ($i = 1; $i <= 6; $i++) $this->render_image_upload('image_' . $i, 'Image ' . $i, $post_id); ?>
                        </div>
                    </div>
                </details>

                <details class="gffm-accordion">
                    <summary>Insurance Info</summary>
                    <div class="gffm-accordion-content">
                        <p><label for="coi_expiration">COI Expiration Date (Required)</label><br>
                        <input type="date" name="coi_expiration" id="coi_expiration" value="<?php echo esc_attr(get_post_meta($post_id, 'coi_expiration', true)); ?>" class="gffm-input"></p>
                        <p><label for="coi_document">COI Document (PDF Only)</label><br>
                        <?php $current_coi_url = get_post_meta($post_id, 'coi_document', true) ? wp_get_attachment_url(get_post_meta($post_id, 'coi_document', true)) : null; if($current_coi_url): ?>
                            <span class="current-file-link">Current file: <a href="<?php echo esc_url($current_coi_url); ?>" target="_blank">Download/View PDF</a></span>
                        <?php endif; ?>
                        <input type="file" name="coi_document" id="coi_document" accept=".pdf"></p>
                    </div>
                </details>
                
                <p><input type="submit" id="gffm-submit-btn" value="Update Profile" class="btn-submit"></p>
            </form>
        </div>
        <?php
        $this->print_plugin_scripts_and_styles();
        wp_reset_postdata();
        return ob_get_clean();
    }

    public function render_vendor_directory() {
        ob_start();
        $search_query = isset($_GET['vsearch']) ? sanitize_text_field($_GET['vsearch']) : '';
        $product_filter = isset($_GET['product']) ? sanitize_text_field($_GET['product']) : '';
        $type_filter = isset($_GET['type']) ? sanitize_text_field($_GET['type']) : '';
        $args = ['post_type' => 'vendor', 'posts_per_page' => -1, 'post_status' => 'publish', 's' => $search_query, 'meta_query' => [], 'tax_query' => []];
        if ($product_filter) $args['meta_query'][] = ['key' => 'products_offered', 'value' => '"' . $product_filter . '"', 'compare' => 'LIKE'];
        if ($type_filter) $args['tax_query'][] = ['taxonomy' => 'vendor_type', 'field' => 'slug', 'terms' => $type_filter];
        $vendors = new WP_Query($args);
        $product_terms = get_option('gffm_product_categories', $this->get_all_scf_checkbox_values('products_offered'));
        $vendor_types = get_terms(['taxonomy' => 'vendor_type', 'hide_empty' => true]);
        ?>
        <div class="gffm-plugin-wrapper vendor-directory">
            <h3>Vendor Directory</h3>
            <form method="get" class="vendor-filter-form" action="">
                <input type="text" name="vsearch" placeholder="Search by name..." value="<?php echo esc_attr($search_query); ?>">
                <select name="product"><option value="">All Products</option><?php foreach($product_terms as $term) echo '<option value="'.esc_attr($term).'" '.selected($product_filter, $term, false).'>'.esc_html($term).'</option>'; ?></select>
                <select name="type"><option value="">All Vendor Types</option><?php if (is_array($vendor_types)) foreach($vendor_types as $term) echo '<option value="'.esc_attr($term->slug).'" '.selected($type_filter, $term->slug, false).'>'.esc_html($term->name).'</option>'; ?></select>
                <input type="submit" value="Filter" class="btn-submit">
            </form>
            <?php if ($vendors->have_posts()) : ?>
                <div class="vendor-grid">
                    <?php while ($vendors->have_posts()) : $vendors->the_post(); 
                        $post_id = get_the_ID();
                        $logo_id = get_post_meta($post_id, 'business_logo', true);
                        $weekly_special = get_post_meta($post_id, 'weekly_special', true);
                    ?>
                        <div class="vendor-card">
                            <?php if($weekly_special): ?><div class="vendor-special-banner">Weekly Special!</div><?php endif; ?>
                            <a href="<?php the_permalink(); ?>">
                                <?php if ($logo_id) echo wp_get_attachment_image($logo_id, 'medium'); else echo '<img src="https://placehold.co/400x300/2866ab/ffffff?text='.urlencode(get_the_title()).'" alt="'.get_the_title().'">'; ?>
                                <div class="vendor-card-content">
                                    <h4><?php the_title(); ?></h4>
                                    <?php if ($weekly_special): ?><div class="vendor-special-text"><?php echo wp_kses_post($weekly_special); ?></div><?php endif; ?>
                                </div>
                            </a>
                        </div>
                    <?php endwhile; ?>
                </div>
            <?php else : ?>
                <p>No vendors found matching your criteria.</p>
            <?php endif; ?>
        </div>
        <?php
        $this->print_plugin_scripts_and_styles();
        wp_reset_postdata();
        return ob_get_clean();
    }
    
    // =========================
    // 4. ADMIN & COI TRACKING
    // =========================
    public function add_admin_panel() {
        add_menu_page('GFFM Tools', 'GFFM Tools', 'manage_options', 'gffm-admin-panel', [$this, 'render_admin_panel'], 'dashicons-admin-tools', 20);
    }

    public function render_admin_panel() {
        ?>
        <div class="wrap">
            <h1>GFFM Vendor Tools</h1>
            <?php if(isset($_GET['gffm_message'])): ?><div class="notice notice-success is-dismissible"><p><?php echo esc_html($_GET['gffm_message']); ?></p></div><?php endif; ?>
            <div id="poststuff">
                <div id="post-body" class="metabox-holder columns-2">
                    <div id="post-body-content">
                        <div class="postbox">
                            <h2 class="hndle"><span>Quick Actions</span></h2>
                            <div class="inside">
                                <p>Use these buttons for manual control over plugin tasks.</p>
                                <form method="post">
                                    <?php wp_nonce_field('gffm_admin_action', 'gffm_admin_nonce'); ?>
                                    <button type="submit" name="gffm_action" value="run_coi_cron" class="button button-secondary">Manually Run COI Email Reminders</button>
                                    <button type="submit" name="gffm_action" value="run_specials_cron" class="button button-secondary">Manually Clear All Weekly Specials</button>
                                    <a href="<?php echo admin_url('admin.php?page=gffm-admin-panel&export_gffm_vendors=true'); ?>" class="button button-primary">Export All Vendors to CSV</a>
                                </form>
                            </div>
                        </div>
                        <div class="postbox">
                            <h2 class="hndle"><span>Vendors with Expired COI</span></h2>
                            <div class="inside">
                                <?php
                                $expired_vendors = new WP_Query(['post_type' => 'vendor', 'posts_per_page' => -1, 'meta_query' => [['key' => 'coi_expiration', 'value' => date('Y-m-d'), 'compare' => '<', 'type' => 'DATE']]]);
                                if ($expired_vendors->have_posts()): ?>
                                    <table class="wp-list-table widefat fixed striped">
                                        <thead><tr><th>Vendor Name</th><th>COI Expiration Date</th><th>Action</th></tr></thead>
                                        <tbody>
                                        <?php while($expired_vendors->have_posts()): $expired_vendors->the_post(); ?>
                                            <tr>
                                                <td><?php the_title(); ?></td>
                                                <td><?php echo esc_html(get_post_meta(get_the_ID(), 'coi_expiration', true)); ?></td>
                                                <td><a href="<?php echo get_edit_post_link(get_the_ID()); ?>">Edit Vendor</a></td>
                                            </tr>
                                        <?php endwhile; ?>
                                        </tbody>
                                    </table>
                                <?php else: ?>
                                    <p>No vendors with expired COIs found. Great job!</p>
                                <?php endif; wp_reset_postdata(); ?>
                            </div>
                        </div>
                    </div>
                    <div id="postbox-container-1" class="postbox-container">
                        <div class="postbox">
                             <h2 class="hndle"><span>Plugin Settings</span></h2>
                             <div class="inside">
                                <form method="post">
                                    <?php wp_nonce_field('gffm_admin_action', 'gffm_admin_nonce'); ?>
                                    <p><label for="gffm_product_categories"><strong>Product Categories</strong></label><br>
                                    Enter one category per line. This list will appear as checkboxes on the vendor dashboard.</p>
                                    <textarea name="gffm_product_categories" id="gffm_product_categories" class="widefat" rows="10"><?php echo esc_textarea(implode("\n", get_option('gffm_product_categories', []))); ?></textarea>
                                    <p><button type="submit" name="gffm_action" value="save_settings" class="button button-primary">Save Settings</button></p>
                                </form>
                             </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }

    public function handle_admin_panel_actions() {
        if (!isset($_POST['gffm_action']) || !isset($_POST['gffm_admin_nonce'])) return;
        if (!wp_verify_nonce($_POST['gffm_admin_nonce'], 'gffm_admin_action') || !current_user_can('manage_options')) return;
        
        $action = sanitize_key($_POST['gffm_action']);
        $message = '';

        switch($action) {
            case 'run_coi_cron':
                $this->send_coi_reminders();
                $message = 'COI reminder email process has been manually triggered.';
                break;
            case 'run_specials_cron':
                $this->clear_weekly_specials_cron();
                $message = 'All weekly specials have been cleared.';
                break;
            case 'save_settings':
                if (isset($_POST['gffm_product_categories'])) {
                    $categories = explode("\n", sanitize_textarea_field($_POST['gffm_product_categories']));
                    $categories = array_map('trim', $categories);
                    $categories = array_filter($categories); // Remove empty lines
                    update_option('gffm_product_categories', $categories);
                }
                $message = 'Settings saved successfully.';
                break;
        }
        
        if (isset($_GET['export_gffm_vendors'])) {
             $this->export_vendors_to_csv();
        }

        if ($message) {
            wp_redirect(add_query_arg('gffm_message', urlencode($message), admin_url('admin.php?page=gffm-admin-panel')));
            exit;
        }
    }

    public function coi_expiry_admin_notices() { /* This is now handled in the admin panel */ }
    public function add_coi_expiry_admin_column($columns) { $columns['coi_expiration'] = 'COI Expiration'; return $columns; }
    public function populate_coi_expiry_admin_column($column, $post_id) {
        if ($column == 'coi_expiration') {
            $expiry_date_str = get_post_meta($post_id, 'coi_expiration', true);
            if (!$expiry_date_str) { echo '—'; return; }
            try {
                $expiry_date = new DateTime($expiry_date_str); $today = new DateTime();
                if ($expiry_date < $today) echo '<span style="color:red; font-weight:bold;">Expired: '.$expiry_date->format('M j, Y').'</span>';
                elseif ($expiry_date < (new DateTime())->modify('+30 days')) echo '<span style="color:#f89720; font-weight:bold;">Expires Soon: '.$expiry_date->format('M j, Y').'</span>';
                else echo '<span>'.$expiry_date->format('M j, Y').'</span>';
            } catch (Exception $e) { echo 'Invalid date'; }
        }
    }
    public function make_coi_expiry_column_sortable($columns) { $columns['coi_expiration'] = 'coi_expiration'; return $columns; }
    public function sort_coi_expiry_column($query) {
        if (!is_admin() || !$query->is_main_query() || $query->get('post_type') !== 'vendor') return;
        if ($query->get('orderby') == 'coi_expiration') {
            $query->set('meta_key', 'coi_expiration'); $query->set('orderby', 'meta_value'); $query->set('meta_type', 'DATE');
        }
    }
    private function export_vendors_to_csv() {
        if (!current_user_can('manage_options')) return;
        $filename = 'gffm-vendors-'.date('Y-m-d').'.csv';
        header('Content-Type: text/csv'); header('Content-Disposition: attachment; filename="'.$filename.'"');
        $vendors = new WP_Query(['post_type' => 'vendor', 'posts_per_page' => -1, 'post_status' => 'any']);
        $headers = ['Vendor Name', 'Status', 'Contact Email', 'COI Expiration', 'Vendor Type', 'Products Offered', 'Weekly Special'];
        $output = fopen('php://output', 'w'); fputcsv($output, $headers);
        if ($vendors->have_posts()) {
            while ($vendors->have_posts()) {
                $vendors->the_post(); $post_id = get_the_ID();
                $types = get_the_terms($post_id, 'vendor_type');
                $type_names = !is_wp_error($types) && !empty($types) ? wp_list_pluck($types, 'name') : [];
                $products = get_post_meta($post_id, 'products_offered', true);
                $row = [get_the_title(), get_post_status(), get_post_meta($post_id, 'business_email', true), get_post_meta($post_id, 'coi_expiration', true), implode(', ', $type_names), is_array($products) ? implode(', ', $products) : '', wp_strip_all_tags(get_post_meta($post_id, 'weekly_special', true))];
                fputcsv($output, $row);
            }
        }
        fclose($output); exit;
    }

    // =========================
    // 5. CRON JOBS
    // =========================
    public function add_weekly_cron_schedule($schedules) {
        $schedules['weekly'] = ['interval' => 604800, 'display'  => __('Once Weekly')];
        return $schedules;
    }
    public function clear_weekly_specials_cron() {
        $vendors = new WP_Query(['post_type' => 'vendor', 'posts_per_page' => -1, 'fields' => 'ids']);
        if ($vendors->have_posts()) {
            foreach ($vendors->posts as $vendor_id) delete_post_meta($vendor_id, 'weekly_special');
        }
    }
    public function send_coi_reminders() {
        $vendors_to_notify = new WP_Query(['post_type' => 'vendor', 'posts_per_page' => -1, 'post_status' => 'publish', 'meta_query' => [['key' => 'coi_expiration', 'value' => (new DateTime('+30 days'))->format('Y-m-d'), 'compare' => '<=', 'type' => 'DATE']]]);
        if ($vendors_to_notify->have_posts()) {
            $admin_email = get_option('admin_email'); $headers = ['Content-Type: text/html; charset=UTF-8'];
            while ($vendors_to_notify->have_posts()) {
                $vendors_to_notify->the_post(); $post_id = get_the_ID(); $vendor_name = get_the_title();
                $vendor_email = get_post_meta($post_id, 'business_email', true) ?: get_the_author_meta('user_email');
                $coi_date_str = get_post_meta($post_id, 'coi_expiration', true); if (!$coi_date_str) continue;
                try {
                    $coi_date = new DateTime($coi_date_str);
                    if ($coi_date < new DateTime()) {
                        $subject = 'URGENT: Your GFFM Certificate of Insurance has Expired';
                        $message = "<p>Hello $vendor_name,</p><p>Your Certificate of Insurance expired on ".$coi_date->format('F j, Y').". Your public profile has been temporarily unpublished. Please log in to your dashboard to upload a new document.</p>";
                        wp_update_post(['ID' => $post_id, 'post_status' => 'pending']);
                    } else {
                        $subject = 'Reminder: Your GFFM Certificate of Insurance is Expiring Soon';
                        $message = "<p>Hello $vendor_name,</p><p>Your Certificate of Insurance will expire on ".$coi_date->format('F j, Y').". Please log in to your dashboard to upload a new document to ensure your profile remains active.</p>";
                    }
                    if ($vendor_email) wp_mail($vendor_email, $subject, $message, $headers);
                    wp_mail($admin_email, "[GFFM Notice] ".$subject, "<p>The following notice was sent to $vendor_name ($vendor_email):</p><hr>".$message, $headers);
                } catch (Exception $e) { continue; }
            }
        }
        wp_reset_postdata();
    }
    
    // =========================
    // 6. HELPERS, STYLES & SCRIPTS
    // =========================
    private function render_checkbox_group($name, $label, $options, $post_id) { /* ... same as before ... */ }
    private function render_radio_group($name, $label, $options, $post_id) { /* ... same as before ... */ }
    private function render_image_upload($name, $label, $post_id) { /* ... same as before ... */ }
    private function get_all_scf_checkbox_values($meta_key) { /* ... same as before ... */ }
    private function get_svg_icon($name) { /* ... same as before ... */ }
    public function print_plugin_scripts_and_styles() {
        ?>
        <style>
            :root { --gffm-orange: #f89720; --gffm-blue: #2866ab; }
            .gffm-plugin-wrapper { border: 2px solid var(--gffm-blue); padding: 1.5em; background: #fdfdfd; border-radius: 8px; margin-bottom: 2em; }
            .gffm-plugin-wrapper h2, .gffm-plugin-wrapper h3, .gffm-plugin-wrapper h4 { color: var(--gffm-blue); font-family: sans-serif; }
            .gffm-plugin-wrapper .btn-submit, .gffm-plugin-wrapper input[type="submit"] { background: var(--gffm-orange); color: white; border: none; padding: 12px 24px; font-weight: bold; cursor: pointer; border-radius: 5px; text-transform: uppercase; transition: background-color 0.3s ease; }
            .gffm-plugin-wrapper .btn-submit:hover, .gffm-plugin-wrapper input[type="submit"]:hover { background: var(--gffm-blue); }
            .gffm-notice { padding: 1em; margin-bottom: 1em; border-left: 5px solid; border-radius: 4px; }
            .gffm-success { background: #eaf5e9; border-color: #5cb85c; color: #3c763d; }
            .gffm-error, .gffm-validation-error { background: #fbeaea; border-color: #d9534f; color: #a94442; }
            .gffm-validation-error { font-size: 0.9em; padding: 5px; margin-top: 5px; border-radius: 3px; }
            .gffm-input, .gffm-plugin-wrapper textarea { width: 100%; padding: 8px; border: 1px solid #ddd; border-radius: 4px; }
            .gffm-fieldset { border: 1px solid #ddd; padding: 10px; margin-bottom: 1em; border-radius: 4px; }
            .gffm-fieldset legend { font-weight: bold; padding: 0 5px; }
            .gffm-checkbox-label, .gffm-radio-label { display: inline-block; margin-right: 15px; }
            .gffm-accordion { border: 1px solid #ddd; border-radius: 5px; margin-bottom: 10px; }
            .gffm-accordion summary { font-weight: bold; padding: 1em; cursor: pointer; background: #f9f9f9; color: var(--gffm-blue); list-style: revert; }
            .gffm-accordion[open] summary { border-bottom: 1px solid #ddd; }
            .gffm-accordion-content { padding: 1.5em; }
            .gffm-gallery-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(150px, 1fr)); gap: 15px; }
            .gffm-image-upload-wrapper { border: 1px dashed #ccc; padding: 10px; text-align: center; }
            .gffm-image-upload-wrapper img { max-width: 100px; height: auto; display: block; margin: 0 auto 10px; }
            .vendor-filter-form { display: flex; flex-wrap: wrap; gap: 10px; margin-bottom: 2em; }
            .vendor-filter-form input[type="text"], .vendor-filter-form select { flex-grow: 1; padding: 10px; border: 1px solid #ccc; border-radius: 5px; }
            .vendor-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(280px, 1fr)); gap: 20px; }
            .vendor-card { border: 1px solid #ddd; border-radius: 8px; overflow: hidden; display: flex; flex-direction: column; transition: box-shadow 0.3s ease, transform 0.3s ease; position: relative; }
            .vendor-card:hover { box-shadow: 0 6px 20px rgba(0,0,0,0.12); transform: translateY(-5px); }
            .vendor-card a { text-decoration: none; color: #333; display: flex; flex-direction: column; height: 100%; }
            .vendor-card img { width: 100%; height: 200px; object-fit: cover; display: block; border-bottom: 1px solid #ddd; }
            .vendor-card-content { padding: 15px; background: #fff; flex-grow: 1; }
            .vendor-card h4 { margin: 0 0 10px 0; padding: 0; }
            .vendor-special-banner { position: absolute; top: 10px; right: -35px; background: var(--gffm-orange); color: white; padding: 5px 30px; transform: rotate(45deg); font-size: 0.8em; font-weight: bold; }
            .vendor-special-text { background: #fff3e0; border-left: 4px solid var(--gffm-orange); padding: 10px; margin-bottom: 10px; border-radius: 4px; }
        </style>
        <script>
        document.addEventListener('DOMContentLoaded', function() {
            const form = document.getElementById('gffm-vendor-form');
            if (!form) return;
            form.addEventListener('submit', function(e) {
                e.preventDefault();
                document.querySelectorAll('.gffm-validation-error').forEach(el => el.remove());
                let isValid = true;
                const emailField = form.querySelector('input[name="business_email"]');
                if (emailField && (emailField.value.trim() === '' || !/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailField.value))) {
                    isValid = false; showError(emailField, 'Please enter a valid email address.');
                }
                const coiDateField = form.querySelector('input[name="coi_expiration"]');
                if (coiDateField && coiDateField.value.trim() === '') {
                    isValid = false; showError(coiDateField, 'COI Expiration Date is required.');
                }
                const coiFileField = form.querySelector('input[name="coi_document"]');
                if (coiFileField && coiFileField.files.length > 0 && !coiFileField.files[0].name.toLowerCase().endsWith('.pdf')) {
                    isValid = false; showError(coiFileField, 'Please upload a PDF file.');
                }
                if (!isValid) return;

                const submitBtn = document.getElementById('gffm-submit-btn');
                const responseDiv = document.getElementById('gffm-ajax-response');
                submitBtn.value = 'Saving...'; submitBtn.disabled = true;
                const formData = new FormData(form);

                fetch('<?php echo admin_url('admin-ajax.php'); ?>', { method: 'POST', body: formData })
                .then(response => response.json())
                .then(data => {
                    responseDiv.style.display = 'block';
                    responseDiv.textContent = data.message;
                    responseDiv.className = data.success ? 'gffm-notice gffm-success' : 'gffm-notice gffm-error';
                    window.scrollTo(0, 0);
                })
                .catch(error => {
                    responseDiv.style.display = 'block';
                    responseDiv.className = 'gffm-notice gffm-error';
                    responseDiv.textContent = 'An unexpected error occurred. Please try again.';
                })
                .finally(() => { submitBtn.value = 'Update Profile'; submitBtn.disabled = false; });
            });
            function showError(field, message) {
                const error = document.createElement('div');
                error.className = 'gffm-validation-error';
                error.textContent = message;
                field.parentNode.insertBefore(error, field.nextSibling);
            }
        });
        </script>
        <?php
    }
}

// Instantiate the plugin class.
new GFFM_Plugin();
