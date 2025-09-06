<?php
/**
 * Database Configuration for DesiDukaan
 */

// Database configuration
define('DB_HOST', 'localhost');
define('DB_NAME', 'desidukaan');
define('DB_USER', 'root'); // Change this for production
define('DB_PASS', ''); // Change this for production
define('DB_CHARSET', 'utf8mb4');

// Database connection class
class Database {
    private static $instance = null;
    private $connection;
    
    private function __construct() {
        try {
            // First try to connect without database to create it if needed
            $dsn_no_db = "mysql:host=" . DB_HOST . ";charset=" . DB_CHARSET;
            $options = [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                PDO::ATTR_EMULATE_PREPARES => false,
            ];

            $temp_connection = new PDO($dsn_no_db, DB_USER, DB_PASS, $options);

            // Create database if it doesn't exist
            $temp_connection->exec("CREATE DATABASE IF NOT EXISTS " . DB_NAME . " CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci");

            // Now connect to the specific database
            $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=" . DB_CHARSET;
            $this->connection = new PDO($dsn, DB_USER, DB_PASS, $options);

        } catch (PDOException $e) {
            // More detailed error message for debugging
            error_log("Database connection error: " . $e->getMessage());
            die("Database connection failed. Please check if MySQL is running and credentials are correct. Error: " . $e->getMessage());
        }
    }
    
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new self();
        }
        return self::$instance;
    }
    
    public function getConnection() {
        return $this->connection;
    }
    
    // Prevent cloning
    private function __clone() {}
    
    // Prevent unserialization
    public function __wakeup() {
        throw new Exception("Cannot unserialize singleton");
    }
}

// Helper function to get database connection
function getDB() {
    return Database::getInstance()->getConnection();
}
?>
