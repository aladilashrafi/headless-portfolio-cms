<?php
declare(strict_types=1);

namespace HPCMS\Admin;

defined('ABSPATH') || exit;

use HPCMS\Admin\Pages\GeneralPage;
use HPCMS\Admin\Pages\SocialPage;
use HPCMS\Admin\Pages\SeoPage;

class Menu {

    /**
     * Tab registry: slug => [ label, page-class | null ]
     * null = handled inline (Configuration tab).
     */
    private static array $tabs = [
        'general'       => [ 'General',       GeneralPage::class ],
        'social'        => [ 'Social Links',  SocialPage::class ],
        'seo'           => [ 'SEO',           SeoPage::class ],
        'configuration' => [ 'Configuration', null ],
    ];

    public static function init(): void {
        add_action( 'admin_menu', [ __CLASS__, 'register_menu' ] );
        add_action( 'admin_enqueue_scripts', [ __CLASS__, 'enqueue_assets' ] );
        add_action( 'custom_menu_order', '__return_true' );
        add_action( 'menu_order', [ __CLASS__, 'reorder_submenu' ], 999 );

        // Initialise page-class settings registration.
        GeneralPage::init();
        SocialPage::init();
        SeoPage::init();
    }

    public static function reorder_submenu( array $menu_order ): array {
        global $submenu;
        $parent_slug = 'headless-portfolio-cms';

        if ( ! isset( $submenu[ $parent_slug ] ) ) {
            return $menu_order;
        }

        $items     = $submenu[ $parent_slug ];
        $dashboard = [];
        $api_ref   = [];
        $settings  = [];
        $cpts      = [];

        foreach ( $items as $item ) {
            $slug = $item[2];
            if ( $slug === $parent_slug ) {
                $dashboard[] = $item;
            } elseif ( $slug === 'hpcms-api-reference' ) {
                $api_ref[] = $item;
            } elseif ( $slug === 'hpcms-settings' ) {
                $settings[] = $item;
            } else {
                $cpts[] = $item;
            }
        }

        // Dashboard → API Ref → CPTs → Settings
        $submenu[ $parent_slug ] = array_merge( $dashboard, $api_ref, $cpts, $settings );

        return $menu_order;
    }

    public static function register_menu(): void {
        add_menu_page(
            __( 'Headless Portfolio CMS', 'headless-portfolio-cms' ),
            __( 'Portfolio CMS', 'headless-portfolio-cms' ),
            'manage_options',
            'headless-portfolio-cms',
            [ Dashboard::class, 'render' ],
            HPCMS_PLUGIN_URL . 'assets/icon.svg',
            100
        );

        add_submenu_page(
            'headless-portfolio-cms',
            __( 'Dashboard', 'headless-portfolio-cms' ),
            __( 'Dashboard', 'headless-portfolio-cms' ),
            'manage_options',
            'headless-portfolio-cms',
            [ Dashboard::class, 'render' ]
        );

        add_submenu_page(
            'headless-portfolio-cms',
            __( 'API Reference', 'headless-portfolio-cms' ),
            __( 'API Reference', 'headless-portfolio-cms' ),
            'manage_options',
            'hpcms-api-reference',
            [ API_Reference::class, 'render' ]
        );

        add_submenu_page(
            'headless-portfolio-cms',
            __( 'Settings', 'headless-portfolio-cms' ),
            __( 'Settings', 'headless-portfolio-cms' ),
            'manage_options',
            'hpcms-settings',
            [ __CLASS__, 'render_settings_page' ]
        );
    }

    public static function enqueue_assets( string $hook ): void {
        if ( strpos( $hook, 'headless-portfolio-cms' ) !== false || strpos( $hook, 'hpcms-' ) !== false ) {
            wp_enqueue_style( 'hpcms-admin-css', HPCMS_PLUGIN_URL . 'assets/css/admin.css', [], HPCMS_VERSION );

            // New per-tab settings stylesheet.
            wp_enqueue_style(
                'hpcms-admin-settings',
                HPCMS_PLUGIN_URL . 'assets/admin/settings.css',
                [],
                HPCMS_VERSION
            );
        }

        // Enqueue media library + admin settings JS only on the settings page.
        if ( strpos( $hook, 'hpcms-settings' ) !== false ) {
            wp_enqueue_media();

            wp_enqueue_script(
                'hpcms-admin-settings',
                HPCMS_PLUGIN_URL . 'assets/admin/settings.js',
                [],
                HPCMS_VERSION,
                true
            );
        }

        if ( 'plugins.php' === $hook ) {
            wp_enqueue_style( 'hpcms-admin-css', HPCMS_PLUGIN_URL . 'assets/css/admin.css', [], HPCMS_VERSION );
            wp_enqueue_script( 'hpcms-deactivation-js', HPCMS_PLUGIN_URL . 'assets/js/deactivation.js', [ 'jquery' ], HPCMS_VERSION, true );
            wp_localize_script( 'hpcms-deactivation-js', 'hpcms_deactivation', [
                'ajax_url'    => admin_url( 'admin-ajax.php' ),
                'nonce'       => wp_create_nonce( 'hpcms_deactivation_nonce' ),
                'plugin_slug' => 'headless-portfolio-cms',
            ] );
        }
    }

