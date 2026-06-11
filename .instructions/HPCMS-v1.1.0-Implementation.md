# Headless Portfolio CMS — v1.1.0 Implementation
## Phases 1–6: Architecture Refactor, Settings Tabs, `/main` Endpoint (Partial)

**Version:** v1.1.0  
**Plugin Namespace:** `HPCMS\`  
**API Namespace:** `hpcms/v1`

---

## Table of Contents

1. [Phase 1 — Architecture Refactor](#phase-1--architecture-refactor)
2. [Phase 2 — General Tab](#phase-2--general-tab)
3. [Phase 3 — Social Links Tab](#phase-3--social-links-tab)
4. [Phase 4 — SEO Tab](#phase-4--seo-tab)
5. [Phase 5 — Configuration Tab Rename](#phase-5--configuration-tab-rename)
6. [Phase 6 — `/main` Endpoint (Partial)](#phase-6--main-endpoint-partial)
7. [Admin Menu Wiring](#admin-menu-wiring)
8. [Admin JS](#admin-js)
9. [Admin CSS](#admin-css)
10. [Bootstrap Integration](#bootstrap-integration)
11. [File Creation Summary](#file-creation-summary)

---

## Phase 1 — Architecture Refactor

This phase touches no UI. It restructures how settings are stored so all future phases have a clean foundation.

### 1.1 Settings Helper Class

**File:** `includes/Core/Settings.php`

Extend the existing `HPCMS\Core\Settings` class with a clean static accessor and a grouped save method.

```php
<?php
declare(strict_types=1);

namespace HPCMS\Core;

defined('ABSPATH') || exit;

class Settings {

    /**
     * All managed option keys and their defaults.
     */
    private static array $groups = [
        'hpcms_general'  => [],
        'hpcms_homepage' => [],
        'hpcms_social'   => [],
        'hpcms_seo'      => [],
    ];

    public static function init(): void {
        // Existing hooks remain here (admin_init, etc.)
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
}
```

### 1.2 Migration Routine

Run once on plugin update. Reads old flat keys, writes them into the new grouped structure, then deletes the old keys.

**File:** `includes/Core/Migrator.php`

```php
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

        $social = [];
        foreach ( $old_map as $new_key => $old_option ) {
            $val = get_option( $old_option, '' );
            if ( $val !== '' ) {
                $social[ $new_key ] = $val;
                delete_option( $old_option );
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

        $seo = [];
        foreach ( $old_map as $new_key => $old_option ) {
            $val = get_option( $old_option, '' );
            if ( $val !== '' ) {
                $seo[ $new_key ] = $val;
                delete_option( $old_option );
            }
        }

        if ( ! empty( $seo ) ) {
            $existing = get_option( 'hpcms_seo', [] );
            update_option( 'hpcms_seo', array_merge( $seo, (array) $existing ) );
        }
    }
}
```

**Hook the migrator into the bootstrap — add this to `hpcms_bootstrap()` in the main plugin file:**

```php
// In headless-portfolio-cms.php, inside hpcms_bootstrap():
\HPCMS\Core\Migrator::run();
```

It checks its own flag first so it's a no-op on every request after the first run.

### 1.3 Update `uninstall.php`

Add the new option keys to the deletion list so a clean uninstall removes everything:

```php
// Add to the existing delete_option calls in uninstall.php:
delete_option( 'hpcms_general' );
delete_option( 'hpcms_homepage' );
delete_option( 'hpcms_social' );
delete_option( 'hpcms_seo' );
delete_option( 'hpcms_migration_v110_done' );
```

---

## Phase 2 — General Tab

**File:** `includes/Admin/Pages/GeneralPage.php`

```php
<?php
declare(strict_types=1);

namespace HPCMS\Admin\Pages;

defined('ABSPATH') || exit;

use HPCMS\Core\Settings;

class GeneralPage {

    public static function init(): void {
        add_action( 'admin_init', [ self::class, 'register_settings' ] );
    }

    public static function register_settings(): void {
        register_setting(
            'hpcms_general_group',
            'hpcms_general',
            [ self::class, 'sanitize' ]
        );
    }

    public static function sanitize( mixed $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }

        $clean = [];

        $text_fields = [ 'name', 'tagline', 'email', 'phone', 'header_button_text', 'footer_text_raw' ];
        foreach ( $text_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $clean[ $field ] = 'footer_text_raw' === $field
                    ? wp_kses_post( $input[ $field ] )
                    : sanitize_text_field( $input[ $field ] );
            }
        }

        if ( isset( $input['email'] ) ) {
            $clean['email'] = sanitize_email( $input['email'] );
        }

        if ( isset( $input['header_button_url'] ) ) {
            $clean['header_button_url'] = esc_url_raw( $input['header_button_url'] );
        }

