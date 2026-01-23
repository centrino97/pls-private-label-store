<?php
/**
 * Comprehensive Debug Logger for PLS Plugin.
 * Provides console logging, error tracking, and debugging capabilities.
 *
 * @package PLS_Private_Label_Store
 */

if ( ! defined( 'ABSPATH' ) ) {
    exit;
}

final class PLS_Debug {

    const LOG_LEVEL_DEBUG = 'debug';
    const LOG_LEVEL_INFO = 'info';
    const LOG_LEVEL_WARN = 'warn';
    const LOG_LEVEL_ERROR = 'error';

    private static $enabled = null;
    private static $log_level = self::LOG_LEVEL_DEBUG;
    private static $logs = array();
    private static $max_logs = 1000;

    /**
     * Initialize debug system.
     */
    public static function init() {
        self::$enabled = get_option( 'pls_debug_enabled', false );
        self::$log_level = get_option( 'pls_debug_log_level', self::LOG_LEVEL_DEBUG );

        // Enqueue debug script if enabled
        if ( self::$enabled && is_admin() ) {
            add_action( 'admin_enqueue_scripts', array( __CLASS__, 'enqueue_scripts' ) );
        }
    }

    /**
     * Enqueue debug scripts.
     */
    public static function enqueue_scripts() {
        wp_enqueue_script(
            'pls-debug',
            PLS_PLS_URL . 'assets/js/debug.js',
            array( 'jquery' ),
            PLS_PLS_VERSION,
            true
        );

        wp_localize_script(
            'pls-debug',
            'PLS_Debug',
            array(
                'enabled' => self::$enabled ? 1 : 0, // Ensure boolean is converted to int for JS
                'log_level' => self::$log_level,
                'ajax_url' => admin_url( 'admin-ajax.php' ),
                'nonce' => wp_create_nonce( 'pls_debug_nonce' ),
            )
        );
        
        // Also set a global flag for early detection
        wp_add_inline_script(
            'pls-debug',
            'window.plsDebugEnabled = ' . ( self::$enabled ? 'true' : 'false' ) . ';',
            'before'
        );

        wp_enqueue_style(
            'pls-debug',
            PLS_PLS_URL . 'assets/css/debug.css',
            array(),
            PLS_PLS_VERSION
        );
    }

    /**
     * Log a debug message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public static function debug( $message, $context = array() ) {
        self::log( self::LOG_LEVEL_DEBUG, $message, $context );
    }

    /**
     * Log an info message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public static function info( $message, $context = array() ) {
        self::log( self::LOG_LEVEL_INFO, $message, $context );
    }

    /**
     * Log a warning message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public static function warn( $message, $context = array() ) {
        self::log( self::LOG_LEVEL_WARN, $message, $context );
    }

    /**
     * Log an error message.
     *
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    public static function error( $message, $context = array() ) {
        self::log( self::LOG_LEVEL_ERROR, $message, $context );
    }

    /**
     * Log an AJAX request.
     *
     * @param string $action AJAX action.
     * @param array  $data Request data.
     */
    public static function log_ajax_request( $action, $data = array() ) {
        if ( ! self::should_log( self::LOG_LEVEL_DEBUG ) ) {
            return;
        }

        self::log(
            self::LOG_LEVEL_DEBUG,
            sprintf( 'AJAX Request: %s', $action ),
            array(
                'type' => 'ajax_request',
                'action' => $action,
                'data' => $data,
                'timestamp' => microtime( true ),
            )
        );
    }

    /**
     * Log an AJAX response.
     *
     * @param string $action AJAX action.
     * @param mixed  $response Response data.
     * @param bool   $success Whether request was successful.
     */
    public static function log_ajax_response( $action, $response, $success = true ) {
        $level = $success ? self::LOG_LEVEL_DEBUG : self::LOG_LEVEL_ERROR;
        
        if ( ! self::should_log( $level ) ) {
            return;
        }

        self::log(
            $level,
            sprintf( 'AJAX Response: %s', $action ),
            array(
                'type' => 'ajax_response',
                'action' => $action,
                'success' => $success,
                'response' => $response,
                'timestamp' => microtime( true ),
            )
        );
    }

    /**
     * Log a sync operation.
     *
     * @param string $operation Operation name.
     * @param array  $data Operation data.
     */
    public static function log_sync( $operation, $data = array() ) {
        if ( ! self::should_log( self::LOG_LEVEL_INFO ) ) {
            return;
        }

        self::log(
            self::LOG_LEVEL_INFO,
            sprintf( 'Sync Operation: %s', $operation ),
            array(
                'type' => 'sync',
                'operation' => $operation,
                'data' => $data,
                'timestamp' => microtime( true ),
            )
        );
    }

