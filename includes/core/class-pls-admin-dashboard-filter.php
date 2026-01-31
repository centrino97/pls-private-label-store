<?php
/**
 * Filters admin dashboard for specific users (bodocibiophysics.com).
 * Hides all WordPress menus except PLS and WooCommerce.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Admin_Dashboard_Filter {

    /**
     * Allowed email domains for simplified dashboard.
     */
    const ALLOWED_DOMAINS = array( 'bodocibiophysics.com' );

    /**
     * Full access email addresses (see everything).
     */
    const FULL_ACCESS_EMAILS = array( 'robertbodoci@gmail.com' );

    /**
     * Initialize dashboard filtering.
     */
    public static function init() {
        // Always register hooks - callbacks will check if filtering is needed
        // Remove all admin menu items except PLS and WooCommerce
        add_action( 'admin_menu', array( __CLASS__, 'remove_admin_menus' ), 999 );
        
        // Remove admin bar items
        add_action( 'admin_bar_menu', array( __CLASS__, 'remove_admin_bar_items' ), 999 );
        
        // Redirect away from restricted pages
        add_action( 'admin_init', array( __CLASS__, 'redirect_restricted_pages' ) );
    }

    /**
     * Check if current user should have filtered dashboard.
     *
     * @return bool
     */
    private static function should_filter_dashboard() {
        if ( ! is_user_logged_in() ) {
            return false;
        }

        $current_user = wp_get_current_user();
        if ( ! $current_user || ! $current_user->exists() ) {
            return false;
        }

        $user_email = $current_user->user_email;
        if ( empty( $user_email ) ) {
            return false;
        }

        // Full access for specific email - don't filter
        if ( in_array( $user_email, self::FULL_ACCESS_EMAILS, true ) ) {
            return false;
        }

        // Filtered access for domain
        foreach ( self::ALLOWED_DOMAINS as $domain ) {
            if ( strpos( $user_email, '@' . $domain ) !== false ) {
                return true;
            }
        }

        return false;
    }

    /**
     * Remove admin menu items except PLS and WooCommerce.
     */
    public static function remove_admin_menus() {
        if ( ! self::should_filter_dashboard() ) {
            return;
        }
        
        global $menu, $submenu;

        // List of menu slugs to keep
        $allowed_menus = array(
            'pls-dashboard', // PLS main menu
            'woocommerce',   // WooCommerce main menu
            'index.php',     // Dashboard (home)
        );

        // Remove all menu items except allowed ones
        if ( isset( $menu ) && is_array( $menu ) ) {
            foreach ( $menu as $key => $item ) {
                if ( ! isset( $item[2] ) ) {
                    continue;
                }

                $menu_slug = $item[2];
                
                // Keep PLS, WooCommerce, and Dashboard
                if ( in_array( $menu_slug, $allowed_menus, true ) ) {
                    continue;
                }

                // Keep separator items
                if ( empty( $item[0] ) ) {
                    continue;
                }

                // Remove everything else
                unset( $menu[ $key ] );
            }
        }

        // Clean up submenus - remove all except PLS and WooCommerce submenus
        if ( isset( $submenu ) && is_array( $submenu ) ) {
            foreach ( $submenu as $parent => $items ) {
                // Keep PLS and WooCommerce submenus
                if ( in_array( $parent, array( 'pls-dashboard', 'woocommerce', 'index.php' ), true ) ) {
                    continue;
                }

                // Remove all other submenus
                unset( $submenu[ $parent ] );
            }
        }
    }

    /**
     * Remove admin bar items except essential ones.
     */
    public static function remove_admin_bar_items( $wp_admin_bar ) {
        if ( ! self::should_filter_dashboard() ) {
            return;
        }
        
        if ( ! $wp_admin_bar ) {
            return;
        }

        // Keep only essential items
        $keep_items = array( 'site-name', 'my-account', 'logout' );

        // Remove all nodes except allowed ones
        $nodes = $wp_admin_bar->get_nodes();
        foreach ( $nodes as $node ) {
            if ( ! in_array( $node->id, $keep_items, true ) ) {
                $wp_admin_bar->remove_node( $node->id );
            }
        }
    }

    /**
     * Redirect away from restricted admin pages.
     */
    public static function redirect_restricted_pages() {
        if ( ! self::should_filter_dashboard() ) {
            return;
        }
        
        $screen = get_current_screen();
        if ( ! $screen ) {
            return;
        }

        // Allow PLS and WooCommerce pages
        if ( strpos( $screen->id, 'pls-' ) === 0 || strpos( $screen->id, 'woocommerce' ) === 0 ) {
            return;
        }

        // Allow dashboard
        if ( $screen->id === 'dashboard' ) {
            return;
        }

        // Redirect everything else to PLS dashboard
        wp_safe_redirect( admin_url( 'admin.php?page=pls-dashboard' ) );
        exit;
    }
}
