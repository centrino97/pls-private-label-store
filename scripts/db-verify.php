<?php
/**
 * Database Verification Script for PLS Plugin
 * 
 * Verifies that all plugin tables exist and checks data integrity.
 * 
 * Usage: php scripts/db-verify.php
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

// Define all PLS plugin tables and their expected structure
$plugin_tables = [
    'pls_base_product' => [
        'description' => 'Base products (main product catalog)',
        'key_fields' => [ 'id', 'slug', 'name', 'wc_product_id', 'status' ],
        'expected_columns' => [ 'id', 'wc_product_id', 'slug', 'name', 'category_path', 'status', 'created_at', 'updated_at' ],
    ],
    'pls_pack_tier' => [
        'description' => 'Pack tiers (pricing tiers for products)',
        'key_fields' => [ 'id', 'base_product_id', 'tier_key', 'units', 'price', 'wc_variation_id' ],
        'expected_columns' => [ 'id', 'base_product_id', 'tier_key', 'units', 'price', 'currency', 'is_enabled', 'wc_variation_id', 'package_type_value_id', 'calculated_price', 'sort_order', 'created_at', 'updated_at' ],
    ],
    'pls_product_profile' => [
        'description' => 'Product profiles (descriptions, images, options)',
        'key_fields' => [ 'id', 'base_product_id', 'short_description', 'featured_image_id' ],
        'expected_columns' => [ 'id', 'base_product_id', 'short_description', 'long_description', 'featured_image_id', 'gallery_ids', 'basics_json', 'skin_types_json', 'benefits_json', 'key_ingredients_json', 'directions_text', 'ingredients_list', 'label_enabled', 'label_price_per_unit', 'label_requires_file', 'label_helper_text', 'label_guide_url', 'created_at', 'updated_at' ],
    ],
    'pls_bundle' => [
        'description' => 'Bundles (product bundles/packages)',
        'key_fields' => [ 'id', 'bundle_key', 'slug', 'name', 'wc_product_id', 'status' ],
        'expected_columns' => [ 'id', 'wc_product_id', 'bundle_key', 'slug', 'name', 'base_price', 'pricing_mode', 'discount_amount', 'status', 'offer_rules_json', 'created_at', 'updated_at' ],
    ],
    'pls_bundle_item' => [
        'description' => 'Bundle items (products in bundles)',
        'key_fields' => [ 'id', 'bundle_id', 'base_product_id', 'tier_key', 'qty' ],
        'expected_columns' => [ 'id', 'bundle_id', 'base_product_id', 'tier_key', 'units_override', 'qty', 'sort_order' ],
    ],
    'pls_attribute' => [
        'description' => 'Product attributes (Package Type, Color, etc.)',
        'key_fields' => [ 'id', 'attr_key', 'label', 'option_type', 'is_variation' ],
        'expected_columns' => [ 'id', 'parent_attribute_id', 'wc_attribute_id', 'attr_key', 'label', 'option_type', 'is_primary', 'is_variation', 'default_min_tier', 'sort_order' ],
    ],
    'pls_attribute_value' => [
        'description' => 'Attribute values (specific options)',
        'key_fields' => [ 'id', 'attribute_id', 'value_key', 'label', 'min_tier_level' ],
        'expected_columns' => [ 'id', 'attribute_id', 'term_id', 'value_key', 'label', 'seo_slug', 'seo_title', 'seo_description', 'sort_order', 'min_tier_level', 'tier_price_overrides' ],
    ],
    'pls_swatch' => [
        'description' => 'Swatches (visual representation of attribute values)',
        'key_fields' => [ 'id', 'attribute_value_id', 'swatch_type', 'swatch_value' ],
        'expected_columns' => [ 'id', 'attribute_value_id', 'swatch_type', 'swatch_value', 'display_hint_json' ],
    ],
    'pls_custom_order' => [
        'description' => 'Custom orders (lead capture and order management)',
        'key_fields' => [ 'id', 'status', 'contact_name', 'contact_email', 'wc_order_id' ],
        'expected_columns' => [ 'id', 'wc_order_id', 'status', 'contact_name', 'contact_email', 'contact_phone', 'company_name', 'category_id', 'message', 'quantity_needed', 'budget', 'timeline', 'production_cost', 'total_value', 'nikola_commission_rate', 'nikola_commission_amount', 'invoiced_at', 'paid_at', 'commission_confirmed', 'sample_status', 'sample_cost', 'sample_sent_date', 'sample_tracking', 'sample_feedback', 'converted_at', 'created_at', 'updated_at' ],
    ],
    'pls_order_commission' => [
        'description' => 'Order commissions (commission tracking for WC orders)',
        'key_fields' => [ 'id', 'wc_order_id', 'product_id', 'units', 'commission_amount', 'status' ],
        'expected_columns' => [ 'id', 'wc_order_id', 'wc_order_item_id', 'product_id', 'tier_key', 'bundle_key', 'units', 'commission_rate_per_unit', 'commission_amount', 'status', 'invoiced_at', 'paid_at', 'created_at' ],
    ],
    'pls_onboarding_progress' => [
        'description' => 'Onboarding progress (user onboarding tracking)',
        'key_fields' => [ 'id', 'user_id', 'current_step', 'completed_steps' ],
        'expected_columns' => [ 'id', 'user_id', 'current_step', 'completed_steps', 'test_product_id', 'started_at', 'completed_at' ],
    ],
    'pls_commission_reports' => [
        'description' => 'Commission reports (monthly commission reports)',
        'key_fields' => [ 'id', 'month_year', 'total_amount', 'sent_at' ],
        'expected_columns' => [ 'id', 'month_year', 'total_amount', 'sent_at', 'marked_paid_at', 'marked_paid_by' ],
    ],
];

echo "========================================\n";
echo "PLS Plugin Database Verification\n";
echo "========================================\n\n";

$errors = [];
$warnings = [];
$stats = [];

// Check each table
foreach ( $plugin_tables as $table_name => $table_info ) {
    $full_table_name = $prefix . $table_name;
    
    echo "Checking table: {$full_table_name}\n";
    echo "  Description: {$table_info['description']}\n";
    
    // Check if table exists
    try {
        $stmt = $pdo->query( "SHOW TABLES LIKE '{$full_table_name}'" );
        $exists = $stmt->fetch();
        
        if ( ! $exists ) {
            $errors[] = "Table {$full_table_name} does NOT exist!";
            echo "  ✗ Table does NOT exist!\n\n";
            continue;
        }
        
        echo "  ✓ Table exists\n";
        
        // Get table structure
        $stmt = $pdo->query( "DESCRIBE {$full_table_name}" );
        $columns = $stmt->fetchAll( PDO::FETCH_COLUMN );
        
        // Check expected columns
        $missing_columns = array_diff( $table_info['expected_columns'], $columns );
        if ( ! empty( $missing_columns ) ) {
            $warnings[] = "Table {$full_table_name} is missing columns: " . implode( ', ', $missing_columns );
            echo "  ⚠ Missing columns: " . implode( ', ', $missing_columns ) . "\n";
        }
        
        // Get row count
        $stmt = $pdo->query( "SELECT COUNT(*) FROM {$full_table_name}" );
        $count = $stmt->fetchColumn();
        $stats[ $table_name ] = $count;
        echo "  ✓ Row count: {$count}\n";
        
        // Check for data integrity issues
        if ( $count > 0 ) {
            // Check for NULL values in key fields
            foreach ( $table_info['key_fields'] as $field ) {
                if ( in_array( $field, $columns ) ) {
                    $stmt = $pdo->query( "SELECT COUNT(*) FROM {$full_table_name} WHERE {$field} IS NULL" );
                    $null_count = $stmt->fetchColumn();
                    if ( $null_count > 0 && ! in_array( $field, [ 'wc_product_id', 'wc_variation_id', 'term_id' ] ) ) {
                        $warnings[] = "Table {$full_table_name} has {$null_count} rows with NULL {$field}";
                    }
                }
            }
            
            // Table-specific checks
            switch ( $table_name ) {
                case 'pls_base_product':
                    // Check for products without pack tiers
                    $stmt = $pdo->query( "
                        SELECT COUNT(DISTINCT bp.id) 
                        FROM {$prefix}pls_base_product bp
                        LEFT JOIN {$prefix}pls_pack_tier pt ON bp.id = pt.base_product_id
                        WHERE pt.id IS NULL
                    " );
                    $orphaned = $stmt->fetchColumn();
                    if ( $orphaned > 0 ) {
                        $warnings[] = "Found {$orphaned} base products without pack tiers";
                    }
                    break;
                    
                case 'pls_pack_tier':
                    // Check for pack tiers without base products
                    $stmt = $pdo->query( "
                        SELECT COUNT(*) 
                        FROM {$prefix}pls_pack_tier pt
                        LEFT JOIN {$prefix}pls_base_product bp ON pt.base_product_id = bp.id
                        WHERE bp.id IS NULL
                    " );
                    $orphaned = $stmt->fetchColumn();
                    if ( $orphaned > 0 ) {
                        $errors[] = "Found {$orphaned} pack tiers without base products (orphaned data!)";
                    }
                    break;
                    
                case 'pls_bundle_item':
                    // Check for bundle items without bundles
                    $stmt = $pdo->query( "
                        SELECT COUNT(*) 
                        FROM {$prefix}pls_bundle_item bi
                        LEFT JOIN {$prefix}pls_bundle b ON bi.bundle_id = b.id
                        WHERE b.id IS NULL
                    " );
                    $orphaned = $stmt->fetchColumn();
                    if ( $orphaned > 0 ) {
                        $errors[] = "Found {$orphaned} bundle items without bundles (orphaned data!)";
                    }
                    break;
                    
                case 'pls_attribute_value':
                    // Check for attribute values without attributes
                    $stmt = $pdo->query( "
                        SELECT COUNT(*) 
                        FROM {$prefix}pls_attribute_value av
                        LEFT JOIN {$prefix}pls_attribute a ON av.attribute_id = a.id
                        WHERE a.id IS NULL
                    " );
                    $orphaned = $stmt->fetchColumn();
                    if ( $orphaned > 0 ) {
                        $errors[] = "Found {$orphaned} attribute values without attributes (orphaned data!)";
                    }
                    break;
            }
        }
        
    } catch ( PDOException $e ) {
        $errors[] = "Error checking table {$full_table_name}: " . $e->getMessage();
        echo "  ✗ Error: " . $e->getMessage() . "\n";
    }
    
    echo "\n";
}

// Summary
echo "========================================\n";
echo "Verification Summary\n";
echo "========================================\n\n";

echo "Table Statistics:\n";
foreach ( $stats as $table => $count ) {
    echo "  {$prefix}{$table}: {$count} rows\n";
}

echo "\n";

if ( empty( $errors ) && empty( $warnings ) ) {
    echo "✓ All checks passed! Database structure is correct.\n";
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

echo "\n✓ Verification complete!\n";
