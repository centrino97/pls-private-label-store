<?php
/**
 * Syncs ingredients between pls_ingredient taxonomy and pls_attribute table.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Ingredient_Sync {

    /**
     * Sync a single ingredient term to attribute system.
     *
     * @param int $term_id Ingredient term ID.
     * @return int|false Attribute ID or false on failure.
     */
    public static function sync_ingredient_to_attribute( $term_id ) {
        global $wpdb;

        $term = get_term( $term_id, 'pls_ingredient' );
        if ( ! $term || is_wp_error( $term ) ) {
            return false;
        }

        $table = PLS_Repositories::table( 'attribute' );

        // Check if attribute already exists for this ingredient
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s AND option_type = 'ingredient'",
                'ingredient-' . $term_id
            )
        );

        $attr_data = array(
            'label'        => $term->name,
            'attr_key'     => 'ingredient-' . $term_id,
            'is_variation' => 0, // Ingredients are typically non-variation
            'sort_order'   => 0,
            'option_type'  => 'ingredient',
        );

        if ( $existing ) {
            // Update existing attribute
            $wpdb->update(
                $table,
                array(
                    'label' => $term->name,
                ),
                array( 'id' => $existing->id ),
                array( '%s' ),
                array( '%d' )
            );
            $attr_id = $existing->id;
        } else {
            // Create new attribute
            $attr_id = PLS_Repo_Attributes::insert_attr( $attr_data );
        }

        if ( ! $attr_id ) {
            return false;
        }

        // Get or create attribute value
        $value_table = PLS_Repositories::table( 'attribute_value' );
        $existing_value = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$value_table} WHERE attribute_id = %d AND term_id = %d",
                $attr_id,
                $term_id
            )
        );

        if ( $existing_value ) {
            // Update existing value to ensure min_tier_level = 3
            PLS_Repo_Attributes::update_value_tier_rules( $existing_value->id, 3, null );
        } else {
            // Create new value
            $value_id = PLS_Repo_Attributes::insert_value(
                array(
                    'attribute_id' => $attr_id,
                    'label'        => $term->name,
                    'value_key'    => sanitize_key( $term->name ),
                )
            );

            if ( $value_id ) {
                // Set min_tier_level = 3 for ingredients
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, 3, null );

                // Link term_id
                $term_id_meta = get_term_meta( $term_id, '_pls_attribute_value_id', true );
                if ( ! $term_id_meta ) {
                    update_term_meta( $term_id, '_pls_attribute_value_id', $value_id );
                }

                // Set term_id on value
                $value_obj = PLS_Repo_Attributes::get_value( $value_id );
                if ( $value_obj && ! $value_obj->term_id ) {
                    PLS_Repo_Attributes::set_term_id_for_value( $value_id, $term_id );
                }
            }
        }

        return $attr_id;
    }

    /**
     * Sync all existing ingredients to attribute system.
     */
    public static function sync_all_ingredients() {
        $ingredients = get_terms(
            array(
                'taxonomy'   => 'pls_ingredient',
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $ingredients ) || empty( $ingredients ) ) {
            return;
        }

        foreach ( $ingredients as $ingredient ) {
            self::sync_ingredient_to_attribute( $ingredient->term_id );
        }
    }

    /**
     * Hook handler for ingredient creation.
     *
     * @param int $term_id Term ID.
     */
    public static function on_ingredient_created( $term_id ) {
        self::sync_ingredient_to_attribute( $term_id );
    }

    /**
     * Hook handler for ingredient update.
     *
     * @param int $term_id Term ID.
     */
    public static function on_ingredient_updated( $term_id ) {
        self::sync_ingredient_to_attribute( $term_id );
    }

    /**
     * Hook handler for ingredient deletion.
     *
     * @param int $term_id Term ID.
     */
    public static function on_ingredient_deleted( $term_id ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute' );

        // Find and delete corresponding attribute
        $attr = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s AND option_type = 'ingredient'",
                'ingredient-' . $term_id
            )
        );

        if ( $attr ) {
            // Delete attribute values first
            $value_table = PLS_Repositories::table( 'attribute_value' );
            $wpdb->delete(
                $value_table,
                array( 'attribute_id' => $attr->id ),
                array( '%d' )
            );

            // Delete attribute
            $wpdb->delete(
                $table,
                array( 'id' => $attr->id ),
                array( '%d' )
            );
        }
    }
}
