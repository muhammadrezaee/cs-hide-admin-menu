<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CS_AHM_Settings {

    const OPTION_KEY = 'cs_ahm_hidden_menus';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    // افزودن صفحه تنظیمات
    public function add_settings_page() {
    add_options_page(
        __( 'مخفی‌سازی منوهای مدیریت', 'cs-admin-hide-menu' ),  // عنوان صفحه
        __( 'مخفی‌سازی منوها', 'cs-admin-hide-menu' ),          // اسم توی منو ← فارسی
        'manage_options',
        'cs-admin-hide-menu',
        array( $this, 'render_page' )
    );
}

    // ثبت تنظیمات
    public function register_settings() {
    register_setting(
        'cs_ahm_settings_group',
        self::OPTION_KEY,
        array(
            'type'              => 'array',
            'sanitize_callback' => array( $this, 'sanitize' ),
            'default'           => array(),
        )
    );

    // ⭐ هر بار که وارد صفحه تنظیمات میشیم، کش رو پاک کن
    if ( isset( $_GET['page'] ) && 'cs-admin-hide-menu' === $_GET['page'] ) {
        delete_transient( 'cs_ahm_all_menus' );
    }
}

    // پاک‌سازی ورودی
    public function sanitize( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }
        return array_map( 'sanitize_text_field', $input );
    }

    // بارگذاری CSS/JS
    public function enqueue_assets( $hook ) {
        if ( 'settings_page_cs-admin-hide-menu' !== $hook ) {
            return;
        }
        wp_enqueue_style( 'cs-ahm-admin', CS_AHM_URL . 'assets/css/admin.css', array(), CS_AHM_VERSION );
        wp_enqueue_script( 'cs-ahm-admin', CS_AHM_URL . 'assets/js/admin.js', array( 'jquery' ), CS_AHM_VERSION, true );
    }

    // رندر صفحه
    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $menus  = $this->get_admin_menus();
        $hidden = (array) get_option( self::OPTION_KEY, array() );
        ?>
        <div class="wrap cs-ahm-wrap">
            <h1>
                <span class="dashicons dashicons-hidden"></span>
                <?php esc_html_e( 'مخفی‌سازی منوهای پنل مدیریت', 'cs-admin-hide-menu' ); ?>
            </h1>

            <p class="description">
                <?php esc_html_e( 'منوهایی که می‌خوای مخفی بشن رو تیک بزن. تغییرات بلافاصله اعمال میشن.', 'cs-admin-hide-menu' ); ?>
            </p>

            <div class="cs-ahm-toolbar">
                <button type="button" class="button" id="cs-ahm-select-all">
                    <?php esc_html_e( 'انتخاب همه', 'cs-admin-hide-menu' ); ?>
                </button>
                <button type="button" class="button" id="cs-ahm-deselect-all">
                    <?php esc_html_e( 'لغو انتخاب همه', 'cs-admin-hide-menu' ); ?>
                </button>
                <span class="cs-ahm-counter">
                    <span id="cs-ahm-count"><?php echo count( $hidden ); ?></span>
                    <?php esc_html_e( 'منو مخفی شده', 'cs-admin-hide-menu' ); ?>
                </span>
            </div>

            <form method="post" action="options.php">
                <?php settings_fields( 'cs_ahm_settings_group' ); ?>

                <table class="wp-list-table widefat fixed striped cs-ahm-table">
                    <thead>
                        <tr>
                            <th width="60"><?php esc_html_e( 'مخفی', 'cs-admin-hide-menu' ); ?></th>
                            <th width="60"><?php esc_html_e( 'آیکون', 'cs-admin-hide-menu' ); ?></th>
                            <th><?php esc_html_e( 'عنوان منو', 'cs-admin-hide-menu' ); ?></th>
                            <th><?php esc_html_e( 'Slug', 'cs-admin-hide-menu' ); ?></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $menus as $menu ) : ?>
                            <?php
                            $slug     = $menu['slug'];
                            $checked  = in_array( $slug, $hidden, true );
                            $is_self  = ( 'options-general.php' === $slug );
                            ?>
                            <tr class="<?php
    echo $is_self ? 'cs-ahm-protected ' : '';
    echo ! empty( $menu['hidden_only'] ) ? 'cs-ahm-orphan' : '';
