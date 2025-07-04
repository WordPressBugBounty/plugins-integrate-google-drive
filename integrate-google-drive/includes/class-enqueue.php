<?php

namespace IGD;

defined( 'ABSPATH' ) || exit;
class Enqueue {
    /**
     * @var null
     */
    protected static $instance = null;

    public function __construct() {
        add_action( 'wp_enqueue_scripts', [$this, 'frontend_scripts'] );
        add_action( 'admin_enqueue_scripts', [$this, 'admin_scripts'] );
    }

    public function frontend_scripts() {
        $load_on_all_pages = igd_get_settings( 'loadScriptsOnAllPages' );
        $css_deps = ['wp-components'];
        // LightGallery CSS
        wp_register_style(
            'igd-lightgallery',
            IGD_ASSETS . '/vendor/lightgallery/css/lightgallery-bundle.min.css',
            [],
            IGD_VERSION
        );
        wp_style_add_data( 'igd-lightgallery', 'rtl', 'replace' );
        $css_deps[] = 'igd-lightgallery';
        // SweetAlert2 CSS
        wp_register_style(
            'igd-sweetalert2',
            IGD_ASSETS . '/vendor/sweetalert2/sweetalert2.min.css',
            [],
            '11.4.8'
        );
        $css_deps[] = 'igd-sweetalert2';
        wp_enqueue_style(
            'igd-dashicons',
            includes_url( 'css/dashicons.min.css' ),
            [],
            IGD_VERSION
        );
        $css_deps[] = 'igd-dashicons';
        wp_register_style(
            'igd-frontend',
            IGD_ASSETS . '/css/frontend.css',
            $css_deps,
            IGD_VERSION
        );
        wp_style_add_data( 'igd-frontend', 'rtl', 'replace' );
        wp_add_inline_style( 'igd-frontend', $this->get_custom_css() );
        /*****------- Scripts ------******/
        $js_deps = array(
            'react',
            'react-dom',
            'wp-i18n',
            'wp-plupload',
            'wp-util',
            'jquery'
        );
        // Light Gallery JS
        wp_register_script(
            'igd-lightgallery',
            IGD_ASSETS . '/vendor/lightgallery/lightgallery.min.js',
            [],
            IGD_VERSION,
            true
        );
        $js_deps[] = 'igd-lightgallery';
        // SweetAlert2
        wp_register_script(
            'igd-sweetalert2',
            IGD_ASSETS . '/vendor/sweetalert2/sweetalert2.min.js',
            [],
            '11.4.8',
            true
        );
        $js_deps[] = 'igd-sweetalert2';
        // Frontend scripts
        wp_register_script(
            'igd-frontend',
            IGD_ASSETS . '/js/frontend.js',
            $js_deps,
            IGD_VERSION,
            true
        );
        // Localize data
        wp_localize_script( 'igd-frontend', 'igd', $this->get_localize_data( false, 'frontend' ) );
        wp_set_script_translations( 'igd-frontend', 'integrate-google-drive', plugin_dir_path( IGD_FILE ) . 'languages' );
        // Enqueue all scripts and styles on all pages if the setting is enabled
        if ( $load_on_all_pages ) {
            wp_enqueue_style( 'igd-frontend' );
            wp_enqueue_script( 'igd-frontend' );
        }
    }

