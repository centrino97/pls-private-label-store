<?php
/**
 * Creates default attributes on plugin activation.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Default_Attributes {

    /**
     * Create default attributes if they don't exist.
     */
    public static function create_defaults() {
        // Check if defaults already created
        if ( get_option( 'pls_default_attributes_created', false ) ) {
            return;
        }

        // 1. Create Pack Tier attribute
        $pack_tier_attr_id = self::create_pack_tier_attribute();

        // 2. Create Package Type attribute
        self::create_package_type_attribute();

        // 3. Create Package Color attribute
        self::create_package_color_attribute();

        // 4. Create Package Cap attribute
        self::create_package_cap_attribute();

        // 5. Create Tier 4+ options (Custom Bottles, Box Packaging)
        self::create_tier4_options();

        // Mark as created
        update_option( 'pls_default_attributes_created', true );
    }

    /**
     * Create Pack Tier attribute with 5 tier values.
     *
     * @return int Attribute ID
     */
    public static function create_pack_tier_attribute() {
        global $wpdb;

        // Check if Pack Tier attribute already exists
        $table = PLS_Repositories::table( 'attribute' );
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s",
                'pack-tier'
            )
        );

        if ( $existing ) {
            update_option( 'pls_pack_tier_attribute_id', $existing->id );
            return $existing->id;
        }

        // Create attribute - use 'pack_tier' as option_type for consistency
        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'            => 'Pack Tier',
                'attr_key'         => 'pack-tier',
                'is_variation'     => 1,
                'sort_order'       => 0,
                'option_type'      => 'pack_tier',
                'is_primary'       => 1,
            )
        );

        if ( ! $attr_id ) {
            return 0;
        }

        update_option( 'pls_pack_tier_attribute_id', $attr_id );

        // Create tier values with default prices from image
        $tiers = array(
            array( 'label' => 'Tier 1', 'tier_level' => 1, 'units' => 50, 'price' => 15.90, 'sort' => 1 ),
            array( 'label' => 'Tier 2', 'tier_level' => 2, 'units' => 100, 'price' => 14.50, 'sort' => 2 ),
            array( 'label' => 'Tier 3', 'tier_level' => 3, 'units' => 250, 'price' => 12.50, 'sort' => 3 ),
            array( 'label' => 'Tier 4', 'tier_level' => 4, 'units' => 500, 'price' => 9.50, 'sort' => 4 ),
            array( 'label' => 'Tier 5', 'tier_level' => 5, 'units' => 1000, 'price' => 7.90, 'sort' => 5 ),
        );

        foreach ( $tiers as $tier ) {
            $value_id = PLS_Repo_Attributes::insert_value(
                array(
                    'attribute_id' => $attr_id,
                    'label'        => $tier['label'],
                    'value_key'    => 'tier-' . $tier['tier_level'],
                    'sort_order'   => $tier['sort'],
                )
            );

            if ( $value_id ) {
                // Set min tier level (pack tier values are available from their tier level)
                PLS_Repo_Attributes::update_value_tier_rules(
                    $value_id,
                    $tier['tier_level'],
                    null
                );

                // Store tier metadata in term meta after sync
                $value = PLS_Repo_Attributes::get_value( $value_id );
                if ( $value && $value->term_id ) {
                    update_term_meta( $value->term_id, '_pls_tier_level', $tier['tier_level'] );
                    update_term_meta( $value->term_id, '_pls_default_units', $tier['units'] );
                    update_term_meta( $value->term_id, '_pls_default_price_per_unit', $tier['price'] );
                }
            }
        }

        return $attr_id;
    }

    /**
     * Create Package Type attribute.
     */
    private static function create_package_type_attribute() {
        global $wpdb;

        $table = PLS_Repositories::table( 'attribute' );
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s",
                'package-type'
            )
        );

        if ( $existing ) {
            return $existing->id;
        }

        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'        => 'Package Type',
                'attr_key'     => 'package-type',
                'is_variation' => 1,
                'sort_order'   => 10,
                'option_type'  => 'product-option',
            )
        );

        if ( ! $attr_id ) {
            return 0;
        }

        $types = array(
            array( 'label' => '30ml Bottle', 'min_tier' => 1 ),
            array( 'label' => '50ml Bottle', 'min_tier' => 1 ),
            array( 'label' => '120ml Bottle', 'min_tier' => 1 ),
            array( 'label' => '50gr Jar', 'min_tier' => 1 ),
        );

        foreach ( $types as $index => $type ) {
            $value_id = PLS_Repo_Attributes::insert_value(
                array(
                    'attribute_id' => $attr_id,
                    'label'        => $type['label'],
                    'value_key'    => sanitize_key( $type['label'] ),
                    'sort_order'   => $index + 1,
                    'min_tier_level' => $type['min_tier'],
                )
            );

            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules(
                    $value_id,
                    $type['min_tier'],
                    null
                );
            }
        }

        return $attr_id;
    }

    /**
     * Create Package Color attribute with tier-variable pricing.
     */
    private static function create_package_color_attribute() {
        global $wpdb;

        $table = PLS_Repositories::table( 'attribute' );
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s",
                'package-color'
            )
        );

        if ( $existing ) {
            return $existing->id;
        }

        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'        => 'Package Color',
                'attr_key'     => 'package-color',
                'is_variation' => 1,
                'sort_order'   => 20,
                'option_type'  => 'product-option',
            )
        );

        if ( ! $attr_id ) {
            return 0;
        }

        // Standard Clear - no price impact
        $clear_id = PLS_Repo_Attributes::insert_value(
            array(
                'attribute_id' => $attr_id,
                'label'        => 'Standard Clear',
                'value_key'    => 'standard-clear',
                'sort_order'   => 1,
                'min_tier_level' => 1,
            )
        );

        if ( $clear_id ) {
            PLS_Repo_Attributes::update_value_tier_rules( $clear_id, 1, null );
        }

        // Frosted - tier-variable pricing
        $frosted_id = PLS_Repo_Attributes::insert_value(
            array(
                'attribute_id' => $attr_id,
                'label'        => 'Frosted',
                'value_key'    => 'frosted',
                'sort_order'   => 2,
                'min_tier_level' => 1,
                'tier_price_overrides' => array(
                    1 => 2.00, // Tier 1: +$2.00
                    2 => 1.80, // Tier 2: +$1.80
                    3 => 1.50, // Tier 3: +$1.50
                    4 => 1.20, // Tier 4: +$1.20
                    5 => 1.00, // Tier 5: +$1.00
                ),
            )
        );

        if ( $frosted_id ) {
            PLS_Repo_Attributes::update_value_tier_rules(
                $frosted_id,
                1,
                array(
                    1 => 2.00,
                    2 => 1.80,
                    3 => 1.50,
                    4 => 1.20,
                    5 => 1.00,
                )
            );
        }

        // Amber - tier-variable pricing
        $amber_id = PLS_Repo_Attributes::insert_value(
            array(
                'attribute_id' => $attr_id,
                'label'        => 'Amber Glass',
                'value_key'    => 'amber-glass',
                'sort_order'   => 3,
                'min_tier_level' => 1,
                'tier_price_overrides' => array(
                    1 => 3.00, // Tier 1: +$3.00
                    2 => 2.80, // Tier 2: +$2.80
                    3 => 2.50, // Tier 3: +$2.50
                    4 => 2.20, // Tier 4: +$2.20
                    5 => 2.00, // Tier 5: +$2.00
                ),
            )
        );

        if ( $amber_id ) {
            PLS_Repo_Attributes::update_value_tier_rules(
                $amber_id,
                1,
                array(
                    1 => 3.00,
                    2 => 2.80,
                    3 => 2.50,
                    4 => 2.20,
                    5 => 2.00,
                )
            );
        }

        return $attr_id;
    }

    /**
     * Create Package Cap attribute with tier-variable pricing for silver options.
     */
    private static function create_package_cap_attribute() {
        global $wpdb;

        $table = PLS_Repositories::table( 'attribute' );
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s",
                'package-cap'
            )
        );

        if ( $existing ) {
            return $existing->id;
        }

        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'        => 'Package Cap',
                'attr_key'     => 'package-cap',
                'is_variation' => 1,
                'sort_order'   => 25,
                'option_type'  => 'product-option',
            )
        );

        if ( ! $attr_id ) {
            return 0;
        }

        // White options - no price impact
        $white_caps = array(
            array( 'label' => 'White Pump', 'key' => 'white-pump', 'sort' => 1 ),
            array( 'label' => 'White Dropper', 'key' => 'white-dropper', 'sort' => 2 ),
            array( 'label' => 'White Lid', 'key' => 'white-lid', 'sort' => 3 ),
        );

        foreach ( $white_caps as $cap ) {
            $value_id = PLS_Repo_Attributes::insert_value(
                array(
                    'attribute_id' => $attr_id,
                    'label'        => $cap['label'],
                    'value_key'    => $cap['key'],
                    'sort_order'   => $cap['sort'],
                    'min_tier_level' => 1,
                )
            );

            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, 1, null );
            }
        }

        // Silver options - tier-variable pricing
        $silver_caps = array(
            array( 'label' => 'Silver Pump', 'key' => 'silver-pump', 'sort' => 4 ),
            array( 'label' => 'Silver Dropper', 'key' => 'silver-dropper', 'sort' => 5 ),
            array( 'label' => 'Silver Lid', 'key' => 'silver-lid', 'sort' => 6 ),
        );

        foreach ( $silver_caps as $cap ) {
            $value_id = PLS_Repo_Attributes::insert_value(
                array(
                    'attribute_id' => $attr_id,
                    'label'        => $cap['label'],
                    'value_key'    => $cap['key'],
                    'sort_order'   => $cap['sort'],
                    'min_tier_level' => 1,
                    'tier_price_overrides' => array(
                        1 => 5.00, // Tier 1: +$5.00
                        2 => 4.50, // Tier 2: +$4.50
                        3 => 4.00, // Tier 3: +$4.00
                        4 => 3.50, // Tier 4: +$3.50
                        5 => 3.00, // Tier 5: +$3.00
                    ),
                )
            );

            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules(
                    $value_id,
                    1,
                    array(
                        1 => 5.00,
                        2 => 4.50,
                        3 => 4.00,
                        4 => 3.50,
                        5 => 3.00,
                    )
                );
            }
        }

        return $attr_id;
    }

    /**
     * Create additional Tier 4+ options (Custom Bottles, Box Packaging).
     */
    private static function create_tier4_options() {
        global $wpdb;

        // Custom Printed Bottles attribute
        $table = PLS_Repositories::table( 'attribute' );
        $existing_bottles = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s",
                'custom-bottles'
            )
        );

        if ( ! $existing_bottles ) {
            $bottles_attr_id = PLS_Repo_Attributes::insert_attr(
                array(
                    'label'        => 'Custom Printed Bottles',
                    'attr_key'     => 'custom-bottles',
                    'is_variation' => 1,
                    'sort_order'   => 30,
                )
            );

            if ( $bottles_attr_id ) {
                $value_id = PLS_Repo_Attributes::insert_value(
                    array(
                        'attribute_id' => $bottles_attr_id,
                        'label'        => 'Custom Printed Bottle',
                        'value_key'    => 'custom-printed-bottle',
                        'sort_order'   => 1,
                    )
                );

                if ( $value_id ) {
                    PLS_Repo_Attributes::update_value_tier_rules( $value_id, 4, null );
                    $value = PLS_Repo_Attributes::get_value( $value_id );
                    if ( $value && $value->term_id ) {
                        update_term_meta( $value->term_id, '_pls_default_price_impact', 15.00 );
                    }
                }
            }
        }

        // External Box Packaging attribute
        $existing_box = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s",
                'box-packaging'
            )
        );

        if ( ! $existing_box ) {
            $box_attr_id = PLS_Repo_Attributes::insert_attr(
                array(
                    'label'        => 'External Box Packaging',
                    'attr_key'     => 'box-packaging',
                    'is_variation' => 1,
                    'sort_order'   => 40,
                    'option_type'  => 'product-option',
                )
            );

            if ( $box_attr_id ) {
                $value_id = PLS_Repo_Attributes::insert_value(
                    array(
                        'attribute_id' => $box_attr_id,
                        'label'        => 'External Box Packaging',
                        'value_key'    => 'external-box-packaging',
                        'sort_order'   => 1,
                    )
                );

                if ( $value_id ) {
                    PLS_Repo_Attributes::update_value_tier_rules( $value_id, 4, null );
                    $value = PLS_Repo_Attributes::get_value( $value_id );
                    if ( $value && $value->term_id ) {
                        update_term_meta( $value->term_id, '_pls_default_price_impact', 8.00 );
                    }
                }
            }
        }
    }
}