        if ( isset( $input['favicon_id'] ) ) {
            $clean['favicon_id'] = absint( $input['favicon_id'] );
        }

        $clean['locations'] = [];
        if ( ! empty( $input['locations'] ) && is_array( $input['locations'] ) ) {
            foreach ( $input['locations'] as $location ) {
                if ( empty( $location['value'] ) ) {
                    continue;
                }
                $clean['locations'][] = [
                    'id'    => sanitize_key( $location['id'] ?? 'loc_' . uniqid() ),
                    'value' => sanitize_text_field( $location['value'] ),
                ];
            }
        }

        return $clean;
    }

    public static function render(): void {
        $data = Settings::get_group( 'hpcms_general' );
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'hpcms_general_group' );
            wp_nonce_field( 'hpcms_general_save', 'hpcms_general_nonce' );
            ?>

            <table class="form-table" role="presentation">

                <!-- Name -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_name"><?php esc_html_e( 'Name', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="hpcms_name" name="hpcms_general[name]"
                               value="<?php echo esc_attr( $data['name'] ?? '' ); ?>"
                               class="regular-text" />
                    </td>
                </tr>

                <!-- Tagline -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_tagline"><?php esc_html_e( 'Tagline', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="hpcms_tagline" name="hpcms_general[tagline]"
                               value="<?php echo esc_attr( $data['tagline'] ?? '' ); ?>"
                               class="regular-text" />
                    </td>
                </tr>

                <!-- Email -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_email"><?php esc_html_e( 'Email', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <input type="email" id="hpcms_email" name="hpcms_general[email]"
                               value="<?php echo esc_attr( $data['email'] ?? '' ); ?>"
                               class="regular-text" />
                    </td>
                </tr>

                <!-- Phone -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_phone"><?php esc_html_e( 'Phone', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="hpcms_phone" name="hpcms_general[phone]"
                               value="<?php echo esc_attr( $data['phone'] ?? '' ); ?>"
                               class="regular-text" />
                    </td>
                </tr>

                <!-- Locations (repeatable) -->
                <tr>
                    <th scope="row"><?php esc_html_e( 'Locations', 'headless-portfolio-cms' ); ?></th>
                    <td>
                        <div id="hpcms-locations-wrap">
                            <?php
                            $locations = $data['locations'] ?? [];
                            if ( empty( $locations ) ) {
                                $locations = [ [ 'id' => 'loc_' . time(), 'value' => '' ] ];
                            }
                            foreach ( $locations as $i => $loc ) :
                            ?>
                            <div class="hpcms-repeatable-row" data-row="<?php echo esc_attr( (string) $i ); ?>">
                                <input type="hidden"
                                    name="hpcms_general[locations][<?php echo $i; ?>][id]"
                                    value="<?php echo esc_attr( $loc['id'] ); ?>"
                                    class="hpcms-loc-id"
                                />
                                <input type="text"
                                    name="hpcms_general[locations][<?php echo $i; ?>][value]"
                                    value="<?php echo esc_attr( $loc['value'] ); ?>"
                                    class="regular-text"
                                    placeholder="<?php esc_attr_e( 'e.g. Dhaka, Bangladesh', 'headless-portfolio-cms' ); ?>"
                                />
                                <button type="button" class="button hpcms-remove-row">
                                    <?php esc_html_e( 'Remove', 'headless-portfolio-cms' ); ?>
                                </button>
                            </div>
                            <?php endforeach; ?>
                        </div>
                        <button type="button" class="button hpcms-add-location">
                            <?php esc_html_e( '+ Add Location', 'headless-portfolio-cms' ); ?>
                        </button>
                        <p class="description">
                            <?php esc_html_e( 'Each location gets a unique ID used in the API response.', 'headless-portfolio-cms' ); ?>
                        </p>
                    </td>
                </tr>

                <!-- Favicon -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_favicon_id"><?php esc_html_e( 'Favicon', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <?php
                        $favicon_id  = absint( $data['favicon_id'] ?? 0 );
                        $favicon_url = $favicon_id ? wp_get_attachment_url( $favicon_id ) : '';
                        ?>
                        <input type="hidden" id="hpcms_favicon_id" name="hpcms_general[favicon_id]"
                               value="<?php echo esc_attr( (string) $favicon_id ); ?>" />
                        <div id="hpcms-favicon-preview" style="margin-bottom:8px;">
                            <?php if ( $favicon_url ) : ?>
                                <img src="<?php echo esc_url( $favicon_url ); ?>" style="max-width:64px;height:auto;" />
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button hpcms-media-picker"
                                data-target="hpcms_favicon_id"
                                data-preview="hpcms-favicon-preview"
                                data-title="<?php esc_attr_e( 'Select Favicon', 'headless-portfolio-cms' ); ?>">
                            <?php esc_html_e( 'Choose Image', 'headless-portfolio-cms' ); ?>
                        </button>
                        <?php if ( $favicon_url ) : ?>
                            <button type="button" class="button hpcms-media-remove"
                                    data-target="hpcms_favicon_id"
                                    data-preview="hpcms-favicon-preview">
                                <?php esc_html_e( 'Remove', 'headless-portfolio-cms' ); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>

                <!-- Header Button -->
                <tr>
                    <th scope="row"><?php esc_html_e( 'Header Button', 'headless-portfolio-cms' ); ?></th>
                    <td>
                        <input type="text"
                               name="hpcms_general[header_button_text]"
                               value="<?php echo esc_attr( $data['header_button_text'] ?? '' ); ?>"
                               class="regular-text"
                               placeholder="<?php esc_attr_e( 'Button Label', 'headless-portfolio-cms' ); ?>"
                        />
                        <input type="url"
                               name="hpcms_general[header_button_url]"
                               value="<?php echo esc_attr( $data['header_button_url'] ?? '' ); ?>"
                               class="regular-text"
                               style="margin-top:6px;"
                               placeholder="<?php esc_attr_e( 'https://...', 'headless-portfolio-cms' ); ?>"
                        />
                    </td>
                </tr>

                <!-- Footer Text (HTML) -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_footer_text"><?php esc_html_e( 'Footer Text', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <textarea id="hpcms_footer_text" name="hpcms_general[footer_text_raw]"
                                  rows="4" class="large-text"><?php echo wp_kses_post( $data['footer_text_raw'] ?? '' ); ?></textarea>
                        <p class="description">
                            <?php esc_html_e( 'Basic HTML is allowed (links, spans, etc.).', 'headless-portfolio-cms' ); ?>
                        </p>
                    </td>
                </tr>

            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }
}
```

---

## Phase 3 — Social Links Tab

**File:** `includes/Admin/Pages/SocialPage.php`

```php
<?php
declare(strict_types=1);

