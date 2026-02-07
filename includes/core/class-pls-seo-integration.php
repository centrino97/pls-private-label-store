<?php
/**
 * SEO and Plugin Integration for PLS.
 * Integrates with Yoast SEO, LiteSpeed Cache, and other plugins.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_SEO_Integration {

    /**
     * Runtime cache for base product lookups by WC product ID.
     *
     * @var array
     */
    private static $base_product_cache = array();

    /**
     * Get base product by WooCommerce product ID (cached per request).
     *
     * @param int $wc_product_id WooCommerce product ID.
     * @return object|null Base product row or null.
     */
    private static function get_base_by_wc_id( $wc_product_id ) {
        $wc_product_id = absint( $wc_product_id );
        if ( isset( self::$base_product_cache[ $wc_product_id ] ) ) {
            return self::$base_product_cache[ $wc_product_id ];
        }
        global $wpdb;
        $result = $wpdb->get_row( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d",
            $wc_product_id
        ), OBJECT );
        self::$base_product_cache[ $wc_product_id ] = $result;
        return $result;
    }

    /**
     * Initialize SEO integration.
     */
    public static function init() {
        // Yoast SEO integration
        if ( defined( 'WPSEO_VERSION' ) ) {
            add_filter( 'wpseo_sitemap_exclude_post_type', array( __CLASS__, 'yoast_sitemap_include_products' ), 10, 2 );
            add_filter( 'wpseo_metabox_prio', array( __CLASS__, 'yoast_metabox_priority' ) );
            add_action( 'wpseo_register_extra_replacements', array( __CLASS__, 'yoast_register_replacements' ) );
            add_filter( 'wpseo_schema_webpage', array( __CLASS__, 'yoast_product_schema' ), 10, 2 );
            add_filter( 'wpseo_breadcrumb_links', array( __CLASS__, 'yoast_breadcrumbs' ) );
            add_filter( 'wpseo_opengraph_title', array( __CLASS__, 'yoast_og_title' ), 10, 1 );
            add_filter( 'wpseo_opengraph_desc', array( __CLASS__, 'yoast_og_description' ), 10, 1 );
        }

        // LiteSpeed Cache compatibility
        if ( defined( 'LSCWP_V' ) ) {
            add_action( 'litespeed_cache_purge_post', array( __CLASS__, 'litespeed_purge_on_sync' ) );
            add_filter( 'litespeed_cache_esi_enabled', array( __CLASS__, 'litespeed_esi_products' ) );
            add_action( 'pls_product_synced', array( __CLASS__, 'litespeed_purge_product' ), 10, 1 );
        }

        // Auto-generate meta for products
        add_filter( 'wpseo_title', array( __CLASS__, 'auto_generate_product_title' ), 10, 1 );
        add_filter( 'wpseo_metadesc', array( __CLASS__, 'auto_generate_product_description' ), 10, 1 );
        add_action( 'wp_head', array( __CLASS__, 'output_product_schema' ), 5 );
        add_action( 'wp_head', array( __CLASS__, 'output_og_tags' ), 5 );
        
        // Hook into product sync to auto-populate SEO meta
        add_action( 'pls_product_synced', array( __CLASS__, 'on_product_synced' ), 10, 2 );

        // Category page SEO
        add_action( 'woocommerce_archive_description', array( __CLASS__, 'category_seo_content' ), 5 );
        add_filter( 'wpseo_taxonomy_meta', array( __CLASS__, 'category_seo_meta' ), 10, 2 );

        // Custom order form page SEO
        add_action( 'wp_head', array( __CLASS__, 'custom_order_page_seo' ), 5 );

        // Brevo integration (if available)
        if ( class_exists( 'Brevo' ) || function_exists( 'brevo_send_email' ) ) {
            add_action( 'pls_custom_order_created', array( __CLASS__, 'brevo_send_custom_order_notification' ), 10, 1 );
        }
    }

    /**
     * Include PLS products in Yoast sitemap.
     */
    public static function yoast_sitemap_include_products( $excluded, $post_type ) {
        if ( 'product' === $post_type ) {
            return false; // Don't exclude products
        }
        return $excluded;
    }

    /**
     * Set Yoast metabox priority for PLS products.
     */
    public static function yoast_metabox_priority( $priority ) {
        global $post;
        if ( $post && 'product' === $post->post_type ) {
            // Check if it's a PLS product
            global $wpdb;
            $pls_product = $wpdb->get_var( $wpdb->prepare(
                "SELECT id FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d",
                $post->ID
            ) );
            if ( $pls_product ) {
                return 'high'; // High priority for PLS products
            }
        }
        return $priority;
    }

    /**
     * Register Yoast SEO custom replacements.
     */
    public static function yoast_register_replacements() {
        wpseo_register_var_replacement(
            '%%pls_product_tier%%',
            array( __CLASS__, 'get_product_tier' ),
            'advanced',
            __( 'Pack Tier', 'pls-private-label-store' )
        );

        wpseo_register_var_replacement(
            '%%pls_product_ingredients%%',
            array( __CLASS__, 'get_product_ingredients' ),
            'advanced',
            __( 'Product Ingredients', 'pls-private-label-store' )
        );
    }

    /**
     * Get product tier for Yoast replacement.
     */
    public static function get_product_tier() {
        global $product;
        if ( ! $product ) {
            return '';
        }

        $base_product = self::get_base_by_wc_id( $product->get_id() );

        if ( ! $base_product ) {
            return '';
        }

        // Get pack tiers for this product
        $tiers = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pls_pack_tier WHERE base_product_id = %d AND is_enabled = 1 ORDER BY units ASC",
            $base_product->id
        ) );

        if ( empty( $tiers ) ) {
            return '';
        }

        $tier_labels = array();
        foreach ( $tiers as $tier ) {
            $tier_labels[] = $tier->units . ' units';
        }

        return implode( ', ', $tier_labels );
    }

    /**
     * Get product ingredients for Yoast replacement.
     */
    public static function get_product_ingredients() {
        global $product;
        if ( ! $product ) {
            return '';
        }

        $base_product = self::get_base_by_wc_id( $product->get_id() );

        if ( ! $base_product ) {
            return '';
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        if ( ! $profile || empty( $profile->ingredients_list ) ) {
            return '';
        }

        $ingredient_ids = array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) );
        $ingredient_names = array();

        foreach ( $ingredient_ids as $term_id ) {
            $term = get_term( $term_id, 'pls_ingredient' );
            if ( $term && ! is_wp_error( $term ) ) {
                $ingredient_names[] = $term->name;
            }
        }

        return implode( ', ', $ingredient_names );
    }

    /**
     * Add product schema markup for Yoast.
     */
    public static function yoast_product_schema( $data, $presentation ) {
        global $product;
        if ( ! $product || ! is_product() ) {
            return $data;
        }

        $base_product = self::get_base_by_wc_id( $product->get_id() );

        if ( ! $base_product ) {
            return $data;
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        if ( ! $profile ) {
            return $data;
        }

        // Enhance schema with PLS-specific data
        if ( ! empty( $profile->ingredients_list ) ) {
            $ingredient_ids = array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) );
            $ingredients = array();
            foreach ( $ingredient_ids as $term_id ) {
                $term = get_term( $term_id, 'pls_ingredient' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $ingredients[] = $term->name;
                }
            }
            if ( ! empty( $ingredients ) ) {
                $data['additionalProperty'][] = array(
                    '@type' => 'PropertyValue',
                    'name' => 'Ingredients',
                    'value' => implode( ', ', $ingredients ),
                );
            }
        }

        return $data;
    }

    /**
     * Enhance Yoast breadcrumbs for PLS products.
     */
    public static function yoast_breadcrumbs( $links ) {
        if ( ! is_product() ) {
            return $links;
        }

        global $product;
        if ( ! $product ) {
            return $links;
        }

        $base_product = self::get_base_by_wc_id( $product->get_id() );

        if ( ! $base_product || ! $base_product->category_path ) {
            return $links;
        }

        // Add category path to breadcrumbs
        $categories = explode( ' > ', $base_product->category_path );
        $category_links = array();
        foreach ( $categories as $index => $cat_name ) {
            $term = get_term_by( 'name', $cat_name, 'product_cat' );
            if ( $term ) {
                $category_links[] = array(
                    'text' => $cat_name,
                    'url' => get_term_link( $term ),
                );
            }
        }

        // Insert category links before product link
        if ( ! empty( $category_links ) ) {
            $product_link = array_pop( $links );
            $links = array_merge( $links, $category_links, array( $product_link ) );
        }

        return $links;
    }

    /**
     * Auto-generate product title for Yoast.
     */
    public static function auto_generate_product_title( $title ) {
        if ( ! is_product() ) {
            return $title;
        }

        global $product;
        if ( ! $product ) {
            return $title;
        }

        // Check if Yoast has custom title set
        $yoast_title = get_post_meta( $product->get_id(), '_yoast_wpseo_title', true );
        if ( ! empty( $yoast_title ) ) {
            return $title; // Use Yoast custom title if set
        }

        // Auto-generate from product data
        $base_product = self::get_base_by_wc_id( $product->get_id() );

        if ( ! $base_product ) {
            return $title;
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        $product_name = $product->get_name();
        $site_name = get_bloginfo( 'name' );

        // Generate title: Product Name | Pack Tiers Available | Site Name
        $tier_info = '';
        $tiers = $wpdb->get_results( $wpdb->prepare(
            "SELECT units FROM {$wpdb->prefix}pls_pack_tier WHERE base_product_id = %d AND is_enabled = 1 ORDER BY units ASC LIMIT 3",
            $base_product->id
        ) );
        if ( ! empty( $tiers ) ) {
            $tier_units = array();
            foreach ( $tiers as $tier ) {
                $tier_units[] = $tier->units;
            }
            $tier_info = ' | ' . implode( ', ', $tier_units ) . ' units';
        }

        return $product_name . $tier_info . ' | ' . $site_name;
    }

    /**
     * Auto-generate product description for Yoast.
     */
    public static function auto_generate_product_description( $description ) {
        if ( ! is_product() ) {
            return $description;
        }

        global $product;
        if ( ! $product ) {
            return $description;
        }

        // Check if Yoast has custom description set
        $yoast_desc = get_post_meta( $product->get_id(), '_yoast_wpseo_metadesc', true );
        if ( ! empty( $yoast_desc ) ) {
            return $description; // Use Yoast custom description if set
        }

        // Auto-generate from product data
        $base_product = self::get_base_by_wc_id( $product->get_id() );

        if ( ! $base_product ) {
            return $description;
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        if ( ! $profile ) {
            return $description;
        }

        // Use short description or long description
        $desc_text = '';
        if ( ! empty( $profile->short_description ) ) {
            $desc_text = wp_strip_all_tags( $profile->short_description );
        } elseif ( ! empty( $profile->long_description ) ) {
            $desc_text = wp_strip_all_tags( $profile->long_description );
        }

        // Limit to 155 characters for meta description
        if ( strlen( $desc_text ) > 155 ) {
            $desc_text = substr( $desc_text, 0, 152 ) . '...';
        }

        return ! empty( $desc_text ) ? $desc_text : $description;
    }

    /**
     * Output product schema markup (for non-Yoast sites).
     */
    public static function output_product_schema() {
        if ( ! is_product() ) {
            return;
        }

        global $product;
        if ( ! $product ) {
            return;
        }

        // Skip if Yoast is handling schema
        if ( defined( 'WPSEO_VERSION' ) ) {
            return;
        }

        $base_product = self::get_base_by_wc_id( $product->get_id() );

        if ( ! $base_product ) {
            return;
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        if ( ! $profile ) {
            return;
        }

        $schema = array(
            '@context' => 'https://schema.org',
            '@type' => 'Product',
            'name' => $product->get_name(),
            'description' => ! empty( $profile->short_description ) ? wp_strip_all_tags( $profile->short_description ) : $product->get_short_description(),
            'sku' => $product->get_sku(),
            'brand' => array(
                '@type' => 'Brand',
                'name' => get_bloginfo( 'name' ),
            ),
        );

        // Add offers (pack tiers)
        $tiers = $wpdb->get_results( $wpdb->prepare(
            "SELECT * FROM {$wpdb->prefix}pls_pack_tier WHERE base_product_id = %d AND is_enabled = 1",
            $base_product->id
        ) );

        if ( ! empty( $tiers ) ) {
            $offers = array();
            foreach ( $tiers as $tier ) {
                $variation = wc_get_product( $tier->wc_variation_id );
                if ( $variation ) {
                    $offers[] = array(
                        '@type' => 'Offer',
                        'name' => $tier->units . ' units',
                        'price' => $variation->get_price(),
                        'priceCurrency' => get_woocommerce_currency(),
                        'availability' => 'https://schema.org/InStock',
                        'url' => $product->get_permalink(),
                    );
                }
            }
            if ( ! empty( $offers ) ) {
                $schema['offers'] = $offers;
            }
        }

        // Add ingredients
        if ( ! empty( $profile->ingredients_list ) ) {
            $ingredient_ids = array_filter( array_map( 'absint', explode( ',', $profile->ingredients_list ) ) );
            $ingredients = array();
            foreach ( $ingredient_ids as $term_id ) {
                $term = get_term( $term_id, 'pls_ingredient' );
                if ( $term && ! is_wp_error( $term ) ) {
                    $ingredients[] = $term->name;
                }
            }
            if ( ! empty( $ingredients ) ) {
                $schema['additionalProperty'] = array(
                    array(
                        '@type' => 'PropertyValue',
                        'name' => 'Ingredients',
                        'value' => implode( ', ', $ingredients ),
                    ),
                );
            }
        }

        echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT ) . '</script>' . "\n";
    }

    /**
     * Output Open Graph tags for products.
     */
    public static function output_og_tags() {
        if ( ! is_product() ) {
            return;
        }

        global $product;
        if ( ! $product ) {
            return;
        }

        // Skip if Yoast is handling OG tags
        if ( defined( 'WPSEO_VERSION' ) ) {
            return;
        }

        $base_product = self::get_base_by_wc_id( $product->get_id() );

        if ( ! $base_product ) {
            return;
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product->id );
        $image_url = $product->get_image_id() ? wp_get_attachment_image_url( $product->get_image_id(), 'full' ) : '';

        echo '<meta property="og:type" content="product" />' . "\n";
        echo '<meta property="og:title" content="' . esc_attr( $product->get_name() ) . '" />' . "\n";
        if ( $profile && ! empty( $profile->short_description ) ) {
            echo '<meta property="og:description" content="' . esc_attr( wp_strip_all_tags( $profile->short_description ) ) . '" />' . "\n";
        }
        if ( $image_url ) {
            echo '<meta property="og:image" content="' . esc_url( $image_url ) . '" />' . "\n";
        }
        echo '<meta property="og:url" content="' . esc_url( $product->get_permalink() ) . '" />' . "\n";
        echo '<meta property="product:price:amount" content="' . esc_attr( $product->get_price() ) . '" />' . "\n";
        echo '<meta property="product:price:currency" content="' . esc_attr( get_woocommerce_currency() ) . '" />' . "\n";
    }

    /**
     * LiteSpeed Cache: Purge cache on product sync.
     */
    public static function litespeed_purge_on_sync( $post_id ) {
        if ( ! defined( 'LSCWP_V' ) ) {
            return;
        }

        // Check if it's a PLS product
        global $wpdb;
        $pls_product = $wpdb->get_var( $wpdb->prepare(
            "SELECT id FROM {$wpdb->prefix}pls_base_product WHERE wc_product_id = %d",
            $post_id
        ) );

        if ( $pls_product ) {
            // Purge product page cache
            do_action( 'litespeed_purge_single_post', $post_id );
            // Also purge category pages
            $terms = wp_get_post_terms( $post_id, 'product_cat' );
            foreach ( $terms as $term ) {
                do_action( 'litespeed_purge', 'product_cat_' . $term->term_id );
            }
        }
    }

    /**
     * LiteSpeed Cache: Enable ESI for product configurator.
     */
    public static function litespeed_esi_products( $enabled ) {
        if ( is_product() && has_shortcode( get_post()->post_content, 'pls_configurator' ) ) {
            return true; // Enable ESI for configurator
        }
        return $enabled;
    }

    /**
     * LiteSpeed Cache: Purge product cache on sync.
     */
    public static function litespeed_purge_product( $product_id ) {
        if ( ! defined( 'LSCWP_V' ) ) {
            return;
        }
        do_action( 'litespeed_purge_single_post', $product_id );
    }

    /**
     * Yoast OG title for products.
     */
    public static function yoast_og_title( $title ) {
        if ( is_product() ) {
            return self::auto_generate_product_title( $title );
        }
        return $title;
    }

    /**
     * Yoast OG description for products.
     */
    public static function yoast_og_description( $description ) {
        if ( is_product() ) {
            return self::auto_generate_product_description( $description );
        }
        return $description;
    }

    /**
     * Add SEO content to category pages.
     */
    public static function category_seo_content() {
        if ( ! is_product_category() ) {
            return;
        }

        $term = get_queried_object();
        if ( ! $term ) {
            return;
        }

        // Check if Yoast has custom description
        $yoast_desc = get_term_meta( $term->term_id, 'wpseo_desc', true );
        if ( empty( $yoast_desc ) && ! empty( $term->description ) ) {
            // Use term description as fallback
            echo '<div class="pls-category-seo-description" style="margin-bottom: 20px;">';
            echo wp_kses_post( wpautop( $term->description ) );
            echo '</div>';
        }
    }

    /**
     * Enhance category SEO meta for Yoast.
     */
    public static function category_seo_meta( $meta, $term ) {
        if ( ! isset( $term->taxonomy ) || 'product_cat' !== $term->taxonomy ) {
            return $meta;
        }

        // Auto-generate title if not set
        if ( empty( $meta['wpseo_title'] ) ) {
            $meta['wpseo_title'] = $term->name . ' | ' . get_bloginfo( 'name' );
        }

        // Auto-generate description if not set
        if ( empty( $meta['wpseo_desc'] ) && ! empty( $term->description ) ) {
            $desc = wp_strip_all_tags( $term->description );
            if ( strlen( $desc ) > 155 ) {
                $desc = substr( $desc, 0, 152 ) . '...';
            }
            $meta['wpseo_desc'] = $desc;
        }

        return $meta;
    }

    /**
     * SEO for custom order form page.
     */
    public static function custom_order_page_seo() {
        if ( ! is_page() ) {
            return;
        }

        global $post;
        if ( ! $post || ! has_shortcode( $post->post_content, 'pls_custom_order_form' ) ) {
            return;
        }

        // Check if Yoast has custom meta
        $yoast_title = get_post_meta( $post->ID, '_yoast_wpseo_title', true );
        $yoast_desc = get_post_meta( $post->ID, '_yoast_wpseo_metadesc', true );

        // Auto-generate if not set
        if ( empty( $yoast_title ) && defined( 'WPSEO_VERSION' ) ) {
            $title = __( 'Request Custom Product Quote', 'pls-private-label-store' ) . ' | ' . get_bloginfo( 'name' );
            echo '<meta property="og:title" content="' . esc_attr( $title ) . '" />' . "\n";
        }

        if ( empty( $yoast_desc ) && defined( 'WPSEO_VERSION' ) ) {
            $desc = __( 'Request a custom product quote for your private label needs. Tell us about your requirements and we\'ll get back to you.', 'pls-private-label-store' );
            echo '<meta property="og:description" content="' . esc_attr( $desc ) . '" />' . "\n";
        }
    }

    /**
     * Hook callback: Sync SEO meta when product is synced.
     */
    public static function on_product_synced( $base_product_id, $wc_product_id ) {
        self::sync_seo_meta_to_wc_product( $base_product_id, $wc_product_id );
    }

    /**
     * Sync SEO meta to WooCommerce product.
     */
    public static function sync_seo_meta_to_wc_product( $base_product_id, $wc_product_id ) {
        if ( ! defined( 'WPSEO_VERSION' ) ) {
            return; // Only sync if Yoast is active
        }

        $base_product = PLS_Repo_Base_Product::get( $base_product_id );
        if ( ! $base_product ) {
            return;
        }

        $profile = PLS_Repo_Product_Profile::get( $base_product_id );
        if ( ! $profile ) {
            return;
        }

        $product = wc_get_product( $wc_product_id );
        if ( ! $product ) {
            return;
        }

        // Only auto-populate if Yoast meta is not manually set
        $yoast_title = get_post_meta( $wc_product_id, '_yoast_wpseo_title', true );
        $yoast_desc = get_post_meta( $wc_product_id, '_yoast_wpseo_metadesc', true );

        // Auto-generate title if not set (generate directly, not via filter)
        if ( empty( $yoast_title ) ) {
            $product_name = $product->get_name();
            $site_name = get_bloginfo( 'name' );
            
            // Get tier info
            global $wpdb;
            $tiers = $wpdb->get_results( $wpdb->prepare(
                "SELECT units FROM {$wpdb->prefix}pls_pack_tier WHERE base_product_id = %d AND is_enabled = 1 ORDER BY units ASC LIMIT 3",
                $base_product_id
            ) );
            
            $tier_info = '';
            if ( ! empty( $tiers ) ) {
                $tier_units = array();
                foreach ( $tiers as $tier ) {
                    $tier_units[] = $tier->units;
                }
                $tier_info = ' | ' . implode( ', ', $tier_units ) . ' units';
            }
            
            $title = $product_name . $tier_info . ' | ' . $site_name;
            if ( ! empty( $title ) ) {
                update_post_meta( $wc_product_id, '_yoast_wpseo_title', $title );
            }
        }

        // Auto-generate description if not set (generate directly, not via filter)
        if ( empty( $yoast_desc ) ) {
            $desc_text = '';
            if ( ! empty( $profile->short_description ) ) {
                $desc_text = wp_strip_all_tags( $profile->short_description );
            } elseif ( ! empty( $profile->long_description ) ) {
                $desc_text = wp_strip_all_tags( $profile->long_description );
            }
            
            // Limit to 155 characters for meta description
            if ( strlen( $desc_text ) > 155 ) {
                $desc_text = substr( $desc_text, 0, 152 ) . '...';
            }
            
            if ( ! empty( $desc_text ) ) {
                update_post_meta( $wc_product_id, '_yoast_wpseo_metadesc', $desc_text );
            }
        }
    }

    /**
     * Brevo: Send notification when custom order is created.
     */
    public static function brevo_send_custom_order_notification( $order_id ) {
        if ( ! function_exists( 'brevo_send_email' ) && ! class_exists( 'Brevo' ) ) {
            return;
        }

        $order = PLS_Repo_Custom_Order::get( $order_id );
        if ( ! $order ) {
            return;
        }

        // Get Brevo API key from settings (if available)
        $brevo_api_key = get_option( 'pls_brevo_api_key', '' );
        if ( empty( $brevo_api_key ) ) {
            return; // Brevo not configured
        }

        // Send notification email via Brevo
        $subject = sprintf( __( 'New Custom Order Request #%d', 'pls-private-label-store' ), $order_id );
        $message = sprintf(
            __( "New custom order request:\n\nName: %s\nEmail: %s\nPhone: %s\nCompany: %s\n\nView in admin: %s", 'pls-private-label-store' ),
            $order->contact_name,
            $order->contact_email,
            $order->contact_phone ?: __( 'Not provided', 'pls-private-label-store' ),
            $order->company_name ?: __( 'Not provided', 'pls-private-label-store' ),
            admin_url( 'admin.php?page=pls-custom-orders' )
        );

        // Use Brevo API if available
        if ( function_exists( 'brevo_send_email' ) ) {
            brevo_send_email( array(
                'to' => get_option( 'admin_email' ),
                'subject' => $subject,
                'html' => nl2br( esc_html( $message ) ),
            ) );
        }
    }
}
