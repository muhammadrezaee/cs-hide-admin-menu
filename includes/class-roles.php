<?php
if ( ! defined( 'ABSPATH' ) ) exit;

class CS_AHM_Roles {

    public function __construct() {
        // فقط متدهای استاتیک
    }

    public static function get_all_roles() {
        $wp_roles = wp_roles();
        $roles    = array();

        foreach ( $wp_roles->roles as $slug => $role ) {
            $roles[ $slug ] = array(
                'name'  => translate_user_role( $role['name'] ),
                'count' => self::count_users_in_role( $slug ),
            );
        }

        return $roles;
    }

    private static function count_users_in_role( $role ) {
        $count = count_users();
        return isset( $count['avail_roles'][ $role ] ) ? $count['avail_roles'][ $role ] : 0;
    }

    /**
     * نرمال‌سازی کانفیگِ یک منو به فرمت یکدست:
     *
     *   [ 'roles' => ['editor', ...] | ['all'], 'users' => [12, 34] ]
     *
     * سه فرمت ورودی رو می‌پذیره:
     *  ۱) فرمت جدید: ['roles' => [...], 'users' => [...]]
     *  ۲) فرمت نقش‌محور قدیمی: ['editor', 'author'] یا [] (خالی = برای همه)
     *  ۳) هر نوع دادهٔ خراب → به‌صورت امن «برای همه» برگشت داده می‌شه
     *
     * قواعد:
     *  - نقش‌ها با sanitize_key پاک‌سازی و یکتا می‌شن؛ وجود 'all' کل لیست رو به ['all'] فشرده می‌کنه.
     *  - شناسهٔ کاربرها عدد صحیح مثبت و یکتا.
     *  - «نقش خالی + کاربر خالی» یعنی مخفی‌سازی برای همه.
     *  - این تابع باید idempotent باشه: normalize(normalize(x)) === normalize(x)
     */
    public static function normalize_config( $value ) {
        $raw_roles = array();
        $raw_users = array();

        if ( is_array( $value ) ) {
            if ( array_key_exists( 'roles', $value ) || array_key_exists( 'users', $value ) ) {
                $raw_roles = isset( $value['roles'] ) && is_array( $value['roles'] ) ? $value['roles'] : array();
                $raw_users = isset( $value['users'] ) && is_array( $value['users'] ) ? $value['users'] : array();
            } else {
                // فرمت قدیمی: لیست سادهٔ نقش‌ها
                $raw_roles = $value;
            }
        }

        $roles = array();
        foreach ( $raw_roles as $role ) {
            if ( ! is_scalar( $role ) ) {
                continue;
            }
            $role = sanitize_key( $role );
            if ( $role ) {
                $roles[] = $role;
            }
        }
        $roles = array_values( array_unique( $roles ) );
        if ( in_array( 'all', $roles, true ) ) {
            $roles = array( 'all' );
        }

        $users = array();
        foreach ( $raw_users as $id ) {
            if ( ! is_scalar( $id ) ) {
                continue;
            }
            $id = (int) $id;
            if ( $id > 0 ) {
                $users[] = $id;
            }
        }
        $users = array_values( array_unique( $users ) );

        // ترتیب کلیدها ثابت بمونه تا مقایسهٔ strict در update_option مقدار تکراری رو تشخیص بده
        return array(
            'roles' => $roles,
            'users' => $users,
        );
    }

    /**
     * تصمیم نهایی: آیا این منو برای کاربرِ جاری مخفی بشه؟
     *
     * منطق (اجتماع نقش و کاربر):
     *  - نقش و کاربری انتخاب نشده  → برای همه
     *  - کلید 'all'                 → برای همه
     *  - در غیر این صورت            → اگر نقش کاربر در لیست باشد یا شناسه‌اش در لیست کاربرها باشد
     */
    public static function should_hide_for_current_user( $slug, $config ) {
        if ( ! is_array( $config ) ) {
            return true;
        }

        $config = self::normalize_config( $config );
        $roles  = $config['roles'];
        $users  = $config['users'];

        if ( empty( $roles ) && empty( $users ) ) {
            return true;
        }

        if ( in_array( 'all', $roles, true ) ) {
            return true;
        }

        $user = wp_get_current_user();
        if ( ! $user || ! $user->exists() ) {
            return false;
        }

        // محافظت از ادمین برای منو تنظیمات
        if ( $slug === 'options-general.php' && in_array( 'administrator', (array) $user->roles, true ) ) {
            return false;
        }

        // مخفی‌سازی صرفاً بر اساس کاربر مشخص‌شده
        if ( in_array( (int) $user->ID, $users, true ) ) {
            return true;
        }

        foreach ( (array) $user->roles as $user_role ) {
            if ( in_array( $user_role, $roles, true ) ) {
                return true;
            }
        }

        return false;
    }

    public static function get_current_user_roles() {
        $user = wp_get_current_user();
        return $user && $user->exists() ? (array) $user->roles : array();
    }

    public static function is_current_user_admin() {
        return in_array( 'administrator', self::get_current_user_roles(), true );
    }
}