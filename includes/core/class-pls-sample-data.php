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
     * Check if WooCommerce is active.
     *
     * @return bool
     */
    private static function is_woocommerce_active() {
        return class_exists( 'WooCommerce' ) && function_exists( 'wc_create_order' );
    }

    /**
     * Clean up existing data and add sample data.
     */
    public static function generate() {
        self::cleanup();
        self::add_categories();
        self::add_ingredients();
        self::add_product_options();
        self::add_products();
        self::add_bundles();
        
        // Sync products and bundles to WooCommerce
        self::sync_to_woocommerce();
        
        // Create comprehensive sample data
        self::add_woocommerce_orders();
        self::add_custom_orders();
        self::add_commissions();
        
        // Ensure commission email settings are configured
        if ( ! get_option( 'pls_commission_email_recipients' ) ) {
            update_option( 'pls_commission_email_recipients', array( 'n.nikolic97@gmail.com' ) );
        }
    }

    /**
     * Clean up existing data.
     */
    private static function cleanup() {
        global $wpdb;

        // Delete WooCommerce products that were synced from PLS
        $pls_products = PLS_Repo_Base_Product::all();
        foreach ( $pls_products as $pls_product ) {
            if ( $pls_product->wc_product_id ) {
                wp_delete_post( $pls_product->wc_product_id, true );
            }
        }

        // Delete WooCommerce bundles
        $pls_bundles = PLS_Repo_Bundle::all();
        foreach ( $pls_bundles as $bundle ) {
            if ( $bundle->wc_product_id ) {
                wp_delete_post( $bundle->wc_product_id, true );
            }
        }

        // Delete sample WooCommerce orders (only those created by sample data)
        $sample_orders = wc_get_orders( array(
            'limit' => -1,
            'meta_query' => array(
                array(
                    'key' => '_pls_sample_order',
                    'value' => '1',
                    'compare' => '='
                )
            )
        ) );
        foreach ( $sample_orders as $order ) {
            wp_delete_post( $order->get_id(), true );
        }

        // Delete all PLS products
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

        // Delete bundles
        $bundles_table = $wpdb->prefix . 'pls_bundle';
        $wpdb->query( "TRUNCATE TABLE {$bundles_table}" );

        $bundle_items_table = $wpdb->prefix . 'pls_bundle_item';
        $wpdb->query( "TRUNCATE TABLE {$bundle_items_table}" );

        // Delete product categories except Face parent
        $face_category = get_term_by( 'slug', 'face', 'product_cat' );
        $all_categories = get_terms( array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ) );

        foreach ( $all_categories as $category ) {
            // Keep Face category if it exists
            if ( $face_category && $category->term_id === $face_category->term_id ) {
                continue;
            }
            wp_delete_term( $category->term_id, 'product_cat' );
        }
    }

    /**
     * Add sample categories.
     */
    private static function add_categories() {
        // Create or get Face parent category
        $face_term = term_exists( 'Face', 'product_cat' );
        if ( ! $face_term ) {
            $face_result = wp_insert_term( 'Face', 'product_cat', array(
                'description' => 'Face skincare products',
                'slug' => 'face'
            ) );
            $face_term_id = is_wp_error( $face_result ) ? null : $face_result['term_id'];
        } else {
            $face_term_id = is_array( $face_term ) ? $face_term['term_id'] : $face_term->term_id;
        }

        if ( ! $face_term_id ) {
            return; // Could not create/get Face category
        }

        // Create subcategories under Face
        $subcategories = array(
            'Cleansers' => 'Gentle cleansers for all skin types',
            'Toning Mists' => 'Refreshing toners and facial mists',
            'Moisturisers' => 'Hydrating creams and lotions',
            'Serums & Oils' => 'Concentrated treatments and facial oils',
            'Masks & Exfoliants' => 'Deep cleansing masks and exfoliating treatments',
            'Eye & Lip Care' => 'Specialized eye creams and lip treatments',
        );

        foreach ( $subcategories as $name => $description ) {
            $term = term_exists( $name, 'product_cat' );
            if ( ! $term ) {
                wp_insert_term( $name, 'product_cat', array(
                    'description' => $description,
                    'parent' => $face_term_id
                ) );
            } else {
                // Update parent if exists
                $term_id = is_array( $term ) ? $term['term_id'] : $term->term_id;
                wp_update_term( $term_id, 'product_cat', array( 'parent' => $face_term_id ) );
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
                PLS_Repo_Attributes::update_value_tier_rules( $value_id, $data['min_tier'], $data['tier_prices'] );
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
                'category' => 'Cleansers',
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
                'name' => 'Gel Cleanser',
                'category' => 'Cleansers',
                'description' => 'Deep cleansing gel formula that removes excess oil and impurities without stripping the skin. Perfect for oily and combination skin types.',
                'directions' => 'Wet face with warm water, massage a small amount into skin in circular motions. Rinse thoroughly and pat dry.',
                'skin_types' => 'Oily / Combination',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 18.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 16.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 14.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 12.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 10.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Cucumber', 'Desert Lime' ),
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
                'name' => 'Rose Water Mist',
                'category' => 'Toning Mists',
                'description' => 'Soothing floral facial mist with rose water to calm and hydrate the skin. Ideal for sensitive and dry skin types.',
                'directions' => 'Spritz onto face throughout the day or after cleansing to refresh and hydrate.',
                'skin_types' => 'Dry / Sensitive',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 17.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 15.50, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 13.50, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 11.50, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 9.50, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Emu Apple', 'Quandong' ),
            ),
            array(
                'name' => 'Daily Moisturiser',
                'category' => 'Moisturisers',
                'description' => 'Lightweight daily hydration that absorbs quickly without leaving a greasy feel. Perfect for all skin types.',
                'directions' => 'Apply to face and neck morning and night after cleansing and toning.',
                'skin_types' => 'All Skin Types',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 21.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 19.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 17.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 15.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 13.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Hyaluronic Acid', 'Cucumber' ),
            ),
            array(
                'name' => 'Rich Night Cream',
                'category' => 'Moisturisers',
                'description' => 'Intensive overnight repair cream that works while you sleep to restore and rejuvenate the skin.',
                'directions' => 'Apply generously to face and neck before bed. Massage in gently until absorbed.',
                'skin_types' => 'Dry / Sensitive',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 25.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 23.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 21.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 19.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 17.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Ceramides', 'Hyaluronic Acid', 'Peptides' ),
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
                'name' => 'Hyaluronic Serum',
                'category' => 'Serums & Oils',
                'description' => 'Deep hydration plumping serum with multiple molecular weights of hyaluronic acid for maximum moisture retention.',
                'directions' => 'Apply 2-3 drops to clean, damp skin morning and night. Follow with moisturizer.',
                'skin_types' => 'All Skin Types',
                'pack_tiers' => array(
                    array( 'tier_key' => 'tier_1', 'units' => 50, 'price' => 26.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_2', 'units' => 100, 'price' => 24.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_3', 'units' => 250, 'price' => 21.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_4', 'units' => 500, 'price' => 18.00, 'enabled' => 1 ),
                    array( 'tier_key' => 'tier_5', 'units' => 1000, 'price' => 15.00, 'enabled' => 1 ),
                ),
                'key_ingredients' => array( 'Hyaluronic Acid', 'Niacinamide', 'Cucumber' ),
            ),
            array(
                'name' => 'Clay Detox Mask',
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
                'name' => 'Eye Repair Cream',
                'category' => 'Eye & Lip Care',
                'description' => 'Anti-aging eye treatment that reduces fine lines, dark circles, and puffiness around the delicate eye area.',
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
                'status' => 'live', // Set to live so they sync properly
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

            // Get product options for basics_json
            global $wpdb;
            $attributes_table = $wpdb->prefix . 'pls_attribute';
            $values_table = $wpdb->prefix . 'pls_attribute_value';
            
            // Get Package Type attribute and values
            $package_type_attr = $wpdb->get_row( "SELECT * FROM {$attributes_table} WHERE label = 'Package Type' LIMIT 1" );
            $package_type_values = array();
            if ( $package_type_attr ) {
                $package_type_values = PLS_Repo_Attributes::values_for_attr( $package_type_attr->id );
            }
            
            // Get Package Color attribute and values
            $package_color_attr = $wpdb->get_row( "SELECT * FROM {$attributes_table} WHERE attr_key = 'package-color' LIMIT 1" );
            $package_color_values = array();
            if ( $package_color_attr ) {
                $package_color_values = PLS_Repo_Attributes::values_for_attr( $package_color_attr->id );
            }
            
            // Get Package Cap attribute and values
            $package_cap_attr = $wpdb->get_row( "SELECT * FROM {$attributes_table} WHERE attr_key = 'package-cap' LIMIT 1" );
            $package_cap_values = array();
            if ( $package_cap_attr ) {
                $package_cap_values = PLS_Repo_Attributes::values_for_attr( $package_cap_attr->id );
            }
            
            // Get Fragrance attribute and values
            $fragrance_attr = $wpdb->get_row( "SELECT * FROM {$attributes_table} WHERE attr_key = 'fragrance' LIMIT 1" );
            $fragrance_values = array();
            if ( $fragrance_attr ) {
                $fragrance_values = PLS_Repo_Attributes::values_for_attr( $fragrance_attr->id );
            }
            
            // Build attributes array for basics_json
            $basics_attrs = array();
            
            // Add Package Type (select first available)
            if ( $package_type_attr && ! empty( $package_type_values ) ) {
                $selected_package_type = $package_type_values[0];
                $basics_attrs[] = array(
                    'attribute_id' => $package_type_attr->id,
                    'attribute_label' => $package_type_attr->label,
                    'values' => array(
                        array(
                            'value_id' => $selected_package_type->id,
                            'value_label' => $selected_package_type->label,
                            'price' => 0,
                        ),
                    ),
                );
            }
            
            // Add Package Colors (select 2-3 colors)
            if ( $package_color_attr && ! empty( $package_color_values ) ) {
                $selected_colors = array_slice( $package_color_values, 0, min( 3, count( $package_color_values ) ) );
                $color_values = array();
                foreach ( $selected_colors as $color ) {
                    $color_values[] = array(
                        'value_id' => $color->id,
                        'value_label' => $color->label,
                        'price' => 0,
                    );
                }
                if ( ! empty( $color_values ) ) {
                    $basics_attrs[] = array(
                        'attribute_id' => $package_color_attr->id,
                        'attribute_label' => $package_color_attr->label,
                        'values' => $color_values,
                    );
                }
            }
            
            // Add Package Caps (select 2-3 caps)
            if ( $package_cap_attr && ! empty( $package_cap_values ) ) {
                $selected_caps = array_slice( $package_cap_values, 0, min( 3, count( $package_cap_values ) ) );
                $cap_values = array();
                foreach ( $selected_caps as $cap ) {
                    $cap_values[] = array(
                        'value_id' => $cap->id,
                        'value_label' => $cap->label,
                        'price' => 0,
                    );
                }
                if ( ! empty( $cap_values ) ) {
                    $basics_attrs[] = array(
                        'attribute_id' => $package_cap_attr->id,
                        'attribute_label' => $package_cap_attr->label,
                        'values' => $cap_values,
                    );
                }
            }
            
            // Add Fragrances for Tier 3+ products (select 2-3 fragrances)
            $product_tier = ! empty( $product_data['pack_tiers'] ) && isset( $product_data['pack_tiers'][2] ) ? 3 : 1;
            if ( $fragrance_attr && ! empty( $fragrance_values ) && $product_tier >= 3 ) {
                $selected_fragrances = array_slice( $fragrance_values, 0, min( 3, count( $fragrance_values ) ) );
                $fragrance_values_array = array();
                foreach ( $selected_fragrances as $fragrance ) {
                    $fragrance_values_array[] = array(
                        'value_id' => $fragrance->id,
                        'value_label' => $fragrance->label,
                        'price' => 0,
                    );
                }
                if ( ! empty( $fragrance_values_array ) ) {
                    $basics_attrs[] = array(
                        'attribute_id' => $fragrance_attr->id,
                        'attribute_label' => $fragrance_attr->label,
                        'values' => $fragrance_values_array,
                    );
                }
            }
            
            // Parse skin types
            $skin_types_array = array_map( function( $type ) {
                return array( 'label' => trim( $type ), 'icon' => '' );
            }, explode( '/', $product_data['skin_types'] ) );
            
            // Build benefits
            $benefits_array = array(
                array( 'label' => 'Natural & Vegan', 'icon' => '' ),
                array( 'label' => 'Australian Made', 'icon' => '' ),
                array( 'label' => 'Cruelty Free', 'icon' => '' ),
            );
            
            // Build key ingredients JSON
            $key_ingredients_array = array();
            if ( ! empty( $product_data['key_ingredients'] ) ) {
                foreach ( $product_data['key_ingredients'] as $ing_name ) {
                    $ing_term = get_term_by( 'name', $ing_name, 'pls_ingredient' );
                    if ( $ing_term ) {
                        $key_ingredients_array[] = array(
                            'label' => $ing_name,
                            'icon' => '',
                            'term_id' => $ing_term->term_id,
                            'short_description' => get_term_meta( $ing_term->term_id, 'description', true ) ?: '',
                        );
                    }
                }
            }
            
            // Add product profile with full options
            $ingredient_ids = array();
            if ( ! empty( $product_data['key_ingredients'] ) ) {
                foreach ( $product_data['key_ingredients'] as $ing_name ) {
                    $ing_term = get_term_by( 'name', $ing_name, 'pls_ingredient' );
                    if ( $ing_term ) {
                        $ingredient_ids[] = $ing_term->term_id;
                    }
                }
            }
            
            PLS_Repo_Product_Profile::upsert( $product_id, array(
                'short_description' => $product_data['description'],
                'long_description' => $product_data['description'] . ' ' . $product_data['directions'],
                'directions_text' => $product_data['directions'],
                'skin_types_json' => $skin_types_array,
                'benefits_json' => $benefits_array,
                'key_ingredients_json' => $key_ingredients_array,
                'ingredients_list' => implode( ',', $ingredient_ids ),
                'basics_json' => $basics_attrs,
                'label_enabled' => 1,
                'label_price_per_unit' => 0.50,
                'label_requires_file' => 1,
                'label_helper_text' => 'Upload your label design',
                'label_guide_url' => 'https://bodocibiophysics.com/label-guide/',
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

    /**
     * Add sample bundles.
     */
    private static function add_bundles() {
        $bundles = array(
            array(
                'name' => 'Mini Line Bundle',
                'bundle_type' => 'mini_line',
                'sku_count' => 2,
                'units_per_sku' => 250,
                'price_per_unit' => 10.90,
                'commission_per_unit' => 0.59,
                'status' => 'live',
            ),
            array(
                'name' => 'Starter Line Bundle',
                'bundle_type' => 'starter_line',
                'sku_count' => 3,
                'units_per_sku' => 300,
                'price_per_unit' => 9.90,
                'commission_per_unit' => 0.49,
                'status' => 'live',
            ),
            array(
                'name' => 'Growth Line Bundle',
                'bundle_type' => 'growth_line',
                'sku_count' => 4,
                'units_per_sku' => 400,
                'price_per_unit' => 8.20,
                'commission_per_unit' => 0.32,
                'status' => 'live',
            ),
            array(
                'name' => 'Premium Line Bundle',
                'bundle_type' => 'premium_line',
                'sku_count' => 6,
                'units_per_sku' => 500,
                'price_per_unit' => 7.50,
                'commission_per_unit' => 0.25,
                'status' => 'draft', // Draft for testing
            ),
        );

        foreach ( $bundles as $bundle_data ) {
            $slug = sanitize_title( $bundle_data['name'] );
            $bundle_key = $bundle_data['bundle_type'] . '_' . $bundle_data['sku_count'] . 'x' . $bundle_data['units_per_sku'];
            
            // Calculate totals
            $total_units = $bundle_data['sku_count'] * $bundle_data['units_per_sku'];
            $total_price = $total_units * $bundle_data['price_per_unit'];

            // Store bundle rules in JSON
            $offer_rules = array(
                'bundle_type' => $bundle_data['bundle_type'],
                'sku_count' => $bundle_data['sku_count'],
                'units_per_sku' => $bundle_data['units_per_sku'],
                'price_per_unit' => $bundle_data['price_per_unit'],
                'commission_per_unit' => $bundle_data['commission_per_unit'],
                'total_units' => $total_units,
                'total_price' => $total_price,
            );

            $data = array(
                'bundle_key' => $bundle_key,
                'slug' => $slug,
                'name' => $bundle_data['name'],
                'base_price' => $total_price,
                'pricing_mode' => 'fixed',
                'status' => $bundle_data['status'],
                'offer_rules_json' => wp_json_encode( $offer_rules ),
            );

            PLS_Repo_Bundle::insert( $data );
        }
    }

    /**
     * Sync products and bundles to WooCommerce.
     */
    private static function sync_to_woocommerce() {
        if ( ! self::is_woocommerce_active() ) {
            return;
        }

        require_once PLS_PLS_DIR . 'includes/wc/class-pls-wc-sync.php';

        // Sync all products
        $products = PLS_Repo_Base_Product::all();
        foreach ( $products as $product ) {
            PLS_WC_Sync::sync_base_product_to_wc( $product->id );
        }

        // Sync all bundles
        $bundles = PLS_Repo_Bundle::all();
        foreach ( $bundles as $bundle ) {
            PLS_WC_Sync::sync_bundle_to_wc( $bundle->id );
        }
    }

    /**
     * Add sample WooCommerce orders with PLS products.
     */
    private static function add_woocommerce_orders() {
        if ( ! class_exists( 'WooCommerce' ) || ! function_exists( 'wc_create_order' ) ) {
            return;
        }

        $products = PLS_Repo_Base_Product::all();
        if ( empty( $products ) ) {
            return;
        }

        // Get pack tier variations for products
        $orders_data = array(
            // Completed orders (2-3)
            array(
                'date' => date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
                'status' => 'completed',
                'customer' => array(
                    'first_name' => 'Sarah',
                    'last_name' => 'Johnson',
                    'email' => 'sarah.johnson@example.com',
                    'address' => '123 Main St',
                    'city' => 'Sydney',
                    'state' => 'NSW',
                    'postcode' => '2000',
                    'country' => 'AU',
                ),
                'items' => array(
                    array( 'product_index' => 0, 'tier' => 'tier_2', 'quantity' => 2 ),
                    array( 'product_index' => 1, 'tier' => 'tier_3', 'quantity' => 1 ),
                ),
            ),
            array(
                'date' => date( 'Y-m-d H:i:s', strtotime( '-4 days' ) ),
                'status' => 'completed',
                'customer' => array(
                    'first_name' => 'Emma',
                    'last_name' => 'Wilson',
                    'email' => 'emma.wilson@example.com',
                    'address' => '789 Queen St',
                    'city' => 'Brisbane',
                    'state' => 'QLD',
                    'postcode' => '4000',
                    'country' => 'AU',
                ),
                'items' => array(
                    array( 'product_index' => 0, 'tier' => 'tier_3', 'quantity' => 1 ),
                    array( 'product_index' => 1, 'tier' => 'tier_3', 'quantity' => 1 ),
                ),
                'bundle_qualified' => 'mini_line',
            ),
            array(
                'date' => date( 'Y-m-d H:i:s', strtotime( '-10 days' ) ),
                'status' => 'completed',
                'customer' => array(
                    'first_name' => 'Thomas',
                    'last_name' => 'Anderson',
                    'email' => 'thomas.anderson@example.com',
                    'address' => '555 Flinders St',
                    'city' => 'Adelaide',
                    'state' => 'SA',
                    'postcode' => '5000',
                    'country' => 'AU',
                ),
                'items' => array(
                    array( 'product_index' => 2, 'tier' => 'tier_5', 'quantity' => 5 ),
                ),
            ),
            // Processing orders (2)
            array(
                'date' => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
                'status' => 'processing',
                'customer' => array(
                    'first_name' => 'Lisa',
                    'last_name' => 'Williams',
                    'email' => 'lisa.williams@example.com',
                    'address' => '789 George St',
                    'city' => 'Brisbane',
                    'state' => 'QLD',
                    'postcode' => '4000',
                    'country' => 'AU',
                ),
                'items' => array(
                    array( 'product_index' => 4, 'tier' => 'tier_3', 'quantity' => 2 ),
                    array( 'product_index' => 5, 'tier' => 'tier_2', 'quantity' => 1 ),
                ),
            ),
            array(
                'date' => date( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
                'status' => 'processing',
                'customer' => array(
                    'first_name' => 'Michael',
                    'last_name' => 'Chen',
                    'email' => 'michael.chen@example.com',
                    'address' => '456 Collins St',
                    'city' => 'Melbourne',
                    'state' => 'VIC',
                    'postcode' => '3000',
                    'country' => 'AU',
                ),
                'items' => array(
                    array( 'product_index' => 6, 'tier' => 'tier_1', 'quantity' => 3 ),
                    array( 'product_index' => 7, 'tier' => 'tier_4', 'quantity' => 1 ),
                ),
            ),
            // On-hold order (1)
            array(
                'date' => date( 'Y-m-d H:i:s', strtotime( '-3 days' ) ),
                'status' => 'on-hold',
                'customer' => array(
                    'first_name' => 'David',
                    'last_name' => 'Brown',
                    'email' => 'david.brown@example.com',
                    'address' => '321 King St',
                    'city' => 'Perth',
                    'state' => 'WA',
                    'postcode' => '6000',
                    'country' => 'AU',
                ),
                'items' => array(
                    array( 'product_index' => 8, 'tier' => 'tier_3', 'quantity' => 1 ),
                    array( 'product_index' => 9, 'tier' => 'tier_2', 'quantity' => 2 ),
                ),
            ),
            // Pending payment order (1)
            array(
                'date' => date( 'Y-m-d H:i:s', strtotime( '-6 hours' ) ),
                'status' => 'pending',
                'customer' => array(
                    'first_name' => 'Jessica',
                    'last_name' => 'Martinez',
                    'email' => 'jessica.martinez@example.com',
                    'address' => '888 Bourke St',
                    'city' => 'Melbourne',
                    'state' => 'VIC',
                    'postcode' => '3000',
                    'country' => 'AU',
                ),
                'items' => array(
                    array( 'product_index' => 3, 'tier' => 'tier_2', 'quantity' => 1 ),
                ),
            ),
        );

        foreach ( $orders_data as $order_data ) {
            $order = wc_create_order( array( 'status' => $order_data['status'] ) );
            
            if ( is_wp_error( $order ) ) {
                continue;
            }

            // Set order date
            $order->set_date_created( strtotime( $order_data['date'] ) );

            // Set customer
            $order->set_billing_first_name( $order_data['customer']['first_name'] );
            $order->set_billing_last_name( $order_data['customer']['last_name'] );
            $order->set_billing_email( $order_data['customer']['email'] );
            $order->set_billing_address_1( $order_data['customer']['address'] );
            $order->set_billing_city( $order_data['customer']['city'] );
            $order->set_billing_state( $order_data['customer']['state'] );
            $order->set_billing_postcode( $order_data['customer']['postcode'] );
            $order->set_billing_country( $order_data['customer']['country'] );

            $order->set_shipping_first_name( $order_data['customer']['first_name'] );
            $order->set_shipping_last_name( $order_data['customer']['last_name'] );
            $order->set_shipping_address_1( $order_data['customer']['address'] );
            $order->set_shipping_city( $order_data['customer']['city'] );
            $order->set_shipping_state( $order_data['customer']['state'] );
            $order->set_shipping_postcode( $order_data['customer']['postcode'] );
            $order->set_shipping_country( $order_data['customer']['country'] );

            // Add products
            foreach ( $order_data['items'] as $item_data ) {
                $product_index = $item_data['product_index'];
                if ( ! isset( $products[ $product_index ] ) ) {
                    continue;
                }

                $base_product = $products[ $product_index ];
                $wc_product_id = $base_product->wc_product_id;

                if ( ! $wc_product_id ) {
                    continue;
                }

                $wc_product = wc_get_product( $wc_product_id );
                if ( ! $wc_product || ! $wc_product->is_type( 'variable' ) ) {
                    continue;
                }

                // Get variation for tier
                $variations = $wc_product->get_children();
                $tier_key = $item_data['tier'];
                $variation_id = null;

                foreach ( $variations as $var_id ) {
                    $variation = wc_get_product( $var_id );
                    if ( ! $variation ) {
                        continue;
                    }

                    $attributes = $variation->get_attributes();
                    if ( isset( $attributes['pa_pack-tier'] ) ) {
                        $term = get_term_by( 'slug', $tier_key, 'pa_pack-tier' );
                        if ( $term && $attributes['pa_pack-tier'] === $term->slug ) {
                            $variation_id = $var_id;
                            break;
                        }
                    }
                }

                if ( ! $variation_id && ! empty( $variations ) ) {
                    $variation_id = $variations[0]; // Fallback to first variation
                }

                if ( $variation_id ) {
                    $variation = wc_get_product( $variation_id );
                    $item = $order->add_product( $variation, $item_data['quantity'] );
                    
                    // Add bundle meta if this order qualifies for bundle pricing
                    if ( isset( $order_data['bundle_qualified'] ) && $item ) {
                        $bundle_type = $order_data['bundle_qualified'];
                        $bundle_key = $bundle_type . '_' . count( $order_data['items'] ) . 'x' . 250; // Approximate
                        $item->update_meta_data( 'pls_bundle_key', $bundle_key );
                        $item->update_meta_data( 'pls_bundle_type', $bundle_type );
                        $item->update_meta_data( 'pls_bundle_price', 10.90 ); // Sample bundle price
                        $item->save();
                    }
                }
            }

            $order->calculate_totals();
            
            // Mark as sample order for cleanup
            $order->update_meta_data( '_pls_sample_order', '1' );
            
            $order->save();
        }
    }

    /**
     * Add sample custom orders.
     */
    private static function add_custom_orders() {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        $categories = get_terms( array(
            'taxonomy' => 'product_cat',
            'hide_empty' => false,
        ) );

        $category_id = ! empty( $categories ) ? $categories[0]->term_id : null;

        $custom_orders = array(
            // New Leads (2)
            array(
                'contact_name' => 'Jennifer Martinez',
                'contact_email' => 'jennifer.martinez@example.com',
                'contact_phone' => '+61 400 123 456',
                'company_name' => 'Beauty Boutique Co.',
                'category_id' => $category_id,
                'quantity' => 500,
                'budget' => 15000.00,
                'timeline' => '4-6 weeks',
                'message' => 'Looking for a custom face cleanser line for our boutique. Need professional label application.',
                'status' => 'new_lead',
                'created_at' => date( 'Y-m-d H:i:s', strtotime( '-2 days' ) ),
            ),
            array(
                'contact_name' => 'Alexandra Green',
                'contact_email' => 'alexandra.green@example.com',
                'contact_phone' => '+61 400 111 222',
                'company_name' => 'Eco Beauty Essentials',
                'category_id' => $category_id,
                'quantity' => 750,
                'budget' => 18000.00,
                'timeline' => '5-7 weeks',
                'message' => 'Interested in organic skincare products with eco-friendly packaging.',
                'status' => 'new_lead',
                'created_at' => date( 'Y-m-d H:i:s', strtotime( '-1 day' ) ),
            ),
            // Sampling (2)
            array(
                'contact_name' => 'Robert Thompson',
                'contact_email' => 'robert.thompson@example.com',
                'contact_phone' => '+61 400 234 567',
                'company_name' => 'Wellness Solutions Ltd',
                'category_id' => $category_id,
                'quantity' => 1000,
                'budget' => 25000.00,
                'timeline' => '6-8 weeks',
                'message' => 'Interested in a complete skincare line. Would like samples first.',
                'status' => 'sampling',
                'created_at' => date( 'Y-m-d H:i:s', strtotime( '-5 days' ) ),
            ),
            array(
                'contact_name' => 'Rachel Kim',
                'contact_email' => 'rachel.kim@example.com',
                'contact_phone' => '+61 400 333 444',
                'company_name' => 'Glow Skincare Studio',
                'category_id' => $category_id,
                'quantity' => 600,
                'budget' => 16000.00,
                'timeline' => '4-6 weeks',
                'message' => 'Need samples for our new product line launch. Testing formulations.',
                'status' => 'sampling',
                'created_at' => date( 'Y-m-d H:i:s', strtotime( '-7 days' ) ),
            ),
            array(
                'contact_name' => 'Amanda Lee',
                'contact_email' => 'amanda.lee@example.com',
                'contact_phone' => '+61 400 345 678',
                'company_name' => 'Natural Skincare Co.',
                'category_id' => $category_id,
                'quantity' => 2000,
                'budget' => 45000.00,
                'timeline' => '8-10 weeks',
                'message' => 'Large order for our new product launch. Need premium packaging options.',
                'status' => 'production',
                'created_at' => date( 'Y-m-d H:i:s', strtotime( '-10 days' ) ),
                'production_cost' => 28000.00,
                'total_value' => 45000.00,
            ),
            array(
                'contact_name' => 'James Wilson',
                'contact_email' => 'james.wilson@example.com',
                'contact_phone' => '+61 400 456 789',
                'company_name' => 'Retail Partners Inc',
                'category_id' => $category_id,
                'quantity' => 5000,
                'budget' => 120000.00,
                'timeline' => '10-12 weeks',
                'message' => 'Bulk order for retail distribution. Need custom printed bottles.',
                'status' => 'done',
                'created_at' => date( 'Y-m-d H:i:s', strtotime( '-20 days' ) ),
                'production_cost' => 75000.00,
                'total_value' => 120000.00,
                'nikola_commission_rate' => 5.00,
                'nikola_commission_amount' => 6000.00,
                'commission_confirmed' => 1,
                'invoiced_at' => date( 'Y-m-d H:i:s', strtotime( '-15 days' ) ),
                'paid_at' => date( 'Y-m-d H:i:s', strtotime( '-10 days' ) ),
            ),
            array(
                'contact_name' => 'Sophie Taylor',
                'contact_email' => 'sophie.taylor@example.com',
                'contact_phone' => '+61 400 567 890',
                'company_name' => 'Luxury Beauty Brands',
                'category_id' => $category_id,
                'quantity' => 3000,
                'budget' => 85000.00,
                'timeline' => '6-8 weeks',
                'message' => 'Premium skincare line with custom fragrances and premium packaging.',
                'status' => 'done',
                'created_at' => date( 'Y-m-d H:i:s', strtotime( '-30 days' ) ),
                'production_cost' => 55000.00,
                'total_value' => 85000.00,
                'nikola_commission_rate' => 3.00,
                'nikola_commission_amount' => 2550.00,
                'commission_confirmed' => 1,
                'invoiced_at' => date( 'Y-m-d H:i:s', strtotime( '-25 days' ) ),
                'paid_at' => date( 'Y-m-d H:i:s', strtotime( '-20 days' ) ),
            ),
            array(
                'contact_name' => 'Mark Davis',
                'contact_email' => 'mark.davis@example.com',
                'contact_phone' => '+61 400 678 901',
                'company_name' => 'Eco Beauty Solutions',
                'category_id' => $category_id,
                'quantity' => 1500,
                'budget' => 35000.00,
                'timeline' => '4-6 weeks',
                'message' => 'Eco-friendly packaging required. Need samples before finalizing.',
                'status' => 'on_hold',
                'created_at' => date( 'Y-m-d H:i:s', strtotime( '-8 days' ) ),
            ),
        );

        foreach ( $custom_orders as $order_data ) {
            $wpdb->insert(
                $table,
                $order_data,
                array( '%s', '%s', '%s', '%s', '%d', '%d', '%f', '%s', '%s', '%s', '%s', '%f', '%f', '%f', '%f', '%d', '%s', '%s' )
            );
        }
    }

    /**
     * Add sample commission records.
     */
    private static function add_commissions() {
        if ( ! self::is_woocommerce_active() ) {
            return;
        }

        require_once PLS_PLS_DIR . 'includes/data/repo-commission.php';

        // Get WooCommerce orders with PLS products
        $orders_query = new WC_Order_Query( array(
            'limit' => -1,
            'status' => array( 'wc-completed', 'wc-processing' ),
        ) );
        $orders = $orders_query->get_orders();

        foreach ( $orders as $order ) {
            // Check if order has PLS products
            $has_pls_product = false;
            $pls_products = PLS_Repo_Base_Product::all();
            $pls_wc_ids = array();
            foreach ( $pls_products as $product ) {
                if ( $product->wc_product_id ) {
                    $pls_wc_ids[] = $product->wc_product_id;
                }
            }

            foreach ( $order->get_items() as $item ) {
                $product_id = $item->get_product_id();
                if ( in_array( $product_id, $pls_wc_ids, true ) ) {
                    $has_pls_product = true;
                    break;
                }
            }

            if ( ! $has_pls_product ) {
                continue;
            }

            // Commission should already be created by check_order_payment hook
            // But we'll create some manually for demonstration
            $existing_commissions = PLS_Repo_Commission::get_by_order( $order->get_id() );
            if ( ! empty( $existing_commissions ) ) {
                continue; // Skip if already exists
            }

            // Create commission record
            $total = $order->get_total();
            $date_created = $order->get_date_created();
            
            // Calculate commission based on items
            foreach ( $order->get_items() as $item_id => $item ) {
                $product_id = $item->get_product_id();
                if ( ! in_array( $product_id, $pls_wc_ids, true ) ) {
                    continue;
                }

                $variation_id = $item->get_variation_id();
                $quantity = $item->get_quantity();

                // Get units from variation
                $units = $quantity;
                if ( $variation_id ) {
                    $variation_units = get_post_meta( $variation_id, '_pls_units', true );
                    if ( $variation_units ) {
                        $units = $quantity * intval( $variation_units );
                    } else {
                        // Try to get from pack tier term
                        $variation = wc_get_product( $variation_id );
                        if ( $variation ) {
                            $attributes = $variation->get_attributes();
                            if ( isset( $attributes['pa_pack-tier'] ) ) {
                                $term = get_term_by( 'slug', $attributes['pa_pack-tier'], 'pa_pack-tier' );
                                if ( $term ) {
                                    $default_units = get_term_meta( $term->term_id, '_pls_default_units', true );
                                    if ( $default_units ) {
                                        $units = $quantity * intval( $default_units );
                                    }
                                }
                            }
                        }
                    }
                }

                // Get commission rate from pack tier
                $commission_rate = 0.80; // Default tier 1
                $tier_key = null;
                if ( $variation_id ) {
                    $variation = wc_get_product( $variation_id );
                    if ( $variation ) {
                        $attributes = $variation->get_attributes();
                        if ( isset( $attributes['pa_pack-tier'] ) ) {
                            $term = get_term_by( 'slug', $attributes['pa_pack-tier'], 'pa_pack-tier' );
                            if ( $term ) {
                                $tier_level = get_term_meta( $term->term_id, '_pls_tier_level', true );
                                if ( $tier_level ) {
                                    $tier_key = 'tier_' . $tier_level;
                                    $rates = get_option( 'pls_commission_rates', array() );
                                    $tier_rates = isset( $rates['tiers'] ) ? $rates['tiers'] : array();
                                    if ( isset( $tier_rates[ $tier_key ] ) ) {
                                        $commission_rate = floatval( $tier_rates[ $tier_key ] );
                                    }
                                }
                            }
                        }
                    }
                }

                $commission_amount = $units * $commission_rate;

                if ( $commission_amount > 0 ) {
                    PLS_Repo_Commission::create( array(
                        'wc_order_id' => $order->get_id(),
                        'wc_order_item_id' => $item_id,
                        'product_id' => $product_id,
                        'tier_key' => $tier_key,
                        'units' => $units,
                        'commission_rate_per_unit' => $commission_rate,
                        'commission_amount' => $commission_amount,
                    ) );
                }
            }
        }
    }
}
