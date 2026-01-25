<?php
/**
 * Preview Renderer - Generates HTML preview for unsaved product data
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Preview_Renderer {

    /**
     * Render preview HTML for unsaved product data.
     *
     * @param array $payload Sanitized product payload from form.
     */
    public static function render( $payload ) {
        // Validate minimum required data
        if ( empty( $payload['name'] ) ) {
            echo '<div class="pls-preview-error">' . esc_html__( 'Product name is required for preview.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        // Check if product is already synced - if so, use actual WooCommerce product
        $wc_product_id = null;
        if ( ! empty( $payload['id'] ) ) {
            $base_product = PLS_Repo_Base_Product::get( $payload['id'] );
            if ( $base_product && $base_product->wc_product_id ) {
                $wc_product_id = $base_product->wc_product_id;
            }
        }

        // If synced, render using actual shortcode
        if ( $wc_product_id ) {
            self::render_live_product_preview( $wc_product_id );
            return;
        }

        // Create mock product object for unsaved products
        $mock_product = self::create_mock_product( $payload );

        // Set up global $product for widgets
        global $product, $wp_query;
        $product = $mock_product;

        // Simulate is_product() for widgets
        $original_query = $wp_query;
        $wp_query->is_product = true;
        $wp_query->is_singular = true;

        // Get CSS/JS URLs
        $offers_css_url = PLS_PLS_URL . 'assets/css/offers.css?ver=' . PLS_PLS_VERSION;
        $offers_js_url  = PLS_PLS_URL . 'assets/js/offers.js?ver=' . PLS_PLS_VERSION;
        $ajax_url       = admin_url( 'admin-ajax.php' );
        $nonce          = wp_create_nonce( 'pls_offers' );

        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( sprintf( __( 'Preview: %s', 'pls-private-label-store' ), $payload['name'] ) ); ?></title>
            <link rel="stylesheet" href="<?php echo esc_url( $offers_css_url ); ?>" />
            <style>
                body {
                    margin: 0;
                    padding: 20px;
                    background: #f0f0f1;
                    font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Oxygen-Sans, Ubuntu, Cantarell, "Helvetica Neue", sans-serif;
                }
                .pls-preview-content {
                    background: #fff;
                    padding: 40px;
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    max-width: 1200px;
                    margin: 0 auto;
                }
                .pls-preview-note {
                    background: #fff3cd;
                    border-left: 4px solid #ffb900;
                    padding: 12px 16px;
                    margin-bottom: 30px;
                    border-radius: 4px;
                }
                .pls-preview-note strong {
                    display: block;
                    margin-bottom: 8px;
                }
                .pls-widget-section {
                    margin-bottom: 40px;
                    padding: 20px;
                    background: #f9f9f9;
                    border-radius: 4px;
                }
                .pls-widget-section h2 {
                    margin-top: 0;
                    font-size: 20px;
                    color: #2271b1;
                }
                .pls-note {
                    padding: 12px;
                    background: #f0f0f1;
                    border-left: 4px solid #dba617;
                    margin: 10px 0;
                    border-radius: 4px;
                }
                .pls-preview-error {
                    padding: 20px;
                    background: #fef2f2;
                    border-left: 4px solid #ef4444;
                    color: #991b1b;
                    border-radius: 4px;
                }
            </style>
        </head>
        <body>
            <div class="pls-preview-content">
                <div class="pls-preview-note">
                    <strong><?php esc_html_e( '📋 Live Preview', 'pls-private-label-store' ); ?></strong>
                    <p style="margin: 0;">
                        <?php esc_html_e( 'This preview shows how your product will look on the frontend using the [pls_product_page] shortcode. Changes update automatically as you edit the form.', 'pls-private-label-store' ); ?>
                    </p>
                </div>

                <!-- Render using PLS Product Page Shortcode -->
                <?php
                // Use shortcode to render actual frontend output
                if ( $mock_product && $mock_product->get_id() ) {
                    echo do_shortcode( '[pls_product_page product_id="' . $mock_product->get_id() . '"]' );
                } else {
                    // Fallback: Show basic preview
                ?>
                <!-- Product Title -->
                <div class="pls-widget-section">
                    <h2><?php esc_html_e( 'Product Title', 'pls-private-label-store' ); ?></h2>
                    <h1><?php echo esc_html( $payload['name'] ); ?></h1>
                </div>

                <!-- Product Images -->
                <?php if ( ! empty( $payload['featured_image_id'] ) || ! empty( $payload['gallery_ids'] ) ) : ?>
                <div class="pls-widget-section">
                    <h2><?php esc_html_e( 'Product Images', 'pls-private-label-store' ); ?></h2>
                    <div style="display: flex; gap: 10px; flex-wrap: wrap;">
                        <?php
                        if ( ! empty( $payload['featured_image_id'] ) ) {
                            $featured_url = wp_get_attachment_image_url( $payload['featured_image_id'], 'medium' );
                            if ( $featured_url ) {
                                echo '<img src="' . esc_url( $featured_url ) . '" style="max-width: 300px; height: auto; border-radius: 8px;" alt="' . esc_attr( $payload['name'] ) . '" />';
                            }
                        }
                        if ( ! empty( $payload['gallery_ids'] ) ) {
                            foreach ( $payload['gallery_ids'] as $gallery_id ) {
                                $gallery_url = wp_get_attachment_image_url( $gallery_id, 'medium' );
                                if ( $gallery_url ) {
                                    echo '<img src="' . esc_url( $gallery_url ) . '" style="max-width: 300px; height: auto; border-radius: 8px;" alt="' . esc_attr( $payload['name'] ) . '" />';
                                }
                            }
                        }
                        ?>
                    </div>
                </div>
                <?php endif; ?>

                <!-- Product Description -->
                <?php if ( ! empty( $payload['short_description'] ) || ! empty( $payload['long_description'] ) ) : ?>
                <div class="pls-widget-section">
                    <h2><?php esc_html_e( 'Product Description', 'pls-private-label-store' ); ?></h2>
                    <?php if ( ! empty( $payload['short_description'] ) ) : ?>
                        <p style="font-size: 18px; color: #374151; margin-bottom: 16px;"><?php echo esc_html( $payload['short_description'] ); ?></p>
                    <?php endif; ?>
                    <?php if ( ! empty( $payload['long_description'] ) ) : ?>
                        <div><?php echo wp_kses_post( $payload['long_description'] ); ?></div>
                    <?php endif; ?>
                </div>
                <?php endif; ?>

                <!-- PLS Configurator Widget Preview -->
                <?php if ( ! empty( $payload['pack_tiers'] ) ) : ?>
                <div class="pls-widget-section">
                    <h2><?php esc_html_e( 'PLS Configurator Widget', 'pls-private-label-store' ); ?></h2>
                    <p class="description"><?php esc_html_e( 'This is how the PLS Configurator widget will render:', 'pls-private-label-store' ); ?></p>
                    <?php self::render_configurator( $payload ); ?>
                </div>
                <?php else : ?>
                <div class="pls-widget-section">
                    <div class="pls-note"><?php esc_html_e( 'Add pack tiers to see the configurator preview.', 'pls-private-label-store' ); ?></div>
                </div>
                <?php endif; ?>

                <!-- Product Options Preview -->
                <?php if ( ! empty( $payload['attributes'] ) ) : ?>
                <div class="pls-widget-section">
                    <h2><?php esc_html_e( 'Product Options', 'pls-private-label-store' ); ?></h2>
                    <ul>
                        <?php foreach ( $payload['attributes'] as $attr ) : ?>
                            <?php if ( ! empty( $attr['attribute_label'] ) && ! empty( $attr['values'] ) ) : ?>
                                <li>
                                    <strong><?php echo esc_html( $attr['attribute_label'] ); ?>:</strong>
                                    <?php
                                    $value_labels = array();
                                    foreach ( $attr['values'] as $value ) {
                                        if ( ! empty( $value['value_label'] ) ) {
                                            $value_labels[] = esc_html( $value['value_label'] );
                                        }
                                    }
                                    echo esc_html( implode( ', ', $value_labels ) );
                                    ?>
                                </li>
                            <?php endif; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
                <?php endif; ?>
                <?php } // End fallback ?>
            </div>

            <script src="<?php echo esc_url( includes_url( 'js/jquery/jquery.min.js' ) ); ?>"></script>
            <script>
                window.PLS_Offers = {
                    ajax_url: <?php echo wp_json_encode( $ajax_url ); ?>,
                    nonce: <?php echo wp_json_encode( $nonce ); ?>
                };
            </script>
            <script src="<?php echo esc_url( $offers_js_url ); ?>"></script>
        </body>
        </html>
        <?php

        // Restore original query
        $wp_query = $original_query;
    }

    /**
     * Render live product preview using actual WooCommerce product.
     */
    private static function render_live_product_preview( $wc_product_id ) {
        $product = wc_get_product( $wc_product_id );
        if ( ! $product ) {
            echo '<div class="pls-preview-error">' . esc_html__( 'Product not found.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        // Set up global $product
        global $wp_query;
        $wp_query->is_product = true;
        $wp_query->is_singular = true;

        // Get CSS/JS URLs
        $offers_css_url = PLS_PLS_URL . 'assets/css/offers.css?ver=' . PLS_PLS_VERSION;
        $offers_js_url  = PLS_PLS_URL . 'assets/js/offers.js?ver=' . PLS_PLS_VERSION;
        ?>
        <!DOCTYPE html>
        <html <?php language_attributes(); ?>>
        <head>
            <meta charset="<?php bloginfo( 'charset' ); ?>">
            <meta name="viewport" content="width=device-width, initial-scale=1">
            <title><?php echo esc_html( sprintf( __( 'Preview: %s', 'pls-private-label-store' ), $product->get_name() ) ); ?></title>
            <?php wp_head(); ?>
            <link rel="stylesheet" href="<?php echo esc_url( $offers_css_url ); ?>" />
            <style>
                body {
                    margin: 0;
                    padding: 20px;
                    background: #f0f0f1;
                }
                .pls-preview-content {
                    background: #fff;
                    padding: 40px;
                    border-radius: 4px;
                    box-shadow: 0 1px 3px rgba(0,0,0,0.1);
                    max-width: 1200px;
                    margin: 0 auto;
                }
            </style>
        </head>
        <body>
            <div class="pls-preview-content">
                <?php
                // Render using actual shortcode
                echo do_shortcode( '[pls_product_page product_id="' . $wc_product_id . '"]' );
                ?>
            </div>
            <script src="<?php echo esc_url( includes_url( 'js/jquery/jquery.min.js' ) ); ?>"></script>
            <script src="<?php echo esc_url( $offers_js_url ); ?>"></script>
            <?php wp_footer(); ?>
        </body>
        </html>
        <?php
    }

    /**
     * Create a mock WooCommerce product object for preview.
     *
     * @param array $payload Product payload.
     * @return object Mock product object.
     */
    private static function create_mock_product( $payload ) {
        // Create a simple mock object that mimics WC_Product_Variable
        $mock = new stdClass();
        $mock->id = 0;
        $mock->name = isset( $payload['name'] ) ? $payload['name'] : '';
        $mock->description = isset( $payload['long_description'] ) ? $payload['long_description'] : '';
        $mock->short_description = isset( $payload['short_description'] ) ? $payload['short_description'] : '';
        $mock->is_type = function( $type ) {
            return 'variable' === $type;
        };
        $mock->get_id = function() use ( $mock ) {
            return $mock->id;
        };
        $mock->get_name = function() use ( $mock ) {
            return $mock->name;
        };
        $mock->get_description = function() use ( $mock ) {
            return $mock->description;
        };
        $mock->get_short_description = function() use ( $mock ) {
            return $mock->short_description;
        };
        $mock->get_variation_attributes = function() use ( $payload ) {
            $attrs = array();
            if ( ! empty( $payload['pack_tiers'] ) ) {
                $tier_slugs = array();
                foreach ( $payload['pack_tiers'] as $tier ) {
                    if ( ! empty( $tier['enabled'] ) ) {
                        $units = isset( $tier['units'] ) ? $tier['units'] : 0;
                        $tier_slugs[] = sanitize_title( $units . '-units' );
                    }
                }
                if ( ! empty( $tier_slugs ) ) {
                    $attrs['pa_pack-tier'] = $tier_slugs;
                }
            }
            return $attrs;
        };

        return $mock;
    }

    /**
     * Render configurator widget preview.
     *
     * @param array $payload Product payload.
     */
    private static function render_configurator( $payload ) {
        if ( empty( $payload['pack_tiers'] ) ) {
            echo '<div class="pls-note">' . esc_html__( 'No pack tiers configured.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        $enabled_tiers = array();
        foreach ( $payload['pack_tiers'] as $tier ) {
            if ( ! empty( $tier['enabled'] ) && ! empty( $tier['units'] ) ) {
                $enabled_tiers[] = array(
                    'units' => $tier['units'],
                    'price' => isset( $tier['price'] ) ? floatval( $tier['price'] ) : 0,
                );
            }
        }

        if ( empty( $enabled_tiers ) ) {
            echo '<div class="pls-note">' . esc_html__( 'Enable at least one pack tier to see the configurator.', 'pls-private-label-store' ) . '</div>';
            return;
        }

        // Sort tiers by units (ascending) to show hierarchy
        usort( $enabled_tiers, function( $a, $b ) {
            return $a['units'] - $b['units'];
        } );

        ?>
        <div class="pls-configurator" data-product-id="0">
            <div class="pls-configurator__title"><?php echo esc_html__( 'Configure your pack', 'pls-private-label-store' ); ?></div>

            <div class="pls-configurator__block" style="border-left: 4px solid #2271b1; padding-left: 16px; background: #f0f7ff;">
                <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                    <span style="background: #2271b1; color: #fff; padding: 2px 6px; border-radius: 2px; font-size: 9px; font-weight: 600; text-transform: uppercase;">PRIMARY</span>
                    <strong><?php echo esc_html__( 'Pack tiers', 'pls-private-label-store' ); ?></strong>
                </div>
                <div class="pls-chips">
                    <?php foreach ( $enabled_tiers as $tier ) : ?>
                        <?php
                        $units = $tier['units'];
                        $price = $tier['price'];
                        $total = $units * $price;
                        $tier_level = self::get_tier_level_from_units( $units );
                        $label = sprintf( '%d units - $%s', $units, number_format( $total, 2 ) );
                        $slug  = sanitize_title( $units . '-units' );
                        ?>
                        <button type="button" class="pls-chip pls-tier-button" data-term="<?php echo esc_attr( $slug ); ?>" data-tier-level="<?php echo esc_attr( $tier_level ); ?>">
                            <?php echo esc_html( $label ); ?>
                            <span style="margin-left: 6px; font-size: 11px; opacity: 0.7;">(Tier <?php echo esc_html( $tier_level ); ?>)</span>
                        </button>
                    <?php endforeach; ?>
                </div>
                <p class="pls-muted"><?php echo esc_html__( 'Pack Tier is PRIMARY - selecting a tier determines which product options are available.', 'pls-private-label-store' ); ?></p>
            </div>

            <?php
            // Show product options if available (SECONDARY options)
            if ( ! empty( $payload['attributes'] ) ) {
                $selected_tier_level = self::get_selected_tier_level( $enabled_tiers );
                ?>
                <div class="pls-configurator__block" style="margin-top: 20px; border-left: 4px solid #64748b; padding-left: 16px;">
                    <div style="display: flex; align-items: center; gap: 8px; margin-bottom: 12px;">
                        <span style="background: #64748b; color: #fff; padding: 2px 6px; border-radius: 2px; font-size: 9px; font-weight: 600; text-transform: uppercase;">PRODUCT OPTIONS</span>
                        <strong><?php echo esc_html__( 'Product Options', 'pls-private-label-store' ); ?></strong>
                        <span style="font-size: 12px; color: #64748b;">(Available for Tier <?php echo esc_html( $selected_tier_level ); ?>+)</span>
                    </div>
                    <?php
                    $has_options = false;
                    foreach ( $payload['attributes'] as $attr ) {
                        if ( empty( $attr['attribute_label'] ) || empty( $attr['values'] ) ) {
                            continue;
                        }
                        
                        // Filter values by tier level
                        $available_values = array();
                        foreach ( $attr['values'] as $value ) {
                            if ( empty( $value['value_label'] ) ) {
                                continue;
                            }
                            $min_tier = isset( $value['min_tier_level'] ) ? absint( $value['min_tier_level'] ) : 1;
                            if ( $min_tier <= $selected_tier_level ) {
                                $available_values[] = $value;
                            }
                        }
                        
                        if ( empty( $available_values ) ) {
                            continue;
                        }
                        
                        $has_options = true;
                        ?>
                        <div style="margin-top: 12px;">
                            <strong><?php echo esc_html( $attr['attribute_label'] ); ?>:</strong>
                            <div class="pls-chips" style="margin-top: 8px;">
                                <?php
                                foreach ( $available_values as $value ) {
                                    $min_tier = isset( $value['min_tier_level'] ) ? absint( $value['min_tier_level'] ) : 1;
                                    $tier_prices = isset( $value['tier_price_overrides'] ) && is_array( $value['tier_price_overrides'] ) ? $value['tier_price_overrides'] : null;
                                    $default_price = isset( $value['price'] ) ? floatval( $value['price'] ) : 0;
                                    
                                    // Calculate price for current tier
                                    $display_price = 0;
                                    if ( $tier_prices && isset( $tier_prices[ $selected_tier_level ] ) ) {
                                        $display_price = floatval( $tier_prices[ $selected_tier_level ] );
                                    } elseif ( $default_price > 0 ) {
                                        $display_price = $default_price;
                                    }
                                    ?>
                                    <button type="button" class="pls-chip">
                                        <?php echo esc_html( $value['value_label'] ); ?>
                                        <?php if ( $min_tier > 1 ) : ?>
                                            <span style="margin-left: 6px; font-size: 10px; color: #6366f1;">(Tier <?php echo esc_html( $min_tier ); ?>+)</span>
                                        <?php endif; ?>
                                        <?php if ( $display_price > 0 ) : ?>
                                            <span style="margin-left: 6px; color: #059669;">+$<?php echo esc_html( number_format( $display_price, 2 ) ); ?></span>
                                            <?php if ( $tier_prices ) : ?>
                                                <span style="margin-left: 4px; font-size: 10px; color: #64748b;">(Tier <?php echo esc_html( $selected_tier_level ); ?>)</span>
                                            <?php endif; ?>
                                        <?php endif; ?>
                                    </button>
                                    <?php
                                }
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    
                    if ( ! $has_options ) {
                        echo '<p class="pls-muted">' . esc_html__( 'No product options available for the selected tier level.', 'pls-private-label-store' ) . '</p>';
                    }
                    ?>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
    }

    /**
     * Get tier level from enabled tiers (assumes first enabled tier is selected).
     *
     * @param array $enabled_tiers Enabled pack tiers.
     * @return int Tier level (1-5).
     */
    private static function get_selected_tier_level( $enabled_tiers ) {
        if ( empty( $enabled_tiers ) ) {
            return 1;
        }

        $first_tier_units = $enabled_tiers[0]['units'];
        return self::get_tier_level_from_units( $first_tier_units );
    }

    /**
     * Get tier level from units count.
     *
     * @param int $units Units count.
     * @return int Tier level (1-5).
     */
    private static function get_tier_level_from_units( $units ) {
        if ( $units <= 50 ) {
            return 1;
        } elseif ( $units <= 100 ) {
            return 2;
        } elseif ( $units <= 250 ) {
            return 3;
        } elseif ( $units <= 500 ) {
            return 4;
        } else {
            return 5;
        }
    }
}
