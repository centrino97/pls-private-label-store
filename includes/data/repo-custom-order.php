<?php
/**
 * Repository for custom order data.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Custom_Order {

    /**
     * Get count of all custom orders.
     *
     * @return int
     */
    public static function count() {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Get all custom orders, optionally filtered by status.
     *
     * @param string|null $status Status filter.
     * @return array
     */
    public static function all( $status = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        $where = '';
        if ( $status ) {
            $where = $wpdb->prepare( ' WHERE status = %s', $status );
        }

        return $wpdb->get_results(
            "SELECT * FROM {$table} {$where} ORDER BY created_at DESC",
            OBJECT
        );
    }

    /**
     * Get a single custom order by ID.
     *
     * @param int $id Order ID.
     * @return object|null
     */
    public static function get( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ),
            OBJECT
        );
    }

    /**
     * Update custom order status.
     *
     * @param int    $id     Order ID.
     * @param string $status New status.
     * @return bool
     */
    public static function update_status( $id, $status ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        return (bool) $wpdb->update(
            $table,
            array( 'status' => $status ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Update custom order financial data.
     *
     * @param int     $id                      Order ID.
     * @param float   $production_cost          Production cost.
     * @param float   $total_value             Total value.
     * @param float   $nikola_commission_rate   Commission rate.
     * @param float   $nikola_commission_amount Commission amount.
     * @return bool
     */
    public static function update_financials( $id, $production_cost, $total_value, $nikola_commission_rate, $nikola_commission_amount ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        return (bool) $wpdb->update(
            $table,
            array(
                'production_cost'          => $production_cost,
                'total_value'              => $total_value,
                'nikola_commission_rate'  => $nikola_commission_rate,
                'nikola_commission_amount' => $nikola_commission_amount,
            ),
            array( 'id' => $id ),
            array( '%f', '%f', '%f', '%f' ),
            array( '%d' )
        );
    }

    /**
     * Mark custom order as invoiced.
     *
     * @param int $id Order ID.
     * @return bool
     */
    public static function mark_invoiced( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        return (bool) $wpdb->update(
            $table,
            array( 'invoiced_at' => current_time( 'mysql' ) ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Mark custom order as paid.
     *
     * @param int $id Order ID.
     * @return bool
     */
    public static function mark_paid( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        return (bool) $wpdb->update(
            $table,
            array( 'paid_at' => current_time( 'mysql' ) ),
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Get count by status.
     *
     * @param string $status Status.
     * @return int
     */
    public static function count_by_status( $status ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        return (int) $wpdb->get_var(
            $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = %s", $status )
        );
    }

    /**
     * Create a new custom order.
     *
     * @param array $data Order data.
     * @return int|false Order ID or false on failure.
     */
    public static function create( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        $defaults = array(
            'status'          => 'new_lead',
            'contact_name'    => '',
            'contact_email'   => '',
            'contact_phone'   => '',
            'company_name'    => '',
            'category_id'     => null,
            'message'         => '',
            'quantity_needed' => null,
            'budget'          => null,
            'timeline'        => '',
            'created_at'      => current_time( 'mysql' ),
            'updated_at'      => current_time( 'mysql' ),
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert(
            $table,
            array(
                'status'          => $data['status'],
                'contact_name'    => $data['contact_name'],
                'contact_email'   => $data['contact_email'],
                'contact_phone'   => $data['contact_phone'],
                'company_name'    => $data['company_name'],
                'category_id'     => $data['category_id'],
                'message'         => $data['message'],
                'quantity_needed' => $data['quantity_needed'],
                'budget'          => $data['budget'],
                'timeline'        => $data['timeline'],
                'created_at'      => $data['created_at'],
                'updated_at'      => $data['updated_at'],
            ),
            array( '%s', '%s', '%s', '%s', '%s', '%d', '%s', '%d', '%f', '%s', '%s', '%s' )
        );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update a custom order.
     *
     * @param int   $id   Order ID.
     * @param array $data Fields to update.
     * @return bool
     */
    public static function update( $id, $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_custom_order';

        // Define allowed fields and their format specifiers
        $allowed_fields = array(
            'status'                  => '%s',
            'contact_name'            => '%s',
            'contact_email'           => '%s',
            'contact_phone'           => '%s',
            'company_name'            => '%s',
            'category_id'             => '%d',
            'message'                 => '%s',
            'quantity_needed'         => '%d',
            'budget'                  => '%f',
            'timeline'                => '%s',
            'production_cost'         => '%f',
            'total_value'             => '%f',
            'nikola_commission_rate'  => '%f',
            'nikola_commission_amount' => '%f',
            'wc_order_id'             => '%d',
            'sample_status'           => '%s',
            'sample_cost'             => '%f',
            'sample_sent_date'         => '%s',
            'sample_tracking'          => '%s',
            'sample_feedback'          => '%s',
            'converted_at'             => '%s',
        );

        $update_data = array();
        $update_format = array();

        foreach ( $data as $field => $value ) {
            if ( isset( $allowed_fields[ $field ] ) ) {
                $update_data[ $field ] = $value;
                $update_format[] = $allowed_fields[ $field ];
            }
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        // Always update the updated_at timestamp
        $update_data['updated_at'] = current_time( 'mysql' );
        $update_format[] = '%s';

        return (bool) $wpdb->update(
            $table,
            $update_data,
            array( 'id' => $id ),
            $update_format,
            array( '%d' )
        );
    }

    /**
     * Create a WooCommerce order from a custom order.
     *
     * @param int   $custom_order_id Custom order ID.
     * @param array $args            Order creation arguments.
     * @return int|false WooCommerce order ID or false on failure.
     */
    public static function create_wc_order( $custom_order_id, $args = array() ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return false;
        }

        $custom_order = self::get( $custom_order_id );
        if ( ! $custom_order ) {
            return false;
        }

        $defaults = array(
            'status'          => 'pending',        // Admin chooses: pending, on-hold, draft
            'products'        => array(),          // Array of product_id => quantity
            'custom_lines'    => array(),          // Custom line items: array( array( 'name' => '...', 'amount' => 0.00 ) )
            'include_sampling' => true,            // Include sample cost as line item
            'notes'           => '',               // Order notes
        );
        $args = wp_parse_args( $args, $defaults );

        // Create WC Order
        $order = wc_create_order( array( 'status' => $args['status'] ) );
        if ( is_wp_error( $order ) ) {
            return false;
        }

        // Set customer info
        $order->set_billing_first_name( $custom_order->contact_name );
        $order->set_billing_email( $custom_order->contact_email );
        $order->set_billing_phone( $custom_order->contact_phone );
        $order->set_billing_company( $custom_order->company_name );

        // Set shipping info (same as billing for custom orders)
        $order->set_shipping_first_name( $custom_order->contact_name );
        $order->set_shipping_company( $custom_order->company_name );

        // Add products
        foreach ( $args['products'] as $product_id => $qty ) {
            $product = wc_get_product( $product_id );
            if ( $product ) {
                $order->add_product( $product, $qty );
            }
        }

        // Add custom line items
        foreach ( $args['custom_lines'] as $line ) {
            if ( ! isset( $line['name'] ) || ! isset( $line['amount'] ) ) {
                continue;
            }
            $item = new WC_Order_Item_Fee();
            $item->set_name( sanitize_text_field( $line['name'] ) );
            $item->set_amount( floatval( $line['amount'] ) );
            $item->set_total( floatval( $line['amount'] ) );
            $order->add_item( $item );
        }

        // Add sampling cost if applicable
        if ( $args['include_sampling'] && isset( $custom_order->sample_cost ) && $custom_order->sample_cost > 0 ) {
            $item = new WC_Order_Item_Fee();
            $item->set_name( 'Sample Cost' );
            $item->set_amount( floatval( $custom_order->sample_cost ) );
            $item->set_total( floatval( $custom_order->sample_cost ) );
            $order->add_item( $item );
        }

        // Add order note with custom order reference
        $note = sprintf(
            'Created from PLS Custom Order #%d - %s',
            $custom_order_id,
            $custom_order->contact_name
        );
        if ( ! empty( $args['notes'] ) ) {
            $note .= "\n\n" . sanitize_textarea_field( $args['notes'] );
        }
        $order->add_order_note( $note );

        // Link orders bidirectionally
        $order->update_meta_data( '_pls_custom_order_id', $custom_order_id );
        $order->calculate_totals();
        $order->save();

        // Update custom order with WC order ID
        self::update( $custom_order_id, array(
            'wc_order_id'  => $order->get_id(),
            'converted_at' => current_time( 'mysql' ),
        ) );

        return $order->get_id();
    }
}
