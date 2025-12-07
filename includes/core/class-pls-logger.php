<?php
/**
 * Lightweight logger wrapper.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Logger {

    public static function info( $message, $context = array() ) {
        if ( function_exists( 'wc_get_logger' ) ) {
            $logger = wc_get_logger();
            $logger->info( $message . ' ' . wp_json_encode( $context ), array( 'source' => 'pls-private-label-store' ) );
            return;
        }
        // Fallback to error_log for non-Woo contexts.
        error_log( '[PLS] ' . $message . ' ' . wp_json_encode( $context ) ); // phpcs:ignore
    }
}
