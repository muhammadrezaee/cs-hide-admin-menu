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

    public static function should_hide_for_current_user( $slug, $config ) {
        if ( ! is_array( $config ) ) {
            return true;
        }

        if ( empty( $config ) ) {
            return true;
        }

        if ( in_array( 'all', $config, true ) ) {
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

        foreach ( (array) $user->roles as $user_role ) {
            if ( in_array( $user_role, $config, true ) ) {
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