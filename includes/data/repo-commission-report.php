<?php
/**
 * Repository for commission reports.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Repo_Commission_Report {

    /**
     * Get report for a specific month.
     *
     * @param string $month_year Month in format 'Y-m'.
     * @return object|null
     */
    public static function get_by_month( $month_year ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_commission_reports';

        return $wpdb->get_row(
            $wpdb->prepare( "SELECT * FROM {$table} WHERE month_year = %s", $month_year ),
            OBJECT
        );
    }

    /**
     * Create or update commission report.
     *
     * @param string $month_year Month in format 'Y-m'.
     * @param float  $total_amount Total commission amount.
     * @return int|false Report ID or false on failure.
     */
    public static function create_or_update( $month_year, $total_amount ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_commission_reports';

        $existing = self::get_by_month( $month_year );

        if ( $existing ) {
            $wpdb->update(
                $table,
                array( 'total_amount' => $total_amount ),
                array( 'month_year' => $month_year ),
                array( '%f' ),
                array( '%s' )
            );
            return $existing->id;
        } else {
            $wpdb->insert(
                $table,
                array(
                    'month_year' => $month_year,
                    'total_amount' => $total_amount,
                ),
                array( '%s', '%f' )
            );
            return $wpdb->insert_id;
        }
    }

    /**
     * Mark report as sent.
     *
     * @param string $month_year Month in format 'Y-m'.
     * @return bool
     */
    public static function mark_sent( $month_year ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_commission_reports';

        return (bool) $wpdb->update(
            $table,
            array( 'sent_at' => current_time( 'mysql' ) ),
            array( 'month_year' => $month_year ),
            array( '%s' ),
            array( '%s' )
        );
    }

    /**
     * Mark report as paid.
     *
     * @param string $month_year Month in format 'Y-m'.
     * @param int    $marked_by  User ID who marked it as paid.
     * @return bool
     */
    public static function mark_paid( $month_year, $marked_by = null ) {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_commission_reports';

        $data = array( 'marked_paid_at' => current_time( 'mysql' ) );
        if ( $marked_by ) {
            $data['marked_paid_by'] = $marked_by;
        }

        return (bool) $wpdb->update(
            $table,
            $data,
            array( 'month_year' => $month_year ),
            array( '%s' ),
            array( '%s' )
        );
    }

    /**
     * Get all reports.
     *
     * @return array
     */
    public static function all() {
        global $wpdb;
        $table = $wpdb->prefix . 'pls_commission_reports';

        return $wpdb->get_results(
            "SELECT * FROM {$table} ORDER BY month_year DESC",
            OBJECT
        );
    }
}
