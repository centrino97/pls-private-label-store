<?php
/**
 * WooCommerce Sync Verification Script
 * 
 * Checks if PLS plugin data is properly synced with WooCommerce.
 * 
 * Usage: php scripts/db-sync-check.php
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

echo "========================================\n";
echo "WooCommerce Sync Verification\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];

// Check base products sync
echo "1. Checking Base Products → WooCommerce Products\n";
try {
    $stmt = $pdo->query( "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN wc_product_id IS NOT NULL THEN 1 ELSE 0 END) as synced,
            SUM(CASE WHEN wc_product_id IS NULL THEN 1 ELSE 0 END) as not_synced
        FROM {$prefix}pls_base_product
        WHERE status = 'published'
    " );
    $stats = $stmt->fetch();
    
    echo "   Total published products: {$stats['total']}\n";
    echo "   Synced with WC: {$stats['synced']}\n";
    echo "   Not synced: {$stats['not_synced']}\n";
    
    if ( $stats['not_synced'] > 0 ) {
        $warnings[] = "{$stats['not_synced']} published base products are not synced with WooCommerce";
        
        // Show which products are not synced
        $stmt = $pdo->query( "
            SELECT id, slug, name 
            FROM {$prefix}pls_base_product 
            WHERE status = 'published' AND wc_product_id IS NULL 
            LIMIT 5
        " );
        $unsynced = $stmt->fetchAll();
        if ( ! empty( $unsynced ) ) {
            echo "   Examples of unsynced products:\n";
            foreach ( $unsynced as $product ) {
                echo "     - ID {$product['id']}: {$product['name']} ({$product['slug']})\n";
            }
        }
    }
    
    // Verify WC products exist
    if ( $stats['synced'] > 0 ) {
        $stmt = $pdo->query( "
            SELECT COUNT(*) 
            FROM {$prefix}pls_base_product bp
            LEFT JOIN {$prefix}posts p ON bp.wc_product_id = p.ID AND p.post_type = 'product'
            WHERE bp.status = 'published' 
            AND bp.wc_product_id IS NOT NULL 
            AND p.ID IS NULL
        " );
        $orphaned = $stmt->fetchColumn();
        if ( $orphaned > 0 ) {
            $errors[] = "Found {$orphaned} base products pointing to non-existent WooCommerce products";
        }
    }
    
} catch ( PDOException $e ) {
    $errors[] = "Error checking base products sync: " . $e->getMessage();
}

echo "\n";

// Check pack tiers sync
echo "2. Checking Pack Tiers → WooCommerce Variations\n";
try {
    $stmt = $pdo->query( "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN wc_variation_id IS NOT NULL THEN 1 ELSE 0 END) as synced,
            SUM(CASE WHEN wc_variation_id IS NULL THEN 1 ELSE 0 END) as not_synced
        FROM {$prefix}pls_pack_tier
        WHERE is_enabled = 1
    " );
    $stats = $stmt->fetch();
    
    echo "   Total enabled pack tiers: {$stats['total']}\n";
    echo "   Synced with WC variations: {$stats['synced']}\n";
    echo "   Not synced: {$stats['not_synced']}\n";
    
    if ( $stats['not_synced'] > 0 ) {
        $warnings[] = "{$stats['not_synced']} enabled pack tiers are not synced with WooCommerce variations";
    }
    
    // Verify WC variations exist
    if ( $stats['synced'] > 0 ) {
        $stmt = $pdo->query( "
            SELECT COUNT(*) 
            FROM {$prefix}pls_pack_tier pt
            LEFT JOIN {$prefix}posts p ON pt.wc_variation_id = p.ID AND p.post_type = 'product_variation'
            WHERE pt.is_enabled = 1 
            AND pt.wc_variation_id IS NOT NULL 
            AND p.ID IS NULL
        " );
        $orphaned = $stmt->fetchColumn();
        if ( $orphaned > 0 ) {
            $errors[] = "Found {$orphaned} pack tiers pointing to non-existent WooCommerce variations";
        }
    }
    
} catch ( PDOException $e ) {
    $errors[] = "Error checking pack tiers sync: " . $e->getMessage();
}

echo "\n";

// Check bundles sync
echo "3. Checking Bundles → WooCommerce Grouped Products\n";
try {
    $stmt = $pdo->query( "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN wc_product_id IS NOT NULL THEN 1 ELSE 0 END) as synced,
            SUM(CASE WHEN wc_product_id IS NULL THEN 1 ELSE 0 END) as not_synced
        FROM {$prefix}pls_bundle
        WHERE status = 'published'
    " );
    $stats = $stmt->fetch();
    
    echo "   Total published bundles: {$stats['total']}\n";
    echo "   Synced with WC: {$stats['synced']}\n";
    echo "   Not synced: {$stats['not_synced']}\n";
    
    if ( $stats['not_synced'] > 0 ) {
        $warnings[] = "{$stats['not_synced']} published bundles are not synced with WooCommerce";
    }
    
} catch ( PDOException $e ) {
    $errors[] = "Error checking bundles sync: " . $e->getMessage();
}

echo "\n";

// Check attributes sync
echo "4. Checking Attributes → WooCommerce Attributes\n";
try {
    $stmt = $pdo->query( "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN wc_attribute_id IS NOT NULL THEN 1 ELSE 0 END) as synced,
            SUM(CASE WHEN wc_attribute_id IS NULL THEN 1 ELSE 0 END) as not_synced
        FROM {$prefix}pls_attribute
    " );
    $stats = $stmt->fetch();
    
    echo "   Total attributes: {$stats['total']}\n";
    echo "   Synced with WC: {$stats['synced']}\n";
    echo "   Not synced: {$stats['not_synced']}\n";
    
    if ( $stats['not_synced'] > 0 && $stats['total'] > 0 ) {
        $warnings[] = "{$stats['not_synced']} attributes are not synced with WooCommerce";
    }
    
} catch ( PDOException $e ) {
    $errors[] = "Error checking attributes sync: " . $e->getMessage();
}

echo "\n";

// Check custom orders sync
echo "5. Checking Custom Orders → WooCommerce Orders\n";
try {
    $stmt = $pdo->query( "
        SELECT 
            COUNT(*) as total,
            SUM(CASE WHEN wc_order_id IS NOT NULL THEN 1 ELSE 0 END) as synced,
            SUM(CASE WHEN wc_order_id IS NULL THEN 1 ELSE 0 END) as not_synced
        FROM {$prefix}pls_custom_order
        WHERE status = 'done'
    " );
    $stats = $stmt->fetch();
    
    echo "   Total completed custom orders: {$stats['total']}\n";
    echo "   Linked to WC orders: {$stats['synced']}\n";
    echo "   Not linked: {$stats['not_synced']}\n";
    
    if ( $stats['not_synced'] > 0 && $stats['total'] > 0 ) {
        $warnings[] = "{$stats['not_synced']} completed custom orders are not linked to WooCommerce orders";
    }
    
} catch ( PDOException $e ) {
    $errors[] = "Error checking custom orders sync: " . $e->getMessage();
}

echo "\n";

// Summary
echo "========================================\n";
echo "Sync Verification Summary\n";
echo "========================================\n\n";

if ( empty( $errors ) && empty( $warnings ) ) {
    echo "✓ All sync checks passed!\n";
} else {
    if ( ! empty( $warnings ) ) {
        echo "Warnings:\n";
        foreach ( $warnings as $warning ) {
            echo "  ⚠ {$warning}\n";
        }
        echo "\n";
    }
    
    if ( ! empty( $errors ) ) {
        echo "Errors:\n";
        foreach ( $errors as $error ) {
            echo "  ✗ {$error}\n";
        }
        echo "\n";
        exit( 1 );
    }
}

echo "\n✓ Sync verification complete!\n";
