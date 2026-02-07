<?php
/**
 * Commission email system for monthly reports.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Commission_Email {

    /**
     * Initialize email system.
     */
    public static function init() {
        // Schedule monthly email cron
        add_action( 'pls_monthly_commission_email', array( __CLASS__, 'send_monthly_report' ) );
        
        // Add custom monthly schedule
        add_filter( 'cron_schedules', array( __CLASS__, 'add_monthly_schedule' ) );
    }

    /**
     * Add monthly schedule to WordPress cron.
     *
     * @param array $schedules Existing schedules.
     * @return array
     */
    public static function add_monthly_schedule( $schedules ) {
        $schedules['monthly'] = array(
            'interval' => 30 * DAY_IN_SECONDS,
            'display'  => __( 'Once Monthly', 'pls-private-label-store' ),
        );
        return $schedules;
    }

    /**
     * Send monthly commission report.
     *
     * @param string|null $month_year Optional month in format 'Y-m'. If null, uses previous month.
     */
    public static function send_monthly_report( $month_year = null ) {
        if ( ! $month_year ) {
            // Previous month
            $month_year = date( 'Y-m', strtotime( '-1 month' ) );
        }

        $month_start = $month_year . '-01';
        $month_end = date( 'Y-m-t', strtotime( $month_start ) );

        // Calculate total commission for the month
        $product_commissions = PLS_Repo_Commission::query(
            array(
                'date_from' => $month_start,
                'date_to'   => $month_end,
                'limit'     => 1000,
            )
        );

        $product_total = 0;
        foreach ( $product_commissions as $comm ) {
            $product_total += floatval( $comm->commission_amount );
        }

        // Custom order commissions
        $custom_orders = PLS_Repo_Custom_Order::all();
        $custom_total = 0;
        foreach ( $custom_orders as $order ) {
            if ( $order->nikola_commission_amount ) {
                $order_month = date( 'Y-m', strtotime( $order->created_at ) );
                if ( $order_month === $month_year ) {
                    $custom_total += floatval( $order->nikola_commission_amount );
                }
            }
        }

        $total_amount = $product_total + $custom_total;

        // Get recipient email
        $recipients = get_option( 'pls_commission_email_recipients', array( PLS_DEFAULT_COMMISSION_EMAIL ) );
        $to = is_array( $recipients ) ? $recipients[0] : $recipients;

        // Send email
        $subject = sprintf( __( 'PLS Commission Report - %s', 'pls-private-label-store' ), date( 'F Y', strtotime( $month_start ) ) );
        $message = sprintf(
            __( "Your PLS commission for %s is %s.\n\nBreakdown:\n- Product Orders: %s\n- Custom Orders: %s\n\nTotal: %s", 'pls-private-label-store' ),
            date( 'F Y', strtotime( $month_start ) ),
            wc_price( $total_amount ),
            wc_price( $product_total ),
            wc_price( $custom_total ),
            wc_price( $total_amount )
        );

        $sent = wp_mail( $to, $subject, $message );

        // Create or update report record
        if ( $sent ) {
            $report_id = PLS_Repo_Commission_Report::create_or_update( $month_year, $total_amount );
            PLS_Repo_Commission_Report::mark_sent( $month_year );
        }

        return $sent;
    }

    /**
     * Send report manually (via AJAX).
     *
     * @param string $month_year Month in format 'Y-m'.
     * @return bool
     */
    public static function send_manual_report( $month_year ) {
        return self::send_monthly_report( $month_year );
    }
}
