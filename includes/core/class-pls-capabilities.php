<?php
/**
 * Capabilities.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Capabilities {

    const CAP_PRODUCTS  = 'pls_manage_products';
    const CAP_ATTRS     = 'pls_manage_attributes';
    const CAP_BUNDLES   = 'pls_manage_bundles';
    const ROLE_PLS_USER = 'pls_user';

    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'maybe_add_caps' ) );
        add_action( 'admin_init', array( __CLASS__, 'maybe_create_pls_user_role' ) );
        add_action( 'admin_init', array( __CLASS__, 'maybe_assign_pls_user_role' ) );
    }

    /**
     * Create PLS user role if it doesn't exist.
     */
    public static function maybe_create_pls_user_role() {
        if ( get_option( 'pls_role_created' ) ) {
            return;
        }

        // Create PLS user role with all PLS capabilities
        $pls_user_role = add_role(
            self::ROLE_PLS_USER,
            __( 'PLS User', 'pls-private-label-store' ),
            array(
                'read' => true,
                'manage_woocommerce' => true,
                self::CAP_PRODUCTS => true,
                self::CAP_ATTRS => true,
                self::CAP_BUNDLES => true,
            )
        );

        if ( $pls_user_role ) {
            update_option( 'pls_role_created', 1 );
        }
    }

    /**
     * Assign PLS user role to Robert and Raniya if they exist.
     * Also assigns role to any users with @bodoci.com email domain.
     */
    public static function maybe_assign_pls_user_role() {
        if ( get_option( 'pls_users_assigned' ) ) {
            return;
        }

        // Find users by username, email, or domain
        $users_to_assign = array();
        
        // Check for Robert (case-insensitive)
        $robert = get_user_by( 'login', 'robert' );
        if ( ! $robert ) {
            $robert = get_user_by( 'login', 'Rober' );
        }
        if ( ! $robert ) {
            $users = get_users( array( 'search' => 'robert', 'search_columns' => array( 'user_login', 'user_email', 'display_name' ) ) );
            if ( ! empty( $users ) ) {
                $robert = $users[0];
            }
        }
        if ( $robert ) {
            $users_to_assign[] = $robert->ID;
        }

        // Check for Raniya (case-insensitive)
        $raniya = get_user_by( 'login', 'raniya' );
        if ( ! $raniya ) {
            $raniya = get_user_by( 'login', 'Raniya' );
        }
        if ( ! $raniya ) {
            $users = get_users( array( 'search' => 'raniya', 'search_columns' => array( 'user_login', 'user_email', 'display_name' ) ) );
            if ( ! empty( $users ) ) {
                $raniya = $users[0];
            }
        }
        if ( $raniya ) {
            $users_to_assign[] = $raniya->ID;
        }

        // Find all users with @bodoci.com email domain
        global $wpdb;
        $bodoci_user_ids = $wpdb->get_col( $wpdb->prepare(
            "SELECT ID FROM {$wpdb->users} WHERE user_email LIKE %s",
            '%@bodoci.com'
        ) );

        foreach ( $bodoci_user_ids as $user_id ) {
            if ( ! in_array( $user_id, $users_to_assign, true ) ) {
                $users_to_assign[] = $user_id;
            }
        }

        // Assign PLS user role to found users
        foreach ( $users_to_assign as $user_id ) {
            $user = new WP_User( $user_id );
            if ( $user && ! in_array( self::ROLE_PLS_USER, $user->roles, true ) ) {
                $user->add_role( self::ROLE_PLS_USER );
            }
        }

        if ( ! empty( $users_to_assign ) ) {
            update_option( 'pls_users_assigned', 1 );
        }
    }

    public static function maybe_add_caps() {
        // Only add caps once. If you need removal, handle separately.
        if ( get_option( 'pls_caps_added' ) ) {
            return;
        }

        $roles = array( 'administrator', 'shop_manager', self::ROLE_PLS_USER );
        foreach ( $roles as $role_key ) {
            $role = get_role( $role_key );
            if ( $role ) {
                $role->add_cap( self::CAP_PRODUCTS );
                $role->add_cap( self::CAP_ATTRS );
                $role->add_cap( self::CAP_BUNDLES );
            }
        }

        update_option( 'pls_caps_added', 1 );
    }
}
