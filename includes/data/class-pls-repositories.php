<?php
/**
 * Repository container (stubs).
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repositories {

    public static function table( $suffix ) {
        global $wpdb;
        return $wpdb->prefix . 'pls_' . $suffix;
    }
}