    public function admin_scripts( $hook = '', $should_check = true ) {
        wp_register_style(
            'igd-lightgallery',
            IGD_ASSETS . '/vendor/lightgallery/css/lightgallery-bundle.min.css',
            [],
            IGD_VERSION
        );
        wp_style_add_data( 'igd-lightgallery', 'rtl', 'replace' );
        wp_register_style(
            'igd-sweetalert2',
            IGD_ASSETS . '/vendor/sweetalert2/sweetalert2.min.css',
            [],
            '11.4.8'
        );
        wp_register_style(
            'igd-slick-theme',
            IGD_ASSETS . '/vendor/slick/slick-theme.css',
            [],
            IGD_VERSION
        );
        wp_register_style(
            'igd-slick',
            IGD_ASSETS . '/vendor/slick/slick.css',
            ['igd-slick-theme'],
            IGD_VERSION
        );
        // Plyr CSS
        wp_register_style(
            'igd-plyr',
            IGD_ASSETS . '/vendor/plyr/plyr.min.css',
            [],
            '3.7.8'
        );
        wp_enqueue_style(
            'igd-dashicons',
            includes_url( 'css/dashicons.min.css' ),
            [],
            IGD_VERSION
        );
        wp_register_style(
            'igd-admin',
            IGD_ASSETS . '/css/admin.css',
            [
                'wp-components',
                'igd-sweetalert2',
                'igd-lightgallery',
                'igd-dashicons'
            ],
            IGD_VERSION
        );
        wp_enqueue_style( 'igd-admin' );
        wp_style_add_data( 'igd-admin', 'rtl', 'replace' );
        wp_add_inline_style( 'igd-admin', $this->get_custom_css() );
        $should_enqueue = !$should_check || $this->should_enqueue_admin_scripts( $hook );
        if ( !$should_enqueue ) {
            return;
        }
        if ( !class_exists( 'IGD\\Admin' ) ) {
            require_once IGD_PATH . '/includes/class-admin.php';
        }
        $admin_pages = Admin::instance()->get_pages();
        // lightGallery
        wp_register_script(
            'igd-lightgallery',
            IGD_ASSETS . '/vendor/lightgallery/lightgallery.min.js',
            [],
            IGD_VERSION,
            true
        );
        wp_register_script(
            'igd-sweetalert2',
            IGD_ASSETS . '/vendor/sweetalert2/sweetalert2.min.js',
            [],
            '11.4.8',
            true
        );
        // Drag-select
        wp_register_script(
            'igd-drag-select',
            IGD_ASSETS . '/vendor/drag-select.js',
            [],
            '3.0.5',
            true
        );
        $igd_admin_src = str_replace( ['http:', 'https:'], '', IGD_ASSETS . '/js/admin.js' );
        wp_enqueue_script(
            'igd-admin',
            $igd_admin_src,
            array(
                "jquery",
                "wp-element",
                "wp-components",
                "wp-i18n",
                "wp-util",
                "wp-plupload",
                "igd-lightgallery",
                "igd-sweetalert2",
                "igd-drag-select"
            ),
            IGD_VERSION,
            true
        );
        // Private Folders page scripts
        if ( !empty( $admin_pages['private_files_page'] ) && $admin_pages['private_files_page'] === $hook ) {
            wp_enqueue_script(
                'igd-private-folders',
                IGD_ASSETS . '/js/private-folders.js',
                ['igd-admin'],
                IGD_VERSION,
                true
            );
        }
        // Settings Page Scripts
        if ( isset( $admin_pages['settings_page'] ) && $admin_pages['settings_page'] === $hook ) {
            // Uploader scripts
            wp_enqueue_media();
            // Code Editor
            wp_enqueue_script( 'wp-theme-plugin-editor' );
            wp_enqueue_style( 'wp-codemirror' );
            $cm_settings = [
                'codeEditor' => wp_enqueue_code_editor( array(
                    'type'  => 'text/css',
                    'theme' => 'dracula',
                ) ),
            ];
            wp_localize_script( 'igd-admin', 'cm_settings', $cm_settings );
            // Enqueue settings page scripts
            wp_enqueue_script(
                'igd-settings',
                IGD_ASSETS . '/js/settings.js',
                ['igd-admin'],
                IGD_VERSION,
                true
            );
        }
        // TinyMCE Editor
        if ( !empty( $hook ) ) {
            wp_enqueue_editor();
        }
        wp_localize_script( 'igd-admin', 'igd', $this->get_localize_data( $hook ) );
        wp_set_script_translations( 'igd-admin', 'integrate-google-drive', plugin_dir_path( IGD_FILE ) . 'languages' );
    }

