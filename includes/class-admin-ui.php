<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CS_AHM_Admin_UI {

    public function __construct() {
        add_action( 'admin_notices', array( $this, 'maybe_show_notice' ) );
    }

    public function maybe_show_notice() {
        if ( ! isset( $_GET['settings-updated'] ) ) {
            return;
        }
        if ( ! isset( $_GET['page'] ) || 'cs-admin-hide-menu' !== $_GET['page'] ) {
            return;
        }
        ?>
        <div class="notice notice-success is-dismissible">
            <p><?php esc_html_e( 'تنظیمات با موفقیت ذخیره شد.', 'cs-admin-hide-menu' ); ?></p>
        </div>
        <?php
    }
}