<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CS_AHM_Settings {

    const OPTION_KEY = 'cs_ahm_hidden_menus';

    public function __construct() {
        add_action( 'admin_menu', array( $this, 'add_settings_page' ) );
        add_action( 'admin_init', array( $this, 'register_settings' ) );
        add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_assets' ) );
    }

    public function add_settings_page() {
        add_options_page(
            __( 'مخفی‌سازی منوهای مدیریت', 'cs-admin-hide-menu' ),
            __( 'مخفی‌سازی منوها', 'cs-admin-hide-menu' ),
            'manage_options',
            'cs-admin-hide-menu',
            array( $this, 'render_page' )
        );
    }

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
        // NOTE: کش منوها عمداً اینجا پاک نمی‌شه؛ admin_init بعد از admin_menu اجرا می‌شه
        // و پاک‌کردن کش باعث می‌شد لیستِ رندرشده (با منوهای حذف‌شده) با لیستِ هنگام
        // ذخیره (لیست کامل) یکی نباشه.
    }

    /**
     * پاک‌سازی ورودی — اسلاگ هر منو از فیلد مخفیِ همون ردیف خونده می‌شه
     *
     * این تابع باید idempotent باشه: وقتی option هنوز ثبت نشده یا مقدار فعلی‌اش
     * با 'default' برابره، update_option() از add_option() رد می‌شه و اون هم دوباره
     * sanitize رو روی خروجیِ همین تابع صدا می‌زنه. اگه خالی برگرده، همهٔ تنظیمات
     * پاک می‌شه (و کاربر فقط پیام «ذخیره شد» می‌بینه).
     */
    public function sanitize( $input ) {
        if ( ! is_array( $input ) ) {
            return array();
        }

        $clean = array();

        foreach ( $input as $key => $data ) {
            // ۱) شکل خام فرم: [index => ['slug' => ..., 'enabled' => 1, 'roles' => [...]]]
            if ( is_array( $data ) && isset( $data['slug'] ) ) {
                $slug    = sanitize_text_field( (string) $data['slug'] );
                $roles   = isset( $data['roles'] ) ? $data['roles'] : array();
                $enabled = ! empty( $data['enabled'] );
            }
            // ۲) شکل از قبلِ پاک‌شده: ['edit.php' => ['editor', ...]] ← فراخوانی دوم sanitize
            elseif ( is_string( $key ) && is_array( $data ) && ! isset( $data['enabled'] ) ) {
                $slug    = sanitize_text_field( $key );
                $roles   = $data;
                $enabled = true;
            }
            // ۳) فرمت قدیمی: [0 => 'edit.php']
            elseif ( is_int( $key ) && is_string( $data ) ) {
                $slug    = sanitize_text_field( $data );
                $roles   = array();
                $enabled = true;
            } else {
                continue;
            }

            if ( '' === $slug || strlen( $slug ) > 191 ) {
                continue;
            }

            if ( ! preg_match( '/^[a-zA-Z0-9_\-\.\?=&:\/]+$/', $slug ) ) {
                continue;
            }

            // اگه تیک نخورده بود، رد شو
            if ( ! $enabled ) {
                continue;
            }

            // محافظت: منو تنظیمات خودمون هرگز مخفی نشه
            if ( 'options-general.php' === $slug ) {
                continue;
            }

            $clean[ $slug ] = self::sanitize_roles( $roles );
        }

        return $clean;
    }

    /**
     * نرمال‌سازی لیست نقش‌ها
     */
    private static function sanitize_roles( $roles ) {
        $clean = array();

        if ( is_array( $roles ) ) {
            foreach ( $roles as $role ) {
                if ( ! is_scalar( $role ) ) {
                    continue;
                }
                $role = sanitize_key( $role );
                if ( $role ) {
                    $clean[] = $role;
                }
            }
        }

        return array_values( array_unique( $clean ) );
    }

    public function enqueue_assets( $hook ) {
        if ( 'settings_page_cs-admin-hide-menu' !== $hook ) {
            return;
        }

        wp_enqueue_style( 'cs-ahm-admin', CS_AHM_URL . 'assets/css/admin.css', array(), CS_AHM_VERSION );
        wp_enqueue_script( 'cs-ahm-admin', CS_AHM_URL . 'assets/js/admin.js', array( 'jquery' ), CS_AHM_VERSION, true );

        wp_localize_script( 'cs-ahm-admin', 'csAhm', array(
            'roles' => CS_AHM_Roles::get_all_roles(),
            'i18n'  => array(
                'forAll'   => __( 'برای همه', 'cs-admin-hide-menu' ),
                'show'     => __( 'نمایش', 'cs-admin-hide-menu' ),
                'roleWord' => __( 'نقش', 'cs-admin-hide-menu' ),
            ),
        ) );
    }

    public function render_page() {
        if ( ! current_user_can( 'manage_options' ) ) {
            return;
        }

        $menus  = $this->get_admin_menus();
        $hidden = $this->get_hidden_config();
        $roles  = CS_AHM_Roles::get_all_roles();
        ?>
        <div class="wrap cs-ahm-wrap">
            <h1>
                <span class="dashicons dashicons-hidden"></span>
                <?php esc_html_e( 'مخفی‌سازی منوهای پنل مدیریت', 'cs-admin-hide-menu' ); ?>
                <span class="cs-ahm-version">v<?php echo esc_html( CS_AHM_VERSION ); ?></span>
            </h1>

            <p class="description">
                <?php esc_html_e( 'منوهایی که می‌خوای مخفی بشن رو تیک بزن. می‌تونی مشخص کنی فقط برای چه نقش‌هایی مخفی بشه.', 'cs-admin-hide-menu' ); ?>
            </p>

            <div class="cs-ahm-toolbar">
                <button type="button" class="button" id="cs-ahm-select-all"><?php esc_html_e( 'انتخاب همه', 'cs-admin-hide-menu' ); ?></button>
                <button type="button" class="button" id="cs-ahm-deselect-all"><?php esc_html_e( 'لغو انتخاب همه', 'cs-admin-hide-menu' ); ?></button>
                <button type="button" class="button" id="cs-ahm-expand-roles"><?php esc_html_e( 'نمایش نقش‌ها', 'cs-admin-hide-menu' ); ?></button>
                <button type="button" class="button" id="cs-ahm-collapse-roles"><?php esc_html_e( 'بستن نقش‌ها', 'cs-admin-hide-menu' ); ?></button>
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
                            <th width="150"><?php esc_html_e( 'وضعیت', 'cs-admin-hide-menu' ); ?></th>
                            <th width="40"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ( $menus as $index => $menu ) : ?>
                            <?php
                            $slug         = $menu['slug'];
                            $is_self      = ( 'options-general.php' === $slug );
                            $is_hidden    = isset( $hidden[ $slug ] );
                            $saved_roles  = $is_hidden ? (array) $hidden[ $slug ] : array();
                            $hide_for_all = $is_hidden && empty( $saved_roles );
                            ?>
                            <tr class="cs-ahm-row <?php echo $is_self ? 'cs-ahm-protected' : ''; ?>"
                                data-slug="<?php echo esc_attr( $slug ); ?>"
                                data-index="<?php echo esc_attr( $index ); ?>">

                                <td>
                                    <input type="hidden"
                                           name="cs_ahm_hidden_menus[<?php echo esc_attr( $index ); ?>][slug]"
                                           value="<?php echo esc_attr( $slug ); ?>" />
                                    <label class="cs-ahm-switch">
                                        <input type="checkbox"
                                               class="cs-ahm-toggle"
                                               name="cs_ahm_hidden_menus[<?php echo esc_attr( $index ); ?>][enabled]"
                                               value="1"
                                               <?php checked( $is_hidden ); ?>
                                               <?php disabled( $is_self ); ?> />
                                        <span class="cs-ahm-slider"></span>
                                    </label>
                                </td>

                                <td class="cs-ahm-icon">
                                    <?php if ( ! empty( $menu['icon'] ) ) : ?>
                                        <?php if ( str_starts_with( $menu['icon'], 'dashicons-' ) ) : ?>
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
                                    <div class="cs-ahm-slug"><code><?php echo esc_html( $slug ); ?></code></div>
                                </td>

                                <td>
                                    <?php if ( $is_hidden ) : ?>
                                        <?php if ( $hide_for_all ) : ?>
                                            <span class="cs-ahm-status cs-ahm-status-all">
                                                <?php esc_html_e( 'برای همه', 'cs-admin-hide-menu' ); ?>
                                            </span>
                                        <?php else : ?>
                                            <span class="cs-ahm-status cs-ahm-status-roles">
                                                <?php
                                                $role_names = array();
                                                foreach ( $saved_roles as $r ) {
                                                    if ( isset( $roles[ $r ] ) ) {
                                                        $role_names[] = $roles[ $r ]['name'];
                                                    }
                                                }
                                                echo esc_html( count( $role_names ) . ' ' . __( 'نقش', 'cs-admin-hide-menu' ) );
                                                ?>
                                            </span>
                                        <?php endif; ?>
                                    <?php else : ?>
                                        <span class="cs-ahm-status cs-ahm-status-none"><?php esc_html_e( 'نمایش', 'cs-admin-hide-menu' ); ?></span>
                                    <?php endif; ?>
                                </td>

                                <td>
                                    <button type="button" class="button-link cs-ahm-expand-single" <?php disabled( ! $is_hidden ); ?>>
                                        <span class="dashicons dashicons-arrow-down-alt2"></span>
                                    </button>
                                </td>
                            </tr>

                            <tr class="cs-ahm-roles-row" data-slug="<?php echo esc_attr( $slug ); ?>" data-index="<?php echo esc_attr( $index ); ?>" style="display:none;">
                                <td colspan="5">
                                    <div class="cs-ahm-roles-panel">
                                        <p class="cs-ahm-roles-title">
                                            <?php esc_html_e( 'این منو برای چه نقش‌هایی مخفی بشه؟', 'cs-admin-hide-menu' ); ?>
                                        </p>

                                        <label class="cs-ahm-role-item cs-ahm-role-all">
                                            <input type="checkbox"
                                                   class="cs-ahm-role-all-check"
                                                   data-slug="<?php echo esc_attr( $slug ); ?>" />
                                            <strong><?php esc_html_e( 'همه نقش‌ها (شامل ادمین)', 'cs-admin-hide-menu' ); ?></strong>
                                        </label>

                                        <div class="cs-ahm-roles-grid">
                                            <?php foreach ( $roles as $role_slug => $role_data ) : ?>
                                                <?php
                                                $checked  = in_array( $role_slug, $saved_roles, true );
                                                $disabled = $hide_for_all;
                                                ?>
                                                <label class="cs-ahm-role-item">
                                                    <input type="checkbox"
                                                           class="cs-ahm-role-check"
                                                           name="cs_ahm_hidden_menus[<?php echo esc_attr( $index ); ?>][roles][]"
                                                           value="<?php echo esc_attr( $role_slug ); ?>"
                                                           data-slug="<?php echo esc_attr( $slug ); ?>"
                                                           <?php checked( $checked || $hide_for_all ); ?>
                                                           <?php disabled( $disabled ); ?> />
                                                    <span class="cs-ahm-role-name"><?php echo esc_html( $role_data['name'] ); ?></span>
                                                    <span class="cs-ahm-role-count"><?php echo esc_html( $role_data['count'] ); ?></span>
                                                </label>
                                            <?php endforeach; ?>
                                        </div>

                                        <p class="cs-ahm-roles-hint">
                                            ⚠️ <?php esc_html_e( 'اگه هیچ نقشی انتخاب نکنی، منو برای همه مخفی میشه.', 'cs-admin-hide-menu' ); ?>
                                        </p>
                                    </div>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>

                <?php submit_button( __( 'ذخیره تغییرات', 'cs-admin-hide-menu' ) ); ?>
            </form>

            <p class="cs-ahm-footer">
                <?php printf(
                    esc_html__( 'ساخته‌شده با ❤️ توسط %s', 'cs-admin-hide-menu' ),
                    '<a href="https://codsoft.ir" target="_blank" rel="noopener">CodSoft</a>'
                ); ?>
            </p>
        </div>
        <?php
    }

    /**
     * گرفتن کانفیگ مخفی‌شده‌ها با نرمال‌سازی فرمت
     */
    public function get_hidden_config() {
        $raw        = (array) get_option( self::OPTION_KEY, array() );
        $normalized = array();

        foreach ( $raw as $key => $value ) {
            // فرمت قدیمی: [0 => 'edit.php']
            if ( is_int( $key ) && is_string( $value ) ) {
                $normalized[ $value ] = array();
            }
            // فرمت جدید: ['edit.php' => ['editor']]
            elseif ( is_string( $key ) && is_array( $value ) ) {
                $normalized[ $key ] = $value;
            }
        }

        return $normalized;
    }

    /**
     * گرفتن لیست منوها با کش
     */
    public function get_admin_menus() {
        $cached = get_transient( 'cs_ahm_all_menus' );

        if ( false === $cached || empty( $cached['menus'] ) ) {
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

            set_transient( 'cs_ahm_all_menus', $cached, DAY_IN_SECONDS );
        }

        // اضافه کردن منوهای مخفی که توی کش نیستن
        $hidden         = $this->get_hidden_config();
        $existing_slugs = wp_list_pluck( $cached['menus'], 'slug' );

        foreach ( $hidden as $slug => $roles ) {
            if ( ! in_array( $slug, $existing_slugs, true ) ) {
                $cached['menus'][] = array(
                    'title'       => $this->guess_title_from_slug( $slug ),
                    'slug'        => $slug,
                    'icon'        => '',
                    'hidden_only' => true,
                );
            }
        }

        $result = array();
        $seen   = array();

        foreach ( $cached['menus'] as $menu ) {
            // هر اسلاگ فقط یک ردیف: زیرمنویی که اسلاگش با منوی اصلی یکیه
            // (مثلاً «نوشته‌ها» و «↳ همه نوشته‌ها» هر دو edit.php) تکراریه و
            // باعث می‌شد تیکِ یکی، تیکِ اون یکی رو هم نگه داره و ذخیره بی‌اثر بشه.
            if ( isset( $seen[ $menu['slug'] ] ) ) {
                continue;
            }
            $seen[ $menu['slug'] ] = true;
            $result[]              = $menu;
        }

        foreach ( $cached['submenus'] as $parent_slug => $subs ) {
            foreach ( $subs as $sub ) {
                if ( isset( $seen[ $sub['slug'] ] ) ) {
                    continue;
                }
                $seen[ $sub['slug'] ] = true;

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

    private function guess_title_from_slug( $slug ) {
        $map = array(
            'index.php'               => 'پیشخوان',
            'edit.php'                => 'نوشته‌ها',
            'upload.php'              => 'رسانه',
            'edit.php?post_type=page' => 'برگه‌ها',
            'edit-comments.php'       => 'دیدگاه‌ها',
            'themes.php'              => 'نمایش',
            'plugins.php'             => 'افزونه‌ها',
            'users.php'               => 'کاربران',
            'tools.php'               => 'ابزارها',
            'options-general.php'     => 'تنظیمات',
        );

        return isset( $map[ $slug ] ) ? $map[ $slug ] : sprintf( '⚠️ %s', $slug );
    }
}