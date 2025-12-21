<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Base_Product {

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
}
