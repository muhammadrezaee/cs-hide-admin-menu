<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CS_AHM_Menu_Hider {

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'cache_all_menus_early' ), 1 );
        add_action( 'admin_menu', array( $this, 'hide_menus' ), 9999 );
        add_action( 'admin_head', array( $this, 'hide_submenu_css' ) );
        add_action( 'admin_init', array( $this, 'maybe_redirect_to_safe_page' ) );
    }

    private function get_hidden_config() {
        $raw        = (array) get_option( 'cs_ahm_hidden_menus', array() );
        $normalized = array();

        foreach ( $raw as $key => $value ) {
            // فرمت خیلی قدیمی: [0 => 'edit.php'] ← مخفی برای همه
            if ( is_int( $key ) && is_string( $value ) ) {
                $normalized[ $value ] = CS_AHM_Roles::normalize_config( array() );
            }
            // فرمت فعلی و فرمت نقش‌محور قدیمی: ['edit.php' => [...]]
            elseif ( is_string( $key ) && is_array( $value ) ) {
                $normalized[ $key ] = CS_AHM_Roles::normalize_config( $value );
            }
        }

        return $normalized;
    }

    public function cache_all_menus_early() {
        if ( ! isset( $_GET['page'] ) || 'cs-admin-hide-menu' !== $_GET['page'] ) {
            return;
        }
        delete_transient( 'cs_ahm_all_menus' );
    }

    public function hide_menus() {
        $this->cache_all_menus();

        $hidden = $this->get_hidden_config();
        if ( empty( $hidden ) ) {
            return;
        }

        $must_show_self = CS_AHM_Roles::is_current_user_admin();

        foreach ( $hidden as $slug => $roles ) {
            if ( 'options-general.php' === $slug && $must_show_self ) {
                continue;
            }

            if ( CS_AHM_Roles::should_hide_for_current_user( $slug, $roles ) ) {
                remove_menu_page( $slug );
            }
        }

        global $submenu;
        if ( ! empty( $submenu ) ) {
            foreach ( $submenu as $parent => $items ) {
                foreach ( $items as $key => $item ) {
                    if ( isset( $hidden[ $item[2] ] ) ) {
                        if ( CS_AHM_Roles::should_hide_for_current_user( $item[2], $hidden[ $item[2] ] ) ) {
                            remove_submenu_page( $parent, $item[2] );
                        }
                    }
                }
            }
        }
    }

    public function hide_submenu_css() {
        $hidden = $this->get_hidden_config();
        if ( empty( $hidden ) ) {
            return;
        }
        ?>
        <style id="cs-ahm-dynamic-css">
            <?php foreach ( $hidden as $slug => $roles ) : ?>
                <?php
                if ( 'options-general.php' === $slug && CS_AHM_Roles::is_current_user_admin() ) continue;
                if ( ! CS_AHM_Roles::should_hide_for_current_user( $slug, $roles ) ) continue;
                ?>
                #adminmenu li a[href*="<?php echo esc_attr( $slug ); ?>"],
                #adminmenu li[class*="<?php echo esc_attr( sanitize_html_class( $slug ) ); ?>"] {
                    display: none !important;
                }
            <?php endforeach; ?>
        </style>
        <?php
    }

    public function maybe_redirect_to_safe_page() {
        if ( wp_doing_ajax() || wp_doing_cron() ) {
            return;
        }
        if ( ! is_admin() ) {
            return;
        }

        $safe_pages   = array( 'cs-admin-hide-menu', 'profile.php' );
        $current_page = isset( $_GET['page'] ) ? sanitize_text_field( $_GET['page'] ) : '';

        if ( in_array( $current_page, $safe_pages, true ) ) {
            return;
        }

        global $menu;
        $visible_menus = 0;
        if ( ! empty( $menu ) ) {
            foreach ( $menu as $item ) {
                if ( empty( $item[2] ) ) continue;
                if ( 'separator' === $item[4] || strpos( $item[4], 'wp-menu-separator' ) !== false ) continue;
                $visible_menus++;
            }
        }

        if ( $visible_menus === 0 && ! CS_AHM_Roles::is_current_user_admin() ) {
            wp_safe_redirect( admin_url( 'profile.php' ) );
            exit;
        }
    }

    private function cache_all_menus() {
        global $menu, $submenu;

        $all = array( 'menus' => array(), 'submenus' => array() );

        if ( ! empty( $menu ) ) {
            foreach ( $menu as $item ) {
                if ( empty( $item[2] ) ) continue;
                if ( 'separator' === $item[4] || strpos( $item[4], 'wp-menu-separator' ) !== false ) continue;
                $all['menus'][] = array(
                    'title' => $item[0],
                    'slug'  => $item[2],
                    'icon'  => isset( $item[6] ) ? $item[6] : '',
                );
            }
        }

        if ( ! empty( $submenu ) ) {
            foreach ( $submenu as $parent => $items ) {
                foreach ( $items as $item ) {
                    if ( empty( $item[2] ) ) continue;
                    $all['submenus'][ $parent ][] = array(
                        'title' => $item[0],
                        'slug'  => $item[2],
                    );
                }
            }
        }

        set_transient( 'cs_ahm_all_menus', $all, DAY_IN_SECONDS );
    }
}