namespace HPCMS\Admin\Pages;

defined('ABSPATH') || exit;

use HPCMS\Core\Settings;

class SocialPage {

    private static array $platforms = [
        'linkedin'      => 'LinkedIn',
        'github'        => 'GitHub',
        'behance'       => 'Behance',
        'dribbble'      => 'Dribbble',
        'gravatar'      => 'Gravatar',
        'wordpress_org' => 'WordPress.org',
        'youtube'       => 'YouTube',
        'x'             => 'X / Twitter',
        'facebook'      => 'Facebook',
        'instagram'     => 'Instagram',
        'whatsapp'      => 'WhatsApp',
    ];

    public static function init(): void {
        add_action( 'admin_init', [ self::class, 'register_settings' ] );
    }

    public static function register_settings(): void {
        register_setting( 'hpcms_social_group', 'hpcms_social', [ self::class, 'sanitize' ] );
    }

    public static function sanitize( mixed $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }
        $clean = [];
        foreach ( array_keys( self::$platforms ) as $key ) {
            if ( isset( $input[ $key ] ) ) {
                $clean[ $key ] = esc_url_raw( $input[ $key ] );
            }
        }
        return $clean;
    }

    public static function render(): void {
        $data = Settings::get_group( 'hpcms_social' );
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'hpcms_social_group' );
            wp_nonce_field( 'hpcms_social_save', 'hpcms_social_nonce' );
            ?>
            <table class="form-table" role="presentation">
                <?php foreach ( self::$platforms as $key => $label ) : ?>
                <tr>
                    <th scope="row">
                        <label for="hpcms_social_<?php echo esc_attr( $key ); ?>">
                            <?php echo esc_html( $label ); ?>
                        </label>
                    </th>
                    <td>
                        <input type="url"
                               id="hpcms_social_<?php echo esc_attr( $key ); ?>"
                               name="hpcms_social[<?php echo esc_attr( $key ); ?>]"
                               value="<?php echo esc_attr( $data[ $key ] ?? '' ); ?>"
                               class="regular-text"
                               placeholder="https://..." />
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }
}
```

---

## Phase 4 — SEO Tab

**File:** `includes/Admin/Pages/SeoPage.php`

```php
<?php
declare(strict_types=1);

namespace HPCMS\Admin\Pages;

defined('ABSPATH') || exit;

use HPCMS\Core\Settings;

class SeoPage {

    public static function init(): void {
        add_action( 'admin_init', [ self::class, 'register_settings' ] );
    }

    public static function register_settings(): void {
        register_setting( 'hpcms_seo_group', 'hpcms_seo', [ self::class, 'sanitize' ] );
    }

