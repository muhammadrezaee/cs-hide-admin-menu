<?php
/**
 * Plugin Name:       CS Admin Hide Menu
 * Plugin URI:        https://codsoft.ir
 * Description:       مخفی‌سازی منوهای پنل مدیریت وردپرس با یک کلیک
 * Version:           1.0.0
 * Requires at least: 5.8
 * Requires PHP:      7.4
 * Author:            کدسافت
 * Author URI:        https://codsoft.ir
 * License:           GPL-2.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-2.0.html
 * Text Domain:       cs-admin-hide-menu
 * Domain Path:       /languages
 */

// جلوگیری از دسترسی مستقیم
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

// ثابت‌های افزونه
define( 'CS_AHM_VERSION', '1.0.0' );
define( 'CS_AHM_FILE', __FILE__ );
define( 'CS_AHM_PATH', plugin_dir_path( __FILE__ ) );
define( 'CS_AHM_URL', plugin_dir_url( __FILE__ ) );
define( 'CS_AHM_BASENAME', plugin_basename( __FILE__ ) );

// بارگذاری کلاس‌ها
require_once CS_AHM_PATH . 'includes/class-settings.php';
require_once CS_AHM_PATH . 'includes/class-menu-hider.php';
require_once CS_AHM_PATH . 'includes/class-admin-ui.php';

// راه‌اندازی
function cs_ahm_init() {
    load_plugin_textdomain( 'cs-admin-hide-menu', false, dirname( CS_AHM_BASENAME ) . '/languages' );

    if ( is_admin() ) {
        new CS_AHM_Settings();
        new CS_AHM_Menu_Hider();
        new CS_AHM_Admin_UI();
    }
}
add_action( 'plugins_loaded', 'cs_ahm_init' );

// فعال‌سازی: مقدار پیش‌فرض
register_activation_hook( __FILE__, function () {
    if ( false === get_option( 'cs_ahm_hidden_menus' ) ) {
        add_option( 'cs_ahm_hidden_menus', array() );
    }
} );

// حذف هنگام غیرفعال‌سازی (اختیاری)
register_deactivation_hook( __FILE__, function () {
    // اگر می‌خوای تنظیمات بمونه، این رو کامنت کن
    delete_option( 'cs_ahm_hidden_menus' );
} );