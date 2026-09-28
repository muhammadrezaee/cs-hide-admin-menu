<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CS_AHM_Menu_Hider {

    public function __construct() {
    // ⭐ کش کردن منوها *قبل از* هر حذفی — با اولویت خیلی پایین
    add_action( 'admin_menu', array( $this, 'cache_all_menus_early' ), 1 );

    // حذف منوها — با اولویت بالا
    add_action( 'admin_menu', array( $this, 'hide_menus' ), 9999 );

    add_action( 'admin_head', array( $this, 'hide_submenu_css' ) );
}

    // گرفتن لیست مخفی‌شده
    private function get_hidden() {
        return (array) get_option( 'cs_ahm_hidden_menus', array() );
    }

    // مخفی کردن منوها
    public function hide_menus() {
    // ⭐ اول از همه، لیست کامل منوها رو کش کن (قبل از هر حذفی)
    $this->cache_all_menus();

    $hidden = $this->get_hidden();
    if ( empty( $hidden ) ) {
        return;
    }

    // حذف منوهای اصلی
    foreach ( $hidden as $slug ) {
        if ( 'options-general.php' === $slug ) {
            continue;
        }
        remove_menu_page( $slug );
    }

    // حذف زیرمنوها
    global $submenu;
    if ( ! empty( $submenu ) ) {
        foreach ( $submenu as $parent => $items ) {
            foreach ( $items as $key => $item ) {
                if ( in_array( $item[2], $hidden, true ) ) {
                    remove_submenu_page( $parent, $item[2] );
                }
            }
        }
    }
}

// ⭐ کش کردن همه منوها برای استفاده بعدی در صفحه تنظیمات
private function cache_all_menus() {
    global $menu, $submenu;

    $all = array(
        'menus'    => array(),
        'submenus' => array(),
    );

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

    // ذخیره در transient (کش ۲۴ ساعته)
    set_transient( 'cs_ahm_all_menus', $all, DAY_IN_SECONDS );
}

    // مخفی کردن زیرمنوهای باقیمانده با CSS (برای مواردی که remove کار نمیکنه)
    public function hide_submenu_css() {
        $hidden = $this->get_hidden();
        if ( empty( $hidden ) ) {
            return;
        }
        ?>
        <style id="cs-ahm-dynamic-css">
            <?php foreach ( $hidden as $slug ) : ?>
                <?php if ( 'options-general.php' === $slug ) continue; ?>
                #adminmenu li a[href*="<?php echo esc_attr( $slug ); ?>"],
                #adminmenu li[class*="<?php echo esc_attr( sanitize_html_class( $slug ) ); ?>"] {
                    display: none !important;
                }
            <?php endforeach; ?>
        </style>
        <?php
    }
	
	
	

// کش در اول کار (قبل از هر تغییری)
public function cache_all_menus_early() {
    // فقط یک بار در هر ریکوئست و در صفحه تنظیمات
    if ( ! isset( $_GET['page'] ) || 'cs-admin-hide-menu' !== $_GET['page'] ) {
        return;
    }

    // پاک کردن کش قبلی
    delete_transient( 'cs_ahm_all_menus' );
}

}