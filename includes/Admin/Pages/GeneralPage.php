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
            'hpcms_general_group',   // option_group (matches settings_fields() call)
            'hpcms_general',         // option_name
            [ self::class, 'sanitize' ]
        );
    }

    /**
     * Sanitize the entire hpcms_general array before it is saved.
     */
    public static function sanitize( mixed $input ): array {
        if ( ! is_array( $input ) ) {
            return [];
        }

        $clean = [];

        // Simple text fields.
        $text_fields = [ 'name', 'tagline', 'phone', 'header_button_text' ];
        foreach ( $text_fields as $field ) {
            if ( isset( $input[ $field ] ) ) {
                $clean[ $field ] = sanitize_text_field( $input[ $field ] );
            }
        }

        // Email — stricter sanitization.
        if ( isset( $input['email'] ) ) {
            $clean['email'] = sanitize_email( $input['email'] );
        }

        // Header button URL.
        if ( isset( $input['header_button_url'] ) ) {
            $clean['header_button_url'] = esc_url_raw( $input['header_button_url'] );
        }

        // Footer text — HTML allowed.
        if ( isset( $input['footer_text_raw'] ) ) {
            $clean['footer_text_raw'] = wp_kses_post( $input['footer_text_raw'] );
        }

        // Favicon — store attachment ID (integer).
        if ( isset( $input['favicon_id'] ) ) {
            $clean['favicon_id'] = absint( $input['favicon_id'] );
        }

        // Repeatable locations array.
        // Input comes in as: locations[0][id], locations[0][value], etc.
        $clean['locations'] = [];
        if ( ! empty( $input['locations'] ) && is_array( $input['locations'] ) ) {
            foreach ( $input['locations'] as $location ) {
                if ( empty( $location['value'] ) ) {
                    continue; // Skip blank rows.
                }
                $clean['locations'][] = [
                    'id'    => sanitize_key( $location['id'] ?? 'loc_' . uniqid() ),
                    'value' => sanitize_text_field( $location['value'] ),
                ];
            }
        }

        return $clean;
    }

    /**
     * Render the General tab HTML.
     */
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
                        <input
                            type="text"
                            id="hpcms_name"
                            name="hpcms_general[name]"
                            value="<?php echo esc_attr( $data['name'] ?? '' ); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>

                <!-- Tagline -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_tagline"><?php esc_html_e( 'Tagline', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="hpcms_tagline"
                            name="hpcms_general[tagline]"
                            value="<?php echo esc_attr( $data['tagline'] ?? '' ); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>

                <!-- Email -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_email"><?php esc_html_e( 'Email', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="email"
                            id="hpcms_email"
                            name="hpcms_general[email]"
                            value="<?php echo esc_attr( $data['email'] ?? '' ); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>

                <!-- Phone -->
                <tr>
                    <th scope="row">
                        <label for="hpcms_phone"><?php esc_html_e( 'Phone', 'headless-portfolio-cms' ); ?></label>
                    </th>
                    <td>
                        <input
                            type="text"
                            id="hpcms_phone"
                            name="hpcms_general[phone]"
                            value="<?php echo esc_attr( $data['phone'] ?? '' ); ?>"
                            class="regular-text"
                        />
                    </td>
                </tr>

                <!-- Locations (repeatable) -->
                <tr>
                    <th scope="row">
                        <?php esc_html_e( 'Locations', 'headless-portfolio-cms' ); ?>
                    </th>
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
                                    name="hpcms_general[locations][<?php echo absint( $i ); ?>][id]"
                                    value="<?php echo esc_attr( $loc['id'] ); ?>"
                                    class="hpcms-loc-id"
                                />
                                <input type="text"
                                    name="hpcms_general[locations][<?php echo absint( $i ); ?>][value]"
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
                    <th scope="row">
                        <?php esc_html_e( 'Header Button', 'headless-portfolio-cms' ); ?>
                    </th>
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
                        <textarea
                            id="hpcms_footer_text"
                            name="hpcms_general[footer_text_raw]"
                            rows="4"
                            class="large-text"
                        ><?php echo wp_kses_post( $data['footer_text_raw'] ?? '' ); ?></textarea>
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