?>">
                                <td>
                                    <label class="cs-ahm-switch">
                                        <input type="checkbox"
                                               name="<?php echo esc_attr( self::OPTION_KEY ); ?>[]"
                                               value="<?php echo esc_attr( $slug ); ?>"
                                               <?php checked( $checked ); ?>
                                               <?php disabled( $is_self ); ?> />
                                        <span class="cs-ahm-slider"></span>
                                    </label>
                                </td>
                                <td class="cs-ahm-icon">
                                    <?php if ( ! empty( $menu['icon'] ) ) : ?>
                                        <?php if ( str_starts_with( $menu['icon'], 'dashicons-' ) || str_starts_with( $menu['icon'], 'data:' ) ) : ?>
                                            <span class="dashicons <?php echo esc_attr( $menu['icon'] ); ?>"></span>
                                        <?php else : ?>
                                            <img src="<?php echo esc_url( $menu['icon'] ); ?>" width="20" height="20" alt="" />
                                        <?php endif; ?>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <strong><?php echo esc_html( wp_strip_all_tags( $menu['title'] ) ); ?></strong>
                                    <?php if ( $is_self ) : ?>
                                        <span class="cs-ahm-badge"><?php esc_html_e( 'محافظت‌شده', 'cs-admin-hide-menu' ); ?></span>
                                    <?php endif; ?>
                                </td>
                                <td><code><?php echo esc_html( $slug ); ?></code></td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button( __( 'ذخیره تغییرات', 'cs-admin-hide-menu' ) ); ?>
            </form>
        </div>
        <?php
    }

    // گرفتن لیست منوهای ادمین
    public function get_admin_menus() {
    // ⭐ اول از کش بخون
    $cached = get_transient( 'cs_ahm_all_menus' );

    // اگه کش نبود، همین الان بگیر (fallback)
    if ( false === $cached ) {
        global $menu, $submenu;
        $cached = array( 'menus' => array(), 'submenus' => array() );

        if ( ! empty( $menu ) ) {
            foreach ( $menu as $item ) {
                if ( empty( $item[2] ) ) continue;
                if ( 'separator' === $item[4] || strpos( $item[4], 'wp-menu-separator' ) !== false ) continue;
                $cached['menus'][] = array(
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
                    $cached['submenus'][ $parent ][] = array(
                        'title' => $item[0],
                        'slug'  => $item[2],
                    );
                }
            }
        }
    }

    // ⭐ مهم: منوهایی که مخفی شدن رو هم دوباره اضافه کن
    // چون کاربر باید بتونه تیکشون رو برداره!
    $hidden = (array) get_option( self::OPTION_KEY, array() );
    $existing_slugs = wp_list_pluck( $cached['menus'], 'slug' );

    foreach ( $hidden as $slug ) {
        if ( ! in_array( $slug, $existing_slugs, true ) ) {
            // این منو مخفی شده و توی کش نبود → دستی اضافه‌اش کن
            $cached['menus'][] = array(
                'title' => $this->guess_title_from_slug( $slug ),
                'slug'  => $slug,
                'icon'  => '',
                'hidden_only' => true, // فلگ برای استایل متفاوت
            );
        }
    }

    // حالا لیست رو با زیرمنوها ترکیب کن
    $result = array();
    foreach ( $cached['menus'] as $menu ) {
        $result[] = $menu;
    }

    foreach ( $cached['submenus'] as $parent_slug => $subs ) {
        foreach ( $subs as $sub ) {
            $result[] = array(
                'title'  => '↳ ' . $sub['title'],
                'slug'   => $sub['slug'],
                'icon'   => '',
                'parent' => $parent_slug,
            );
        }
    }

    return $result;
}

// تلاش برای ساختن عنوان خوانا از slug
private function guess_title_from_slug( $slug ) {
    // اگه توی همه منوها هست، عنوانش رو برگردون
    $map = array(
        'index.php'              => 'پیشخوان',
        'edit.php'               => 'نوشته‌ها',
        'upload.php'             => 'رسانه',
        'edit.php?post_type=page' => 'برگه‌ها',
        'edit-comments.php'      => 'دیدگاه‌ها',
        'themes.php'             => 'نمایش',
        'plugins.php'            => 'افزونه‌ها',
        'users.php'              => 'کاربران',
        'tools.php'              => 'ابزارها',
        'options-general.php'    => 'تنظیمات',
    );

    if ( isset( $map[ $slug ] ) ) {
        return $map[ $slug ];
    }

    // اگه پیدا نشد، از slug بساز
    return sprintf( '⚠️ %s (مخفی‌شده)', $slug );
}

    // گرفتن زیرمنوها
    public function get_admin_submenus() {
        global $submenu;
        if ( empty( $submenu ) ) {
            return array();
        }

        $result = array();
        foreach ( $submenu as $parent => $items ) {
            foreach ( $items as $item ) {
                if ( empty( $item[2] ) ) continue;
                $result[ $parent ][] = array(
                    'title' => $item[0],
                    'slug'  => $item[2],
                );
            }
        }
        return $result;
    }
}