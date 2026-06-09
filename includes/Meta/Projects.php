<?php
namespace HPCMS\Meta;

defined( 'ABSPATH' ) || exit;

class Projects {
    public static function register(): void {
        $fields = [
            '_hpcms_client_name'      => 'string',
            '_hpcms_project_url'      => 'string',
            '_hpcms_github_url'       => 'string',
            '_hpcms_completion_date'  => 'string',
            '_hpcms_featured'         => 'boolean',
            '_hpcms_tech_stack'       => 'string',
            '_hpcms_key_results'      => 'string',
            '_hpcms_gallery'          => 'string',
            '_hpcms_seo_title'        => 'string',
            '_hpcms_seo_description'  => 'string',
        ];
        foreach ( $fields as $key => $type ) {
            $sanitize = 'sanitize_text_field';
            if ( $type === 'boolean' ) {
                $sanitize = 'rest_sanitize_boolean';
            } elseif ( in_array( $key, [ '_hpcms_key_results', '_hpcms_seo_description', '_hpcms_gallery' ], true ) ) {
                $sanitize = 'sanitize_textarea_field';
            }

            register_post_meta( 'hpcms_project', $key, [
                'type'              => $type,
                'single'            => true,
                'show_in_rest'      => true,
                'sanitize_callback' => $sanitize,
                'default'           => $type === 'boolean' ? false : '',
            ] );
        }
    }

    public static function render_box( \WP_Post $post ): void {
        wp_nonce_field( 'hpcms_save_meta', 'hpcms_nonce' );
        $fields = [
            '_hpcms_client_name'     => [ 'label' => 'Client Name',          'type' => 'text',   'placeholder' => 'e.g. Acme Corp' ],
            '_hpcms_project_url'     => [ 'label' => 'Project / Live URL',   'type' => 'url',    'placeholder' => 'https://example.com' ],
            '_hpcms_github_url'      => [ 'label' => 'GitHub URL',           'type' => 'url',    'placeholder' => 'https://github.com/...' ],
            '_hpcms_completion_date' => [ 'label' => 'Completion Date',      'type' => 'text',   'placeholder' => 'e.g. 2024-03' ],
            '_hpcms_tech_stack'      => [ 'label' => 'Tech Stack (comma-separated)', 'type' => 'text', 'placeholder' => 'React, Next.js, Tailwind' ],
            '_hpcms_key_results'     => [ 'label' => 'Key Results (one per line)', 'type' => 'textarea', 'placeholder' => "+40% Organic Traffic\n50+ Leads Generated" ],
            '_hpcms_seo_title'       => [ 'label' => 'SEO Title',            'type' => 'text',   'placeholder' => 'Custom meta title' ],
            '_hpcms_seo_description' => [ 'label' => 'SEO Description',      'type' => 'textarea', 'placeholder' => 'Custom meta description' ],
            '_hpcms_gallery'         => [ 'label' => 'Gallery URLs (JSON array)', 'type' => 'textarea', 'placeholder' => '["https://..."]' ],
        ];
        Helper::render_fields( $post, $fields );

        $featured = get_post_meta( $post->ID, '_hpcms_featured', true );
        echo '<p><label><input type="checkbox" name="_hpcms_featured" value="1"' . checked( $featured, true, false ) . '> Production Status </label></p>';
    }
}
