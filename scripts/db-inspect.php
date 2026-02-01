<?php
/**
 * Database Inspection Script for PLS Plugin
 * 
 * Allows you to inspect specific data from plugin tables.
 * 
 * Usage: 
 *   php scripts/db-inspect.php [table] [limit]
 *   php scripts/db-inspect.php pls_base_product 10
 * 
 * @package PLS_Private_Label_Store
 */

// Load configuration
$config_file = __DIR__ . '/db-config.php';
if ( ! file_exists( $config_file ) ) {
    die( "ERROR: db-config.php not found!\n" .
         "Please copy db-config.example.php to db-config.php and fill in your credentials.\n" );
}

$config = require $config_file;

// Validate required config
$required = [ 'host', 'database', 'username', 'password', 'prefix' ];
foreach ( $required as $key ) {
    if ( empty( $config[ $key ] ) ) {
        die( "ERROR: Missing required config: {$key}\n" );
    }
}

// Connect to database
try {
    $port = $config['port'] ?? 3306;
    $charset = $config['charset'] ?? 'utf8mb4';
    
    $dsn = sprintf(
        'mysql:host=%s;port=%d;dbname=%s;charset=%s',
        $config['host'],
        $port,
        $config['database'],
        $charset
    );
    
    $pdo = new PDO( $dsn, $config['username'], $config['password'], [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ] );
    
} catch ( PDOException $e ) {
    die( "ERROR: Database connection failed!\n" . $e->getMessage() . "\n" );
}

$prefix = $config['prefix'];

// Get command line arguments
$table_arg = $argv[1] ?? null;
$limit = isset( $argv[2] ) ? (int) $argv[2] : 10;

// Available tables
$available_tables = [
    'pls_base_product',
    'pls_pack_tier',
    'pls_product_profile',
    'pls_bundle',
    'pls_bundle_item',
    'pls_attribute',
    'pls_attribute_value',
    'pls_swatch',
    'pls_custom_order',
    'pls_order_commission',
    'pls_onboarding_progress',
    'pls_commission_reports',
];

if ( ! $table_arg ) {
    echo "Usage: php scripts/db-inspect.php [table] [limit]\n\n";
    echo "Available tables:\n";
    foreach ( $available_tables as $table ) {
        echo "  - {$table}\n";
    }
    echo "\nExample: php scripts/db-inspect.php pls_base_product 5\n";
    exit( 0 );
}

$table_name = $prefix . $table_arg;

// Validate table name
if ( ! in_array( $table_arg, $available_tables ) ) {
    die( "ERROR: Invalid table name '{$table_arg}'\n" );
}

// Check if table exists
try {
    $stmt = $pdo->query( "SHOW TABLES LIKE '{$table_name}'" );
    if ( ! $stmt->fetch() ) {
        die( "ERROR: Table {$table_name} does not exist!\n" );
    }
    
    // Get total count
    $stmt = $pdo->query( "SELECT COUNT(*) FROM {$table_name}" );
    $total = $stmt->fetchColumn();
    
    echo "========================================\n";
    echo "Inspecting: {$table_name}\n";
    echo "Total rows: {$total}\n";
    echo "Showing: " . min( $limit, $total ) . " rows\n";
    echo "========================================\n\n";
    
    // Get column names
    $stmt = $pdo->query( "DESCRIBE {$table_name}" );
    $columns = $stmt->fetchAll( PDO::FETCH_COLUMN );
    
    // Fetch data
    $stmt = $pdo->query( "SELECT * FROM {$table_name} LIMIT {$limit}" );
    $rows = $stmt->fetchAll();
    
    if ( empty( $rows ) ) {
        echo "No data found in table.\n";
        exit( 0 );
    }
    
    // Display data in a readable format
    foreach ( $rows as $index => $row ) {
        echo "Row #" . ( $index + 1 ) . ":\n";
        echo str_repeat( '-', 50 ) . "\n";
        
        foreach ( $columns as $col ) {
            $value = $row[ $col ] ?? null;
            
            // Format the value
            if ( $value === null ) {
                $display = '[NULL]';
            } elseif ( strlen( $value ) > 100 ) {
                $display = substr( $value, 0, 100 ) . '...';
            } else {
                $display = $value;
            }
            
            // Handle JSON fields
            if ( strpos( $col, '_json' ) !== false && ! empty( $value ) ) {
                $json = json_decode( $value, true );
                if ( json_last_error() === JSON_ERROR_NONE ) {
                    $display = json_encode( $json, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES );
                }
            }
            
            echo sprintf( "  %-25s: %s\n", $col, $display );
        }
        
        echo "\n";
    }
    
} catch ( PDOException $e ) {
    die( "ERROR: " . $e->getMessage() . "\n" );
}
