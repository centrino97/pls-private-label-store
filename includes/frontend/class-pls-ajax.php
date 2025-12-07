<?php
/**
 * Frontend AJAX endpoints for offers / upgrades.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Ajax {

    public static function init() {
        add_action( 'wp_ajax_pls_get_offers', array( __CLASS__, 'get_offers' ) );
        add_action( 'wp_ajax_nopriv_pls_get_offers', array( __CLASS__, 'get_offers' ) );

        add_action( 'wp_ajax_pls_apply_offer', array( __CLASS__, 'apply_offer' ) );
        add_action( 'wp_ajax_nopriv_pls_apply_offer', array( __CLASS__, 'apply_offer' ) );
    }

    public static function get_offers() {
        check_ajax_referer( 'pls_offers', 'nonce' );

        // TODO: Replace with real eligibility logic reading PLS bundle tables.
        wp_send_json_success(
            array(
                'offers' => array(
                    array(
                        'id' => 1,
                        'title' => 'Upgrade offer (stub)',
                        'description' => 'This is a placeholder offer. Implement eligibility + bundle mapping.',
                        'action' => array(
                            'type' => 'apply_offer',
                            'offer_id' => 1,
                        ),
                    ),
                ),
            )
        );
    }

    public static function apply_offer() {
        check_ajax_referer( 'pls_offers', 'nonce' );

        if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
            wp_send_json_error( array( 'message' => 'Cart not available.' ), 400 );
        }

        // TODO: Replace with real "upgrade" behavior (add bundle items, remove originals, etc.)
        wp_send_json_success( array( 'message' => 'Offer applied (stub).' ) );
    }
}
