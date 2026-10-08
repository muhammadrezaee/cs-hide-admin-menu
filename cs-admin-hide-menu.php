<?php
/**
 * Plugin Name:       CS Admin Hide Menu
 * Plugin URI:        https://codsoft.ir
 * Description:       مخفی‌سازی منوهای پنل مدیریت وردپرس بر اساس نقش کاربر یا کاربر مشخص — ساخته‌شده توسط CodSoft
 * Version:           1.2.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            CodSoft
 * Author URI:        https://codsoft.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cs-admin-hide-menu
 * Domain Path:       /languages
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

define( 'CS_AHM_VERSION', '1.2.0' );
define( 'CS_AHM_FILE', __FILE__ );
define( 'CS_AHM_PATH', plugin_dir_path( __FILE__ ) );
define( 'CS_AHM_URL', plugin_dir_url( __FILE__ ) );
define( 'CS_AHM_BASENAME', plugin_basename( __FILE__ ) );

require_once CS_AHM_PATH . 'includes/class-roles.php';
require_once CS_AHM_PATH . 'includes/class-settings.php';
require_once CS_AHM_PATH . 'includes/class-menu-hider.php';
require_once CS_AHM_PATH . 'includes/class-admin-ui.php';

function cs_ahm_init() {
    load_plugin_textdomain( 'cs-admin-hide-menu', false, dirname( CS_AHM_BASENAME ) . '/languages' );

    if ( is_admin() ) {
        new CS_AHM_Roles();
        new CS_AHM_Settings();
        new CS_AHM_Menu_Hider();
        new CS_AHM_Admin_UI();
    }
}
add_action( 'plugins_loaded', 'cs_ahm_init' );

// لینک تنظیمات کنار نام افزونه
add_filter( 'plugin_action_links_' . CS_AHM_BASENAME, function ( $links ) {
    $settings_url  = admin_url( 'options-general.php?page=cs-admin-hide-menu' );
    $settings_link = '<a href="' . esc_url( $settings_url ) . '">' . esc_html__( 'تنظیمات', 'cs-admin-hide-menu' ) . '</a>';
    array_unshift( $links, $settings_link );
    return $links;
} );

// فعال‌سازی
register_activation_hook( __FILE__, function () {
    if ( false === get_option( 'cs_ahm_hidden_menus' ) ) {
        add_option( 'cs_ahm_hidden_menus', array() );
    }
} );