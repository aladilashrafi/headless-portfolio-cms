<?php
declare(strict_types=1);

namespace HPCMS\Core;

defined('ABSPATH') || exit;

class Migrator {

    private const MIGRATION_FLAG = 'hpcms_migration_v110_done';

    public static function run(): void {
        if ( get_option( self::MIGRATION_FLAG ) ) {
            return; // Already ran — bail early.
        }

        self::migrate_general();
        self::migrate_social();
        self::migrate_seo();

        update_option( self::MIGRATION_FLAG, true );
    }

    private static function migrate_general(): void {
        // Map old flat keys -> new grouped keys.
        $old_map = [
            'full_name' => 'hpcms_full_name',
            'tagline'   => 'hpcms_tagline',
            'email'     => 'hpcms_email',
            'phone'     => 'hpcms_phone',
        ];

        $general = [];
        foreach ( $old_map as $new_key => $old_option ) {
            $val = get_option( $old_option, '' );
            if ( $val !== '' ) {
                $general[ $new_key ] = $val;
                delete_option( $old_option );
            }
        }

        // Old location was a plain string — migrate to the new repeatable format.
        $old_location = get_option( 'hpcms_location', '' );
        if ( $old_location !== '' ) {
            $general['locations'] = [
                [ 'id' => 'loc_' . time(), 'value' => $old_location ],
            ];
            delete_option( 'hpcms_location' );
        }

        if ( ! empty( $general ) ) {
            // Merge over whatever may already exist (safe re-run).
            $existing = get_option( 'hpcms_general', [] );
            update_option( 'hpcms_general', array_merge( $general, (array) $existing ) );
        }
    }

    private static function migrate_social(): void {
        $old_map = [
            'github'   => 'hpcms_github_url',
            'linkedin' => 'hpcms_linkedin_url',
            'x'        => 'hpcms_twitter_url',
            'youtube'  => 'hpcms_youtube_url',
            'behance'  => 'hpcms_behance_url',
        ];

        // Also check legacy keys used in v1.0.0 (without _url suffix).
        $old_map_v1 = [
            'github'   => 'hpcms_github',
            'linkedin' => 'hpcms_linkedin',
            'x'        => 'hpcms_twitter',
            'youtube'  => 'hpcms_youtube',
            'behance'  => 'hpcms_behance',
            'dribbble' => 'hpcms_dribbble',
        ];

        $social = [];

        foreach ( $old_map as $new_key => $old_option ) {
            $val = get_option( $old_option, '' );
            if ( $val !== '' ) {
                $social[ $new_key ] = $val;
                delete_option( $old_option );
            }
        }

        foreach ( $old_map_v1 as $new_key => $old_option ) {
            if ( ! isset( $social[ $new_key ] ) ) {
                $val = get_option( $old_option, '' );
                if ( $val !== '' ) {
                    $social[ $new_key ] = $val;
                    delete_option( $old_option );
                }
            }
        }

        if ( ! empty( $social ) ) {
            $existing = get_option( 'hpcms_social', [] );
            update_option( 'hpcms_social', array_merge( $social, (array) $existing ) );
        }
    }

    private static function migrate_seo(): void {
        $old_map = [
            'meta_title'       => 'hpcms_seo_title',
            'meta_description' => 'hpcms_seo_description',
        ];

        // Also check legacy keys used in v1.0.0.
        $old_map_v1 = [
            'meta_title'       => 'hpcms_meta_title',
            'meta_description' => 'hpcms_meta_description',
        ];

        $seo = [];

        foreach ( $old_map as $new_key => $old_option ) {
            $val = get_option( $old_option, '' );
            if ( $val !== '' ) {
                $seo[ $new_key ] = $val;
                delete_option( $old_option );
            }
        }

        foreach ( $old_map_v1 as $new_key => $old_option ) {
            if ( ! isset( $seo[ $new_key ] ) ) {
                $val = get_option( $old_option, '' );
                if ( $val !== '' ) {
                    $seo[ $new_key ] = $val;
                    delete_option( $old_option );
                }
            }
        }

        if ( ! empty( $seo ) ) {
            $existing = get_option( 'hpcms_seo', [] );
            update_option( 'hpcms_seo', array_merge( $seo, (array) $existing ) );
        }
    }
}
