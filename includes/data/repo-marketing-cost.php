<?php
/**
 * Repository for marketing cost data.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Marketing_Cost {

    /**
     * Create marketing cost entry.
     *
     * @param array $data Marketing cost data.
     * @return int|false Marketing cost ID or false on failure.
     */
    public static function create( $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_marketing_cost';

        $defaults = array(
            'cost_date'   => current_time( 'Y-m-d' ),
            'channel'    => '',
            'amount'     => 0.00,
            'description' => '',
            'created_by' => get_current_user_id(),
        );

        $data = wp_parse_args( $data, $defaults );

        $result = $wpdb->insert(
            $table,
            array(
                'cost_date'   => $data['cost_date'],
                'channel'     => sanitize_text_field( $data['channel'] ),
                'amount'      => floatval( $data['amount'] ),
                'description' => sanitize_textarea_field( $data['description'] ),
                'created_at'  => current_time( 'mysql' ),
                'created_by'  => absint( $data['created_by'] ),
            ),
            array( '%s', '%s', '%f', '%s', '%s', '%d' )
        );

        if ( false === $result ) {
            return false;
        }

        return $wpdb->insert_id;
    }

    /**
     * Update marketing cost entry.
     *
     * @param int   $id   Marketing cost ID.
     * @param array $data Marketing cost data.
     * @return bool
     */
    public static function update( $id, $data ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_marketing_cost';

        $update_data = array();
        $format = array();

        if ( isset( $data['cost_date'] ) ) {
            $update_data['cost_date'] = sanitize_text_field( $data['cost_date'] );
            $format[] = '%s';
        }

        if ( isset( $data['channel'] ) ) {
            $update_data['channel'] = sanitize_text_field( $data['channel'] );
            $format[] = '%s';
        }

        if ( isset( $data['amount'] ) ) {
            $update_data['amount'] = floatval( $data['amount'] );
            $format[] = '%f';
        }

        if ( isset( $data['description'] ) ) {
            $update_data['description'] = sanitize_textarea_field( $data['description'] );
            $format[] = '%s';
        }

        if ( empty( $update_data ) ) {
            return false;
        }

        return (bool) $wpdb->update(
            $table,
            $update_data,
            array( 'id' => absint( $id ) ),
            $format,
            array( '%d' )
        );
    }

    /**
     * Delete marketing cost entry.
     *
     * @param int $id Marketing cost ID.
     * @return bool
     */
    public static function delete( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_marketing_cost';

        return (bool) $wpdb->delete(
            $table,
            array( 'id' => absint( $id ) ),
            array( '%d' )
        );
    }

    /**
     * Get marketing costs by date range.
     *
     * @param string $from Start date (Y-m-d).
     * @param string $to   End date (Y-m-d).
     * @return array
     */
    public static function get_by_date_range( $from, $to ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_marketing_cost';

        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT * FROM {$table} WHERE cost_date >= %s AND cost_date <= %s ORDER BY cost_date DESC, id DESC",
                $from,
                $to
            ),
            OBJECT
        );
    }

    /**
     * Get total marketing cost by channel for date range.
     *
     * @param string $from Start date (Y-m-d).
     * @param string $to   End date (Y-m-d).
     * @return array Associative array with channel as key and total as value.
     */
    public static function get_total_by_channel( $from, $to ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_marketing_cost';

        $results = $wpdb->get_results(
            $wpdb->prepare(
                "SELECT channel, SUM(amount) as total FROM {$table} WHERE cost_date >= %s AND cost_date <= %s GROUP BY channel",
                $from,
                $to
            ),
            OBJECT_K
        );

        $totals = array();
        foreach ( $results as $channel => $row ) {
            $totals[ $channel ] = floatval( $row->total );
        }

        return $totals;
    }

    /**
     * Get total marketing cost for date range.
     *
     * @param string $from Start date (Y-m-d).
     * @param string $to   End date (Y-m-d).
     * @return float
     */
    public static function get_total( $from, $to ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_marketing_cost';

        return (float) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT SUM(amount) FROM {$table} WHERE cost_date >= %s AND cost_date <= %s",
                $from,
                $to
            )
        );
    }

    /**
     * Get marketing cost entry by ID.
     *
     * @param int $id Marketing cost ID.
     * @return object|null
     */
    public static function get( $id ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_marketing_cost';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", absint( $id ) ),
            OBJECT
        );
    }
}
