<?php
declare(strict_types=1);

namespace HPCMS\Core;

defined('ABSPATH') || exit;

class Settings {

    /**
     * All managed option groups and their empty defaults.
     */
    private static array $groups = [
        'hpcms_general'  => [],
        'hpcms_homepage' => [],
        'hpcms_social'   => [],
        'hpcms_seo'      => [],
    ];

    public static function init(): void {
        add_action( 'admin_init', [ __CLASS__, 'register_settings' ] );

        // Bust the /main response cache whenever any grouped option is saved.
        add_action( 'update_option_hpcms_general',  [ __CLASS__, 'bust_main_cache' ] );
        add_action( 'update_option_hpcms_social',   [ __CLASS__, 'bust_main_cache' ] );
        add_action( 'update_option_hpcms_seo',      [ __CLASS__, 'bust_main_cache' ] );
        add_action( 'update_option_hpcms_homepage', [ __CLASS__, 'bust_main_cache' ] );
    }

    public static function bust_main_cache(): void {
        delete_transient( 'hpcms_main_response' );
    }

    /**
     * Get a full option group as an array.
     * Returns the stored value merged over defaults so keys are always present.
     */
    public static function get_group( string $group ): array {
        $defaults = self::$groups[ $group ] ?? [];
        $stored   = get_option( $group, [] );
        return wp_parse_args( (array) $stored, $defaults );
    }

    /**
     * Get a single field from a group.
     *
     * @param mixed $default
     * @return mixed
     */
    public static function get( string $group, string $key, mixed $default = '' ): mixed {
        $data = self::get_group( $group );
        return $data[ $key ] ?? $default;
    }

    /**
     * Save an entire group at once (call after sanitization).
     */
    public static function save_group( string $group, array $data ): bool {
        return update_option( $group, $data );
    }

    /**
     * Register the legacy flat settings (kept for the API/CORS tab which is untouched).
     */
    public static function register_settings(): void {
        // API & CORS (Configuration tab) — untouched in v1.1.0.
        register_setting( 'hpcms_settings_api', 'hpcms_enable_api',      [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '1' ] );
        register_setting( 'hpcms_settings_api', 'hpcms_enable_cors',     [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '1' ] );
        register_setting( 'hpcms_settings_api', 'hpcms_allowed_origins', [ 'sanitize_callback' => 'sanitize_textarea_field', 'default' => "http://localhost:3000\nhttp://localhost:8000" ] );
        register_setting( 'hpcms_settings_api', 'hpcms_cache_duration',  [ 'sanitize_callback' => 'absint', 'default' => 3600 ] );
        register_setting( 'hpcms_settings_api', 'hpcms_api_token',       [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
        register_setting( 'hpcms_settings_api', 'hpcms_frontend_url',    [ 'sanitize_callback' => 'esc_url_raw', 'default' => '' ] );
        register_setting( 'hpcms_settings_api', 'hpcms_revalidate_token', [ 'sanitize_callback' => 'sanitize_text_field', 'default' => '' ] );
        register_setting( 'hpcms_settings_api', 'hpcms_contact_email',   [ 'sanitize_callback' => 'sanitize_email', 'default' => '' ] );
    }

    /**
     * Legacy accessor — still used by Profile API endpoint (deprecated, removed in v1.3.0).
     */
    public static function get_profile(): array {
        $general = self::get_group( 'hpcms_general' );
        $social  = self::get_group( 'hpcms_social' );
        $seo     = self::get_group( 'hpcms_seo' );

        return [
            'name'     => esc_html( $general['name'] ?? '' ),
            'tagline'  => esc_html( $general['tagline'] ?? '' ),
            'email'    => sanitize_email( $general['email'] ?? '' ),
            'phone'    => esc_html( $general['phone'] ?? '' ),
            'location' => esc_html( isset( $general['locations'][0]['value'] ) ? $general['locations'][0]['value'] : '' ),
            'social'   => [
                'github'   => esc_url( $social['github'] ?? '' ),
                'linkedin' => esc_url( $social['linkedin'] ?? '' ),
                'twitter'  => esc_url( $social['x'] ?? '' ),
                'youtube'  => esc_url( $social['youtube'] ?? '' ),
                'behance'  => esc_url( $social['behance'] ?? '' ),
                'dribbble' => esc_url( $social['dribbble'] ?? '' ),
            ],
            'seo' => [
                'title'       => esc_html( $seo['meta_title'] ?? '' ),
                'description' => esc_html( $seo['meta_description'] ?? '' ),
                'ogImage'     => $seo['og_image_id'] ?? 0
                    ? esc_url( (string) wp_get_attachment_url( absint( $seo['og_image_id'] ) ) )
                    : '',
            ],
        ];
    }

    public static function get_allowed_origins(): array {
        $raw   = get_option( 'hpcms_allowed_origins', 'http://localhost:3000' );
        $lines = array_filter( array_map( 'trim', explode( "\n", $raw ) ) );
        return array_values( $lines );
    }
}
