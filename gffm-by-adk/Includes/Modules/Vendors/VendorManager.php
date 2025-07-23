<?php
/**
 * Manages core vendor data, status, and dashboard interactions.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\Modules\Vendors;

use GFFM\Includes\Modules\Notifications\NotificationManager;

class VendorManager {

    public function handle_dashboard_save() {
        check_ajax_referer('gffm_update_nonce', 'security');

        if (!is_user_logged_in()) {
            wp_send_json_error(['message' => 'You must be logged in to update your profile.']);
        }

        $vendor_id = isset($_POST['vendor_id']) ? intval($_POST['vendor_id']) : 0;
        $user_id = get_current_user_id();

        if (!$vendor_id || get_post_field('post_author', $vendor_id) != $user_id) {
            wp_send_json_error(['message' => 'Invalid vendor profile or permission denied.']);
        }

        // --- Sanitize and Save Fields ---
        $fields_to_save = [
            'business_name'        => 'sanitize_text_field',
            'business_description' => 'wp_kses_post',
            'public_email'         => 'sanitize_email',
            'website_url'          => 'esc_url_raw',
            'facebook_url'         => 'esc_url_raw',
            'instagram_url'        => 'esc_url_raw',
            'coi_expiration_date'  => 'sanitize_text_field',
            'weekly_special_desc'  => 'wp_kses_post',
            'cdphp_participant'    => 'sanitize_text_field',
            'booth_number'         => 'sanitize_text_field',
        ];

        foreach ($fields_to_save as $key => $sanitizer) {
            if (isset($_POST[$key])) {
                update_post_meta($vendor_id, $key, call_user_func($sanitizer, $_POST[$key]));
            }
        }
        
        $products = isset($_POST['products_offered']) ? array_map('sanitize_text_field', $_POST['products_offered']) : [];
        update_post_meta($vendor_id, 'products_offered', $products);

        // --- Handle File Uploads ---
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        require_once(ABSPATH . 'wp-admin/includes/image.php');
        require_once(ABSPATH . 'wp-admin/includes/media.php');
        
        $file_fields = ['business_logo', 'coi_document', 'weekly_special_image'];
        foreach ($file_fields as $field) {
            if (isset($_FILES[$field]) && !empty($_FILES[$field]['name'])) {
                $attachment_id = media_handle_upload($field, $vendor_id);
                if (!is_wp_error($attachment_id)) {
                    update_post_meta($vendor_id, $field, $attachment_id);
                }
            }
        }

        wp_send_json_success(['message' => 'Profile updated successfully!']);
    }
    
    public function handle_status_change_notifications($post_id, $post, $update) {
        if (wp_is_post_revision($post_id) || !$update) {
            return;
        }

        $old_status = get_post_meta($post_id, '_previous_status', true);
        $new_status = $post->post_status;

        if ($old_status !== $new_status) {
            $notification_manager = new NotificationManager();
            $notification_manager->send_status_change_email($post_id, $new_status, $old_status);
        }
        
        update_post_meta($post_id, '_previous_status', $new_status);
    }
}
