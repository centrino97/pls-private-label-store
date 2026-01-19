<?php
/**
 * Repository for revenue snapshot data.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Revenue_Snapshot {

    /**
     * Generate revenue snapshot for a specific date.
     *
     * @param string $date Date (Y-m-d). Defaults to today.
     * @return int|false Snapshot ID or false on failure.
     */
    public static function generate_snapshot( $date = null ) {
        if ( ! $date ) {
            $date = current_time( 'Y-m-d' );
        }

        // Check if snapshot already exists
        $existing = self::get_by_date( $date );
        if ( $existing ) {
            return $existing->id;
        }

        // Calculate totals
        $total_revenue = self::calculate_revenue( $date );
        $total_commission = self::calculate_commission( $date );
        $total_marketing_cost = PLS_Repo_Marketing_Cost::get_total( $date, $date );
        $net_profit = $total_revenue - $total_commission - $total_marketing_cost;

        global $wpdb;
        $table = $wpdb->prefix . 'pls_revenue_snapshot';

        $result = $wpdb->insert(
            $table,
            array(
                'snapshot_date'      => $date,
                'total_revenue'      => $total_revenue,
                'total_commission'   => $total_commission,
                'total_marketing_cost' => $total_marketing_cost,
                'net_profit'         => $net_profit,
                'created_at'         => current_time( 'mysql' ),
            ),
            array( '%s', '%f', '%f', '%f', '%f', '%s' )
        );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Get snapshot by date.
     *
     * @param string $date Date (Y-m-d).
     * @return object|null
     */
    public static function get_by_date( $date ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_revenue_snapshot';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE snapshot_date = %s", $date ),
            OBJECT
        );
    }

    /**
     * Get snapshots by date range.
     *
     * @param string $from Start date (Y-m-d).
     * @param string $to   End date (Y-m-d).
     * @return array
     */
    public static function get_by_date_range( $from, $to ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_revenue_snapshot';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE snapshot_date >= %s AND snapshot_date <= %s ORDER BY snapshot_date ASC",
                $from,
                $to
            ),
            OBJECT
        );
    }

    /**
     * Calculate total revenue for a date.
     *
     * @param string $date Date (Y-m-d).
     * @return float
     */
    private static function calculate_revenue( $date ) {
        if ( ! class_exists( 'WooCommerce' ) ) {
            return 0.00;
        }

        // Get WooCommerce orders for the date
        $orders = wc_get_orders(
            array(
                'date_created' => $date,
                'status'       => array( 'wc-completed', 'wc-processing' ),
                'limit'        => -1,
            )
        );

        $total = 0.00;
        foreach ( $orders as $order ) {
            $total += floatval( $order->get_total() );
        }

        // Add custom orders marked as "done" for the date
        global $wpdb;
        $custom_order_table = $wpdb->prefix . 'pls_custom_order';
        $custom_total = $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(total_value) FROM {$custom_order_table} WHERE status = 'done' AND DATE(updated_at) = %s",
                $date
            )
        );

        return $total + floatval( $custom_total );
    }

    /**
     * Calculate total commission for a date.
     *
     * @param string $date Date (Y-m-d).
     * @return float
     */
    private static function calculate_commission( $date ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_order_commission';

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(commission_amount) FROM {$table} WHERE DATE(created_at) = %s",
                $date
            )
        );
    }

    /**
     * Delete snapshot by date.
     *
     * @param string $date Date (Y-m-d).
     * @return bool
     */
    public static function delete_by_date( $date ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_revenue_snapshot';

        return (bool) $wpdb->delete(
            $table,
            array( 'snapshot_date' => $date ),
            array( '%s' )
        );
    }
}
