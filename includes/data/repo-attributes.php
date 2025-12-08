<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Attributes {

    public static function attrs_all() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY sort_order ASC, id ASC" );
    }

    public static function insert_attr( $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        $wpdb->insert(
            $table,
            array(
                'attr_key'    => $data['attr_key'],
                'label'       => $data['label'],
                'is_variation'=> $data['is_variation'],
                'sort_order'  => $data['sort_order'],
            ),
            array( '%s', '%s', '%d', '%d' )
        );

        return $wpdb->insert_id;
    }

    public static function set_wc_attribute_id( $id, $wc_attribute_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        return $wpdb->update(
            $table,
            array( 'wc_attribute_id' => $wc_attribute_id ),
            array( 'id' => $id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    public static function values_for_attr( $attribute_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE attribute_id = %d ORDER BY sort_order ASC, id ASC",
                $attribute_id
            )
        );
    }

    public static function insert_value( $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        $wpdb->insert(
            $table,
            array(
                'attribute_id' => $data['attribute_id'],
                'value_key'    => $data['value_key'],
                'label'        => $data['label'],
                'seo_slug'     => $data['seo_slug'],
                'seo_title'    => $data['seo_title'],
                'seo_description' => $data['seo_description'],
                'sort_order'   => $data['sort_order'],
            ),
            array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' )
        );

        return $wpdb->insert_id;
    }

    public static function set_term_id_for_value( $id, $term_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        return $wpdb->update(
            $table,
            array( 'term_id' => $term_id ),
            array( 'id' => $id ),
            array( '%d' ),
            array( '%d' )
        );
    }

    public static function swatch_for_value( $attribute_value_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'swatch' );

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE attribute_value_id = %d",
                $attribute_value_id
            )
        );
    }

    public static function upsert_swatch_for_value( $attribute_value_id, $type, $value ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'swatch' );

        $existing_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attribute_value_id = %d",
                $attribute_value_id
            )
        );

        if ( $existing_id ) {
            $wpdb->update(
                $table,
                array(
                    'swatch_type'  => $type,
                    'swatch_value' => $value,
                ),
                array( 'id' => $existing_id ),
                array( '%s', '%s' ),
                array( '%d' )
            );
            return $existing_id;
        }

        $wpdb->insert(
            $table,
            array(
                'attribute_value_id' => $attribute_value_id,
                'swatch_type'        => $type,
                'swatch_value'       => $value,
            ),
            array( '%d', '%s', '%s' )
        );

        return $wpdb->insert_id;
    }
}
