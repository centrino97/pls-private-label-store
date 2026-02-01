<?php
/**
 * Cleanup functions for WooCommerce attribute sync issues.
 * Removes incorrectly synced ingredient attributes from WooCommerce.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_WC_Sync_Cleanup {

    /**
     * Clean up incorrectly synced ingredient attributes from WooCommerce.
     * This removes WooCommerce attributes that were created from ingredients
     * (which should only exist as taxonomy terms, not WooCommerce attributes).
     *
     * @return array Results with counts of deleted attributes.
     */
    public static function cleanup_ingredient_attributes() {
        if ( ! function_exists( 'wc_get_attribute_taxonomies' ) || ! function_exists( 'wc_delete_attribute' ) ) {
            return array(
                'success' => false,
                'message' => 'WooCommerce attribute functions not available.',
                'deleted_count' => 0,
            );
        }

        global $wpdb;
        $deleted_count = 0;
        $deleted_attributes = array();
        $errors = array();

        // Get all PLS attributes that are ingredients
        $pls_ingredient_attrs = $wpdb->get_results(
            "SELECT id, attr_key, label FROM {$wpdb->prefix}pls_attribute WHERE option_type = 'ingredient' OR attr_key LIKE 'ingredient-%'"
        );

        foreach ( $pls_ingredient_attrs as $pls_attr ) {
            // Find corresponding WooCommerce attribute
            $slug = sanitize_title( $pls_attr->attr_key );
            $taxonomies = wc_get_attribute_taxonomies();
            
            foreach ( $taxonomies as $tax ) {
                if ( $slug === $tax->attribute_name ) {
                    // Get taxonomy name
                    $taxonomy = wc_attribute_taxonomy_name( $tax->attribute_name );
                    
                    // Delete all terms in this taxonomy first
                    $terms = get_terms( array(
                        'taxonomy' => $taxonomy,
                        'hide_empty' => false,
                    ) );
                    
                    if ( ! is_wp_error( $terms ) && ! empty( $terms ) ) {
                        foreach ( $terms as $term ) {
                            wp_delete_term( $term->term_id, $taxonomy );
                        }
                    }
                    
                    // Delete WooCommerce attribute from database
                    // WooCommerce stores attributes in wp_woocommerce_attribute_taxonomies table
                    global $wpdb;
                    $deleted = $wpdb->delete(
                        $wpdb->prefix . 'woocommerce_attribute_taxonomies',
                        array( 'attribute_id' => $tax->attribute_id ),
                        array( '%d' )
                    );
                    
                    if ( $deleted !== false ) {
                        $deleted_count++;
                        $deleted_attributes[] = array(
                            'pls_id' => $pls_attr->id,
                            'wc_id' => $tax->attribute_id,
                            'name' => $tax->attribute_name,
                            'label' => $pls_attr->label,
                        );
                        
                        // Clear WooCommerce attribute taxonomies transient
                        delete_transient( 'wc_attribute_taxonomies' );
                        
                        // Unregister the taxonomy
                        if ( taxonomy_exists( $taxonomy ) ) {
                            unregister_taxonomy( $taxonomy );
                        }
                    } else {
                        $errors[] = "Failed to delete WooCommerce attribute: {$tax->attribute_name}";
                    }
                    break;
                }
            }
        }

        return array(
            'success' => true,
            'message' => sprintf( 'Cleaned up %d incorrectly synced ingredient attributes from WooCommerce.', $deleted_count ),
            'deleted_count' => $deleted_count,
            'deleted_attributes' => $deleted_attributes,
            'errors' => $errors,
        );
    }

    /**
     * Re-sync all ingredients with correct tier levels.
     * This ensures ingredients are properly synced to PLS attributes
     * (but NOT to WooCommerce attributes).
     *
     * @return array Results with sync counts.
     */
    public static function resync_ingredients() {
        require_once PLS_PLS_DIR . 'includes/core/class-pls-ingredient-sync.php';

        $ingredients = get_terms(
            array(
                'taxonomy'   => 'pls_ingredient',
                'hide_empty' => false,
            )
        );

        if ( is_wp_error( $ingredients ) || empty( $ingredients ) ) {
            return array(
                'success' => false,
                'message' => 'No ingredients found to sync.',
                'synced_count' => 0,
            );
        }

        $synced_count = 0;
        $errors = array();

        foreach ( $ingredients as $ingredient ) {
            $result = PLS_Ingredient_Sync::sync_ingredient_to_attribute( $ingredient->term_id );
            if ( $result ) {
                $synced_count++;
            } else {
                $errors[] = "Failed to sync ingredient: {$ingredient->name} (ID: {$ingredient->term_id})";
            }
        }

        return array(
            'success' => true,
            'message' => sprintf( 'Re-synced %d ingredients to PLS attributes with correct tier levels.', $synced_count ),
            'synced_count' => $synced_count,
            'total_count' => count( $ingredients ),
            'errors' => $errors,
        );
    }

    /**
     * Full cleanup and re-sync process.
     * 1. Clean up incorrectly synced WooCommerce attributes
     * 2. Re-sync ingredients with correct tier levels
     *
     * @return array Combined results.
     */
    public static function full_cleanup_and_resync() {
        $cleanup_result = self::cleanup_ingredient_attributes();
        $resync_result = self::resync_ingredients();

        return array(
            'cleanup' => $cleanup_result,
            'resync' => $resync_result,
            'success' => $cleanup_result['success'] && $resync_result['success'],
            'message' => sprintf(
                'Cleanup: %s | Re-sync: %s',
                $cleanup_result['message'],
                $resync_result['message']
            ),
        );
    }
}
