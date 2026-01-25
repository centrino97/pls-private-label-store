<?php
/**
 * Repository for commission data.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Commission {

    /**
     * Get count of all commission records.
     *
     * @return int
     */
    public static function count() {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';
        return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table}" );
    }

    /**
     * Get all commission records.
     *
     * @return array
     */
    public static function all() {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';
        return $wpdb->get_results( "SELECT * FROM {$table} ORDER BY created_at DESC", OBJECT );
    }

    /**
     * Get commissions for a WooCommerce order.
     *
     * @param int $wc_order_id WooCommerce order ID.
     * @return array
     */
    public static function get_by_order( $wc_order_id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        return $wpdb->get_results(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE wc_order_id = %d ORDER BY id ASC", $wc_order_id ),
            OBJECT
        );
    }

    /**
     * Create commission record.
     *
     * @param array $data Commission data.
     * @return int|false Commission ID or false on failure.
     */
    public static function create( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        $defaults = array(
            'wc_order_id'            => 0,
            'wc_order_item_id'       => null,
            'product_id'              => null,
            'tier_key'                => null,
            'bundle_key'              => null,
            'units'                   => 0,
            'commission_rate_per_unit' => 0.00,
            'commission_amount'      => 0.00,
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert(
            $table,
            $data,
            array( '%d', '%d', '%d', '%s', '%s', '%d', '%f', '%f' )
        );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Mark commission as invoiced.
     *
     * @param int $id Commission ID.
     * @return bool
     */
    public static function mark_invoiced( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        return (bool) $wpdb->update(
            $table,
            array(
                'invoiced_at' => current_time( 'mysql' ),
                'status' => 'invoiced',
            ),
            array( 'id' => $id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    /**
     * Mark commission as paid.
     *
     * @param int $id Commission ID.
     * @return bool
     */
    public static function mark_paid( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        return (bool) $wpdb->update(
            $table,
            array(
                'paid_at' => current_time( 'mysql' ),
                'status' => 'paid',
            ),
            array( 'id' => $id ),
            array( '%s', '%s' ),
            array( '%d' )
        );
    }

    /**
     * Update commission status.
     *
     * @param int    $id     Commission ID.
     * @param string $status Status: pending, invoiced, paid.
     * @return bool
     */
    public static function update_status( $id, $status ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        $data = array( 'status' => $status );
        if ( 'invoiced' === $status && ! $wpdb->get_var( $wpdb->prepare( "SELECT invoiced_at FROM {$table} WHERE id = %d", $id ) ) ) {
            $data['invoiced_at'] = current_time( 'mysql' );
        }
        if ( 'paid' === $status && ! $wpdb->get_var( $wpdb->prepare( "SELECT paid_at FROM {$table} WHERE id = %d", $id ) ) ) {
            $data['paid_at'] = current_time( 'mysql' );
        }

        return (bool) $wpdb->update(
            $table,
            $data,
            array( 'id' => $id ),
            array( '%s' ),
            array( '%d' )
        );
    }

    /**
     * Bulk update commission status.
     *
     * @param array  $ids    Commission IDs.
     * @param string $status Status: pending, invoiced, paid.
     * @return int Number of rows updated.
     */
    public static function bulk_update_status( $ids, $status ) {
        if ( empty( $ids ) ) {
            return 0;
        }

        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        $ids_placeholders = implode( ',', array_fill( 0, count( $ids ), '%d' ) );
        $ids_array = array_merge( array( $status ), $ids );

        $data = array( 'status' => $status );
        if ( 'invoiced' === $status ) {
            $data['invoiced_at'] = current_time( 'mysql' );
        }
        if ( 'paid' === $status ) {
            $data['paid_at'] = current_time( 'mysql' );
        }

        $set_clause = array();
        $set_values = array();
        foreach ( $data as $key => $value ) {
            $set_clause[] = "{$key} = %s";
            $set_values[] = $value;
        }
        $set_clause = implode( ', ', $set_clause );
        $set_values = array_merge( $set_values, $ids );

        $query = "UPDATE {$table} SET {$set_clause} WHERE id IN ({$ids_placeholders})";
        
        return $wpdb->query( $wpdb->prepare( $query, $set_values ) );
    }

    /**
     * Get all commissions with filters.
     *
     * @param array $args Query arguments.
     * @return array
     */
    public static function query( $args = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        $defaults = array(
            'date_from' => null,
            'date_to'   => null,
            'invoiced'  => null,
            'paid'      => null,
            'limit'     => 100,
            'offset'    => 0,
        );

        $args = wp_parse_args( $args, $defaults );

        $where = array( '1=1' );

        if ( $args['date_from'] ) {
            $where[] = $wpdb->prepare( 'created_at >= %s', $args['date_from'] );
        }

        if ( $args['date_to'] ) {
            $where[] = $wpdb->prepare( 'created_at <= %s', $args['date_to'] );
        }

        if ( null !== $args['invoiced'] ) {
            if ( $args['invoiced'] ) {
                $where[] = 'invoiced_at IS NOT NULL';
            } else {
                $where[] = 'invoiced_at IS NULL';
            }
        }

        if ( null !== $args['paid'] ) {
            if ( $args['paid'] ) {
                $where[] = 'paid_at IS NOT NULL';
            } else {
                $where[] = 'paid_at IS NULL';
            }
        }

        $where_clause = implode( ' AND ', $where );
        $limit_clause = $wpdb->prepare( 'LIMIT %d OFFSET %d', $args['limit'], $args['offset'] );

        return $wpdb->get_results(
            "SELECT * FROM {$table} WHERE {$where_clause} ORDER BY created_at DESC {$limit_clause}",
            OBJECT
        );
    }

    /**
     * Get total commission amount.
     *
     * @param array $args Query arguments.
     * @return float
     */
    public static function get_total( $args = array() ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        $defaults = array(
            'date_from' => null,
            'date_to'   => null,
            'invoiced'  => null,
            'paid'      => null,
        );

        $args = wp_parse_args( $args, $defaults );

        $where = array( '1=1' );

        if ( $args['date_from'] ) {
            $where[] = $wpdb->prepare( 'created_at >= %s', $args['date_from'] );
        }

        if ( $args['date_to'] ) {
            $where[] = $wpdb->prepare( 'created_at <= %s', $args['date_to'] );
        }

        if ( null !== $args['invoiced'] ) {
            if ( $args['invoiced'] ) {
                $where[] = 'invoiced_at IS NOT NULL';
            } else {
                $where[] = 'invoiced_at IS NULL';
            }
        }

        if ( null !== $args['paid'] ) {
            if ( $args['paid'] ) {
                $where[] = 'paid_at IS NOT NULL';
            } else {
                $where[] = 'paid_at IS NULL';
            }
        }

        $where_clause = implode( ' AND ', $where );

        return (float) $wpdb->get_var( "SELECT SUM(commission_amount) FROM {$table} WHERE {$where_clause}" );
    }
}
