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

        $insert_data = array(
            'attr_key'     => $attr_key,
            'label'        => $data['label'],
            'is_variation' => ! empty( $data['is_variation'] ) ? 1 : 0,
            'sort_order'   => isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0,
        );

        $format = array( '%s', '%s', '%d', '%d' );

        // Add optional hierarchy fields
        if ( isset( $data['parent_attribute_id'] ) ) {
            $insert_data['parent_attribute_id'] = absint( $data['parent_attribute_id'] );
            $format[] = '%d';
        }

        if ( isset( $data['option_type'] ) ) {
            $insert_data['option_type'] = sanitize_text_field( $data['option_type'] );
            $format[] = '%s';
        }

        if ( isset( $data['is_primary'] ) ) {
            $insert_data['is_primary'] = ! empty( $data['is_primary'] ) ? 1 : 0;
            $format[] = '%d';
            
            // If setting as primary, unset any existing primary
            if ( $insert_data['is_primary'] ) {
                $wpdb->update(
                    $table,
                    array( 'is_primary' => 0 ),
                    array( 'is_primary' => 1 ),
                    array( '%d' ),
                    array( '%d' )
                );
            }
        }

        $wpdb->insert( $table, $insert_data, $format );

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

    /**
     * Update tier rules for an attribute value.
     *
     * @param int $value_id Attribute value ID.
     * @param int $min_tier_level Minimum tier level required (1-5).
     * @param array|null $tier_price_overrides Optional tier-specific price overrides (tier_level => price).
     * @return bool|int Number of rows affected or false on error.
     */
    public static function update_value_tier_rules( $value_id, $min_tier_level, $tier_price_overrides = null ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        $data = array(
            'min_tier_level' => absint( $min_tier_level ),
        );

        if ( is_array( $tier_price_overrides ) ) {
            $data['tier_price_overrides'] = wp_json_encode( $tier_price_overrides );
        }

        return $wpdb->update(
            $table,
            $data,
            array( 'id' => $value_id ),
            array( '%d', '%s' ),
            array( '%d' )
        );
    }

    /**
     * Get attribute values available for a specific tier level.
     *
     * @param int $attribute_id Attribute ID.
     * @param int $tier_level Tier level (1-5).
     * @return array Array of attribute value objects.
     */
    public static function get_values_for_tier( $attribute_id, $tier_level ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                WHERE attribute_id = %d 
                AND min_tier_level <= %d 
                ORDER BY sort_order ASC, id ASC",
                $attribute_id,
                $tier_level
            )
        );
    }

    /**
     * Get tier-specific pricing for an attribute value.
     *
     * @param int $value_id Attribute value ID.
     * @param int $tier_level Tier level (1-5).
     * @return float Price for this tier, or 0 if not set.
     */
    public static function get_tier_pricing( $value_id, $tier_level ) {
        $value = self::get_value( $value_id );
        if ( ! $value || empty( $value->tier_price_overrides ) ) {
            return 0.0;
        }

        $overrides = json_decode( $value->tier_price_overrides, true );
        if ( is_array( $overrides ) && isset( $overrides[ $tier_level ] ) ) {
            return floatval( $overrides[ $tier_level ] );
        }

        return 0.0;
    }

    /**
     * Get the primary attribute (Pack Tier).
     *
     * @return object|null Attribute object or null if not found.
     */
    public static function get_primary_attribute() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        return $wpdb->get_row(
            "SELECT * FROM {$table} WHERE is_primary = 1 LIMIT 1"
        );
    }

    /**
     * Get attributes filtered by option_type.
     *
     * @param string $type Option type: 'pack-tier', 'product-option', or 'ingredient'.
     * @return array Array of attribute objects.
     */
    public static function get_attributes_by_type( $type ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE option_type = %s ORDER BY sort_order ASC, id ASC",
                $type
            )
        );
    }

    /**
     * Get all product options (non-primary, non-ingredient attributes).
     *
     * @return array Array of attribute objects.
     */
    public static function get_product_options() {
        return self::get_attributes_by_type( 'product-option' );
    }

    /**
     * Get all ingredient-type attributes.
     *
     * @return array Array of attribute objects.
     */
    public static function get_ingredient_attributes() {
        return self::get_attributes_by_type( 'ingredient' );
    }

    /**
     * Get child attributes of a parent attribute.
     *
     * @param int $parent_id Parent attribute ID.
     * @return array Array of attribute objects.
     */
    public static function get_child_attributes( $parent_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE parent_attribute_id = %d ORDER BY sort_order ASC, id ASC",
                $parent_id
            )
        );
    }

    /**
     * Set an attribute as primary (unset others).
     *
     * @param int $attribute_id Attribute ID to set as primary.
     * @return bool|int Number of rows affected or false on error.
     */
    public static function set_primary_attribute( $attribute_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        // Unset any existing primary
        $wpdb->update(
            $table,
            array( 'is_primary' => 0 ),
            array( 'is_primary' => 1 ),
            array( '%d' ),
            array( '%d' )
        );

        // Set new primary
        return $wpdb->update(
            $table,
            array( 'is_primary' => 1 ),
            array( 'id' => $attribute_id ),
            array( '%d' ),
            array( '%d' )
        );
    }
}