    public static function render_settings_page(): void {
        if ( ! current_user_can( 'manage_options' ) ) {
            wp_die( esc_html__( 'You do not have permission to access this page.', 'headless-portfolio-cms' ) );
        }

        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        $active_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : 'general';

        if ( ! array_key_exists( $active_tab, self::$tabs ) ) {
            $active_tab = 'general';
        }
        ?>
        <div class="wrap hpcms-admin-wrap">
            <header class="hpcms-admin-header">
                <h1><?php esc_html_e( 'Settings', 'headless-portfolio-cms' ); ?></h1>
            </header>

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
     * The existing API & CORS tab — renamed to "Configuration", content unchanged.
     */
    private static function render_configuration_tab(): void {
        // phpcs:ignore WordPress.Security.NonceVerification.Recommended
        if ( isset( $_GET['settings-updated'] ) ) {
            add_settings_error( 'hpcms_messages', 'hpcms_message', __( 'Settings Saved', 'headless-portfolio-cms' ), 'updated' );
        }
        ?>
        <form method="post" action="options.php" class="hpcms-settings-form">
            <?php settings_fields( 'hpcms_settings_api' ); ?>
            <table class="form-table">
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable REST API', 'headless-portfolio-cms' ); ?></th>
                    <td>
                        <label><input type="radio" name="hpcms_enable_api" value="1" <?php checked( get_option( 'hpcms_enable_api', '1' ), '1' ); ?>> <?php esc_html_e( 'Yes', 'headless-portfolio-cms' ); ?></label><br>
                        <label><input type="radio" name="hpcms_enable_api" value="0" <?php checked( get_option( 'hpcms_enable_api' ), '0' ); ?>> <?php esc_html_e( 'No', 'headless-portfolio-cms' ); ?></label>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Enable CORS', 'headless-portfolio-cms' ); ?></th>
                    <td>
                        <label><input type="radio" name="hpcms_enable_cors" value="1" <?php checked( get_option( 'hpcms_enable_cors', '1' ), '1' ); ?>> <?php esc_html_e( 'Yes', 'headless-portfolio-cms' ); ?></label><br>
                        <label><input type="radio" name="hpcms_enable_cors" value="0" <?php checked( get_option( 'hpcms_enable_cors' ), '0' ); ?>> <?php esc_html_e( 'No', 'headless-portfolio-cms' ); ?></label>
                        <p class="description"><?php esc_html_e( 'Required if your frontend is hosted on a different domain.', 'headless-portfolio-cms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Allowed Origins', 'headless-portfolio-cms' ); ?></th>
                    <td>
                        <textarea name="hpcms_allowed_origins" class="large-text" rows="3"><?php echo esc_textarea( get_option( 'hpcms_allowed_origins' ) ); ?></textarea>
                        <p class="description"><?php esc_html_e( 'One URL per line. Use * to allow all (not recommended for production).', 'headless-portfolio-cms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'Frontend URL', 'headless-portfolio-cms' ); ?></th>
                    <td>
                        <input type="url" name="hpcms_frontend_url" value="<?php echo esc_attr( get_option( 'hpcms_frontend_url' ) ); ?>" class="regular-text">
                        <p class="description"><?php esc_html_e( 'Used for ISR revalidation webhooks (e.g., Next.js).', 'headless-portfolio-cms' ); ?></p>
                    </td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'API Cache Duration (s)', 'headless-portfolio-cms' ); ?></th>
                    <td><input type="number" name="hpcms_cache_duration" value="<?php echo esc_attr( get_option( 'hpcms_cache_duration', 3600 ) ); ?>" class="small-text"></td>
                </tr>
                <tr>
                    <th scope="row"><?php esc_html_e( 'API Secret Token', 'headless-portfolio-cms' ); ?></th>
                    <td>
                        <input type="text" name="hpcms_api_token" value="<?php echo esc_attr( get_option( 'hpcms_api_token' ) ); ?>" class="regular-text" readonly>
                        <p class="description"><?php esc_html_e( 'Generated automatically on activation. Use this to authenticate webhooks.', 'headless-portfolio-cms' ); ?></p>
                    </td>
                </tr>
            </table>
            <?php submit_button(); ?>
        </form>
        <?php
    }
}
