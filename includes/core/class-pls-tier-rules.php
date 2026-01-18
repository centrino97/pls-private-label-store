<?php
/**
 * Tier-based business logic for product restrictions and pricing.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Tier_Rules {

    /**
     * Get available attribute values for a specific tier level.
     *
     * @param int $attribute_id Attribute ID.
     * @param int $tier_level Tier level (1-5).
     * @return array Array of attribute value objects.
     */
    public static function get_available_values( $attribute_id, $tier_level ) {
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
     * Calculate price for an attribute value at a specific tier.
     *
     * @param int $value_id Attribute value ID.
     * @param int $tier_level Tier level (1-5).
     * @param float $base_price Base price from term meta.
     * @return float Calculated price.
     */
    public static function calculate_price( $value_id, $tier_level, $base_price = 0 ) {
        $value = PLS_Repo_Attributes::get_value( $value_id );
        if ( ! $value ) {
            return $base_price;
        }

        // Check for tier-specific override
        if ( ! empty( $value->tier_price_overrides ) ) {
            $overrides = json_decode( $value->tier_price_overrides, true );
            if ( is_array( $overrides ) && isset( $overrides[ $tier_level ] ) ) {
                return floatval( $overrides[ $tier_level ] );
            }
        }

        // Use base price from term meta if available
        if ( $value->term_id ) {
            $term_price = get_term_meta( $value->term_id, '_pls_default_price_impact', true );
            if ( '' !== $term_price ) {
                return floatval( $term_price );
            }
        }

        return floatval( $base_price );
    }

    /**
     * Check if an attribute value is available for a tier level.
     *
     * @param int $value_id Attribute value ID.
     * @param int $tier_level Tier level (1-5).
     * @return bool True if available.
     */
    public static function is_value_available( $value_id, $tier_level ) {
        $value = PLS_Repo_Attributes::get_value( $value_id );
        if ( ! $value ) {
            return false;
        }

        return (int) $value->min_tier_level <= (int) $tier_level;
    }

    /**
     * Get label application fee for a tier level.
     *
     * @param int $tier_level Tier level (1-5).
     * @return float Fee amount (0 for Tier 3+).
     */
    public static function get_label_fee( $tier_level ) {
        // Tier 1-2: charge fee
        // Tier 3-5: free
        return ( $tier_level >= 3 ) ? 0.0 : 25.00;
    }

    /**
     * Calculate total extras for a product configuration.
     *
     * @param int $tier_level Tier level (1-5).
     * @param array $selected_options Array of selected attribute value IDs or keys.
     * @return float Total extra cost.
     */
    public static function calculate_product_extras( $tier_level, $selected_options = array() ) {
        $extras = 0.0;

        // Label application fee
        $extras += self::get_label_fee( $tier_level );

        // Calculate price impacts from selected attribute values
        foreach ( $selected_options as $option ) {
            $value_id = is_numeric( $option ) ? absint( $option ) : 0;
            if ( ! $value_id ) {
                // Try to find by value_key
                $value_id = self::find_value_id_by_key( $option );
            }

            if ( $value_id && self::is_value_available( $value_id, $tier_level ) ) {
                $price = self::calculate_price( $value_id, $tier_level );
                if ( $price > 0 ) {
                    $extras += $price;
                }
            }
        }

        // Custom bottle option (Tier 4+)
        if ( $tier_level >= 4 ) {
            $custom_bottle_id = self::find_value_id_by_label( 'Custom Printed Bottle' );
            if ( $custom_bottle_id && in_array( $custom_bottle_id, $selected_options, true ) ) {
                $extras += 15.00; // Example price
            }
        }

        // Box packaging (Tier 4+)
        if ( $tier_level >= 4 ) {
            $box_packaging_id = self::find_value_id_by_label( 'External Box Packaging' );
            if ( $box_packaging_id && in_array( $box_packaging_id, $selected_options, true ) ) {
                $extras += 8.00; // Example price
            }
        }

        return $extras;
    }

    /**
     * Find attribute value ID by value key.
     *
     * @param string $value_key Value key.
     * @return int|false Value ID or false if not found.
     */
    private static function find_value_id_by_key( $value_key ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        $value_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE value_key = %s LIMIT 1",
                $value_key
            )
        );

        return $value_id ? absint( $value_id ) : false;
    }

    /**
     * Find attribute value ID by label.
     *
     * @param string $label Value label.
     * @return int|false Value ID or false if not found.
     */
    private static function find_value_id_by_label( $label ) {
        global $wpdb;
        $table = PLS_Repositories::table( 'attribute_value' );

        $value_id = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE label = %s LIMIT 1",
                $label
            )
        );

        return $value_id ? absint( $value_id ) : false;
    }

    /**
     * Get tier level from pack tier value ID.
     *
     * @param int $value_id Pack tier attribute value ID.
     * @return int|false Tier level (1-5) or false if not found.
     */
    public static function get_tier_level_from_value( $value_id ) {
        $value = PLS_Repo_Attributes::get_value( $value_id );
        if ( ! $value || ! $value->term_id ) {
            return false;
        }

        $tier_level = get_term_meta( $value->term_id, '_pls_tier_level', true );
        return $tier_level ? absint( $tier_level ) : false;
    }

    /**
     * Get default units for a pack tier value.
     *
     * @param int $value_id Pack tier attribute value ID.
     * @return int|false Units or false if not found.
     */
    public static function get_default_units_for_tier( $value_id ) {
        $value = PLS_Repo_Attributes::get_value( $value_id );
        if ( ! $value || ! $value->term_id ) {
            return false;
        }

        $units = get_term_meta( $value->term_id, '_pls_default_units', true );
        return $units ? absint( $units ) : false;
    }
}
