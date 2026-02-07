<?php
/**
 * Shared helper utilities.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Helpers {

    /**
     * Get tier key from WooCommerce term name.
     *
     * Supports both formats:
     *   - "Trial Pack (50 units)" -> tier_1
     *   - "Trial Pack"            -> tier_1
     *
     * @param string $term_name Term name.
     * @return string|null Tier key like "tier_1" or null.
     */
    public static function get_tier_key_from_term( $term_name ) {
        // Try regex extraction first: "Trial Pack (50 units)" -> 50 -> tier_1
        if ( preg_match( '/\((\d+)\s*units?\)/i', $term_name, $matches ) ) {
            $units = (int) $matches[1];
            $units_to_tier = array(
                50   => 'tier_1',
                100  => 'tier_2',
                250  => 'tier_3',
                500  => 'tier_4',
                1000 => 'tier_5',
            );
            if ( isset( $units_to_tier[ $units ] ) ) {
                return $units_to_tier[ $units ];
            }
        }

        // Fallback: match tier name strings
        $name_mapping = array(
            'Trial Pack'       => 'tier_1',
            'Starter Pack'     => 'tier_2',
            'Brand Entry'      => 'tier_3',
            'Growth Brand'     => 'tier_4',
            'Wholesale Launch'  => 'tier_5',
        );
        foreach ( $name_mapping as $name => $key ) {
            if ( stripos( $term_name, $name ) !== false ) {
                return $key;
            }
        }

        // Last resort: match "tier_N" pattern
        if ( preg_match( '/tier[_\s-]*(\d+)/i', $term_name, $matches ) ) {
            return 'tier_' . $matches[1];
        }

        return null;
    }

    /**
     * Get bundle key from a WooCommerce product ID.
     *
     * @param int $product_id WooCommerce product ID.
     * @return string|null Bundle key or null.
     */
    public static function get_bundle_key_from_product( $product_id ) {
        // Check post meta first (fastest)
        $bundle_key = get_post_meta( $product_id, '_pls_bundle_key', true );
        if ( $bundle_key ) {
            return $bundle_key;
        }

        // Check bundle table via meta reference
        $bundle_id = get_post_meta( $product_id, '_pls_bundle_id', true );
        if ( $bundle_id ) {
            $bundle = PLS_Repo_Bundle::get( $bundle_id );
            if ( $bundle ) {
                return $bundle->bundle_key;
            }
        }

        // Direct DB lookup by wc_product_id
        global $wpdb;
        $table  = PLS_Repositories::table( 'bundle' );
        $result = $wpdb->get_var(
            $wpdb->prepare( "SELECT bundle_key FROM {$table} WHERE wc_product_id = %d LIMIT 1", $product_id )
        );

        return $result ? $result : null;
    }

    /**
     * Get all WooCommerce product IDs that belong to PLS.
     * Includes base product IDs, variation IDs, and bundle IDs.
     *
     * @param bool $include_variations Whether to include variation IDs (slower, requires WC).
     * @param bool $include_bundles    Whether to include bundle WC product IDs.
     * @return array Array of WC product IDs.
     */
    public static function get_pls_wc_product_ids( $include_variations = true, $include_bundles = true ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );
        $ids   = $wpdb->get_col( "SELECT wc_product_id FROM {$table} WHERE wc_product_id IS NOT NULL AND wc_product_id > 0" );
        $ids   = array_map( 'absint', $ids );

        if ( $include_variations && function_exists( 'wc_get_product' ) ) {
            $variation_ids = array();
            foreach ( $ids as $wc_id ) {
                $product = wc_get_product( $wc_id );
                if ( $product && $product->is_type( 'variable' ) ) {
                    $variation_ids = array_merge( $variation_ids, $product->get_children() );
                }
            }
            $ids = array_merge( $ids, $variation_ids );
        }

        if ( $include_bundles ) {
            $bundle_table = PLS_Repositories::table( 'bundle' );
            $bundle_ids   = $wpdb->get_col( "SELECT wc_product_id FROM {$bundle_table} WHERE wc_product_id IS NOT NULL AND wc_product_id > 0" );
            $ids = array_merge( $ids, array_map( 'absint', $bundle_ids ) );
        }

        return array_unique( $ids );
    }
}
