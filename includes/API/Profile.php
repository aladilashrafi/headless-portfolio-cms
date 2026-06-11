<?php
declare(strict_types=1);

namespace HPCMS\API;

defined('ABSPATH') || exit;

use HPCMS\Core\Settings;

/**
 * /profile endpoint — deprecated since v1.1.0.
 * Use /main instead. Will be removed in v1.3.0.
 */
class Profile {

    public static function register_routes( string $ns ): void {
        register_rest_route( $ns, '/profile', [
            'methods'             => 'GET',
            'callback'            => [ __CLASS__, 'get_item' ],
            'permission_callback' => [ Registry::class, 'check_public_read_permission' ],
        ] );
    }

    public static function get_item( \WP_REST_Request $req ): \WP_REST_Response {
        $response = new \WP_REST_Response( Settings::get_profile(), 200 );
        $response->header( 'X-HPCMS-Deprecated', 'This endpoint is deprecated since v1.1.0. Use /main instead. Will be removed in v1.3.0.' );
        return $response;
    }
}
