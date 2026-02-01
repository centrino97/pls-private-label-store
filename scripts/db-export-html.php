<?php
/**
 * Database Export to HTML Report
 * 
 * Exports database verification results to a beautiful HTML report.
 * 
 * Usage: php scripts/db-export-html.php [output-file.html]
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
$output_file = $argv[1] ?? __DIR__ . '/db-verification-report.html';

// Plugin tables
$plugin_tables = [
    'pls_base_product' => 'Base Products',
    'pls_pack_tier' => 'Pack Tiers',
    'pls_product_profile' => 'Product Profiles',
    'pls_bundle' => 'Bundles',
    'pls_bundle_item' => 'Bundle Items',
    'pls_attribute' => 'Attributes',
    'pls_attribute_value' => 'Attribute Values',
    'pls_swatch' => 'Swatches',
    'pls_custom_order' => 'Custom Orders',
    'pls_order_commission' => 'Order Commissions',
    'pls_onboarding_progress' => 'Onboarding Progress',
    'pls_commission_reports' => 'Commission Reports',
];

$report_data = [
    'database' => $config['database'],
    'host' => $config['host'],
    'prefix' => $prefix,
    'timestamp' => date( 'Y-m-d H:i:s' ),
    'tables' => [],
    'errors' => [],
    'warnings' => [],
    'stats' => [],
];

// Check each table
foreach ( $plugin_tables as $table_name => $display_name ) {
    $full_table_name = $prefix . $table_name;
    
    try {
        $stmt = $pdo->query( "SHOW TABLES LIKE '{$full_table_name}'" );
        $exists = $stmt->fetch();
        
        if ( ! $exists ) {
            $report_data['errors'][] = "Table {$full_table_name} does NOT exist!";
            $report_data['tables'][ $table_name ] = [
                'name' => $full_table_name,
                'display_name' => $display_name,
                'exists' => false,
                'count' => 0,
            ];
            continue;
        }
        
        // Get row count
        $stmt = $pdo->query( "SELECT COUNT(*) FROM {$full_table_name}" );
        $count = $stmt->fetchColumn();
        
        // Get column info
        $stmt = $pdo->query( "DESCRIBE {$full_table_name}" );
        $columns = $stmt->fetchAll();
        
        $report_data['tables'][ $table_name ] = [
            'name' => $full_table_name,
            'display_name' => $display_name,
            'exists' => true,
            'count' => $count,
            'columns' => array_column( $columns, 'Field' ),
        ];
        
        $report_data['stats'][ $table_name ] = $count;
        
    } catch ( PDOException $e ) {
        $report_data['errors'][] = "Error checking table {$full_table_name}: " . $e->getMessage();
    }
}

// Generate HTML
$html = <<<HTML
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>PLS Plugin Database Verification Report</title>
    <style>
        * { margin: 0; padding: 0; box-sizing: border-box; }
        body {
            font-family: -apple-system, BlinkMacSystemFont, 'Segoe UI', Roboto, Oxygen, Ubuntu, Cantarell, sans-serif;
            background: #f5f5f5;
            padding: 20px;
            line-height: 1.6;
        }
        .container {
            max-width: 1200px;
            margin: 0 auto;
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
            overflow: hidden;
        }
        .header {
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            color: white;
            padding: 30px;
            text-align: center;
        }
        .header h1 { font-size: 2em; margin-bottom: 10px; }
        .header p { opacity: 0.9; }
        .content { padding: 30px; }
        .summary {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 30px;
        }
        .summary-card {
            background: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
            border-left: 4px solid #667eea;
        }
        .summary-card h3 {
            color: #667eea;
            font-size: 0.9em;
            text-transform: uppercase;
            margin-bottom: 10px;
        }
        .summary-card .value {
            font-size: 2em;
            font-weight: bold;
            color: #333;
        }
        .table-section {
            margin-bottom: 30px;
        }
        .table-section h2 {
            color: #333;
            margin-bottom: 15px;
            padding-bottom: 10px;
            border-bottom: 2px solid #eee;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            margin-bottom: 20px;
        }
        th, td {
            padding: 12px;
            text-align: left;
            border-bottom: 1px solid #eee;
        }
        th {
            background: #f8f9fa;
            font-weight: 600;
            color: #333;
        }
        tr:hover {
            background: #f8f9fa;
        }
        .status-badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 12px;
            font-size: 0.85em;
            font-weight: 600;
        }
        .status-exists {
            background: #d4edda;
            color: #155724;
        }
        .status-missing {
            background: #f8d7da;
            color: #721c24;
        }
        .error-box, .warning-box {
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 15px;
        }
        .error-box {
            background: #f8d7da;
            border-left: 4px solid #dc3545;
            color: #721c24;
        }
        .warning-box {
            background: #fff3cd;
            border-left: 4px solid #ffc107;
            color: #856404;
        }
        .footer {
            text-align: center;
            padding: 20px;
            color: #666;
            font-size: 0.9em;
            border-top: 1px solid #eee;
        }
    </style>
</head>
<body>
    <div class="container">
        <div class="header">
            <h1>🔍 PLS Plugin Database Verification</h1>
            <p>Generated on {$report_data['timestamp']}</p>
        </div>
        <div class="content">
            <div class="summary">
                <div class="summary-card">
                    <h3>Database</h3>
                    <div class="value">{$report_data['database']}</div>
                </div>
                <div class="summary-card">
                    <h3>Host</h3>
                    <div class="value">{$report_data['host']}</div>
                </div>
                <div class="summary-card">
                    <h3>Tables Found</h3>
                    <div class="value">" . count( array_filter( $report_data['tables'], fn($t) => $t['exists'] ) ) . " / " . count( $plugin_tables ) . "</div>
                </div>
                <div class="summary-card">
                    <h3>Total Records</h3>
                    <div class="value">" . array_sum( $report_data['stats'] ) . "</div>
                </div>
            </div>
HTML;

// Errors and warnings
if ( ! empty( $report_data['errors'] ) ) {
    $html .= '<div class="error-box"><strong>Errors:</strong><ul>';
    foreach ( $report_data['errors'] as $error ) {
        $html .= '<li>' . htmlspecialchars( $error ) . '</li>';
    }
    $html .= '</ul></div>';
}

if ( ! empty( $report_data['warnings'] ) ) {
    $html .= '<div class="warning-box"><strong>Warnings:</strong><ul>';
    foreach ( $report_data['warnings'] as $warning ) {
        $html .= '<li>' . htmlspecialchars( $warning ) . '</li>';
    }
    $html .= '</ul></div>';
}

// Tables section
$html .= '<div class="table-section"><h2>Plugin Tables</h2><table>';
$html .= '<thead><tr><th>Table Name</th><th>Display Name</th><th>Status</th><th>Row Count</th><th>Columns</th></tr></thead><tbody>';

foreach ( $report_data['tables'] as $table ) {
    $status_class = $table['exists'] ? 'status-exists' : 'status-missing';
    $status_text = $table['exists'] ? '✓ Exists' : '✗ Missing';
    $columns_count = isset( $table['columns'] ) ? count( $table['columns'] ) : 0;
    
    $html .= '<tr>';
    $html .= '<td><code>' . htmlspecialchars( $table['name'] ) . '</code></td>';
    $html .= '<td>' . htmlspecialchars( $table['display_name'] ) . '</td>';
    $html .= '<td><span class="status-badge ' . $status_class . '">' . $status_text . '</span></td>';
    $html .= '<td><strong>' . number_format( $table['count'] ) . '</strong></td>';
    $html .= '<td>' . $columns_count . ' columns</td>';
    $html .= '</tr>';
}

$html .= '</tbody></table></div>';

// Stats section
$html .= '<div class="table-section"><h2>Table Statistics</h2><table>';
$html .= '<thead><tr><th>Table</th><th>Row Count</th></tr></thead><tbody>';

foreach ( $report_data['stats'] as $table_name => $count ) {
    $html .= '<tr>';
    $html .= '<td>' . htmlspecialchars( $plugin_tables[ $table_name ] ?? $table_name ) . '</td>';
    $html .= '<td><strong>' . number_format( $count ) . '</strong></td>';
    $html .= '</tr>';
}

$html .= '</tbody></table></div>';

$html .= <<<HTML
        </div>
        <div class="footer">
            <p>PLS Private Label Store Plugin - Database Verification Report</p>
            <p>Generated by db-export-html.php</p>
        </div>
    </div>
</body>
</html>
HTML;

// Write HTML file
file_put_contents( $output_file, $html );

echo "✓ HTML report generated: {$output_file}\n";
echo "  Open in browser to view the report\n";
