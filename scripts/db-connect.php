<?php
/**
 * Database Connection Script for PLS Plugin Verification
 * 
 * This script connects directly to the WordPress MySQL database
 * without loading WordPress, allowing you to inspect plugin data.
 * 
 * Usage: php scripts/db-connect.php
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
    
    // If run standalone, show connection message
    if ( php_sapi_name() === 'cli' && basename( $_SERVER['PHP_SELF'] ) === 'db-connect.php' ) {
        echo "✓ Successfully connected to database: {$config['database']}\n";
        echo "  Host: {$config['host']}\n";
        echo "  Prefix: {$config['prefix']}\n\n";
    }
    
    return $pdo;
    
} catch ( PDOException $e ) {
    die( "ERROR: Database connection failed!\n" . $e->getMessage() . "\n" );
}
