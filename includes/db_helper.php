<?php
/**
 * Database Helper Class
 * Provides safe database operations with comprehensive error handling
 */

class DatabaseHelper {
    private $conn;
    private $log_queries;
    
    public function __construct($connection) {
        $this->conn = $connection;
        $this->log_queries = ($_ENV['APP_ENV'] ?? 'development') === 'development';
    }
    
    /**
     * Execute a prepared statement safely
     * 
     * @param string $query SQL query with placeholders
     * @param string $types Parameter types (e.g., "ssi" for string, string, int)
     * @param array $params Parameters to bind
     * @return mysqli_result|bool Result object or false on failure
     */
    public function executeQuery($query, $types = "", $params = []) {
        try {
            // Log query in development
            if ($this->log_queries) {
                error_log("Executing query: " . $query);
            }
            
            // Prepare statement
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            
            // Bind parameters if provided
            if (!empty($types) && !empty($params)) {
                if (strlen($types) !== count($params)) {
                    throw new Exception("Parameter count mismatch: types=$types, params=" . count($params));
                }
                
                $stmt->bind_param($types, ...$params);
            }
            
            // Execute statement
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            // Get result
            $result = $stmt->get_result();
            $stmt->close();
            
            return $result;
            
        } catch (Exception $e) {
            error_log("Database query error: " . $e->getMessage());
            error_log("Query: " . $query);
            
            if (isset($stmt)) {
                $stmt->close();
            }
            
            // In production, don't expose error details
            if (($_ENV['APP_ENV'] ?? 'development') === 'production') {
                return false;
            } else {
                throw $e;
            }
        }
    }
    
    /**
     * Execute an INSERT query and return the inserted ID
     * 
     * @param string $query SQL INSERT query
     * @param string $types Parameter types
     * @param array $params Parameters to bind
     * @return int|false Inserted ID or false on failure
     */
    public function insert($query, $types = "", $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            
            if (!empty($types) && !empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $insert_id = $stmt->insert_id;
            $stmt->close();
            
            return $insert_id;
            
        } catch (Exception $e) {
            error_log("Database insert error: " . $e->getMessage());
            
            if (isset($stmt)) {
                $stmt->close();
            }
            
            return false;
        }
    }
    
    /**
     * Execute an UPDATE or DELETE query and return affected rows
     * 
     * @param string $query SQL UPDATE/DELETE query
     * @param string $types Parameter types
     * @param array $params Parameters to bind
     * @return int|false Number of affected rows or false on failure
     */
    public function update($query, $types = "", $params = []) {
        try {
            $stmt = $this->conn->prepare($query);
            if (!$stmt) {
                throw new Exception("Prepare failed: " . $this->conn->error);
            }
            
            if (!empty($types) && !empty($params)) {
                $stmt->bind_param($types, ...$params);
            }
            
            if (!$stmt->execute()) {
                throw new Exception("Execute failed: " . $stmt->error);
            }
            
            $affected_rows = $stmt->affected_rows;
            $stmt->close();
            
            return $affected_rows;
            
        } catch (Exception $e) {
            error_log("Database update error: " . $e->getMessage());
            
            if (isset($stmt)) {
                $stmt->close();
            }
            
            return false;
        }
    }
    
    /**
     * Fetch a single row from the database
     * 
     * @param string $query SQL SELECT query
     * @param string $types Parameter types
     * @param array $params Parameters to bind
     * @return array|null Associative array or null if not found
     */
    public function fetchOne($query, $types = "", $params = []) {
        try {
            $result = $this->executeQuery($query, $types, $params);
            
            if ($result === false) {
                return null;
            }
            
            if ($result->num_rows === 0) {
                return null;
            }
            
            return $result->fetch_assoc();
            
        } catch (Exception $e) {
            error_log("Database fetchOne error: " . $e->getMessage());
            return null;
        }
    }
    
    /**
     * Fetch all rows from the database
     * 
     * @param string $query SQL SELECT query
     * @param string $types Parameter types
     * @param array $params Parameters to bind
     * @return array Array of associative arrays
     */
    public function fetchAll($query, $types = "", $params = []) {
        try {
            $result = $this->executeQuery($query, $types, $params);
            
            if ($result === false) {
                return [];
            }
            
            $rows = [];
            while ($row = $result->fetch_assoc()) {
                $rows[] = $row;
            }
            
            return $rows;
            
        } catch (Exception $e) {
            error_log("Database fetchAll error: " . $e->getMessage());
            return [];
        }
    }
    
    /**
     * Check if a record exists
     * 
     * @param string $query SQL SELECT query
     * @param string $types Parameter types
     * @param array $params Parameters to bind
     * @return bool True if exists, false otherwise
     */
    public function exists($query, $types = "", $params = []) {
        try {
            $result = $this->executeQuery($query, $types, $params);
            
            if ($result === false) {
                return false;
            }
            
            return $result->num_rows > 0;
            
        } catch (Exception $e) {
            error_log("Database exists error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Begin a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function beginTransaction() {
        try {
            return $this->conn->begin_transaction();
        } catch (Exception $e) {
            error_log("Begin transaction error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Commit a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function commit() {
        try {
            return $this->conn->commit();
        } catch (Exception $e) {
            error_log("Commit error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Rollback a transaction
     * 
     * @return bool True on success, false on failure
     */
    public function rollback() {
        try {
            return $this->conn->rollback();
        } catch (Exception $e) {
            error_log("Rollback error: " . $e->getMessage());
            return false;
        }
    }
    
    /**
     * Escape a string for use in SQL queries (use prepared statements instead when possible)
     * 
     * @param string $string String to escape
     * @return string Escaped string
     */
    public function escape($string) {
        return $this->conn->real_escape_string($string);
    }
    
    /**
     * Get the last error message
     * 
     * @return string Error message
     */
    public function getError() {
        return $this->conn->error;
    }
    
    /**
     * Get the last error number
     * 
     * @return int Error number
     */
    public function getErrorNo() {
        return $this->conn->errno;
    }
    
    /**
     * Check if connection is still alive
     * 
     * @return bool True if alive, false otherwise
     */
    public function ping() {
        try {
            return $this->conn->ping();
        } catch (Exception $e) {
            error_log("Database ping error: " . $e->getMessage());
            return false;
        }
    }
}

// Create global database helper instance
if (isset($conn)) {
    $db = new DatabaseHelper($conn);
}
?>
