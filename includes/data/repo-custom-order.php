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
}