    public static function sanitize( mixed $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }
        return [
            'meta_title'       => sanitize_text_field( $input['meta_title'] ?? '' ),
            'meta_description' => sanitize_textarea_field( $input['meta_description'] ?? '' ),
            'og_image_id'      => absint( $input['og_image_id'] ?? 0 ),
        ];
    }

    public static function render(): void {
        $data       = Settings::get_group( 'hpcms_seo' );
        $og_img_id  = absint( $data['og_image_id'] ?? 0 );
        $og_img_url = $og_img_id ? wp_get_attachment_url( $og_img_id ) : '';
        ?>
        <form method="post" action="options.php">
            <?php
            settings_fields( 'hpcms_seo_group' );
            wp_nonce_field( 'hpcms_seo_save', 'hpcms_seo_nonce' );
            ?>
            <table class="form-table" role="presentation">

                <tr>
                    <th scope="row">
                        <label for="hpcms_meta_title"><?php esc_html_e( 'Meta Title', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <input type="text" id="hpcms_meta_title"
                               name="hpcms_seo[meta_title]"
                               value="<?php echo esc_attr( $data['meta_title'] ?? '' ); ?>"
                               class="regular-text" />
                    </td>
                </tr>

                <tr>
                    <th scope="row">
                        <label for="hpcms_meta_desc"><?php esc_html_e( 'Meta Description', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <textarea id="hpcms_meta_desc" name="hpcms_seo[meta_description]"
                                  rows="3" class="large-text"><?php echo esc_textarea( $data['meta_description'] ?? '' ); ?></textarea>
                    </td>
                </tr>

                <tr>
                    <th scope="row"><?php esc_html_e( 'OG Image', 'headless-portfolio-cms' ); ?></th>
                    <td>
                        <input type="hidden" id="hpcms_og_image_id" name="hpcms_seo[og_image_id]"
                               value="<?php echo esc_attr( (string) $og_img_id ); ?>" />
                        <div id="hpcms-og-preview" style="margin-bottom:8px;">
                            <?php if ( $og_img_url ) : ?>
                                <img src="<?php echo esc_url( $og_img_url ); ?>" style="max-width:300px;height:auto;" />
                            <?php endif; ?>
                        </div>
                        <button type="button" class="button hpcms-media-picker"
                                data-target="hpcms_og_image_id"
                                data-preview="hpcms-og-preview"
                                data-title="<?php esc_attr_e( 'Select OG Image', 'headless-portfolio-cms' ); ?>">
                            <?php esc_html_e( 'Choose Image', 'headless-portfolio-cms' ); ?>
                        </button>
                        <?php if ( $og_img_url ) : ?>
                            <button type="button" class="button hpcms-media-remove"
                                    data-target="hpcms_og_image_id"
                                    data-preview="hpcms-og-preview">
                                <?php esc_html_e( 'Remove', 'headless-portfolio-cms' ); ?>
                            </button>
                        <?php endif; ?>
                    </td>
                </tr>

            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }
}
```

---

## Phase 5 — Configuration Tab Rename

This is a one-line string change in `HPCMS\Admin\Menu`. Find wherever the tab label `'API & CORS'` or `'API Settings'` is defined and change it:

```php
// Before:
__( 'API & CORS', 'headless-portfolio-cms' )

// After:
__( 'Configuration', 'headless-portfolio-cms' )
```

No data or logic changes. No migration needed.

---

## Phase 6 — `/main` Endpoint (Partial)

At this point, homepage data doesn't exist yet. The endpoint returns general, social, and seo. The `homepage` key is present but empty — this is intentional so frontends can code against the full shape immediately.

**File:** `includes/API/MainController.php`

```php
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
                'permission_callback' => '__return_true',
            ]
        );
    }

    public static function get_data( WP_REST_Request $request ): WP_REST_Response {
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
        $favicon_url = $favicon_id ? wp_get_attachment_url( $favicon_id ) : '';

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
        $raw      = Settings::get_group( 'hpcms_social' );
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
            'og_image'         => $og_img_id ? esc_url( wp_get_attachment_url( $og_img_id ) ) : '',
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
```

**Register in `HPCMS\API\Registry`:**

```php
// In includes/API/Registry.php, inside init():
add_action( 'rest_api_init', [ \HPCMS\API\MainController::class, 'register_routes' ] );
```

**Deprecate `/profile` — in the existing ProfileController:**

```php
public static function get_data( WP_REST_Request $request ): WP_REST_Response {
    $response = new WP_REST_Response( self::build_legacy_data(), 200 );
    $response->header( 'X-HPCMS-Deprecated', 'This endpoint is deprecated. Use /main instead.' );
    return $response;
}
```

**Invalidate the cache when settings are saved.** Add to `includes/Core/Settings.php` or each Page class:

```php
add_action( 'update_option_hpcms_general',  [ self::class, 'bust_main_cache' ] );
add_action( 'update_option_hpcms_social',   [ self::class, 'bust_main_cache' ] );
add_action( 'update_option_hpcms_seo',      [ self::class, 'bust_main_cache' ] );
add_action( 'update_option_hpcms_homepage', [ self::class, 'bust_main_cache' ] );

public static function bust_main_cache(): void {
    delete_transient( 'hpcms_main_response' );
}
```

---

## Admin Menu Wiring

**File:** `includes/Admin/Menu.php`

```php
<?php
declare(strict_types=1);

namespace HPCMS\Admin;

defined('ABSPATH') || exit;

use HPCMS\Admin\Pages\GeneralPage;
use HPCMS\Admin\Pages\SocialPage;
use HPCMS\Admin\Pages\SeoPage;
use HPCMS\Admin\Pages\HomepagePage;

class Menu {

    private static array $tabs = [
        'general'       => [ 'General',       GeneralPage::class ],
        'homepage'      => [ 'Home Page',     HomepagePage::class ],
        'social'        => [ 'Social Links',  SocialPage::class ],
        'seo'           => [ 'SEO',           SeoPage::class ],
        'configuration' => [ 'Configuration', null ], // Existing CORS/API tab — handled inline.
    ];

    public static function init(): void {
        add_action( 'admin_menu', [ self::class, 'register_menus' ] );
        add_action( 'admin_enqueue_scripts', [ self::class, 'enqueue_assets' ] );

        GeneralPage::init();
        SocialPage::init();
        SeoPage::init();
        HomepagePage::init();
    }

    public static function register_menus(): void {
        add_menu_page(
            __( 'Portfolio CMS', 'headless-portfolio-cms' ),
            __( 'Portfolio CMS', 'headless-portfolio-cms' ),
            'manage_options',
            'hpcms-settings',
            [ self::class, 'render_settings_page' ],
            'dashicons-portfolio',
            30
        );

        add_submenu_page(
            'hpcms-settings',
            __( 'Dashboard', 'headless-portfolio-cms' ),
            __( 'Dashboard', 'headless-portfolio-cms' ),
            'manage_options',
            'hpcms-settings',
            [ self::class, 'render_settings_page' ]
        );

        add_submenu_page(
            'hpcms-settings',
            __( 'API Reference', 'headless-portfolio-cms' ),
            __( 'API Reference', 'headless-portfolio-cms' ),
            'manage_options',
            'hpcms-api-reference',
            [ self::class, 'render_api_reference' ]
        );
    }

    public static function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'hpcms' ) === false ) {
            return;
        }

        wp_enqueue_media();

        wp_enqueue_script(
            'hpcms-admin-settings',
            HPCMS_PLUGIN_URL . 'assets/admin/settings.js',
            [],
            HPCMS_VERSION,
            true
        );

        wp_enqueue_style(
            'hpcms-admin-settings',
            HPCMS_PLUGIN_URL . 'assets/admin/settings.css',
            [],
            HPCMS_VERSION
        );
    }

    public static function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'headless-portfolio-cms' ) );
        }

        $active_tab = isset( $_GET['tab'] )
            ? sanitize_key( $_GET['tab'] )
            : 'general';

        if ( ! array_key_exists( $active_tab, self::$tabs ) ) {
            $active_tab = 'general';
        }

        ?>
        <div class="wrap hpcms-settings-wrap">
            <h1><?php esc_html_e( 'Portfolio CMS Settings', 'headless-portfolio-cms' ); ?></h1>

            <nav class="nav-tab-wrapper hpcms-tabs">
                <?php foreach ( self::$tabs as $slug => [ $label ] ) : ?>
                    <a href="<?php echo esc_url( admin_url( 'admin.php?page=hpcms-settings&tab=' . $slug ) ); ?>"
                       class="nav-tab <?php echo $active_tab === $slug ? 'nav-tab-active' : ''; ?>">
                        <?php echo esc_html( $label ); ?>
                    </a>
                <?php endforeach; ?>
            </nav>

            <div class="hpcms-tab-content">
                <?php
                settings_errors( 'hpcms_messages' );
                [ , $page_class ] = self::$tabs[ $active_tab ];

                if ( $page_class !== null && method_exists( $page_class, 'render' ) ) {
                    $page_class::render();
                } elseif ( $active_tab === 'configuration' ) {
                    self::render_configuration_tab();
                }
                ?>
            </div>
        </div>
        <?php
    }

    /**
     * The existing API & CORS tab — renamed to Configuration, content unchanged.
     * Move the existing render logic here from wherever it currently lives.
     */
    private static function render_configuration_tab(): void {
        // PASTE YOUR EXISTING API & CORS SETTINGS FORM HERE.
        // Only the tab label changed. No data or logic changes needed.
    }

    public static function render_api_reference(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'headless-portfolio-cms' ) );
        }
        // Existing API reference render logic.
        // In v1.3.0: add /main to the endpoint table, remove /profile.
    }
}
```

---

## Admin JS

**File:** `assets/admin/settings.js`  
All admin JS in a single file. No jQuery required — vanilla JS only.

```javascript
/* globals wp */
( function () {
    'use strict';

    // -------------------------------------------------------------------------
    // 1. Media Picker (single image — Favicon, OG Image, About Image, Contact Image)
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-media-picker' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const targetId  = btn.dataset.target;
            const previewId = btn.dataset.preview;
            const title     = btn.dataset.title || 'Select Image';

            const frame = wp.media( {
                title:    title,
                button:   { text: 'Use this image' },
                multiple: false,
                library:  { type: 'image' },
            } );

            frame.on( 'select', function () {
                const attachment = frame.state().get( 'selection' ).first().toJSON();
                document.getElementById( targetId ).value = attachment.id;

                const preview = document.getElementById( previewId );
                preview.innerHTML = '<img src="' + attachment.url + '" style="max-width:300px;height:auto;" />';
            } );

            frame.open();
        } );
    } );

    // -------------------------------------------------------------------------
    // 2. Media Remove Button
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-media-remove' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            document.getElementById( btn.dataset.target ).value = '';
            document.getElementById( btn.dataset.preview ).innerHTML = '';
        } );
    } );

    // -------------------------------------------------------------------------
    // 3. Gallery Picker (multiple images — Hero images)
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-gallery-picker' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const previewId = btn.dataset.preview;
            const inputName = btn.dataset.name;

            const frame = wp.media( {
                title:    'Select Images',
                button:   { text: 'Add to Gallery' },
                multiple: true,
                library:  { type: 'image' },
            } );

            frame.on( 'select', function () {
                const preview = document.getElementById( previewId );
                const selection = frame.state().get( 'selection' );

                selection.each( function ( attachment ) {
                    const att = attachment.toJSON();
                    const thumb = document.createElement( 'div' );
                    thumb.className = 'hpcms-gallery-thumb';
                    thumb.dataset.id = att.id;
                    thumb.innerHTML =
                        '<img src="' + att.url + '" />' +
                        '<input type="hidden" name="' + inputName + '" value="' + att.id + '" />' +
                        '<button type="button" class="hpcms-remove-gallery-item">✕</button>';
                    preview.appendChild( thumb );
                    bindRemoveGalleryItem( thumb.querySelector( '.hpcms-remove-gallery-item' ) );
                } );
            } );

            frame.open();
        } );
    } );

    function bindRemoveGalleryItem( btn ) {
        btn.addEventListener( 'click', function () {
            btn.closest( '.hpcms-gallery-thumb' ).remove();
        } );
    }

    document.querySelectorAll( '.hpcms-remove-gallery-item' ).forEach( bindRemoveGalleryItem );

    // -------------------------------------------------------------------------
    // 4. Repeatable Location Rows
    // -------------------------------------------------------------------------
    const locWrap = document.getElementById( 'hpcms-locations-wrap' );
    if ( locWrap ) {
        document.querySelector( '.hpcms-add-location' )?.addEventListener( 'click', function () {
            const rows    = locWrap.querySelectorAll( '.hpcms-repeatable-row' );
            const newIdx  = rows.length;
            const newId   = 'loc_' + Date.now();
            const row     = document.createElement( 'div' );
            row.className = 'hpcms-repeatable-row';
            row.innerHTML =
                '<input type="hidden" name="hpcms_general[locations][' + newIdx + '][id]" value="' + newId + '" class="hpcms-loc-id" />' +
                '<input type="text" name="hpcms_general[locations][' + newIdx + '][value]" value="" class="regular-text" placeholder="e.g. Remote" />' +
                '<button type="button" class="button hpcms-remove-row">Remove</button>';
            locWrap.appendChild( row );
            bindRemoveRow( row.querySelector( '.hpcms-remove-row' ) );
        } );

        function bindRemoveRow( btn ) {
            btn?.addEventListener( 'click', function () {
                btn.closest( '.hpcms-repeatable-row' ).remove();
                reindexRows( locWrap, 'hpcms_general[locations]' );
            } );
        }

        locWrap.querySelectorAll( '.hpcms-remove-row' ).forEach( bindRemoveRow );
    }

    // -------------------------------------------------------------------------
    // 5. Repeatable Highlighted Cards
    // -------------------------------------------------------------------------
    const cardsWrap = document.getElementById( 'hpcms-cards-wrap' );
    if ( cardsWrap ) {
        document.querySelector( '.hpcms-add-card' )?.addEventListener( 'click', function () {
            const rows   = cardsWrap.querySelectorAll( '.hpcms-card-row' );
            const newIdx = rows.length;
            const newId  = 'card_' + Date.now();
            const row    = document.createElement( 'div' );
            row.className = 'hpcms-card-row';
            row.innerHTML =
                '<input type="hidden" name="hpcms_homepage[highlighted_cards][cards][' + newIdx + '][id]" value="' + newId + '" />' +
                '<input type="text" name="hpcms_homepage[highlighted_cards][cards][' + newIdx + '][title]" placeholder="Card Title" class="regular-text" />' +
                '<input type="text" name="hpcms_homepage[highlighted_cards][cards][' + newIdx + '][subtitle]" placeholder="Card Subtitle" class="regular-text" />' +
                '<input type="text" name="hpcms_homepage[highlighted_cards][cards][' + newIdx + '][icon]" placeholder="Lucide name, SVG, or URL" class="regular-text" />' +
                '<button type="button" class="button hpcms-remove-card">Remove</button>';
            cardsWrap.appendChild( row );
        } );

        cardsWrap.querySelectorAll( '.hpcms-remove-card' ).forEach( function ( btn ) {
            btn.addEventListener( 'click', function () {
                btn.closest( '.hpcms-card-row' ).remove();
            } );
        } );
    }

    // -------------------------------------------------------------------------
    // 6. Accordion toggle for Home Page sections
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-accordion-toggle' ).forEach( function ( toggle ) {
        toggle.addEventListener( 'click', function () {
            const body = toggle.nextElementSibling;
            const icon = toggle.querySelector( '.hpcms-accordion-icon' );
            const isOpen = body.style.display !== 'none';
            body.style.display = isOpen ? 'none' : 'block';
            if ( icon ) icon.textContent = isOpen ? '▼' : '▲';
        } );
    } );

    // -------------------------------------------------------------------------
    // Helper: reindex array input names after a row is removed.
    // -------------------------------------------------------------------------
    function reindexRows( wrap, baseName ) {
        wrap.querySelectorAll( '.hpcms-repeatable-row' ).forEach( function ( row, i ) {
            row.querySelectorAll( 'input' ).forEach( function ( input ) {
                input.name = input.name.replace( /\[\d+\]/, '[' + i + ']' );
            } );
        } );
    }

    // -------------------------------------------------------------------------
    // 7. Repeatable Skill Tags
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-add-tag' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            const wrapId   = btn.dataset.wrap;
            const name     = btn.dataset.name;
            const wrap     = document.getElementById( wrapId );
            if ( ! wrap ) return;

            const row      = document.createElement( 'div' );
            row.className  = 'hpcms-tag-row';
            row.innerHTML  =
                '<input type="text" name="' + name + '" value="" class="regular-text" placeholder="e.g. React" />' +
                '<button type="button" class="button hpcms-remove-tag">Remove</button>';
            wrap.appendChild( row );

            row.querySelector( '.hpcms-remove-tag' ).addEventListener( 'click', function () {
                row.remove();
            } );
        } );
    } );

    document.querySelectorAll( '.hpcms-remove-tag' ).forEach( function ( btn ) {
        btn.addEventListener( 'click', function () {
            btn.closest( '.hpcms-tag-row' ).remove();
        } );
    } );

    // -------------------------------------------------------------------------
    // 8. Accordion — toggle is-open class for CSS transitions
    // NOTE: Remove the Section 6 handler above and keep only this version.
    // -------------------------------------------------------------------------
    document.querySelectorAll( '.hpcms-accordion-toggle' ).forEach( function ( toggle ) {
        toggle.addEventListener( 'click', function () {
            const section = toggle.closest( '.hpcms-accordion-section' );
            const body    = toggle.nextElementSibling;
            const isOpen  = section.classList.contains( 'is-open' );

            if ( isOpen ) {
                section.classList.remove( 'is-open' );
                body.style.display = 'none';
            } else {
                section.classList.add( 'is-open' );
                body.style.display = 'block';
            }
        } );
    } );

} )();
```

---

## Admin CSS

**File:** `assets/admin/settings.css`

```css
/* ============================================================
   Headless Portfolio CMS — Admin Settings Styles
   ============================================================ */

