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

        // 3. Create Package Colour attribute
        self::create_package_colour_attribute();

        // 4. Create Tier 4+ options (Custom Bottles, Box Packaging)
        self::create_tier4_options();

        // Mark as created
        update_option( 'pls_default_attributes_created', true );
    }

    /**
     * Create Pack Tier attribute with 5 tier values.
     *
     * @return int Attribute ID
     */
    private static function create_pack_tier_attribute() {
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

        // Create attribute
        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'            => 'Pack Tier',
                'attr_key'         => 'pack-tier',
                'is_variation'     => 1,
                'sort_order'       => 0,
                'option_type'      => 'pack-tier',
                'is_primary'       => 1,
            )
        );

        if ( ! $attr_id ) {
            return 0;
        }

        update_option( 'pls_pack_tier_attribute_id', $attr_id );

        // Create tier values
        $tiers = array(
            array( 'label' => 'Tier 1', 'tier_level' => 1, 'units' => 50, 'sort' => 1 ),
            array( 'label' => 'Tier 2', 'tier_level' => 2, 'units' => 100, 'sort' => 2 ),
            array( 'label' => 'Tier 3', 'tier_level' => 3, 'units' => 250, 'sort' => 3 ),
            array( 'label' => 'Tier 4', 'tier_level' => 4, 'units' => 500, 'sort' => 4 ),
            array( 'label' => 'Tier 5', 'tier_level' => 5, 'units' => 1000, 'sort' => 5 ),
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
            array( 'label' => 'Glass Bottle 30ml', 'min_tier' => 1 ),
            array( 'label' => 'Glass Bottle 50ml', 'min_tier' => 1 ),
            array( 'label' => 'Glass Bottle 120ml', 'min_tier' => 1 ),
            array( 'label' => '50gr Jar', 'min_tier' => 1 ),
        );

        foreach ( $types as $index => $type ) {
            $value_id = PLS_Repo_Attributes::insert_value(
                array(
                    'attribute_id' => $attr_id,
                    'label'        => $type['label'],
                    'value_key'    => sanitize_key( $type['label'] ),
                    'sort_order'   => $index + 1,
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
     * Create Package Colour attribute.
     */
    private static function create_package_colour_attribute() {
        global $wpdb;

        $table = PLS_Repositories::table( 'attribute' );
        $existing = $wpdb->get_row(
            $wpdb->prepare(
                "SELECT id FROM {$table} WHERE attr_key = %s",
                'package-colour'
            )
        );

        if ( $existing ) {
            return $existing->id;
        }

        $attr_id = PLS_Repo_Attributes::insert_attr(
            array(
                'label'        => 'Package Colour',
                'attr_key'     => 'package-colour',
                'is_variation' => 1,
                'sort_order'   => 20,
                'option_type'  => 'product-option',
            )
        );

        if ( ! $attr_id ) {
            return 0;
        }

        $colours = array(
            array(
                'label'     => 'White with White Lid & Pump',
                'price'     => 0,
                'min_tier'  => 1,
                'swatch'    => array( 'type' => 'color', 'value' => '#ffffff' ),
            ),
            array(
                'label'     => 'Frosted with White Lid & Pump',
                'price'     => 0,
                'min_tier'  => 1,
                'swatch'    => array( 'type' => 'color', 'value' => '#f0f0f0' ),
            ),
            array(
                'label'     => 'White with Silver Lid & Pump',
                'price'     => 5.00,
                'min_tier'  => 3, // Extra cost option available from Tier 3+
                'swatch'    => array( 'type' => 'color', 'value' => '#c0c0c0' ),
            ),
            array(
                'label'     => 'Frosted with Silver Lid & Pump',
                'price'     => 5.00,
                'min_tier'  => 3,
                'swatch'    => array( 'type' => 'color', 'value' => '#d0d0d0' ),
            ),
        );

        foreach ( $colours as $index => $colour ) {
            $value_id = PLS_Repo_Attributes::insert_value(
                array(
                    'attribute_id' => $attr_id,
                    'label'        => $colour['label'],
                    'value_key'    => sanitize_key( $colour['label'] ),
                    'sort_order'   => $index + 1,
                )
            );

            if ( $value_id ) {
                // Set tier rules
                PLS_Repo_Attributes::update_value_tier_rules(
                    $value_id,
                    $colour['min_tier'],
                    null
                );

                // Set default price impact
                $value = PLS_Repo_Attributes::get_value( $value_id );
                if ( $value && $value->term_id && $colour['price'] > 0 ) {
                    update_term_meta( $value->term_id, '_pls_default_price_impact', $colour['price'] );
                }

                // Create swatch
                if ( isset( $colour['swatch'] ) ) {
                    PLS_Repo_Attributes::upsert_swatch_for_value(
                        $value_id,
                        $colour['swatch']['type'],
                        $colour['swatch']['value']
                    );
                }
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
