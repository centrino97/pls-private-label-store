<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Bundle {

    /**
     * Get count of all bundles.
     *
     * @return int
     */
    public static function count() {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle' );
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    public static function all() {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle' );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
    }

    public static function get( $id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    }

    public static function get_by_bundle_key( $bundle_key ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE bundle_key = %s", $bundle_key ) );
    }

    public static function insert( $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle' );

        $wpdb->insert(
            $table,
            array(
                'bundle_key'    => $data['bundle_key'],
                'slug'          => $data['slug'],
                'name'          => $data['name'],
                'base_price'    => isset( $data['base_price'] ) ? $data['base_price'] : null,
                'pricing_mode'  => isset( $data['pricing_mode'] ) ? $data['pricing_mode'] : 'fixed',
                'discount_amount' => isset( $data['discount_amount'] ) ? $data['discount_amount'] : null,
                'status'        => isset( $data['status'] ) ? $data['status'] : 'draft',
                'offer_rules_json' => isset( $data['offer_rules_json'] ) ? $data['offer_rules_json'] : null,
            ),
            array( '%s', '%s', '%s', '%f', '%s', '%f', '%s', '%s' )
        );

        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle' );

        $update_data = array();
        $update_format = array();

        if ( isset( $data['slug'] ) ) {
            $update_data['slug'] = $data['slug'];
            $update_format[] = '%s';
        }
        if ( isset( $data['name'] ) ) {
            $update_data['name'] = $data['name'];
            $update_format[] = '%s';
        }
        if ( isset( $data['base_price'] ) ) {
            $update_data['base_price'] = $data['base_price'];
            $update_format[] = '%f';
        }
        if ( isset( $data['pricing_mode'] ) ) {
            $update_data['pricing_mode'] = $data['pricing_mode'];
            $update_format[] = '%s';
        }
        if ( isset( $data['discount_amount'] ) ) {
            $update_data['discount_amount'] = $data['discount_amount'];
            $update_format[] = '%f';
        }
        if ( isset( $data['status'] ) ) {
            $update_data['status'] = $data['status'];
            $update_format[] = '%s';
        }
        if ( isset( $data['offer_rules_json'] ) ) {
            $update_data['offer_rules_json'] = $data['offer_rules_json'];
            $update_format[] = '%s';
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        $update_format[] = '%d'; // For WHERE clause

        return $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            $update_format,
            array( '%d' )
        );
    }

    public static function set_wc_product_id( $id, $wc_product_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle' );

        return $wpdb->update(
            $table,
            array( 'wc_product_id' => $wc_product_id ),
            array( 'id' => $id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    public static function delete( $id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle' );

        // Delete bundle items first
        PLS_Repo_Bundle_Item::delete_for_bundle( $id );

        return $wpdb->delete(
            $table,
            array( 'id' => $id ),
            array( '%d' )
        );
    }
}

final class PLS_Repo_Bundle_Item {

    public static function for_bundle( $bundle_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle_item' );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE bundle_id = %d ORDER BY sort_order ASC, id ASC",
                $bundle_id
            )
        );
    }

    public static function insert( $bundle_id, $base_product_id, $tier_key, $units_override, $qty, $sort_order ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle_item' );

        $wpdb->insert(
            $table,
            array(
                'bundle_id'      => $bundle_id,
                'base_product_id' => $base_product_id,
                'tier_key'       => $tier_key,
                'units_override' => $units_override,
                'qty'            => $qty,
                'sort_order'     => $sort_order,
            ),
            array( '%d', '%d', '%s', '%d', '%d', '%d' )
        );

        return $wpdb->insert_id;
    }

    public static function delete_for_bundle( $bundle_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'bundle_item' );

        return $wpdb->delete(
            $table,
            array( 'bundle_id' => $bundle_id ),
            array( '%d' )
        );
    }
}