.hpcms-settings-wrap {
    max-width: 960px;
}

.hpcms-tab-content {
    margin-top: 20px;
}

/* ---- Accordion ---- */
.hpcms-accordion-section {
    margin-bottom: 4px;
    border: 1px solid #dcdcde;
    border-radius: 3px;
    background: #fff;
}

.hpcms-accordion-toggle {
    display: flex;
    align-items: center;
    justify-content: space-between;
    width: 100%;
    padding: 14px 18px;
    background: #f6f7f7;
    border: none;
    border-bottom: 1px solid transparent;
    cursor: pointer;
    font-size: 14px;
    font-weight: 600;
    color: #1d2327;
    text-align: left;
    border-radius: 3px;
    transition: background 0.15s ease;
}

.hpcms-accordion-toggle:hover {
    background: #edeff0;
}

.hpcms-accordion-section.is-open .hpcms-accordion-toggle {
    border-bottom-color: #dcdcde;
    border-radius: 3px 3px 0 0;
}

.hpcms-accordion-icon {
    font-size: 11px;
    color: #787c82;
    transition: transform 0.2s ease;
}

.hpcms-accordion-section.is-open .hpcms-accordion-icon {
    transform: rotate(180deg);
}

.hpcms-accordion-body {
    padding: 0 18px 18px;
}

