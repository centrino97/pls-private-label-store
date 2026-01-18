<?php
/**
 * Sample data generator for PLS plugin.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Sample_Data {

    /**
     * Clean up existing data and add sample data.
     */
    public static function generate() {
        self::cleanup();
        self::add_categories();
        self::add_ingredients();
        self::add_product_options();
        self::add_products();
    }

    /**
     * Clean up existing data.
     */
    private static function cleanup() {
        global $wpdb;

        // Delete all products
        $products_table = $wpdb->prefix . 'pls_base_product';
        $wpdb->query( "TRUNCATE TABLE {$products_table}" );

        $pack_tiers_table = $wpdb->prefix . 'pls_pack_tier';
        $wpdb->query( "TRUNCATE TABLE {$pack_tiers_table}" );

        $product_profiles_table = $wpdb->prefix . 'pls_product_profile';
        $wpdb->query( "TRUNCATE TABLE {$product_profiles_table}" );

        // Delete custom orders
        $custom_orders_table = $wpdb->prefix . 'pls_custom_order';
        $wpdb->query( "TRUNCATE TABLE {$custom_orders_table}" );

        // Delete commissions
        $commissions_table = $wpdb->prefix . 'pls_order_commission';
        $wpdb->query( "TRUNCATE TABLE {$commissions_table}" );

        // Delete all attributes except Pack Tier
        $attributes_table = $wpdb->prefix . 'pls_attribute';
        $pack_tier_attr = $wpdb->get_row( "SELECT * FROM {$attributes_table} WHERE option_type = 'pack_tier' LIMIT 1" );
        
        $wpdb->query( "DELETE FROM {$attributes_table} WHERE option_type != 'pack_tier'" );

        // Delete all attribute values except Pack Tier values
        $values_table = $wpdb->prefix . 'pls_attribute_value';
        if ( $pack_tier_attr ) {
            $wpdb->query( $wpdb->prepare( "DELETE FROM {$values_table} WHERE attribute_id != %d", $pack_tier_attr->id ) );
        } else {
            // Keep pack tier values if they exist
            $pack_tier_attrs = $wpdb->get_col( "SELECT id FROM {$attributes_table} WHERE option_type = 'pack_tier' OR option_type = 'pack-tier'" );
            if ( ! empty( $pack_tier_attrs ) ) {
                $placeholders = implode( ',', array_fill( 0, count( $pack_tier_attrs ), '%d' ) );
                $wpdb->query( $wpdb->prepare( "DELETE FROM {$values_table} WHERE attribute_id NOT IN ({$placeholders})", ...$pack_tier_attrs ) );
            } else {
                $wpdb->query( "TRUNCATE TABLE {$values_table}" );
            }
        }

        // Delete all ingredients (terms)
        $ingredients = get_terms( array(
            'taxonomy' => 'pls_ingredient',
            'hide_empty' => false,
        ) );

        foreach ( $ingredients as $ingredient ) {
            wp_delete_term( $ingredient->term_id, 'pls_ingredient' );
        }
    }

    /**
     * Add sample categories.
     */
    private static function add_categories() {
        $categories = array(
            'Face Cleansers' => 'Gentle cleansers for all skin types',
            'Toning Mists' => 'Refreshing toners and facial mists',
            'Moisturisers' => 'Hydrating creams and lotions',
            'Serums & Oils' => 'Concentrated treatments and facial oils',
            'Masks & Exfoliants' => 'Deep cleansing masks and exfoliating treatments',
            'Eye & Lip Care' => 'Specialized eye creams and lip treatments',
        );

        foreach ( $categories as $name => $description ) {
            $term = term_exists( $name, 'product_cat' );
            if ( ! $term ) {
                wp_insert_term( $name, 'product_cat', array( 'description' => $description ) );
            }
        }
    }

    /**
     * Add sample ingredients.
     */
    private static function add_ingredients() {
        $ingredients = array(
            // Tier 3+ ingredients
            'Emu Apple' => array( 'description' => 'Wild-harvested Australian ingredient with anti-inflammatory properties', 'min_tier' => 3 ),
            'Cucumber' => array( 'description' => 'Cooling and hydrating extract', 'min_tier' => 3 ),
            'Desert Lime' => array( 'description' => 'Protective Australian citrus extract', 'min_tier' => 3 ),
            'Kakadu Plum' => array( 'description' => 'High vitamin C content, brightening properties', 'min_tier' => 3 ),
            'Quandong' => array( 'description' => 'Antioxidant-rich Australian native fruit', 'min_tier' => 3 ),
            
            // Tier 4+ ingredients
            'Niacinamide' => array( 'description' => 'Vitamin B3, improves skin barrier and reduces inflammation', 'min_tier' => 4 ),
            'Hyaluronic Acid' => array( 'description' => 'Intense hydration and plumping effect', 'min_tier' => 4 ),
            'Retinol' => array( 'description' => 'Vitamin A derivative, anti-aging properties', 'min_tier' => 4 ),
            'Peptides' => array( 'description' => 'Collagen-boosting amino acids', 'min_tier' => 4 ),
            'Vitamin C' => array( 'description' => 'Brightening and antioxidant protection', 'min_tier' => 4 ),
            'Ceramides' => array( 'description' => 'Restores skin barrier function', 'min_tier' => 4 ),
            
            // Tier 5+ ingredients
            'Coenzyme Q10' => array( 'description' => 'Powerful antioxidant, reduces fine lines', 'min_tier' => 5 ),
            'Resveratrol' => array( 'description' => 'Grape-derived antioxidant', 'min_tier' => 5 ),
            'Bakuchiol' => array( 'description' => 'Natural retinol alternative', 'min_tier' => 5 ),
        );

        foreach ( $ingredients as $name => $data ) {
            $term = term_exists( $name, 'pls_ingredient' );
            if ( ! $term ) {
                $term = wp_insert_term( $name, 'pls_ingredient', array( 'description' => $data['description'] ) );
                if ( ! is_wp_error( $term ) ) {
                    $term_id = $term['term_id'];
                    update_term_meta( $term_id, '_pls_min_tier_level', $data['min_tier'] );
                    // Sync to attribute system
                    PLS_Ingredient_Sync::sync_ingredient_to_attribute( $term_id );
                    
                    // Update tier rules for the synced attribute value
                    $value_id = get_term_meta( $term_id, '_pls_attribute_value_id', true );
                    if ( $value_id ) {
                        $tier_prices = array();
                        if ( $data['min_tier'] >= 4 ) {
                            $tier_prices = array( 4 => 2.50, 5 => 2.00 );
                        } else {
                            $tier_prices = array( 3 => 3.00, 4 => 2.50, 5 => 2.00 );
                        }
                        PLS_Repo_Attributes::update_value_tier_rules( $value_id, $data['min_tier'], $tier_prices );
                    }
                }
            } else {
                $term_id = is_array( $term ) ? $term['term_id'] : $term->term_id;
                update_term_meta( $term_id, '_pls_min_tier_level', $data['min_tier'] );
                PLS_Ingredient_Sync::sync_ingredient_to_attribute( $term_id );
                
                // Update tier rules
                $value_id = get_term_meta( $term_id, '_pls_attribute_value_id', true );
                if ( $value_id ) {
                    $tier_prices = array();
                    if ( $data['min_tier'] >= 4 ) {
                        $tier_prices = array( 4 => 2.50, 5 => 2.00 );
                    } else {
                        $tier_prices = array( 3 => 3.00, 4 => 2.50, 5 => 2.00 );
                    }
                    PLS_Repo_Attributes::update_value_tier_rules( $value_id, $data['min_tier'], $tier_prices );
                }
            }
        }
    }

    /**
     * Add product options with values.
     */
    private static function add_product_options() {
        global $wpdb;
        $attributes_table = $wpdb->prefix . 'pls_attribute';
        $values_table = $wpdb->prefix . 'pls_attribute_value';
        $attr_table = $wpdb->prefix . 'pls_attribute';

        // Ensure ingredient sync class is loaded
        require_once PLS_PLS_DIR . 'includes/core/class-pls-ingredient-sync.php';

        // Package Type
        $package_type_id = PLS_Repo_Attributes::insert_attr( array(
            'label' => 'Package Type',
            'option_type' => 'product_option',
            'is_variation' => 1,
        ) );

        $package_types = array(
            '30ml Glass Bottle' => array( 'price' => 0, 'tier_prices' => array() ),
            '50ml Glass Bottle' => array( 'price' => 0, 'tier_prices' => array() ),
            '120ml Glass Bottle' => array( 'price' => 0, 'tier_prices' => array() ),
            '50gr Jar' => array( 'price' => 0, 'tier_prices' => array() ),
        );

        foreach ( $package_types as $label => $data ) {
            $value_id = PLS_Repo_Attributes::insert_value( array(
                'attribute_id' => $package_type_id,
                'label' => $label,
            ) );
            if ( $value_id && ! empty( $data['tier_prices'] ) ) {
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, 1, $data['tier_prices'] );
            }
        }

        // Package Color
        $existing_package_color = $wpdb->get_row( "SELECT id FROM {$attr_table} WHERE attr_key = 'package-color' LIMIT 1" );
        
        if ( $existing_package_color ) {
            $package_color_id = $existing_package_color->id;
        } else {
            $package_color_id = PLS_Repo_Attributes::insert_attr( array(
                'label' => 'Package Color',
                'attr_key' => 'package-color',
                'option_type' => 'product_option',
                'is_variation' => 1,
            ) );
        }

        $package_colors = array(
            'Standard White (White Lid & Pump)' => array( 'price' => 0, 'tier_prices' => array() ),
            'Standard Frosted (White Lid & Pump)' => array( 'price' => 0, 'tier_prices' => array() ),
            'Amber Bottle (White Lid & Pump)' => array( 'price' => 2.50, 'tier_prices' => array( 1 => 3.00, 2 => 2.75, 3 => 2.50, 4 => 2.00, 5 => 1.50 ) ),
            'Silver Pump & Lid (Upgrade)' => array( 'price' => 1.50, 'tier_prices' => array( 1 => 2.00, 2 => 1.75, 3 => 1.50, 4 => 1.00, 5 => 0.75 ) ),
        );

        // Check existing values
        $existing_color_values = PLS_Repo_Attributes::values_for_attr( $package_color_id );
        $existing_color_labels = array_map( function( $v ) { return $v->label; }, $existing_color_values );
        
        foreach ( $package_colors as $label => $data ) {
            if ( in_array( $label, $existing_color_labels, true ) ) {
                continue;
            }
            $value_id = PLS_Repo_Attributes::insert_value( array(
                'attribute_id' => $package_color_id,
                'label' => $label,
            ) );
            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, 1, $data['tier_prices'] );
            }
        }

        // Package Cap
        $existing_package_cap = $wpdb->get_row( "SELECT id FROM {$attr_table} WHERE attr_key = 'package-cap' LIMIT 1" );
        
        if ( $existing_package_cap ) {
            $package_cap_id = $existing_package_cap->id;
        } else {
            $package_cap_id = PLS_Repo_Attributes::insert_attr( array(
                'label' => 'Package Cap',
                'attr_key' => 'package-cap',
                'option_type' => 'product_option',
                'is_variation' => 0,
            ) );
        }

        $package_caps = array(
            'White Pump' => array( 'price' => 0, 'tier_prices' => array() ),
            'Silver Pump' => array( 'price' => 1.50, 'tier_prices' => array( 1 => 2.00, 2 => 1.75, 3 => 1.50, 4 => 1.00, 5 => 0.75 ) ),
            'White Lid' => array( 'price' => 0, 'tier_prices' => array() ),
            'Silver Lid' => array( 'price' => 1.50, 'tier_prices' => array( 1 => 2.00, 2 => 1.75, 3 => 1.50, 4 => 1.00, 5 => 0.75 ) ),
            'Dropper' => array( 'price' => 0.50, 'tier_prices' => array( 1 => 0.75, 2 => 0.65, 3 => 0.50, 4 => 0.35, 5 => 0.25 ) ),
        );

        // Check existing values
        $existing_cap_values = PLS_Repo_Attributes::values_for_attr( $package_cap_id );
        $existing_cap_labels = array_map( function( $v ) { return $v->label; }, $existing_cap_values );
        
        foreach ( $package_caps as $label => $data ) {
            if ( in_array( $label, $existing_cap_labels, true ) ) {
                continue;
            }
            $value_id = PLS_Repo_Attributes::insert_value( array(
                'attribute_id' => $package_cap_id,
                'label' => $label,
            ) );
            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, 1, $data['tier_prices'] );
            }
        }

        // Label Application
        $existing_label_app = $wpdb->get_row( "SELECT id FROM {$attr_table} WHERE attr_key = 'label-application' LIMIT 1" );
        
        if ( $existing_label_app ) {
            $label_app_id = $existing_label_app->id;
        } else {
            $label_app_id = PLS_Repo_Attributes::insert_attr( array(
                'label' => 'Label Application',
                'attr_key' => 'label-application',
                'option_type' => 'product_option',
                'is_variation' => 0,
            ) );
        }

        $label_price_tier_1_2 = get_option( 'pls_label_price_tier_1_2', 0.50 );
        
        $label_app_values = array(
            'DIY Label Application' => array( 'price' => 0, 'tier_prices' => array(), 'min_tier' => 1 ),
            'Professional Label Application' => array( 
                'price' => $label_price_tier_1_2, 
                'tier_prices' => array( 
                    'tier_1' => $label_price_tier_1_2, 
                    'tier_2' => $label_price_tier_1_2,
                    'tier_3' => 0,
                    'tier_4' => 0,
                    'tier_5' => 0,
                ),
                'min_tier' => 1 
            ),
        );

        foreach ( $label_app_values as $label => $data ) {
            $value_id = PLS_Repo_Attributes::insert_value( array(
                'attribute_id' => $label_app_id,
                'label' => $label,
            ) );
            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, $data['min_tier'], $data['tier_prices'] );
            }
        }

        // Fragrances (Tier 3+)
        $existing_fragrance = $wpdb->get_row( "SELECT id FROM {$attr_table} WHERE attr_key = 'fragrance' LIMIT 1" );
        
        if ( $existing_fragrance ) {
            $fragrance_id = $existing_fragrance->id;
        } else {
            $fragrance_id = PLS_Repo_Attributes::insert_attr( array(
                'label' => 'Fragrance',
                'attr_key' => 'fragrance',
                'option_type' => 'product_option',
                'is_variation' => 0,
            ) );
        }

        $fragrances = array(
            'Unscented' => array( 'price' => 0, 'tier_prices' => array(), 'min_tier' => 3 ),
            'Sweet Oranges & Ylang Ylang' => array( 'price' => 1.50, 'tier_prices' => array( 3 => 1.50, 4 => 1.25, 5 => 1.00 ), 'min_tier' => 3 ),
            'Deep Cedarwood & Neroli' => array( 'price' => 1.50, 'tier_prices' => array( 3 => 1.50, 4 => 1.25, 5 => 1.00 ), 'min_tier' => 3 ),
            'Lavender & Chamomile' => array( 'price' => 1.50, 'tier_prices' => array( 3 => 1.50, 4 => 1.25, 5 => 1.00 ), 'min_tier' => 3 ),
            'Eucalyptus & Mint' => array( 'price' => 1.50, 'tier_prices' => array( 3 => 1.50, 4 => 1.25, 5 => 1.00 ), 'min_tier' => 3 ),
            'Rose & Geranium' => array( 'price' => 1.50, 'tier_prices' => array( 3 => 1.50, 4 => 1.25, 5 => 1.00 ), 'min_tier' => 3 ),
        );

        // Check existing values
        $existing_fragrance_values = PLS_Repo_Attributes::values_for_attr( $fragrance_id );
        $existing_fragrance_labels = array_map( function( $v ) { return $v->label; }, $existing_fragrance_values );
        
        foreach ( $fragrances as $label => $data ) {
            if ( in_array( $label, $existing_fragrance_labels, true ) ) {
                continue;
            }
            $value_id = PLS_Repo_Attributes::insert_value( array(
                'attribute_id' => $fragrance_id,
                'label' => $label,
            ) );
            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, $data['min_tier'], $data['tier_prices'] );
            }
        }

        // Custom Printed Bottles (Tier 4+)
        $existing_custom_bottle = $wpdb->get_row( "SELECT id FROM {$attr_table} WHERE attr_key = 'custom-bottles' LIMIT 1" );
        
        if ( $existing_custom_bottle ) {
            $custom_bottle_id = $existing_custom_bottle->id;
        } else {
            $custom_bottle_id = PLS_Repo_Attributes::insert_attr( array(
                'label' => 'Custom Printed Bottles',
                'attr_key' => 'custom-bottles',
                'option_type' => 'product_option',
                'is_variation' => 0,
            ) );
        }

        $custom_bottles = array(
            'No Custom Printing' => array( 'price' => 0, 'tier_prices' => array(), 'min_tier' => 4 ),
            'Custom Printed Bottle' => array( 'price' => 3.50, 'tier_prices' => array( 4 => 3.50, 5 => 3.00 ), 'min_tier' => 4 ),
        );

        foreach ( $custom_bottles as $label => $data ) {
            $value_id = PLS_Repo_Attributes::insert_value( array(
                'attribute_id' => $custom_bottle_id,
                'label' => $label,
            ) );
            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules( $data['min_tier'], $value_id, $data['tier_prices'] );
            }
        }

        // External Box Packaging (Tier 4+)
        $existing_box_packaging = $wpdb->get_row( "SELECT id FROM {$attr_table} WHERE attr_key = 'box-packaging' LIMIT 1" );
        
        if ( $existing_box_packaging ) {
            $box_packaging_id = $existing_box_packaging->id;
        } else {
            $box_packaging_id = PLS_Repo_Attributes::insert_attr( array(
                'label' => 'External Box Packaging',
                'attr_key' => 'box-packaging',
                'option_type' => 'product_option',
                'is_variation' => 0,
            ) );
        }

        $box_options = array(
            'No Box Packaging' => array( 'price' => 0, 'tier_prices' => array(), 'min_tier' => 4 ),
            'Eco-Friendly Box' => array( 'price' => 2.00, 'tier_prices' => array( 4 => 2.00, 5 => 1.75 ), 'min_tier' => 4 ),
            'Premium Gift Box' => array( 'price' => 4.00, 'tier_prices' => array( 4 => 4.00, 5 => 3.50 ), 'min_tier' => 4 ),
        );

        // Check existing values
        $existing_box_values = PLS_Repo_Attributes::values_for_attr( $box_packaging_id );
        $existing_box_labels = array_map( function( $v ) { return $v->label; }, $existing_box_values );
        
        foreach ( $box_options as $label => $data ) {
            if ( in_array( $label, $existing_box_labels, true ) ) {
                continue;
            }
            $value_id = PLS_Repo_Attributes::insert_value( array(
                'attribute_id' => $box_packaging_id,
                'label' => $label,
            ) );
            if ( $value_id ) {
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, $data['min_tier'], $data['tier_prices'] );
            }
        }

        // Ingredients are automatically synced via PLS_Ingredient_Sync when created
        // They appear as product options with tier restrictions
    }

    /**
     * Add sample products.
     */
    private static function add_products() {
        $categories = get_terms( array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ) );

        $products = array(
            array(
                'name' => 'Milk Cleanser',
                'category' => 'Face Cleansers',
                'description' => 'Turns out you can bottle heaven, and it looks a little something like this milk cleanser. Vegan, natural and the ultimate hero for dry or sensitive skin.',
                'directions' => 'Drench skin, add 1-2 pumps to twinkling fingertips and gently massage into the face and neck in circular motions. Rinse with warm water.',
                'skin_types' => 'All Skin Types / Dry / Sensitive',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 19.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 17.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 15.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 13.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 11.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Emu Apple', 'Cucumber', 'Desert Lime' ),
            ),
            array(
                'name' => 'Hydrating Toner',
                'category' => 'Toning Mists',
                'description' => 'A refreshing facial mist that balances pH and prepares skin for serums and moisturizers.',
                'directions' => 'Spritz onto clean skin morning and night, or throughout the day for a refreshing boost.',
                'skin_types' => 'All Skin Types',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 16.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 14.50, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 12.50, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 10.50, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 9.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Cucumber', 'Kakadu Plum' ),
            ),
            array(
                'name' => 'Nourishing Moisturiser',
                'category' => 'Moisturisers',
                'description' => 'Rich, hydrating cream that locks in moisture and supports skin barrier function.',
                'directions' => 'Apply to face and neck morning and night after cleansing and toning.',
                'skin_types' => 'Dry / Sensitive',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 22.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 20.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 18.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 16.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 14.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Hyaluronic Acid', 'Ceramides', 'Emu Apple' ),
            ),
            array(
                'name' => 'Vitamin C Serum',
                'category' => 'Serums & Oils',
                'description' => 'Brightening serum with high-potency vitamin C and Australian native extracts.',
                'directions' => 'Apply 2-3 drops to clean skin morning and night, before moisturizer.',
                'skin_types' => 'All Skin Types',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 28.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 25.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 22.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 19.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 16.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Vitamin C', 'Kakadu Plum', 'Desert Lime' ),
            ),
            array(
                'name' => 'Detoxifying Face Mask',
                'category' => 'Masks & Exfoliants',
                'description' => 'Deep cleansing clay mask that draws out impurities and refines pores.',
                'directions' => 'Apply a thin layer to clean skin, leave for 10-15 minutes, then rinse with warm water. Use 1-2 times per week.',
                'skin_types' => 'Oily / Combination',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 18.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 16.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 14.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 12.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 10.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Cucumber', 'Quandong' ),
            ),
            array(
                'name' => 'Eye Cream',
                'category' => 'Eye & Lip Care',
                'description' => 'Gentle eye cream that reduces puffiness and fine lines around the delicate eye area.',
                'directions' => 'Apply a small amount around the eye area using your ring finger, morning and night.',
                'skin_types' => 'All Skin Types',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 24.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 22.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 20.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 18.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 16.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Peptides', 'Hyaluronic Acid', 'Coenzyme Q10' ),
            ),
        );

        foreach ( $products as $product_data ) {
            $category_term = null;
            foreach ( $categories as $cat ) {
                if ( $cat->name === $product_data['category'] ) {
                    $category_term = $cat;
                    break;
                }
            }

            if ( ! $category_term ) {
                continue;
            }

            $product_id = PLS_Repo_Base_Product::insert( array(
                'name' => $product_data['name'],
                'slug' => sanitize_title( $product_data['name'] ),
                'status' => 'draft',
                'category_path' => (string) $category_term->term_id,
            ) );

            if ( ! $product_id ) {
                continue;
            }

            // Add pack tiers
            foreach ( $product_data['pack_tiers'] as $index => $tier_data ) {
                PLS_Repo_Pack_Tier::upsert(
                    $product_id,
                    $tier_data['tier_key'],
                    $tier_data['units'],
                    $tier_data['price'],
                    $tier_data['enabled'],
                    $index
                );
            }

            // Add product profile
            PLS_Repo_Product_Profile::upsert( $product_id, array(
                'description' => $product_data['description'],
                'directions' => $product_data['directions'],
                'skin_types' => $product_data['skin_types'],
            ) );

            // Add key ingredients
            if ( ! empty( $product_data['key_ingredients'] ) ) {
                $ingredient_ids = array();
                foreach ( $product_data['key_ingredients'] as $ing_name ) {
                    $ing_term = get_term_by( 'name', $ing_name, 'pls_ingredient' );
                    if ( $ing_term ) {
                        $ingredient_ids[] = $ing_term->term_id;
                    }
                }
                if ( ! empty( $ingredient_ids ) ) {
                    wp_set_object_terms( $product_id, $ingredient_ids, 'pls_ingredient' );
                }
            }
        }
    }
}