    /**
     * Log page load.
     *
     * @param string $page Page identifier.
     */
    public static function log_page_load( $page ) {
        if ( ! self::should_log( self::LOG_LEVEL_DEBUG ) ) {
            return;
        }

        self::log(
            self::LOG_LEVEL_DEBUG,
            sprintf( 'Page Load: %s', $page ),
            array(
                'type' => 'page_load',
                'page' => $page,
                'user_id' => get_current_user_id(),
                'timestamp' => microtime( true ),
            )
        );
    }

    /**
     * Core logging method.
     *
     * @param string $level Log level.
     * @param string $message Message to log.
     * @param array  $context Additional context data.
     */
    private static function log( $level, $message, $context = array() ) {
        if ( ! self::$enabled ) {
            return;
        }

        if ( ! self::should_log( $level ) ) {
            return;
        }

        $log_entry = array(
            'level' => $level,
            'message' => $message,
            'context' => $context,
            'timestamp' => microtime( true ),
            'time' => current_time( 'mysql' ),
            'user_id' => get_current_user_id(),
            'page' => isset( $_SERVER['REQUEST_URI'] ) ? esc_url_raw( wp_unslash( $_SERVER['REQUEST_URI'] ) ) : '',
        );

        // Add stack trace for errors
        if ( $level === self::LOG_LEVEL_ERROR ) {
            $log_entry['stack_trace'] = self::get_stack_trace();
        }

        self::$logs[] = $log_entry;

        // Limit log size
        if ( count( self::$logs ) > self::$max_logs ) {
            array_shift( self::$logs );
        }

        // Also log to PHP error log if error level
        if ( $level === self::LOG_LEVEL_ERROR ) {
            error_log( sprintf( '[PLS Debug] %s: %s', strtoupper( $level ), $message ) );
        }
    }

    /**
     * Check if a log level should be logged.
     *
     * @param string $level Log level to check.
     * @return bool
     */
    private static function should_log( $level ) {
        $levels = array(
            self::LOG_LEVEL_DEBUG => 0,
            self::LOG_LEVEL_INFO => 1,
            self::LOG_LEVEL_WARN => 2,
            self::LOG_LEVEL_ERROR => 3,
        );

        $current_level = isset( $levels[ self::$log_level ] ) ? $levels[ self::$log_level ] : 0;
        $check_level = isset( $levels[ $level ] ) ? $levels[ $level ] : 0;

        return $check_level >= $current_level;
    }

    /**
     * Get stack trace.
     *
     * @return array Stack trace.
     */
    private static function get_stack_trace() {
        $trace = debug_backtrace( DEBUG_BACKTRACE_IGNORE_ARGS, 10 );
        $formatted = array();

        foreach ( $trace as $index => $frame ) {
            if ( $index === 0 ) {
                continue; // Skip this method
            }

            $formatted[] = array(
                'file' => isset( $frame['file'] ) ? basename( $frame['file'] ) : '',
                'line' => isset( $frame['line'] ) ? $frame['line'] : 0,
                'function' => isset( $frame['function'] ) ? $frame['function'] : '',
                'class' => isset( $frame['class'] ) ? $frame['class'] : '',
            );
        }

        return $formatted;
    }

    /**
     * Get all logs.
     *
     * @return array Log entries.
     */
    public static function get_logs() {
        return self::$logs;
    }

    /**
     * Clear all logs.
     */
    public static function clear_logs() {
        self::$logs = array();
    }

    /**
     * Output logs to browser console.
     * CSP-safe implementation (no eval, no inline scripts with dynamic content).
     */
    public static function output_logs() {
        if ( ! self::$enabled || empty( self::$logs ) ) {
            return;
        }

        // Use wp_localize_script instead of inline script to avoid CSP issues
        // Logs will be passed via PLS_Debug object
        wp_localize_script(
            'pls-debug',
            'PLS_Debug_Logs',
            self::$logs
        );
    }

    /**
     * Check if debugging is enabled.
     *
     * @return bool
     */
    public static function is_enabled() {
        return self::$enabled;
    }

    /**
     * Get current log level.
     *
     * @return string
     */
    public static function get_log_level() {
        return self::$log_level;
    }
}
