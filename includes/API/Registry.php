<?php
declare(strict_types=1);

namespace HPCMS\API;

defined('ABSPATH') || exit;

class Registry {

    public static function init(): void {
        add_action( 'rest_api_init', [ __CLASS__, 'register_all' ] );
    }

    public static function register_all(): void {
        register_rest_route( 'hpcms/v1', '/test', [
            'methods'             => 'GET',
            'callback'            => function () { return [ 'status' => 'ok' ]; },
            'permission_callback' => '__return_true',
        ] );

        self::init_content_filters();

        register_rest_route( 'hpcms/v1', '/ping', [
            'methods'             => 'GET',
            'callback'            => function () { return [ 'pong' => true, 'time' => time() ]; },
            'permission_callback' => '__return_true',
        ] );

        $ns = HPCMS_API_NS;

        Projects::register_routes( $ns );
        Experience::register_routes( $ns );
        Education::register_routes( $ns );
        Resume::register_routes( $ns );
        Skills::register_routes( $ns );
        Testimonials::register_routes( $ns );
        Services::register_routes( $ns );
        Contact::register_routes( $ns );
        Clients::register_routes( $ns );

        // Deprecated in v1.1.0 — returns X-HPCMS-Deprecated header. Removed in v1.3.0.
        Profile::register_routes( $ns );

        // New in v1.1.0.
        MainController::register_routes();
    }

    private static function init_content_filters(): void {
        $filters = [
            'wptexturize',
            'convert_chars',
            'wpautop',
            'shortcode_unautop',
            'do_shortcode',
        ];
        foreach ( $filters as $filter ) {
            add_filter( 'hpcms_content', $filter );
        }
    }

    public static function check_public_read_permission(): bool {
        return true;
    }
}
