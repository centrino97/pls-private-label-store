<?php
/**
 * Database Export to SQL Dump
 * 
 * Exports PLS plugin tables to SQL dump file.
 * 
 * Usage: php scripts/db-export-sql.php [output-file.sql]
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
$output_file = $argv[1] ?? __DIR__ . '/pls-plugin-data-' . date( 'Y-m-d-His' ) . '.sql';

// Plugin tables
$plugin_tables = [
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

$sql_dump = "-- PLS Plugin Database Export\n";
$sql_dump .= "-- Generated: " . date( 'Y-m-d H:i:s' ) . "\n";
$sql_dump .= "-- Database: {$config['database']}\n";
$sql_dump .= "-- Prefix: {$prefix}\n\n";
$sql_dump .= "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
$sql_dump .= "SET time_zone = \"+00:00\";\n\n";

foreach ( $plugin_tables as $table_name ) {
    $full_table_name = $prefix . $table_name;
    
    try {
        // Check if table exists
        $stmt = $pdo->query( "SHOW TABLES LIKE '{$full_table_name}'" );
        if ( ! $stmt->fetch() ) {
            echo "⚠ Table {$full_table_name} does not exist, skipping...\n";
            continue;
        }
        
        echo "Exporting {$full_table_name}...\n";
        
        // Get table structure
        $stmt = $pdo->query( "SHOW CREATE TABLE `{$full_table_name}`" );
        $create_table = $stmt->fetch();
        $sql_dump .= "\n-- Table structure for `{$full_table_name}`\n";
        $sql_dump .= "DROP TABLE IF EXISTS `{$full_table_name}`;\n";
        $sql_dump .= $create_table['Create Table'] . ";\n\n";
        
        // Get table data
        $stmt = $pdo->query( "SELECT * FROM `{$full_table_name}`" );
        $rows = $stmt->fetchAll();
        
        if ( ! empty( $rows ) ) {
            $sql_dump .= "-- Data for table `{$full_table_name}`\n";
            
            // Get column names
            $columns = array_keys( $rows[0] );
            $column_list = '`' . implode( '`, `', $columns ) . '`';
            
            foreach ( $rows as $row ) {
                $values = [];
                foreach ( $row as $value ) {
                    if ( $value === null ) {
                        $values[] = 'NULL';
                    } else {
                        $values[] = $pdo->quote( $value );
                    }
                }
                
                $sql_dump .= "INSERT INTO `{$full_table_name}` ({$column_list}) VALUES (" . implode( ', ', $values ) . ");\n";
            }
            
            $sql_dump .= "\n";
        }
        
    } catch ( PDOException $e ) {
        echo "✗ Error exporting {$full_table_name}: " . $e->getMessage() . "\n";
    }
}

// Write SQL file
file_put_contents( $output_file, $sql_dump );

echo "\n✓ SQL dump generated: {$output_file}\n";
echo "  File size: " . number_format( filesize( $output_file ) ) . " bytes\n";
