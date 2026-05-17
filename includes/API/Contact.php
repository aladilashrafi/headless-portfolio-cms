<?php
namespace HPCMS\API;

defined( 'ABSPATH' ) || exit;

class Contact {
    public static function register_routes( string $ns ): void {
        register_rest_route( $ns, '/contact', [
            'methods'             => 'POST',
            'callback'            => [ __CLASS__, 'handle_submission' ],
            // This is a public contact form endpoint — unauthenticated submissions are intentional.
            'permission_callback' => '__return_true',
        ] );
    }

    public static function handle_submission( \WP_REST_Request $req ) {
        $params = $req->get_json_params();
        
        $name    = sanitize_text_field( $params['name']    ?? '' );
        $email   = sanitize_email( $params['email']       ?? '' );
        $subject = sanitize_text_field( $params['subject'] ?? '' );
        $message = sanitize_textarea_field( $params['message'] ?? '' );
        $budget  = sanitize_text_field( $params['budget']  ?? '' );

        if ( ! $subject ) {
            $subject = __( 'New Website Inquiry', 'headless-portfolio-cms' );
        }

        if ( ! $name || ! is_email( $email ) || strlen( $message ) < 10 ) {
            return new \WP_Error( 'hpcms_invalid_data', 'Please fill all required fields correctly (Message must be at least 10 characters).', [ 'status' => 422 ] );
        }

        // Rate limiting
        $ip = isset( $_SERVER['REMOTE_ADDR'] ) ? sanitize_text_field( wp_unslash( $_SERVER['REMOTE_ADDR'] ) ) : '0.0.0.0';
        $transient_key = 'hpcms_contact_limit_' . md5( $ip );
        $attempts = (int) get_transient( $transient_key );
        
        if ( $attempts >= 5 ) {
            return new \WP_Error( 'hpcms_too_many_requests', 'Too many requests. Please try again in an hour.', [ 'status' => 429 ] );
        }
        set_transient( $transient_key, $attempts + 1, HOUR_IN_SECONDS );

        // Save to Inbox
        $post_id = wp_insert_post( [
            'post_type'   => 'hpcms_contact_log',
            'post_title'  => sprintf( '[%s] %s', $name, $subject ),
            'post_status' => 'publish',
        ] );

        if ( is_wp_error( $post_id ) ) {
            return new \WP_Error( 'hpcms_save_failed', 'Could not save message.', [ 'status' => 500 ] );
        }

        update_post_meta( $post_id, '_hpcms_contact_name',    $name );
        update_post_meta( $post_id, '_hpcms_contact_email',   $email );
        update_post_meta( $post_id, '_hpcms_contact_subject', $subject );
        update_post_meta( $post_id, '_hpcms_contact_message', $message );
        update_post_meta( $post_id, '_hpcms_contact_budget',  $budget );
        update_post_meta( $post_id, '_hpcms_contact_ip',      $ip );
        update_post_meta( $post_id, '_hpcms_contact_read',    '0' );

        // Send Email
        $admin_email = get_option( 'hpcms_contact_email', get_option( 'admin_email' ) );
        $headers = [ 'Reply-To: ' . $name . ' <' . $email . '>' ];
        $body = "Name: $name\nEmail: $email\nSubject: $subject\nBudget: $budget\n\nMessage:\n$message";
        
        wp_mail( $admin_email, "New Portfolio Inquiry: $subject", $body, $headers );

        return new \WP_REST_Response( [
            'success' => true,
            'message' => "Thanks $name! Your message has been received. I'll get back to you shortly.",
        ], 200 );
    }
}
