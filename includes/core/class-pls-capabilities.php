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

    public static function init() {
        add_action( 'admin_init', array( __CLASS__, 'maybe_add_caps' ) );
    }

    public static function maybe_add_caps() {
        // Only add caps once. If you need removal, handle separately.
        if ( get_option( 'pls_caps_added' ) ) {
            return;
        }

        $roles = array( 'administrator', 'shop_manager' );
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
