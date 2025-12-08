<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Pack_Tier {

    public static function for_base( $base_product_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'pack_tier' );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE base_product_id = %d ORDER BY sort_order ASC, id ASC",
                $base_product_id
            )
        );
    }

    public static function upsert( $base_product_id, $tier_key, $units, $price, $enabled, $sort_order ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'pack_tier' );

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE base_product_id = %d AND tier_key = %s",
                $base_product_id,
                $tier_key
            )
        );

        if ( $existing_id ) {
            $wpdb->update(
                $table,
                array(
                    'units'      => $units,
                    'price'      => $price,
                    'is_enabled' => $enabled,
                    'sort_order' => $sort_order,
                ),
                array( 'id' => $existing_id ),
                array( '%d', '%f', '%d', '%d' ),
                array( '%d' )
            );
            return $existing_id;
        }

        $wpdb->insert(
            $table,
            array(
                'base_product_id' => $base_product_id,
                'tier_key'        => $tier_key,
                'units'           => $units,
                'price'           => $price,
                'is_enabled'      => $enabled,
                'sort_order'      => $sort_order,
            ),
            array( '%d', '%s', '%d', '%f', '%d', '%d' )
        );

        return $wpdb->insert_id;
    }

    public static function set_wc_variation_id( $base_product_id, $tier_key, $variation_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'pack_tier' );

        return $wpdb->update(
            $table,
            array( 'wc_variation_id' => $variation_id ),
            array(
                'base_product_id' => $base_product_id,
                'tier_key'        => $tier_key,
            ),
            array( '%d' ),
            array( '%d', '%s' )
        );
    }
}
