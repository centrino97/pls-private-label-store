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

    /**
     * Get attribute by label.
     *
     * @param string $label Attribute label.
     * @return object|null
     */
    public static function get_by_label( $label ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE label = %s", $label )
        );
    }

    /**
     * Get attribute by attr_key.
     *
     * @param string $attr_key Attribute key.
     * @return object|null
     */
    public static function get_by_key( $attr_key ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );
        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE attr_key = %s", $attr_key )
        );
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

        if ( isset( $data['default_min_tier'] ) ) {
            $insert_data['default_min_tier'] = max( 1, min( 5, absint( $data['default_min_tier'] ) ) );
            $format[] = '%d';
        }

        $wpdb->insert( $table, $insert_data, $format );

        return $wpdb->insert_id;
    }

    /**
     * Update an attribute.
     *
     * @param int   $id   Attribute ID.
     * @param array $data Update data.
     * @return bool
     */
    public static function update_attr( $id, $data ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        $update_data = array();
        $format = array();

        if ( isset( $data['label'] ) ) {
            $update_data['label'] = sanitize_text_field( $data['label'] );
            $format[] = '%s';
        }

        if ( isset( $data['is_variation'] ) ) {
            $update_data['is_variation'] = ! empty( $data['is_variation'] ) ? 1 : 0;
            $format[] = '%d';
        }

        if ( isset( $data['default_min_tier'] ) ) {
            $update_data['default_min_tier'] = max( 1, min( 5, absint( $data['default_min_tier'] ) ) );
            $format[] = '%d';
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        return (bool) $wpdb->update( $table, $update_data, array( 'id' => $id ), $format, array( '%d' ) );
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

        $insert_data = array(
            'attribute_id' => $data['attribute_id'],
            'value_key'    => $value_key,
            'label'        => $data['label'],
            'seo_slug'     => isset( $data['seo_slug'] ) ? $data['seo_slug'] : '',
            'seo_title'    => isset( $data['seo_title'] ) ? $data['seo_title'] : '',
            'seo_description' => isset( $data['seo_description'] ) ? $data['seo_description'] : '',
            'sort_order'   => isset( $data['sort_order'] ) ? absint( $data['sort_order'] ) : 0,
        );

        $format = array( '%d', '%s', '%s', '%s', '%s', '%s', '%d' );

        // Add optional min_tier_level
        if ( isset( $data['min_tier_level'] ) ) {
            $insert_data['min_tier_level'] = absint( $data['min_tier_level'] );
            $format[] = '%d';
        }

        // Add optional tier_price_overrides
        if ( isset( $data['tier_price_overrides'] ) && is_array( $data['tier_price_overrides'] ) ) {
            $insert_data['tier_price_overrides'] = wp_json_encode( $data['tier_price_overrides'] );
            $format[] = '%s';
        }

        // Add optional ingredient_category
        if ( isset( $data['ingredient_category'] ) ) {
            $insert_data['ingredient_category'] = sanitize_text_field( $data['ingredient_category'] );
            $format[] = '%s';
        }

        $wpdb->insert( $table, $insert_data, $format );

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

    public static function get_attr( $id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        return $wpdb->get_row(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE id = %d",
                $id
            )
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
     * Get the effective minimum tier for a value.
     * 
     * Logic:
     * 1. If value has min_tier_level > 1, use it
     * 2. Otherwise, inherit from option's default_min_tier
     * 3. If neither is set, default to Tier 1
     *
     * @param object $value Value object with min_tier_level.
     * @param object|null $attribute Optional attribute object with default_min_tier.
     * @return int Effective minimum tier level (1-5).
     */
    public static function get_effective_min_tier( $value, $attribute = null ) {
        // If value has an explicit tier set (> 1), use it
        if ( isset( $value->min_tier_level ) && (int) $value->min_tier_level > 1 ) {
            return (int) $value->min_tier_level;
        }

        // If attribute has default_min_tier, use it
        if ( $attribute && isset( $attribute->default_min_tier ) && (int) $attribute->default_min_tier > 1 ) {
            return (int) $attribute->default_min_tier;
        }

        // Default to Tier 1 (available to all)
        return 1;
    }

    /**
     * Get attribute values available for a specific tier level with inheritance.
     *
     * @param int $attribute_id Attribute ID.
     * @param int $tier_level Tier level (1-5).
     * @return array Array of attribute value objects.
     */
    public static function get_values_for_tier( $attribute_id, $tier_level ) {
        global $wpdb;
        $attr_table = PLS_Repositories::table( 'attribute' );
        $value_table = PLS_Repositories::table( 'attribute_value' );

        // Get the attribute's default_min_tier
        $attribute = $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$attr_table} WHERE id = %d", $attribute_id )
        );
        $default_tier = ( $attribute && isset( $attribute->default_min_tier ) ) ? (int) $attribute->default_min_tier : 1;

        // Get all values for this attribute
        $all_values = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$value_table} WHERE attribute_id = %d ORDER BY sort_order ASC, id ASC",
                $attribute_id
            )
        );

        // Filter values based on effective tier
        $filtered = array();
        foreach ( $all_values as $value ) {
            $effective_tier = self::get_effective_min_tier( $value, $attribute );
            if ( $effective_tier <= $tier_level ) {
                $value->effective_min_tier = $effective_tier;
                $filtered[] = $value;
            }
        }

        return $filtered;
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

    /**
     * Update tier price overrides for an attribute value.
     *
     * @param int $value_id Attribute value ID.
     * @param array $tier_overrides Array of tier_level => price (e.g., [1 => 2.00, 2 => 1.80]).
     * @return bool|int Number of rows affected or false on error.
     */
    public static function update_tier_price_overrides( $value_id, $tier_overrides ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        $json = is_array( $tier_overrides ) ? wp_json_encode( $tier_overrides ) : null;

        return $wpdb->update(
            $table,
            array( 'tier_price_overrides' => $json ),
            array( 'id' => $value_id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Get fragrances (ingredients with category 'fragrance').
     *
     * @return array Array of attribute value objects.
     */
    public static function get_fragrances() {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} 
                WHERE ingredient_category = %s 
                ORDER BY sort_order ASC, id ASC",
                'fragrance'
            )
        );
    }

    /**
     * Update ingredient category for an attribute value.
     *
     * @param int $value_id Attribute value ID.
     * @param string|null $category Category name (e.g., 'fragrance') or null to clear.
     * @return bool|int Number of rows affected or false on error.
     */
    public static function update_ingredient_category( $value_id, $category ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        return $wpdb->update(
            $table,
            array( 'ingredient_category' => $category ? sanitize_text_field( $category ) : null ),
            array( 'id' => $value_id ),
            array( '%s' ),
            array( '%d' )
        );
    }
}
