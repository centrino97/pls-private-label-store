<?php
/**
 * Database Configuration Template
 * 
 * Copy this file to db-config.php and fill in your Hostinger MySQL credentials.
 * NEVER commit db-config.php to version control!
 * 
 * @package PLS_Private_Label_Store
 */

return [
    // Hostinger MySQL Database Credentials
    'host'     => 'localhost',           // Usually 'localhost' or your Hostinger MySQL host
    'database' => 'your_database_name',  // Your WordPress database name
    'username' => 'your_username',       // Your MySQL username
    'password' => 'your_password',       // Your MySQL password
    'charset'  => 'utf8mb4',
    
    // WordPress Table Prefix (usually 'wp_' but check your wp-config.php)
    'prefix'   => 'wp_',
    
    // Optional: Port (default 3306)
    'port'     => 3306,
];
