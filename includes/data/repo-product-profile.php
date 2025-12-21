<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Product_Profile {

    public static function get_for_base( $base_product_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'product_profile' );

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE base_product_id = %d",
                $base_product_id
            )
        );
    }

    public static function upsert( $base_product_id, $data ) {
        global $wpdb;
        $table      = PLS_Repositories::table( 'product_profile' );
        $existing   = self::get_for_base( $base_product_id );
        $json_fields = array(
            'basics_json',
            'skin_types_json',
            'benefits_json',
            'key_ingredients_json',
        );

        foreach ( $json_fields as $field ) {
            if ( isset( $data[ $field ] ) && is_array( $data[ $field ] ) ) {
                $data[ $field ] = wp_json_encode( $data[ $field ] );
            }
        }

        if ( isset( $data['gallery_ids'] ) && is_array( $data['gallery_ids'] ) ) {
            $data['gallery_ids'] = implode( ',', array_filter( array_map( 'absint', $data['gallery_ids'] ) ) );
        }

        if ( $existing ) {
            return $wpdb->update(
                $table,
                $data,
                array( 'base_product_id' => $base_product_id ),
                self::prepare_formats( $data ),
                array( '%d' )
            );
        }

        $wpdb->insert(
            $table,
            array_merge( array( 'base_product_id' => $base_product_id ), $data ),
            array_merge( array( '%d' ), self::prepare_formats( $data ) )
        );

        return $wpdb->insert_id;
    }

    private static function prepare_formats( $data ) {
        $formats = array();
        foreach ( $data as $key => $value ) {
            switch ( $key ) {
                case 'featured_image_id':
                case 'label_enabled':
                case 'label_requires_file':
                    $formats[] = '%d';
                    break;
                case 'label_price_per_unit':
                    $formats[] = '%f';
                    break;
                default:
                    $formats[] = '%s';
            }
        }

        return $formats;
    }

    public static function delete_for_base( $base_product_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'product_profile' );

        return $wpdb->delete(
            $table,
            array( 'base_product_id' => $base_product_id ),
            array( '%d' )
        );
    }
}