    /**
     * @return array
     */
    public function get_localize_data( $hook = false, $script = 'admin' ) {
        $accounts = Account::instance()->get_accounts();
        $localize_data = array(
            'isAdmin'       => is_admin(),
            'pluginUrl'     => IGD_URL,
            'adminUrl'      => admin_url(),
            'siteUrl'       => site_url(),
            'homeUrl'       => home_url(),
            'ajaxUrl'       => admin_url( 'admin-ajax.php' ),
            'nonce'         => wp_create_nonce( 'igd' ),
            'isPro'         => igd_fs()->can_use_premium_code__premium_only(),
            'upgradeUrl'    => igd_fs()->get_upgrade_url(),
            'accounts'      => base64_encode( json_encode( $accounts ) ),
            'activeAccount' => base64_encode( json_encode( Account::instance()->get_active_account() ) ),
            'settings'      => igd_get_settings(),
        );
        if ( is_admin() ) {
            $admin_pages = Admin::instance()->get_pages();
            $is_settings_page = !empty( $admin_pages['settings_page'] ) && $admin_pages['settings_page'] === $hook;
            if ( !$is_settings_page && isset( $_GET['page'] ) && $_GET['page'] == 'integrate-google-drive-settings' ) {
                $is_settings_page = true;
            }
            $is_file_browser_page = !empty( $admin_pages['file_browser_page'] ) && $admin_pages['file_browser_page'] === $hook;
            // Localize the Google Drive API auth URL only on settings or file browser pages
            if ( $is_settings_page || $is_file_browser_page ) {
                $localize_data['authUrl'] = Client::instance()->get_auth_url();
            }
        }
        if ( is_admin() || $this->is_divi_builder() || $this->is_elementor_editor() ) {
            // User access folder data
            $localize_data['userAccessData'] = igd_get_user_access_data();
            // Shortcodes data
            $localize_data['shortcodes'] = Shortcode::instance()->get_shortcodes();
        }
        return apply_filters( 'igd_localize_data', $localize_data, $script );
    }

    public function get_custom_css() {
        $css = '';
        $custom_css = igd_get_settings( 'customCss' );
        if ( !empty( $custom_css ) ) {
            $css .= $custom_css;
        }
        $primary_color = igd_get_settings( 'primaryColor', '#3C82F6' );
        ob_start();
        ?>
        :root {
        --color-primary: <?php 
        echo $primary_color;
        ?>;
        --color-primary-dark: <?php 
        echo igd_color_brightness( $primary_color, -30 );
        ?>;
        --color-primary-light: <?php 
        echo igd_hex2rgba( $primary_color, '.5' );
        ?>;
        --color-primary-light-alt: <?php 
        echo igd_color_brightness( $primary_color, 30 );
        ?>;
        --color-primary-lighter: <?php 
        echo igd_hex2rgba( $primary_color, '.1' );
        ?>;
        --color-primary-lighter-alt: <?php 
        echo igd_color_brightness( $primary_color, 50 );
        ?>;
        }
		<?php 
        $css .= ob_get_clean();
        return $css;
    }

    public function is_block_editor() {
        if ( function_exists( 'get_current_screen' ) ) {
            $current_screen = get_current_screen();
            if ( !empty( $current_screen->is_block_editor ) ) {
                return true;
            }
        }
        return false;
    }

    public function is_divi_builder() {
        if ( function_exists( 'et_fb_is_enabled' ) ) {
            if ( et_fb_is_enabled() ) {
                return true;
            }
        }
        return false;
    }

    public function is_elementor_editor() {
        return isset( $_GET['elementor-preview'] );
    }

    public function should_enqueue_admin_scripts( $hook ) {
        if ( !class_exists( 'IGD\\Admin' ) ) {
            require_once IGD_PATH . '/includes/class-admin.php';
        }
        $admin_pages = Admin::instance()->get_pages();
        $integration = Integration::instance();
        if ( $integration->is_active( 'gutenberg-editor' ) ) {
            if ( $this->is_block_editor() ) {
                return true;
            }
        }
        if ( $integration->is_active( 'elementor' ) ) {
            if ( $this->is_elementor_editor() ) {
                return true;
            }
        }
        if ( $integration->is_active( 'divi' ) ) {
            if ( $this->is_divi_builder() ) {
                return true;
            }
        }
        if ( $integration->is_active( 'cf7' ) ) {
            $admin_pages[] = 'toplevel_page_wpcf7';
            $admin_pages[] = 'contact_page_wpcf7-new';
        }
        if ( in_array( $hook, $admin_pages ) ) {
            return true;
        }
        return false;
    }

    /**
     * @return Enqueue|null
     */
    public static function instance() {
        if ( is_null( self::$instance ) ) {
            self::$instance = new self();
        }
        return self::$instance;
    }

}

Enqueue::instance();