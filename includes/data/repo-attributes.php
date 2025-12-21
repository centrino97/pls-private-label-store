<?php
if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Attributes {

    public static function attrs_all() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY id DESC" );
    }

    public static function insert_attr( $data ) {
        global $wpdb;
        $table    = PLS_Repositories::table( 'attribute' );
        $attr_key = ! empty( $data['attr_key'] ) ? $data['attr_key'] : self::generate_unique_attr_key( $data['label'] );

        $wpdb->insert(
            $table,
            array(
                'attr_key'     => $attr_key,
                'label'        => $data['label'],
                'is_variation' => ! empty( $data['is_variation'] ) ? 1 : 0,
                'sort_order'   => isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0,
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
                "SELECT * FROM {$table} WHERE attribute_id = %d ORDER BY id DESC",
                $attribute_id
            )
        );
    }

    public static function insert_value( $data ) {
        global $wpdb;
        $table     = PLS_Repositories::table( 'attribute_value' );
        $value_key = ! empty( $data['value_key'] ) ? $data['value_key'] : self::generate_unique_value_key( $data['attribute_id'], $data['label'] );

        $wpdb->insert(
            $table,
            array(
                'attribute_id' => $data['attribute_id'],
                'value_key'    => $value_key,
                'label'        => $data['label'],
                'seo_slug'     => isset( $data['seo_slug'] ) ? $data['seo_slug'] : '',
                'seo_title'    => isset( $data['seo_title'] ) ? $data['seo_title'] : '',
                'seo_description' => isset( $data['seo_description'] ) ? $data['seo_description'] : '',
                'sort_order'   => isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0,
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

    public static function get_value( $id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $id
            )
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

    private static function generate_unique_attr_key( $label ) {
        global $wpdb;
        $base = sanitize_title( $label );
        if ( ! $base ) {
            $base = 'attr';
        }

        $table    = PLS_Repositories::table( 'attribute' );
        $candidate = $base;
        $i         = 2;

        while ( $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE attr_key = %s", $candidate ) ) ) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }

    private static function generate_unique_value_key( $attribute_id, $label ) {
        global $wpdb;
        $base = sanitize_title( $label );
        if ( ! $base ) {
            $base = 'option';
        }

        $table     = PLS_Repositories::table( 'attribute_value' );
        $candidate = $base;
        $i         = 2;

        while ( $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attribute_id = %d AND value_key = %s",
                $attribute_id,
                $candidate
            )
        ) ) {
            $candidate = $base . '-' . $i;
            $i++;
        }

        return $candidate;
    }
}
