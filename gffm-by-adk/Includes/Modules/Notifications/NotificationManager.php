<?php
/**
 * Manages all email notifications sent by the plugin.
 *
 * @since      3.0.0
 * @package    GFFM
 * @author     ADK Web Solutions
 */
namespace GFFM\Includes\Modules\Notifications;

class NotificationManager {

    /**
     * Sends an email to a vendor when their application status changes.
     *
     * @param int    $vendor_id   The ID of the vendor post.
     * @param string $new_status  The new post status.
     * @param string $old_status  The old post status.
     */
    public function send_status_change_email($vendor_id, $new_status, $old_status) {
        $vendor_user_id = get_post_field('post_author', $vendor_id);
        $user_info = get_userdata($vendor_user_id);
        if (!$user_info) {
            return;
        }

        $vendor_email = $user_info->user_email;
        $vendor_name = get_the_title($vendor_id);
        $subject = '';
        $message = '';
        $dashboard_link = home_url('/vendor-dashboard/'); // Assuming a page with this slug exists

        // Determine the email content based on the status change
        switch ($new_status) {
            case 'publish':
                $subject = __('Your Vendor Application has been Approved!', 'gffm-plugin');
                $message = sprintf(
                    __('<p>Hello %s,</p><p>Congratulations! Your application to the Glens Falls Farmers\' Market has been approved. Your profile is now live in our public directory.</p><p>You can manage your profile by logging into your dashboard: <a href="%s">Vendor Dashboard</a></p>', 'gffm-plugin'),
                    $vendor_name,
                    esc_url($dashboard_link)
                );
                break;
            case 'pending':
                $subject = __('Your Vendor Application is Under Review', 'gffm-plugin');
                $message = sprintf(
                    __('<p>Hello %s,</p><p>Thank you for your application. Your profile is now under review by the market administrator. We will notify you once a decision has been made.</p>', 'gffm-plugin'),
                    $vendor_name
                );
                break;
            case 'draft':
                // Do not send email for drafts by default.
                return;
        }

        if (!empty($subject) && !empty($message)) {
            $this->send_email($vendor_email, $subject, $message);
        }
    }

    /**
     * Queries for vendors with expiring or expired COIs and sends reminders.
     * Called by a daily cron job.
     */
    public function send_coi_expiration_reminders() {
        $today = new \DateTime();
        $thirty_days = new \DateTime('+30 days');
        $dashboard_link = home_url('/vendor-dashboard/');

        $args = [
            'post_type' => 'gffm_vendor',
            'posts_per_page' => -1,
            'post_status' => 'publish',
            'meta_query' => [
                'relation' => 'OR',
                [
                    // Expired
                    'key' => 'coi_expiration_date',
                    'value' => $today->format('Y-m-d'),
                    'compare' => '<',
                    'type' => 'DATE'
                ],
                [
                    // Expiring within 30 days
                    'key' => 'coi_expiration_date',
                    'value' => $thirty_days->format('Y-m-d'),
                    'compare' => '<=',
                    'type' => 'DATE'
                ]
            ]
        ];

        $vendors_to_notify = new \WP_Query($args);

        if ($vendors_to_notify->have_posts()) {
            while ($vendors_to_notify->have_posts()) {
                $vendors_to_notify->the_post();
                $vendor_id = get_the_ID();
                $vendor_name = get_the_title();
                $vendor_user_id = get_post_field('post_author', $vendor_id);
                $user_info = get_userdata($vendor_user_id);
                if (!$user_info) continue;

                $vendor_email = $user_info->user_email;
                $coi_date_str = get_post_meta($vendor_id, 'coi_expiration_date', true);
                
                try {
                    $coi_date = new \DateTime($coi_date_str);

                    if ($coi_date < $today) {
                        // Expired
                        $subject = __('URGENT: Your Certificate of Insurance has Expired', 'gffm-plugin');
                        $message = sprintf(
                            __('<p>Hello %s,</p><p>This is an urgent reminder that your Certificate of Insurance (COI) on file with the market expired on %s. Your profile may be hidden from the public directory until an updated document is provided.</p><p>Please log in to your dashboard to upload a new COI immediately: <a href="%s">Vendor Dashboard</a></p>', 'gffm-plugin'),
                            $vendor_name,
                            $coi_date->format('F j, Y'),
                            esc_url($dashboard_link)
                        );
                    } else {
                        // Expiring soon
                        $subject = __('Reminder: Your Certificate of Insurance is Expiring Soon', 'gffm-plugin');
                         $message = sprintf(
                            __('<p>Hello %s,</p><p>This is a friendly reminder that your Certificate of Insurance (COI) on file with the market will expire on %s.</p><p>Please log in to your dashboard to upload a new document to ensure your profile remains active: <a href="%s">Vendor Dashboard</a></p>', 'gffm-plugin'),
                            $vendor_name,
                            $coi_date->format('F j, Y'),
                            esc_url($dashboard_link)
                        );
                    }
                    $this->send_email($vendor_email, $subject, $message);
                } catch (\Exception $e) {
                    // Log error if date is invalid, but continue the loop
                    error_log('GFFM Plugin: Could not parse COI date for vendor ID ' . $vendor_id);
                    continue;
                }
            }
        }
        wp_reset_postdata();
    }

    /**
     * Helper function to send emails and CC the admin.
     *
     * @param string $to The recipient's email address.
     * @param string $subject The email subject.
     * @param string $message The email body.
     */
    private function send_email($to, $subject, $message) {
        $admin_email = get_option('admin_email');
        $headers = ['Content-Type: text/html; charset=UTF-8'];
        
        // Add admin email as CC
        if ($admin_email && $admin_email !== $to) {
            $headers[] = 'Cc: ' . get_bloginfo('name') . ' Admin <' . $admin_email . '>';
        }

        wp_mail($to, '[GFFM] ' . $subject, $message, $headers);
    }
}
