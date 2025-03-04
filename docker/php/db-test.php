<?php
/**
 * Database Connection Test Script
 * 
 * This script tests the database connection using the environment variables
 * from the .env file. It's designed to be run from the Docker container.
 */

// Check if we're in the right environment
if (!file_exists('/var/www/.env')) {
    echo "Error: .env file not found in /var/www/.env\n";
    exit(1);
}

try {
    // Load Laravel environment if available
    if (file_exists('/var/www/vendor/autoload.php')) {
        require_once '/var/www/vendor/autoload.php';
        
        // Use Laravel's Dotenv if available
        if (class_exists('Dotenv\Dotenv')) {
            $dotenv = Dotenv\Dotenv::createImmutable('/var/www');
            $dotenv->load();
        }
    } else {
        // Manual parsing of .env file if Laravel is not available
        $env_file = file_get_contents('/var/www/.env');
        $lines = explode("\n", $env_file);
        
        foreach ($lines as $line) {
            $line = trim($line);
            
            // Skip comments and empty lines
            if (empty($line) || strpos($line, '#') === 0) {
                continue;
            }
            
            // Parse key=value pairs
            if (strpos($line, '=') !== false) {
                list($key, $value) = explode('=', $line, 2);
                $key = trim($key);
                $value = trim($value);
                
                // Remove quotes if present
                if ((strpos($value, '"') === 0 && substr($value, -1) === '"') || 
                    (strpos($value, "'") === 0 && substr($value, -1) === "'")) {
                    $value = substr($value, 1, -1);
                }
                
                putenv("$key=$value");
            }
        }
    }
    
    // Get database connection parameters
    $host = getenv('DB_HOST');
    $database = getenv('DB_DATABASE');
    $username = getenv('DB_USERNAME');
    $password = getenv('DB_PASSWORD');
    
    // Check if we have all required parameters
    if (!$host || !$database || !$username) {
        echo "❌ Missing required database configuration in .env file\n";
        echo "Required: DB_HOST, DB_DATABASE, DB_USERNAME, DB_PASSWORD\n";
        exit(1);
    }
    
    echo "Testing connection to database: {$host}/{$database} as {$username}\n";
    
    // Test connection
    $pdo = new PDO("mysql:host={$host};dbname={$database}", $username, $password);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    
    // Check if we can query the database
    $stmt = $pdo->query('SELECT 1');
    $stmt->fetch();
    
    echo "✅ Connection successful!\n";
    
} catch (Exception $e) {
    echo "❌ Error: " . $e->getMessage() . "\n";
    exit(1);
} 