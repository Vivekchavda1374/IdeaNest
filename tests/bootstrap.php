<?php
/**
 * PHPUnit Bootstrap File
 */

require_once __DIR__ . '/../vendor/autoload.php';

// Test database configuration
define('TEST_DB_HOST', $_ENV['DB_HOST'] ?? 'localhost');
define('TEST_DB_USER', $_ENV['DB_USER'] ?? 'ictmu6ya_ideanest');
define('TEST_DB_PASS', $_ENV['DB_PASS'] ?? 'ictmu6ya_ideanest');
define('TEST_DB_NAME', $_ENV['DB_NAME'] ?? 'ictmu6ya_ideanest_test');

// Create test database connection
function getTestConnection() {
    static $conn = null;
    if ($conn === null) {
        $conn = new mysqli(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, TEST_DB_NAME);
        if ($conn->connect_error) {
            throw new Exception("Test database connection failed: " . $conn->connect_error);
        }
    }
    return $conn;
}

// Setup test database
function setupTestDatabase() {
    try {
        // Try to connect with socket path for XAMPP/LAMPP
        $socket = '/opt/lampp/var/mysql/mysql.sock';
        if (file_exists($socket)) {
            $conn = new mysqli(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS, '', 0, $socket);
        } else {
            $conn = new mysqli(TEST_DB_HOST, TEST_DB_USER, TEST_DB_PASS);
        }
        
        if ($conn->connect_error) {
            // Skip database setup if connection fails
            return;
        }
        
        $conn->query("CREATE DATABASE IF NOT EXISTS " . TEST_DB_NAME);
        $conn->select_db(TEST_DB_NAME);
        
        // Create minimal test tables
        $conn->query("CREATE TABLE IF NOT EXISTS register (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(255),
            email VARCHAR(255) UNIQUE,
            github_username VARCHAR(255),
            github_profile_url VARCHAR(500),
            github_repos_count INT DEFAULT 0
        )");
        
        $conn->query("CREATE TABLE IF NOT EXISTS projects (
            id INT AUTO_INCREMENT PRIMARY KEY,
            title VARCHAR(255),
            description TEXT,
            user_id INT,
            status ENUM('pending', 'approved', 'rejected') DEFAULT 'pending'
        )");
        
        $conn->close();
    } catch (Exception $e) {
        // Skip database setup if it fails
        return;
    }
}

// Cleanup test database
function cleanupTestDatabase() {
    try {
        $conn = getTestConnection();
        $conn->query("TRUNCATE TABLE register");
        $conn->query("TRUNCATE TABLE projects");
    } catch (Exception $e) {
        // Skip cleanup if it fails
    }
}

// Initialize test environment (skip if database not available)
try {
    setupTestDatabase();
} catch (Exception $e) {
    // Tests will run without database setup
}