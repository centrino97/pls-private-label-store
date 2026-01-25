<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Base_Product {

    /**
     * Get count of all base products.
     *
     * @return int
     */
    public static function count() {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    public static function all() {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
    }

    public static function get( $id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );
        return $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );
    }

    public static function insert( $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );

        $wpdb->insert(
            $table,
            array(
                'slug'          => $data['slug'],
                'name'          => $data['name'],
                'category_path' => $data['category_path'],
                'status'        => $data['status'],
            ),
            array( '%s', '%s', '%s', '%s' )
        );

        return $wpdb->insert_id;
    }

    public static function update( $id, $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );

        return $wpdb->update(
            $table,
            array(
                'slug'          => $data['slug'],
                'name'          => $data['name'],
                'category_path' => $data['category_path'],
                'status'        => $data['status'],
            ),
            array( 'id' => $id ),
            array( '%s', '%s', '%s', '%s' ),
            array( '%d' )
        );
    }

    public static function set_wc_product_id( $id, $wc_product_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );

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
        $table = PLS_Repositories::table( 'base_product' );

        return $wpdb->delete(
            $table,
            array( 'id' => $id ),
            array( '%d' )
        );
    }

    /**
     * Update stock management fields.
     *
     * @param int   $id   Product ID.
     * @param array $data Stock data.
     * @return int|false
     */
    public static function update_stock( $id, $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );

        $update_data = array();
        $formats = array();

        if ( isset( $data['manage_stock'] ) ) {
            $update_data['manage_stock'] = (int) $data['manage_stock'];
            $formats[] = '%d';
        }

        if ( isset( $data['stock_quantity'] ) ) {
            $update_data['stock_quantity'] = $data['stock_quantity'] !== '' ? (int) $data['stock_quantity'] : null;
            $formats[] = $data['stock_quantity'] !== '' ? '%d' : null;
        }

        if ( isset( $data['stock_status'] ) ) {
            $update_data['stock_status'] = sanitize_text_field( $data['stock_status'] );
            $formats[] = '%s';
        }

        if ( isset( $data['backorders_allowed'] ) ) {
            $update_data['backorders_allowed'] = (int) $data['backorders_allowed'];
            $formats[] = '%d';
        }

        if ( isset( $data['low_stock_threshold'] ) ) {
            $update_data['low_stock_threshold'] = $data['low_stock_threshold'] !== '' ? (int) $data['low_stock_threshold'] : null;
            $formats[] = $data['low_stock_threshold'] !== '' ? '%d' : null;
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        // Filter out null formats for NULL values
        $clean_formats = array_filter( $formats, function( $f ) { return $f !== null; } );
        
        return $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            $clean_formats,
            array( '%d' )
        );
    }

    /**
     * Update cost fields.
     *
     * @param int   $id   Product ID.
     * @param array $data Cost data.
     * @return int|false
     */
    public static function update_costs( $id, $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'base_product' );

        $update_data = array();
        $formats = array();

        if ( isset( $data['shipping_cost'] ) ) {
            $update_data['shipping_cost'] = $data['shipping_cost'] !== '' ? floatval( $data['shipping_cost'] ) : null;
            $formats[] = $data['shipping_cost'] !== '' ? '%f' : null;
        }

        if ( isset( $data['packaging_cost'] ) ) {
            $update_data['packaging_cost'] = $data['packaging_cost'] !== '' ? floatval( $data['packaging_cost'] ) : null;
            $formats[] = $data['packaging_cost'] !== '' ? '%f' : null;
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        // Filter out null formats for NULL values
        $clean_formats = array_filter( $formats, function( $f ) { return $f !== null; } );

        return $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            $clean_formats,
            array( '%d' )
        );
    }
}