.hpcms-accordion-body .form-table th {
    width: 200px;
}

/* ---- Gallery / Image Picker ---- */
.hpcms-gallery-preview {
    display: flex;
    flex-wrap: wrap;
    gap: 8px;
    margin-bottom: 10px;
}

.hpcms-gallery-thumb {
    position: relative;
    width: 80px;
    height: 80px;
}

.hpcms-gallery-thumb img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    border-radius: 3px;
    border: 1px solid #dcdcde;
    display: block;
}

.hpcms-remove-gallery-item {
    position: absolute;
    top: -8px;
    right: -8px;
    width: 20px;
    height: 20px;
    border-radius: 50%;
    background: #cc1818;
    color: #fff;
    border: none;
    cursor: pointer;
    font-size: 11px;
    line-height: 1;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 0;
}

.hpcms-remove-gallery-item:hover {
    background: #a00;
}

/* ---- Repeatable rows (location, skill tags) ---- */
.hpcms-repeatable-row,
.hpcms-tag-row {
    display: flex;
    align-items: center;
    gap: 8px;
    margin-bottom: 6px;
}

/* ---- Card rows ---- */
.hpcms-card-row {
    border: 1px solid #dcdcde;
    border-radius: 3px;
    padding: 14px;
    margin-bottom: 8px;
    background: #f9f9f9;
}

