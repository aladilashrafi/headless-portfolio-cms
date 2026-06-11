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
                    <th scope="row">
                        <?php esc_html_e( 'OG Image', 'headless-portfolio-cms' ); ?>
                    </th>
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
