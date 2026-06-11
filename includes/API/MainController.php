<?php
declare(strict_types=1);

namespace HPCMS\API;

defined('ABSPATH') || exit;

use HPCMS\Core\Settings;
use WP_REST_Request;
use WP_REST_Response;

class MainController {

    public static function register_routes(): void {
        register_rest_route(
            HPCMS_API_NS,
            '/main',
            [
                'methods'             => 'GET',
                'callback'            => [ self::class, 'get_data' ],
                'permission_callback' => '__return_true', // Read-only public endpoint.
            ]
        );
    }

    public static function get_data( WP_REST_Request $request ): WP_REST_Response {
        // Check transient cache first.
        $cache_key = 'hpcms_main_response';
        $cached    = get_transient( $cache_key );

        if ( false !== $cached ) {
            return new WP_REST_Response( $cached, 200 );
        }

        $data = [
            'general'  => self::build_general(),
            'homepage' => self::build_homepage(),
            'social'   => self::build_social(),
            'seo'      => self::build_seo(),
        ];

        $cache_duration = absint( get_option( 'hpcms_cache_duration', 3600 ) );
        if ( $cache_duration > 0 ) {
            set_transient( $cache_key, $data, $cache_duration );
        }

        return new WP_REST_Response( $data, 200 );
    }

    private static function build_general(): array {
        $raw = Settings::get_group( 'hpcms_general' );

        $favicon_id  = absint( $raw['favicon_id'] ?? 0 );
        $favicon_url = $favicon_id ? (string) wp_get_attachment_url( $favicon_id ) : '';

        return [
            'name'          => esc_html( $raw['name'] ?? '' ),
            'tagline'       => esc_html( $raw['tagline'] ?? '' ),
            'email'         => sanitize_email( $raw['email'] ?? '' ),
            'phone'         => esc_html( $raw['phone'] ?? '' ),
            'locations'     => self::sanitize_locations( $raw['locations'] ?? [] ),
            'favicon'       => esc_url( $favicon_url ),
            'header_button' => [
                'text' => esc_html( $raw['header_button_text'] ?? '' ),
                'url'  => esc_url( $raw['header_button_url'] ?? '' ),
            ],
            'footer_text'   => wp_kses_post( $raw['footer_text_raw'] ?? '' ),
        ];
    }

    private static function build_homepage(): array {
        // In v1.1.0 this returns an empty array.
        // Fully populated in v1.2.0 (Phase 8).
        $raw = Settings::get_group( 'hpcms_homepage' );
        if ( empty( $raw ) ) {
            return [];
        }
        return $raw;
    }

    private static function build_social(): array {
        $raw       = Settings::get_group( 'hpcms_social' );
        $platforms = [
            'linkedin', 'github', 'behance', 'dribbble',
            'gravatar', 'wordpress_org', 'youtube', 'x',
            'facebook', 'instagram', 'whatsapp',
        ];

        $social = [];
        foreach ( $platforms as $key ) {
            $social[ $key ] = isset( $raw[ $key ] ) ? esc_url( $raw[ $key ] ) : '';
        }
        return $social;
    }

    private static function build_seo(): array {
        $raw       = Settings::get_group( 'hpcms_seo' );
        $og_img_id = absint( $raw['og_image_id'] ?? 0 );

        return [
            'meta_title'       => esc_html( $raw['meta_title'] ?? '' ),
            'meta_description' => esc_html( $raw['meta_description'] ?? '' ),
            'og_image'         => $og_img_id ? esc_url( (string) wp_get_attachment_url( $og_img_id ) ) : '',
        ];
    }

    private static function sanitize_locations( array $locations ): array {
        $clean = [];
        foreach ( $locations as $loc ) {
            if ( empty( $loc['value'] ) ) {
                continue;
            }
            $clean[] = [
                'id'    => sanitize_key( $loc['id'] ?? '' ),
                'value' => esc_html( $loc['value'] ),
            ];
        }
        return $clean;
    }
}