.hpcms-card-row p {
    margin: 0 0 8px;
}

.hpcms-card-row label {
    font-weight: 600;
    font-size: 12px;
    color: #50575e;
    display: block;
    margin-bottom: 4px;
}

/* ---- Buttons ---- */
.hpcms-add-location,
.hpcms-add-card,
.hpcms-add-tag {
    margin-top: 4px !important;
}
```

---

## Bootstrap Integration

**File:** `headless-portfolio-cms.php` — updated `hpcms_bootstrap()`:

```php
function hpcms_bootstrap(): void {
    // --- Existing initializations (order unchanged) ---
    \HPCMS\CPT\Registry::init();
    \HPCMS\Core\Taxonomies::init();
    \HPCMS\Meta\Registry::init();
    \HPCMS\Core\Settings::init();
    \HPCMS\Core\CORS::init();
    \HPCMS\API\Registry::init();
    \HPCMS\Admin\Menu::init();

    // --- New in v1.1.0 ---
    \HPCMS\Core\Migrator::run();
}
add_action( 'plugins_loaded', 'hpcms_bootstrap' );
```

**File:** `includes/API/Registry.php` — register `/main`, deprecate `/profile`:

```php
public static function init(): void {
    add_action( 'rest_api_init', function() {
        // --- Existing routes ---
        ( new \HPCMS\API\ProjectsController() )->register_routes();
        ( new \HPCMS\API\ExperienceController() )->register_routes();
        ( new \HPCMS\API\EducationController() )->register_routes();
        ( new \HPCMS\API\ResumeController() )->register_routes();
        ( new \HPCMS\API\SkillsController() )->register_routes();
        ( new \HPCMS\API\TestimonialsController() )->register_routes();

        // --- Deprecated in v1.1.0 (remove in v1.3.0) ---
        \HPCMS\API\ProfileController::register_routes();

        // --- New in v1.1.0 ---
        \HPCMS\API\MainController::register_routes();
    } );
}
```

---

## File Creation Summary — v1.1.0

| Action | File Path |
|---|---|
| **Modify** | `headless-portfolio-cms.php` — add `Migrator::run()` to bootstrap |
| **Create** | `includes/Core/Migrator.php` |
| **Modify** | `includes/Core/Settings.php` — add `get_group()`, `get()`, `save_group()`, cache bust hooks |
| **Create** | `includes/Admin/Pages/GeneralPage.php` |
| **Create** | `includes/Admin/Pages/SocialPage.php` |
| **Create** | `includes/Admin/Pages/SeoPage.php` |
| **Modify** | `includes/Admin/Menu.php` — add tabs, wire page classes, rename Configuration tab |
| **Create** | `includes/API/MainController.php` |
| **Modify** | `includes/API/Registry.php` — add `MainController`, add deprecation header to `ProfileController` |
| **Modify** | `uninstall.php` — add new option keys to deletion list |
| **Create** | `assets/admin/settings.js` |
| **Create** | `assets/admin/settings.css` |
