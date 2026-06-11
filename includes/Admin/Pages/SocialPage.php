<?php
declare(strict_types=1);

namespace HPCMS\Admin\Pages;

defined('ABSPATH') || exit;

use HPCMS\Core\Settings;

class SocialPage {

    /**
     * All social platforms in display order.
     * Key = stored field name, Value = display label.
     */
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
                        <input
                            type="url"
                            id="hpcms_social_<?php echo esc_attr( $key ); ?>"
                            name="hpcms_social[<?php echo esc_attr( $key ); ?>]"
                            value="<?php echo esc_attr( $data[ $key ] ?? '' ); ?>"
                            class="regular-text"
                            placeholder="https://..."
                        />
                    </td>
                </tr>
                <?php endforeach; ?>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }
}
